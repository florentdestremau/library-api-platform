<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BookCopy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookCopyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookCopy::class);
    }

    public function findByBarcode(string $barcode): ?BookCopy
    {
        return $this->findOneBy(['barcode' => $barcode]);
    }
}
