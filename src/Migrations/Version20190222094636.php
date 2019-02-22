<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190222094636 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ratecode (id INT AUTO_INCREMENT NOT NULL, ratetype_id INT NOT NULL, name VARCHAR(255) NOT NULL, price_single NUMERIC(5, 2) NOT NULL, price_double NUMERIC(5, 2) NOT NULL, price_triple NUMERIC(5, 2) NOT NULL, INDEX IDX_31A656BB9E885A7B (ratetype_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ratecode ADD CONSTRAINT FK_31A656BB9E885A7B FOREIGN KEY (ratetype_id) REFERENCES ratetype (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ratecode');
    }
}
