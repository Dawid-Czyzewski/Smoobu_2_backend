<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250925071522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        
        // First, convert existing binary UUIDs to string format
        $this->addSql('UPDATE password_reset_tokens SET id = CONCAT(
            SUBSTRING(HEX(id), 1, 8), "-",
            SUBSTRING(HEX(id), 9, 4), "-",
            SUBSTRING(HEX(id), 13, 4), "-",
            SUBSTRING(HEX(id), 17, 4), "-",
            SUBSTRING(HEX(id), 21, 12)
        )');
        
        // Then change the column type
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE id id VARCHAR(36) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
    }
}
