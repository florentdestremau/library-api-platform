<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416061839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE author (id BLOB NOT NULL --(DC2Type:uuid)
        , first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, biography CLOB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE book (id BLOB NOT NULL --(DC2Type:uuid)
        , title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, published_year INTEGER DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, language VARCHAR(10) DEFAULT \'fr\' NOT NULL, page_count INTEGER DEFAULT NULL, cover_image_path VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , deleted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331CC1CF4E6 ON book (isbn)');
        $this->addSql('CREATE TABLE book_author (book_id BLOB NOT NULL --(DC2Type:uuid)
        , author_id BLOB NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(book_id, author_id), CONSTRAINT FK_9478D34516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9478D345F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9478D34516A2B381 ON book_author (book_id)');
        $this->addSql('CREATE INDEX IDX_9478D345F675F31B ON book_author (author_id)');
        $this->addSql('CREATE TABLE book_genre (book_id BLOB NOT NULL --(DC2Type:uuid)
        , genre_id BLOB NOT NULL --(DC2Type:uuid)
        , PRIMARY KEY(book_id, genre_id), CONSTRAINT FK_8D92268116A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8D9226814296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8D92268116A2B381 ON book_genre (book_id)');
        $this->addSql('CREATE INDEX IDX_8D9226814296D31F ON book_genre (genre_id)');
        $this->addSql('CREATE TABLE book_copy (id BLOB NOT NULL --(DC2Type:uuid)
        , book_id BLOB NOT NULL --(DC2Type:uuid)
        , barcode VARCHAR(50) NOT NULL, location VARCHAR(100) DEFAULT NULL, status VARCHAR(20) DEFAULT \'available\' NOT NULL, condition VARCHAR(10) DEFAULT \'good\' NOT NULL, acquired_at DATE DEFAULT NULL, notes CLOB DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_5427F08A16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5427F08A97AE0266 ON book_copy (barcode)');
        $this->addSql('CREATE INDEX IDX_5427F08A16A2B381 ON book_copy (book_id)');
        $this->addSql('CREATE INDEX IDX_5427F08A7B00651C ON book_copy (status)');
        $this->addSql('CREATE TABLE configuration ("key" VARCHAR(100) NOT NULL, value CLOB NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY("key"))');
        $this->addSql('CREATE TABLE genre (id BLOB NOT NULL --(DC2Type:uuid)
        , parent_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , name VARCHAR(100) NOT NULL, slug VARCHAR(110) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_835033F8727ACA70 FOREIGN KEY (parent_id) REFERENCES genre (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_835033F8989D9B62 ON genre (slug)');
        $this->addSql('CREATE INDEX IDX_835033F8727ACA70 ON genre (parent_id)');
        $this->addSql('CREATE TABLE loan (id BLOB NOT NULL --(DC2Type:uuid)
        , book_copy_id BLOB NOT NULL --(DC2Type:uuid)
        , member_id BLOB NOT NULL --(DC2Type:uuid)
        , librarian_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , borrowed_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , due_date DATE NOT NULL, returned_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , renewed_count INTEGER DEFAULT 0 NOT NULL, return_condition VARCHAR(10) DEFAULT NULL, late_fee NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL, notes CLOB DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_C5D30D033B550FE4 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C5D30D037597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C5D30D03D8B58D1F FOREIGN KEY (librarian_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C5D30D033B550FE4 ON loan (book_copy_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D037597D3FE ON loan (member_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D03D8B58D1F ON loan (librarian_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D03E673A031530929C8 ON loan (due_date, returned_at)');
        $this->addSql('CREATE TABLE member (id BLOB NOT NULL --(DC2Type:uuid)
        , member_number VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address CLOB DEFAULT NULL, birth_date DATE DEFAULT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, max_loans INTEGER DEFAULT 5 NOT NULL, max_reservations INTEGER DEFAULT 3 NOT NULL, membership_expiry DATE NOT NULL, photo_path VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78B2469D67 ON member (member_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_70E4FA78E7927C74 ON member (email)');
        $this->addSql('CREATE INDEX IDX_70E4FA78B2469D67 ON member (member_number)');
        $this->addSql('CREATE INDEX IDX_70E4FA78E7927C74 ON member (email)');
        $this->addSql('CREATE TABLE notification (id BLOB NOT NULL --(DC2Type:uuid)
        , member_id BLOB NOT NULL --(DC2Type:uuid)
        , type VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, body CLOB NOT NULL, sent_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , status VARCHAR(10) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_BF5476CA7597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BF5476CA7597D3FE ON notification (member_id)');
        $this->addSql('CREATE TABLE reservation (id BLOB NOT NULL --(DC2Type:uuid)
        , book_id BLOB NOT NULL --(DC2Type:uuid)
        , member_id BLOB NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , notified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , status VARCHAR(20) DEFAULT \'pending\' NOT NULL, queue_position INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_42C8495516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C849557597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_42C8495516A2B381 ON reservation (book_id)');
        $this->addSql('CREATE INDEX IDX_42C849557597D3FE ON reservation (member_id)');
        $this->addSql('CREATE INDEX IDX_42C849557B00651C ON reservation (status)');
        $this->addSql('CREATE TABLE user (id BLOB NOT NULL --(DC2Type:uuid)
        , member_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, role VARCHAR(30) DEFAULT \'ROLE_MEMBER\' NOT NULL, is_active BOOLEAN DEFAULT 1 NOT NULL, last_login_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_8D93D6497597D3FE FOREIGN KEY (member_id) REFERENCES member (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D6497597D3FE ON user (member_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE book_author');
        $this->addSql('DROP TABLE book_genre');
        $this->addSql('DROP TABLE book_copy');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE genre');
        $this->addSql('DROP TABLE loan');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE user');
    }
}
