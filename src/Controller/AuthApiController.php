<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasskeyAuthService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenManagerInterface $refreshManager,
        private UserRepository $userRepo
    ) {}

    // --- REGISTRATION ---

    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(Request $request, PasskeyAuthService $passkey): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email valide requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepo->findOrCreate($email);

        try {
            return $this->json($passkey->getRegistrationOptions($user));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(Request $request, PasskeyAuthService $passkey, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepo->findOneBy(['email' => $data['email'] ?? '']);

        if (!$user) return $this->json(['error' => 'User not found'], 400);

        try {
            $passkey->verifyRegistration($data['credential'], $user);
            
            // Generate token and send Gmail Verification
            $token = $user->generateEmailVerificationToken();
            $this->userRepo->save($user);
            $this->sendVerificationEmail($mailer, $user, $token);

            // We do NOT return a JWT here. User must verify email first.
            return $this->json([
                'success' => true, 
                'message' => 'Vérifiez votre Gmail pour valider votre compte.'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // --- LOGIN ---

    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(PasskeyAuthService $passkey): JsonResponse
    {
        try {
            return $this->json($passkey->getLoginOptions());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(Request $request, PasskeyAuthService $passkey): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            $user = $passkey->verifyLogin($data['credential']); 

            // BLOCK UNVERIFIED USERS
            if (!$user->isEmailVerified()) {
                return $this->json([
                    'error' => 'Veuillez d\'abord vérifier votre email dans Gmail.'
                ], Response::HTTP_FORBIDDEN);
            }

            return $this->json($this->buildTokenResponse($user));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    // --- EMAIL VERIFICATION & ME ---

    #[Route('/verify-email', methods: ['GET'])]
    public function verifyEmail(Request $request): Response // Fixed type: JsonResponse -> Response
    {
        $token = $request->query->get('token');
        $user = $this->userRepo->findOneBy(['emailVerificationToken' => $token]);

        // Check if token exists and hasn't expired
        if (!$user || ($user->getEmailVerificationExpiry() < new \DateTime())) {
            // Return JSON error if the token is dead
            return $this->json(['error' => 'Token invalide ou expiré'], Response::HTTP_BAD_REQUEST);
        }

        // Validate the user
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiry(null);
        $this->userRepo->save($user);

        // Success: Redirect to the login page with a success flag
        return new RedirectResponse('/login.html?verified=1');
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->json([
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'emailVerified' => $user->isEmailVerified(),
        ]);
    }

    // --- HELPERS ---

    private function sendVerificationEmail(MailerInterface $mailer, User $user, string $token): void
    {
        $verificationUrl = 'http://localhost/api/auth/verify-email?token=' . $token;
        
        $email = (new Email())
            ->from('noreply@eventreservation.tn')
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email — EventSphere')
            ->html("<h3>Bienvenue sur EventSphere !</h3><p>Veuillez cliquer sur le bouton ci-dessous pour valider votre compte :</p><a href='$verificationUrl' style='display:inline-block;padding:10px 20px;background:#6366f1;color:white;text-decoration:none;border-radius:5px;'>Vérifier mon email</a>");

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            // Silence mailer errors in dev, or log them
        }
    }

    private function buildTokenResponse(User $user): array
    {
        $jwt = $this->jwtManager->create($user);
        $refreshTokenString = null;

        try {
            $refreshToken = $this->refreshManager->create();
            $refreshToken->setRefreshToken();
            $refreshToken->setUsername($user->getUserIdentifier());
            
            $valid = new \DateTime();
            $valid->add(new \DateInterval('P1M'));
            $refreshToken->setValid($valid);

            $this->refreshManager->save($refreshToken);
            $refreshTokenString = $refreshToken->getRefreshToken();
        } catch (\Exception $e) {
            // Log error
        }

        return [
            'success' => true,
            'token' => $jwt,
            'refresh_token' => $refreshTokenString,
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ];
    }
}
