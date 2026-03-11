<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311203500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional external reference for payments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment ADD external_reference VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP external_reference');
    }
}
