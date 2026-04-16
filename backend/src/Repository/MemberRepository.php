<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    public function findNextMemberNumber(int $year): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.memberNumber LIKE :prefix')
            ->setParameter('prefix', "BIB-{$year}-%")
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result + 1;
    }
}
