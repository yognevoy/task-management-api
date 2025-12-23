<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223200452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks ADD assignee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_5058659759EC7D60 FOREIGN KEY (assignee_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5058659759EC7D60 ON tasks (assignee_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_5058659759EC7D60');
        $this->addSql('DROP INDEX IDX_5058659759EC7D60 ON tasks');
        $this->addSql('ALTER TABLE tasks DROP assignee_id');
    }
}
