<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pdf_theme table and seed default active themes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE pdf_theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, document_type VARCHAR(20) NOT NULL, image_path VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, anchors JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A8F24E1A4A5240B2 (document_type), INDEX IDX_A8F24E1A1B5F89D (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("INSERT INTO pdf_theme (name, document_type, image_path, is_active, anchors, created_at) VALUES ('Theme White BL', 'DELIVERY', '/uploads/pdf-themes/theme-white.jpg', 1, '{}', NOW())");
        $this->addSql("INSERT INTO pdf_theme (name, document_type, image_path, is_active, anchors, created_at) VALUES ('Theme White Facture', 'INVOICE', '/uploads/pdf-themes/theme-white.jpg', 1, '{}', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pdf_theme');
    }
}
