<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618023206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD login VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD roles JSON DEFAULT NULL');
        $this->addSql('UPDATE "user" SET roles = \'["ROLE_ADMIN"]\' WHERE admin = true');
        $this->addSql('UPDATE "user" SET roles = \'[]\' WHERE roles IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER roles SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649AA08CB10 ON "user" (login)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649AA08CB10 ON "user"');
        $this->addSql('ALTER TABLE "user" DROP login');
        $this->addSql('ALTER TABLE "user" DROP password');
        $this->addSql('ALTER TABLE "user" DROP roles');
    }
}
