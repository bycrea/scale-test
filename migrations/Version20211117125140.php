<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211117125140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diagnostic DROP questions');
        $this->addSql('ALTER TABLE question ADD diagnostic_id INT NOT NULL');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E224CCA91 FOREIGN KEY (diagnostic_id) REFERENCES diagnostic (id)');
        $this->addSql('CREATE INDEX IDX_B6F7494E224CCA91 ON question (diagnostic_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diagnostic ADD questions LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E224CCA91');
        $this->addSql('DROP INDEX IDX_B6F7494E224CCA91 ON question');
        $this->addSql('ALTER TABLE question DROP diagnostic_id');
    }
}
