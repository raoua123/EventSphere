function bufferToBase64Url(buffer) {
  return btoa(String.fromCharCode(...new Uint8Array(buffer)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlToBuffer(base64url) {
  let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
  base64 += '='.repeat((4 - base64.length % 4) % 4);
  return Uint8Array.from(atob(base64), c => c.charCodeAt(0)).buffer;
}

// ── Token & Auth State ──────────────────────────────────────────────────────
export function isLoggedIn() {
  const token = localStorage.getItem('jwt_token');
  if (!token) return false;
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.exp > Math.floor(Date.now() / 1000);
  } catch { return false; }
}

export function logout() {
  localStorage.removeItem('jwt_token');
  localStorage.removeItem('refresh_token');
  window.location.replace('/login.html');
}

// ── API Fetch with JWT ──────────────────────────────────────────────────────
export async function authFetch(url, options = {}) {
  const token = localStorage.getItem('jwt_token');
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  const res = await fetch(url, { ...options, headers });
  if (res.status === 401) {
    logout();
  }
  return res;
}

// ── Passkey Registration ────────────────────────────────────────────────────
export async function registerPasskey(email) {
  const optRes = await fetch('/api/auth/register/options', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ email }),
  });
  
  const options = await optRes.json();
  if (!optRes.ok) throw new Error(options.error || 'Erreur options');

  const credential = await navigator.credentials.create({
    publicKey: {
      ...options,
      challenge: base64UrlToBuffer(options.challenge),
      user: { ...options.user, id: base64UrlToBuffer(options.user.id) },
    },
  });

  const verifyRes = await fetch('/api/auth/register/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({
      email,
      credential: {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        response: {
          clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
          attestationObject: bufferToBase64Url(credential.response.attestationObject),
        },
        type: credential.type,
      },
    }),
  });

  const result = await verifyRes.json();
  if (!verifyRes.ok) throw new Error(result.error || 'Échec');
  
  alert("Compte créé ! Vérifiez votre Gmail.");
  window.location.replace('/login.html');
}

// ── Passkey Login ───────────────────────────────────────────────────────────
export async function loginWithPasskey() {
  const optRes = await fetch('/api/auth/login/options', { 
    method: 'POST', 
    headers: {'Accept': 'application/json'} 
  });
  const options = await optRes.json();

  const assertion = await navigator.credentials.get({
    publicKey: { 
      ...options, 
      challenge: base64UrlToBuffer(options.challenge) 
    },
  });

  const verifyRes = await fetch('/api/auth/login/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({
      credential: {
        id: assertion.id,
        rawId: bufferToBase64Url(assertion.rawId),
        response: {
          clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
          authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
          signature: bufferToBase64Url(assertion.response.signature),
        },
        type: assertion.type,
      },
    }),
  });

  const result = await verifyRes.json();
  if (!verifyRes.ok) throw new Error(result.error || 'Échec login');

  localStorage.setItem('jwt_token', result.token);
  window.location.replace('/events.html');
}
