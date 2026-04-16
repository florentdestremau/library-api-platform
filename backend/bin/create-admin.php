<?php

/**
 * Script de création de l'utilisateur admin en production.
 * Usage: php bin/create-admin.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\Configuration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$kernel = new App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get('security.user_password_hasher');

// Configuration par défaut
$configs = [
    ['loan_duration_days', '21', 'Durée standard emprunts (jours)'],
    ['max_renewals', '1', 'Nombre max de renouvellements'],
    ['late_fee_per_day', '0.10', 'Pénalité journalière'],
    ['reservation_expiry_hours', '48', 'Délai retrait (heures)'],
    ['reminder_days_before', '3', 'Jours avant échéance pour rappel'],
    ['library_name', getenv('LIBRARY_NAME') ?: 'Bibliothèque Municipale', 'Nom'],
    ['library_email', getenv('LIBRARY_EMAIL') ?: 'contact@bibliotheque.fr', 'Email'],
];

foreach ($configs as [$key, $value, $desc]) {
    $existing = $em->find(Configuration::class, $key);
    if ($existing === null) {
        $config = new Configuration();
        $config->setKey($key);
        $config->setValue($value);
        $config->setDescription($desc);
        $em->persist($config);
    }
}

// Créer l'admin
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@bibliotheque.fr';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'Admin1234!';

$existing = $em->getRepository(User::class)->findOneBy(['email' => $adminEmail]);
if ($existing === null) {
    $admin = new User();
    $admin->setEmail($adminEmail);
    $admin->setRole(User::ROLE_SUPER_ADMIN);
    $admin->setPasswordHash($hasher->hashPassword($admin, $adminPassword));
    $em->persist($admin);
    echo "Admin créé : {$adminEmail}\n";
} else {
    echo "Admin déjà existant : {$adminEmail}\n";
}

$em->flush();
echo "Initialisation terminée.\n";
