<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217211506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5C93B3A47E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A47E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tasks ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)');
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597166D1F9C');
        $this->addSql('ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A47E3C61F9');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP INDEX IDX_50586597166D1F9C ON tasks');
        $this->addSql('ALTER TABLE tasks DROP project_id');
    }
}
