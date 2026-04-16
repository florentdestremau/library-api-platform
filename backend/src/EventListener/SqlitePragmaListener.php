<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class SqlitePragmaListener
{
    public function postConnect(ConnectionEventArgs $args): void
    {
        $connection = $args->getConnection();
        if ($connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
            $connection->executeStatement('PRAGMA journal_mode = WAL');
        }
    }
}
