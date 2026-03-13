<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add remise amount to facture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE facture ADD remise NUMERIC(12, 3) DEFAULT '0.000' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facture DROP remise');
    }
}