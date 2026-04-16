<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Member;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /** @return Reservation[] */
    public function findActiveByBook(Book $book): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.book = :book')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('book', $book)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_NOTIFIED])
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextInQueue(Book $book): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->where('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', Reservation::STATUS_PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_NOTIFIED])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findExpiredNotified(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.expiresAt < :now')
            ->setParameter('status', Reservation::STATUS_NOTIFIED)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function hasActiveReservation(Member $member, Book $book): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.member = :member')
            ->andWhere('r.book = :book')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('book', $book)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_NOTIFIED])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
