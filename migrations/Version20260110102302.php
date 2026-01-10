<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Creates a default admin user during installation
 */
final class Version20260110102302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO user (email, roles, password)
            SELECT 'admin@example.com', '[\"ROLE_ADMIN\"]', :hashed_password
            WHERE NOT EXISTS (
                SELECT 1 FROM user WHERE email = 'admin@example.com'
            )
        ", [
            'hashed_password' => '$2y$12$gn7Rb0xFGBXvh57PewKAqu6vmDrhO/AO.z9Z2wtgsG7KRsUAT/9H6'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user WHERE email = 'admin@example.com'");
    }
}
