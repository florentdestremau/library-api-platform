<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function getValue(string $key, string $default = ''): string
    {
        $config = $this->find($key);

        return $config ? $config->getValue() : $default;
    }
}
