<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Configuration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Créer l\'utilisateur administrateur')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email admin', 'admin@bibliotheque.fr')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe', 'Admin1234!');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');

        // Configuration par défaut
        $this->initConfig();

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null) {
            $output->writeln("<comment>Utilisateur déjà existant : {$email}</comment>");

            return Command::SUCCESS;
        }

        $admin = new User();
        $admin->setEmail($email);
        $admin->setRole(User::ROLE_SUPER_ADMIN);
        $admin->setPasswordHash($this->hasher->hashPassword($admin, $password));

        $this->em->persist($admin);
        $this->em->flush();

        $output->writeln("<info>Admin créé : {$email}</info>");

        return Command::SUCCESS;
    }

    private function initConfig(): void
    {
        $defaults = [
            ['loan_duration_days', '21'],
            ['max_renewals', '1'],
            ['late_fee_per_day', '0.10'],
            ['reservation_expiry_hours', '48'],
            ['reminder_days_before', '3'],
            ['library_name', $_ENV['LIBRARY_NAME'] ?? 'Bibliothèque Municipale'],
            ['library_email', $_ENV['LIBRARY_EMAIL'] ?? 'contact@bibliotheque.fr'],
        ];

        foreach ($defaults as [$key, $value]) {
            if ($this->em->find(Configuration::class, $key) === null) {
                $config = new Configuration();
                $config->setKey($key);
                $config->setValue($value);
                $this->em->persist($config);
            }
        }
        $this->em->flush();
    }
}
