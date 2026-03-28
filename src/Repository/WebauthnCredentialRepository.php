<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function save(WebauthnCredential $credential, bool $flush = true): void
    {
        $this->getEntityManager()->persist($credential);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}