<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216111450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create countries table with UUID primary key and embedded currency';
    }

    public function up(Schema $schema): void
    {
        // Drop old table if exists
        $this->addSql('DROP TABLE IF EXISTS countries');
        
        // Create fresh countries table
        $this->addSql('CREATE TABLE countries (
            uuid CHAR(36) NOT NULL COMMENT \'(DC2Type: uuid)\',
            name VARCHAR(255) NOT NULL,
            region VARCHAR(100) DEFAULT NULL,
            sub_region VARCHAR(100) DEFAULT NULL,
            demonym VARCHAR(100) DEFAULT NULL,
            population INT UNSIGNED DEFAULT 0 NOT NULL,
            independent TINYINT(1) DEFAULT 0 NOT NULL,
            flag VARCHAR(500) DEFAULT NULL,
            currency_name VARCHAR(100) DEFAULT NULL,
            currency_symbol VARCHAR(10) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type: datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type: datetime_immutable)\',
            PRIMARY KEY(uuid),
            INDEX idx_name (name),
            INDEX idx_region (region)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE countries');
    }
}