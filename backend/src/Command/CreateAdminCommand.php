<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Configuration;
use App\Entity\Genre;
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

        $this->seedCatalogue($output);

        $output->writeln('<info>Initialisation terminée.</info>');

        return Command::SUCCESS;
    }

    private function seedCatalogue(OutputInterface $output): void
    {
        $bookCount = $this->em->getRepository(Book::class)->count([]);
        if ($bookCount > 0) {
            $output->writeln('<comment>Catalogue déjà initialisé.</comment>');
            return;
        }

        // Genres
        $roman = $this->genre('Roman', 'roman');
        $sf = $this->genre('Science-Fiction', 'science-fiction', $roman);
        $policier = $this->genre('Policier', 'policier', $roman);
        $essai = $this->genre('Essai', 'essai');
        $jeunesse = $this->genre('Jeunesse', 'jeunesse');
        $this->em->flush();

        // Auteurs
        $camus   = $this->author('Albert', 'Camus');
        $hugo    = $this->author('Victor', 'Hugo');
        $asimov  = $this->author('Isaac', 'Asimov');
        $levy    = $this->author('Marc', 'Levy');
        $rowling = $this->author('J.K.', 'Rowling');
        $orwell  = $this->author('George', 'Orwell');
        $dumas   = $this->author('Alexandre', 'Dumas');
        $this->em->flush();

        // Livres (titre, auteur, genre, isbn, année, éditeur, pages, nb exemplaires)
        $data = [
            ["L'Etranger",                       $camus,   $roman,    '9782070360024', 1942, 'Gallimard',          159, 3],
            ['La Peste',                          $camus,   $roman,    '9782070360413', 1947, 'Gallimard',          308, 2],
            ['Les Miserables',                    $hugo,    $roman,    '9782070409228', 1862, 'Gallimard',         1900, 2],
            ['Fondation',                         $asimov,  $sf,       '9782070415700', 1951, 'Gallimard',          256, 2],
            ['Le Guide du voyageur galactique',   $asimov,  $sf,       null,            1979, 'Denoël',             215, 1],
            ["Et si c'etait vrai...",             $levy,    $roman,    null,            2000, 'Pocket',             225, 2],
            ["Harry Potter a l'ecole des sorciers", $rowling, $jeunesse, '9782070541348', 1997, 'Gallimard Jeunesse', 309, 3],
            ['Harry Potter et la Chambre des Secrets', $rowling, $jeunesse, '9782070541522', 1998, 'Gallimard Jeunesse', 360, 2],
            ['1984',                              $orwell,  $sf,       '9782070368228', 1949, 'Gallimard',          384, 2],
            ['La Ferme des animaux',              $orwell,  $roman,    '9782070360581', 1945, 'Gallimard',          125, 2],
            ['Les Trois Mousquetaires',           $dumas,   $roman,    '9782070411566', 1844, 'Gallimard',          736, 2],
            ['Le Comte de Monte-Cristo',          $dumas,   $roman,    null,            1845, 'Gallimard',         1200, 1],
        ];

        foreach ($data as $i => [$title, $author, $genre, $isbn, $year, $publisher, $pages, $copies]) {
            $book = new Book();
            $book->setTitle($title);
            $book->addAuthor($author);
            $book->addGenre($genre);
            if ($isbn !== null) {
                $book->setIsbn($isbn);
            }
            $book->setPublishedYear($year);
            $book->setPublisher($publisher);
            $book->setPageCount($pages);
            $book->setLanguage('fr');
            $book->setDescription('Un classique de la littérature française.');
            $this->em->persist($book);

            for ($j = 0; $j < $copies; $j++) {
                $copy = new BookCopy();
                $copy->setBook($book);
                $copy->setBarcode(sprintf('BC%04d%02d', $i + 1, $j + 1));
                $copy->setLocation('Rayon ' . chr(65 + ($i % 6)));
                $copy->setStatus(BookCopy::STATUS_AVAILABLE);
                $this->em->persist($copy);
            }
        }

        $this->em->flush();
        $output->writeln('<info>Catalogue créé : 12 ouvrages, genres et auteurs.</info>');
    }

    private function genre(string $name, string $slug, ?Genre $parent = null): Genre
    {
        $genre = new Genre();
        $genre->setName($name);
        $genre->setSlug($slug);
        if ($parent !== null) {
            $genre->setParent($parent);
        }
        $this->em->persist($genre);

        return $genre;
    }

    private function author(string $firstName, string $lastName): Author
    {
        $author = new Author();
        $author->setFirstName($firstName);
        $author->setLastName($lastName);
        $this->em->persist($author);

        return $author;
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
        $base = $this->em->getRepository(Member::class)->count([]);
        static $seq = 0;
        $seq++;

        $member = new Member();
        $member->setFirstName($firstName);
        $member->setLastName($lastName);
        $member->setEmail($email);
        $member->setMemberNumber(sprintf('BIB-%d-%05d', $year, $base + $seq));
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
