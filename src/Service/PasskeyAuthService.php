<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\AbstractUid;

class PasskeyAuthService
{
    public function __construct(
        private RequestStack $requestStack,
        private WebauthnCredentialRepository $credRepo,
        private UserRepository $userRepo,
        private string $rpId = 'localhost',
        private string $rpName = 'Event Reservation App'
    ) {}

    private function getSession(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        $session = $this->requestStack->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        return $session;
    }

    private function generateChallenge(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public function getRegistrationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();
        $session = $this->getSession();
        $session->set('webauthn_registration_challenge', $challenge);
        $session->save(); // Save to ensure it persists in Docker

        $userId = $user->getId();
        $binaryId = ($userId instanceof AbstractUid) ? $userId->toBinary() : (string)$userId;
        $encodedUserId = rtrim(strtr(base64_encode($binaryId), '+/', '-_'), '=');

        return [
            'challenge' => $challenge,
            'rp' => ['name' => $this->rpName, 'id' => $this->rpId],
            'user' => [
                'id'          => $encodedUserId,
                'name'        => $user->getEmail(),
                'displayName' => $user->getEmail(),
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
    }

    public function verifyRegistration(array $credential, User $user): void
    {
        $session = $this->getSession();
        $challenge = $session->get('webauthn_registration_challenge');

        if (!$challenge) {
            throw new \RuntimeException('Session expired or challenge missing.');
        }

        $clientDataJSON = json_decode(base64_decode(strtr($credential['response']['clientDataJSON'], '-_', '+/')), true);

        if (rtrim($clientDataJSON['challenge'], '=') !== rtrim($challenge, '=')) {
            throw new \RuntimeException('Challenge mismatch.');
        }

        $credEntity = new WebauthnCredential();
        $credEntity->setUser($user);
        $credEntity->setCredentialId($credential['id']);
        $credEntity->setCredentialData(json_encode($credential));
        
        $this->credRepo->save($credEntity);
        
        $session->remove('webauthn_registration_challenge');
        $session->save(); 
    }

    public function getLoginOptions(): array
    {
        $challenge = $this->generateChallenge();
        $session = $this->getSession();
        $session->set('webauthn_login_challenge', $challenge);
        $session->save();

        return [
            'challenge'        => $challenge,
            'rpId'             => $this->rpId,
            'timeout'          => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => [],
        ];
    }

    public function verifyLogin(array $credential): User
    {
        $session = $this->getSession();
        $challenge = $session->get('webauthn_login_challenge');

        if (!$challenge) {
            throw new \RuntimeException('Session expired. Please try again.');
        }

        $clientDataJSON = json_decode(base64_decode(strtr($credential['response']['clientDataJSON'], '-_', '+/')), true);

        if (rtrim($clientDataJSON['challenge'], '=') !== rtrim($challenge, '=')) {
            throw new \RuntimeException('Invalid challenge.');
        }

        $credEntity = $this->credRepo->findOneBy(['credentialId' => $credential['id']]);

        if (!$credEntity) {
            throw new \RuntimeException('Passkey not found. Please register first.');
        }

        if (method_exists($credEntity, 'touch')) {
            $credEntity->touch();
        }
        
        $this->credRepo->save($credEntity);
        $session->remove('webauthn_login_challenge');
        $session->save();

        return $credEntity->getUser();
    }
}
