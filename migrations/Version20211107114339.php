<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211107114339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, diagnostic_id INT NOT NULL, answers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', meta JSON DEFAULT NULL, done TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_AB55E24FA76ED395 (user_id), INDEX IDX_AB55E24F224CCA91 (diagnostic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F224CCA91 FOREIGN KEY (diagnostic_id) REFERENCES diagnostic (id)');
        $this->addSql('ALTER TABLE diagnostic DROP FOREIGN KEY FK_FA7C8889A76ED395');
        $this->addSql('DROP INDEX IDX_FA7C8889A76ED395 ON diagnostic');
        $this->addSql('ALTER TABLE diagnostic ADD categories_scales LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD global_scale LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', DROP user_id, DROP meta, DROP done');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE participation');
        $this->addSql('ALTER TABLE diagnostic ADD user_id INT DEFAULT NULL, ADD meta JSON DEFAULT NULL, ADD done TINYINT(1) NOT NULL, DROP categories_scales, DROP global_scale');
        $this->addSql('ALTER TABLE diagnostic ADD CONSTRAINT FK_FA7C8889A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FA7C8889A76ED395 ON diagnostic (user_id)');
    }
}
