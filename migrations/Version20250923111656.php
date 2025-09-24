<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923111656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9BACE7E15F37A13B ON refresh_tokens');
        $this->addSql('ALTER TABLE refresh_tokens ADD username VARCHAR(255) NOT NULL, DROP user_id, CHANGE token refresh_token VARCHAR(255) NOT NULL, CHANGE expires_at valid DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens');
        $this->addSql('ALTER TABLE refresh_tokens ADD token VARCHAR(255) NOT NULL, ADD user_id INT NOT NULL, DROP refresh_token, DROP username, CHANGE valid expires_at DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E15F37A13B ON refresh_tokens (token)');
    }
}
