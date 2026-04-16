<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Configuration;
use App\Entity\Member;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: "Créer l'admin et les données initiales")]
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
        $this->initConfig();

        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');

        $this->createUser($email, $password, User::ROLE_SUPER_ADMIN, $output, 'Admin');
        $this->createUser('bibliothecaire@bibliotheque.fr', 'Biblio1234!', User::ROLE_LIBRARIAN, $output, 'Bibliothécaire');
        $this->createDemoMember('alice.martin@example.com', 'Alice', 'Martin', 'password123', $output);
        $this->createDemoMember('bob.dupont@example.com', 'Bob', 'Dupont', 'password123', $output);

        $this->em->flush();
        $output->writeln('<info>Initialisation terminée.</info>');

        return Command::SUCCESS;
    }

    private function createUser(string $email, string $password, string $role, OutputInterface $output, string $label): void
    {
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $output->writeln("<comment>{$label} déjà existant : {$email}</comment>");
            return;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRole($role);
        $user->setPasswordHash($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $output->writeln("<info>{$label} créé : {$email}</info>");
    }

    private function createDemoMember(string $email, string $firstName, string $lastName, string $password, OutputInterface $output): void
    {
        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return;
        }

        $year = (int) date('Y');
        static $seq = 0; $seq++; $base = $this->em->getRepository(Member::class)->count([]); $count = $base + $seq;

        $member = new Member();
        $member->setFirstName($firstName);
        $member->setLastName($lastName);
        $member->setEmail($email);
        $member->setMemberNumber(sprintf('BIB-%d-%05d', $year, $count));
        $member->setMembershipExpiry(new \DateTimeImmutable('+1 year'));
        $member->setStatus(Member::STATUS_ACTIVE);
        $this->em->persist($member);

        $user = new User();
        $user->setEmail($email);
        $user->setRole(User::ROLE_MEMBER);
        $user->setMember($member);
        $user->setPasswordHash($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $output->writeln("<info>Adhérent créé : {$email}</info>");
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
