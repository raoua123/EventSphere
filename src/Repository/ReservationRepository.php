<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

 
    public function findByUser(User $user): array
    {
        return $this->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }


    public function findByEvent(Event $event): array
    {
        return $this->findBy(
            ['event' => $event],
            ['createdAt' => 'DESC']
        );
    }

 
    public function save(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reservation);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

   
    public function remove(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->remove($reservation);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
