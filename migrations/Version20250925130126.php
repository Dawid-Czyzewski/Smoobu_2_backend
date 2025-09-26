<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925130126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE udzial (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, apartment_id INT NOT NULL, procent NUMERIC(5, 2) NOT NULL, INDEX IDX_FDF92B9EA76ED395 (user_id), INDEX IDX_FDF92B9E176DFE85 (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE udzial ADD CONSTRAINT FK_FDF92B9EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE udzial ADD CONSTRAINT FK_FDF92B9E176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE udzial DROP FOREIGN KEY FK_FDF92B9EA76ED395');
        $this->addSql('ALTER TABLE udzial DROP FOREIGN KEY FK_FDF92B9E176DFE85');
        $this->addSql('DROP TABLE udzial');
    }
}
