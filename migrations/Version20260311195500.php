<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add immutable client and delivery snapshots to facture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facture ADD client_snapshot JSON DEFAULT NULL, ADD delivery_snapshot JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facture DROP client_snapshot, DROP delivery_snapshot');
    }
}
