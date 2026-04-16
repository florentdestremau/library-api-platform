<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Configuration;
use App\Entity\Genre;
use App\Entity\Member;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Configuration par défaut
        foreach ($this->getDefaultConfig() as [$key, $value, $description]) {
            $config = new Configuration();
            $config->setKey($key);
            $config->setValue($value);
            $config->setDescription($description);
            $manager->persist($config);
        }

        // Genres
        $genreRoman = $this->createGenre($manager, 'Roman', 'roman');
        $this->createGenre($manager, 'Science-Fiction', 'science-fiction', $genreRoman);
        $this->createGenre($manager, 'Policier', 'policier', $genreRoman);
        $genreEssai = $this->createGenre($manager, 'Essai', 'essai');
        $genreJeunesse = $this->createGenre($manager, 'Jeunesse', 'jeunesse');

        // Auteurs
        $camus = $this->createAuthor($manager, 'Albert', 'Camus');
        $hugo = $this->createAuthor($manager, 'Victor', 'Hugo');
        $asimov = $this->createAuthor($manager, 'Isaac', 'Asimov');
        $levy = $this->createAuthor($manager, 'Marc', 'Levy');
        $rowling = $this->createAuthor($manager, 'J.K.', 'Rowling');

        // Livres avec exemplaires
        $this->createBook($manager, "L'Etranger", $camus, $genreRoman, '9782070360024', 1942, 'Gallimard', 159, 2);
        $this->createBook($manager, 'La Peste', $camus, $genreRoman, '9782070360413', 1947, 'Gallimard', 308, 2);
        $this->createBook($manager, 'Les Miserables', $hugo, $genreRoman, '9782070409228', 1862, 'Gallimard', 1900, 3);
        $this->createBook($manager, 'Fondation', $asimov, $genreEssai, '9782070415700', 1951, 'Gallimard', 256, 2);
        $this->createBook($manager, "Et si c'etait vrai...", $levy, $genreRoman, null, 2000, 'Pocket', 225, 1);
        $this->createBook($manager, "Harry Potter a l'ecole des sorciers", $rowling, $genreJeunesse, '9782070541348', 1997, 'Gallimard Jeunesse', 309, 3);
        $this->createBook($manager, 'Harry Potter et la Chambre des Secrets', $rowling, $genreJeunesse, '9782070541522', 1998, 'Gallimard Jeunesse', 360, 2);

        $manager->flush();

        // Adhérents et utilisateurs
        $membersData = [
            ['Alice', 'Martin', 'alice.martin@example.com'],
            ['Bob', 'Dupont', 'bob.dupont@example.com'],
            ['Claire', 'Bernard', 'claire.bernard@example.com'],
            ['David', 'Petit', 'david.petit@example.com'],
            ['Emma', 'Durand', 'emma.durand@example.com'],
        ];

        foreach ($membersData as $i => [$firstName, $lastName, $email]) {
            $member = new Member();
            $member->setFirstName($firstName);
            $member->setLastName($lastName);
            $member->setEmail($email);
            $member->setMemberNumber(sprintf('BIB-%d-%05d', (int) date('Y'), $i + 1));
            $member->setMembershipExpiry(new \DateTimeImmutable('+1 year'));
            $member->setStatus(Member::STATUS_ACTIVE);
            $manager->persist($member);

            $user = new User();
            $user->setEmail($email);
            $user->setRole(User::ROLE_MEMBER);
            $user->setMember($member);
            $user->setPasswordHash($this->hasher->hashPassword($user, 'password123'));
            $manager->persist($user);
        }

        // Admin et bibliothécaire
        $adminUser = new User();
        $adminUser->setEmail('admin@bibliotheque.fr');
        $adminUser->setRole(User::ROLE_SUPER_ADMIN);
        $adminUser->setPasswordHash($this->hasher->hashPassword($adminUser, 'Admin1234!'));
        $manager->persist($adminUser);

        $libUser = new User();
        $libUser->setEmail('bibliothecaire@bibliotheque.fr');
        $libUser->setRole(User::ROLE_LIBRARIAN);
        $libUser->setPasswordHash($this->hasher->hashPassword($libUser, 'Biblio1234!'));
        $manager->persist($libUser);

        $manager->flush();
    }

    private function createGenre(ObjectManager $manager, string $name, string $slug, ?Genre $parent = null): Genre
    {
        $genre = new Genre();
        $genre->setName($name);
        $genre->setSlug($slug);
        if ($parent !== null) {
            $genre->setParent($parent);
        }
        $manager->persist($genre);

        return $genre;
    }

    private function createAuthor(ObjectManager $manager, string $firstName, string $lastName): Author
    {
        $author = new Author();
        $author->setFirstName($firstName);
        $author->setLastName($lastName);
        $manager->persist($author);

        return $author;
    }

    private function createBook(
        ObjectManager $manager,
        string $title,
        Author $author,
        Genre $genre,
        ?string $isbn,
        int $year,
        string $publisher,
        int $pages,
        int $copyCount,
    ): Book {
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
        $manager->persist($book);

        for ($i = 0; $i < $copyCount; $i++) {
            $copy = new BookCopy();
            $copy->setBook($book);
            $copy->setBarcode(sprintf('BC%s%02d', substr(md5($title), 0, 4), $i + 1));
            $copy->setLocation('Rayon A');
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $manager->persist($copy);
        }

        return $book;
    }

    private function getDefaultConfig(): array
    {
        return [
            ['loan_duration_days', '21', 'Duree standard emprunts (jours)'],
            ['max_renewals', '1', 'Nombre max de renouvellements'],
            ['late_fee_per_day', '0.10', 'Penalite journaliere (euros)'],
            ['reservation_expiry_hours', '48', 'Delai retrait apres notification (heures)'],
            ['reminder_days_before', '3', 'Jours avant echeance pour rappel'],
            ['library_name', 'Bibliotheque Municipale', 'Nom de la bibliotheque'],
            ['library_email', 'contact@bibliotheque.fr', 'Email de contact'],
        ];
    }
}
