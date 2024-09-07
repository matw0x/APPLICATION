<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240907082550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE magic_link_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE magic_link_token (id INT NOT NULL, owner_id INT DEFAULT NULL, token VARCHAR(127) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E08FED567E3C61F9 ON magic_link_token (owner_id)');
        $this->addSql('ALTER TABLE magic_link_token ADD CONSTRAINT FK_E08FED567E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE magic_link_token_id_seq CASCADE');
        $this->addSql('ALTER TABLE magic_link_token DROP CONSTRAINT FK_E08FED567E3C61F9');
        $this->addSql('DROP TABLE magic_link_token');
    }
}
