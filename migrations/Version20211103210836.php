<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211103210836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, answer_type_id INT NOT NULL, category_id INT NOT NULL, ask LONGTEXT NOT NULL, helper LONGTEXT NOT NULL, answers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', category_factor INT DEFAULT NULL, global_factor INT DEFAULT NULL, required TINYINT(1) NOT NULL, qlink LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', qnext LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', hex_color VARCHAR(10) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_B6F7494E8D0C43E2 (answer_type_id), INDEX IDX_B6F7494E12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E8D0C43E2 FOREIGN KEY (answer_type_id) REFERENCES answer_type (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE question');
    }
}
