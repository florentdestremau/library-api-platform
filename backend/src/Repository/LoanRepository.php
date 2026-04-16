<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /** @return Loan[] */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.dueDate < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('l.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Loan[] */
    public function findActiveLoansByMember(Member $member): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.member = :member')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('member', $member)
            ->getQuery()
            ->getResult();
    }

    /** @return Loan[] */
    public function findDueSoon(int $daysBeforeDue): array
    {
        $from = new \DateTimeImmutable();
        $to = new \DateTimeImmutable("+{$daysBeforeDue} days");

        return $this->createQueryBuilder('l')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.dueDate >= :from')
            ->andWhere('l.dueDate <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.returnedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOverdue(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.dueDate < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<array{book_title: string, count: int}> */
    public function findMostBorrowed(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->select('b.title as book_title, b.id as book_id, COUNT(l.id) as borrow_count')
            ->join('l.bookCopy', 'bc')
            ->join('bc.book', 'b')
            ->groupBy('b.id')
            ->orderBy('borrow_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
