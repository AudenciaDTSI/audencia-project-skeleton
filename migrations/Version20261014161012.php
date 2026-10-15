<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261014161012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Utilisateurs connectés par SSO Microsoft';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_security_user (id BLOB NOT NULL, azure_id VARCHAR(64) NOT NULL, email VARCHAR(180) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles CLOB NOT NULL, last_login_at DATETIME DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8FF67247C7EC4E7 ON app_security_user (azure_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8FF6724E7927C74 ON app_security_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_security_user');
    }
}
