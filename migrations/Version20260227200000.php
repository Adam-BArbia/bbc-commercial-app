<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all security and commercial workflow tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS = 0");
        
        // Security tables
        $this->addSql("CREATE TABLE IF NOT EXISTS `user` (
          `id` int NOT NULL AUTO_INCREMENT,
          `role_id` int NOT NULL,
          `email` varchar(180) NOT NULL,
          `name` varchar(255) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `active` tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`),
          KEY `IDX_U1` (`role_id`),
          CONSTRAINT `FK_U1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `role` (
          `id` int NOT NULL AUTO_INCREMENT,
          `name` varchar(180) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_NAME` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `privilege` (
          `id` int NOT NULL AUTO_INCREMENT,
          `code` varchar(255) NOT NULL,
          `description` varchar(500) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_CODE` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `role_privilege` (
          `role_id` int NOT NULL,
          `privilege_id` int NOT NULL,
          PRIMARY KEY (`role_id`, `privilege_id`),
          KEY `IDX_RP2` (`privilege_id`),
          CONSTRAINT `FK_RP1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`),
          CONSTRAINT `FK_RP2` FOREIGN KEY (`privilege_id`) REFERENCES `privilege` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Commercial tables
        $this->addSql("CREATE TABLE IF NOT EXISTS `client` (
          `id` int NOT NULL AUTO_INCREMENT,
          `client_code` varchar(50) NOT NULL,
          `matricule_fiscale` varchar(50) NOT NULL,
          `name` varchar(255) NOT NULL,
          `address` longtext NOT NULL,
          `active` tinyint(1) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_C74404552` (`matricule_fiscale`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `article` (
          `id` int NOT NULL AUTO_INCREMENT,
          `code` varchar(50) NOT NULL,
          `designation` varchar(255) NOT NULL,
          `active` tinyint(1) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_23A0E663` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `bon_commande` (
          `id` int NOT NULL AUTO_INCREMENT,
          `client_id` int NOT NULL,
          `created_by` int NOT NULL,
          `reference` varchar(50) NOT NULL,
          `status` varchar(50) NOT NULL,
          `client_snapshot` json DEFAULT NULL,
          `created_at` datetime NOT NULL,
          `cancelled_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_5C1F99` (`reference`),
          KEY `IDX_C` (`client_id`),
          KEY `IDX_C2` (`created_by`),
          CONSTRAINT `FK_C1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
          CONSTRAINT `FK_C2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `bon_commande_item` (
          `id` int NOT NULL AUTO_INCREMENT,
          `bon_commande_id` int NOT NULL,
          `article_id` int NOT NULL,
          `quantity` numeric(10,2) NOT NULL,
          `unit_price_snapshot` numeric(10,3) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_BCI1` (`bon_commande_id`),
          KEY `IDX_BCI2` (`article_id`),
          CONSTRAINT `FK_BCI1` FOREIGN KEY (`bon_commande_id`) REFERENCES `bon_commande` (`id`),
          CONSTRAINT `FK_BCI2` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `facture` (
          `id` int NOT NULL AUTO_INCREMENT,
          `created_by` int NOT NULL,
          `reference` varchar(50) NOT NULL,
          `status` varchar(50) NOT NULL,
          `total_ht` numeric(12,3) NOT NULL,
          `tva_rate` numeric(5,2) NOT NULL,
          `tva_amount` numeric(12,3) NOT NULL,
          `timbre` numeric(12,3) NOT NULL,
          `total_ttc` numeric(12,3) NOT NULL,
          `created_at` datetime NOT NULL,
          `cancelled_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_F1` (`reference`),
          KEY `IDX_F1` (`created_by`),
          CONSTRAINT `FK_F1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `bon_livraison` (
          `id` int NOT NULL AUTO_INCREMENT,
          `bon_commande_id` int NOT NULL,
          `created_by` int NOT NULL,
          `facture_id` int DEFAULT NULL,
          `reference` varchar(50) NOT NULL,
          `status` varchar(50) NOT NULL,
          `created_at` datetime NOT NULL,
          `cancelled_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `UNIQ_BL1` (`reference`),
          KEY `IDX_BL1` (`bon_commande_id`),
          KEY `IDX_BL2` (`created_by`),
          KEY `IDX_BL3` (`facture_id`),
          CONSTRAINT `FK_BL1` FOREIGN KEY (`bon_commande_id`) REFERENCES `bon_commande` (`id`),
          CONSTRAINT `FK_BL2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
          CONSTRAINT `FK_BL3` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `bon_livraison_item` (
          `id` int NOT NULL AUTO_INCREMENT,
          `bon_livraison_id` int NOT NULL,
          `bon_commande_item_id` int NOT NULL,
          `quantity_delivered` numeric(10,2) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_BLI1` (`bon_livraison_id`),
          KEY `IDX_BLI2` (`bon_commande_item_id`),
          CONSTRAINT `FK_BLI1` FOREIGN KEY (`bon_livraison_id`) REFERENCES `bon_livraison` (`id`),
          CONSTRAINT `FK_BLI2` FOREIGN KEY (`bon_commande_item_id`) REFERENCES `bon_commande_item` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `facture_item` (
          `id` int NOT NULL AUTO_INCREMENT,
          `facture_id` int NOT NULL,
          `article_id` int NOT NULL,
          `quantity` numeric(10,2) NOT NULL,
          `unit_price` numeric(10,3) NOT NULL,
          `total_line_ht` numeric(12,3) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_FI1` (`facture_id`),
          KEY `IDX_FI2` (`article_id`),
          CONSTRAINT `FK_FI1` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`),
          CONSTRAINT `FK_FI2` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `payment` (
          `id` int NOT NULL AUTO_INCREMENT,
          `created_by` int NOT NULL,
          `payment_date` date NOT NULL,
          `method` varchar(50) NOT NULL,
          `reference` varchar(100) DEFAULT NULL,
          `amount` numeric(12,3) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_P1` (`created_by`),
          CONSTRAINT `FK_P1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `payment_facture` (
          `id` int NOT NULL AUTO_INCREMENT,
          `payment_id` int NOT NULL,
          `facture_id` int NOT NULL,
          `amount_allocated` numeric(12,3) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_PF1` (`payment_id`),
          KEY `IDX_PF2` (`facture_id`),
          CONSTRAINT `FK_PF1` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`),
          CONSTRAINT `FK_PF2` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `audit_log` (
          `id` int NOT NULL AUTO_INCREMENT,
          `performed_by` int NOT NULL,
          `table_name` varchar(100) NOT NULL,
          `record_id` int NOT NULL,
          `action` varchar(50) NOT NULL,
          `old_data` json DEFAULT NULL,
          `new_data` json DEFAULT NULL,
          `performed_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `IDX_AL1` (`table_name`),
          KEY `IDX_AL2` (`performed_by`),
          KEY `IDX_AL3` (`performed_at`),
          CONSTRAINT `FK_AL1` FOREIGN KEY (`performed_by`) REFERENCES `user` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE IF NOT EXISTS `document_counter` (
          `document_type` varchar(50) NOT NULL,
          `year` int NOT NULL,
          `last_number` int NOT NULL,
          PRIMARY KEY (`document_type`, `year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $this->addSql("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS = 0");
        $this->addSql("DROP TABLE IF EXISTS `document_counter`");
        $this->addSql("DROP TABLE IF EXISTS `audit_log`");
        $this->addSql("DROP TABLE IF EXISTS `payment_facture`");
        $this->addSql("DROP TABLE IF EXISTS `payment`");
        $this->addSql("DROP TABLE IF EXISTS `facture_item`");
        $this->addSql("DROP TABLE IF EXISTS `bon_livraison_item`");
        $this->addSql("DROP TABLE IF EXISTS `bon_livraison`");
        $this->addSql("DROP TABLE IF EXISTS `facture`");
        $this->addSql("DROP TABLE IF EXISTS `bon_commande_item`");
        $this->addSql("DROP TABLE IF EXISTS `bon_commande`");
        $this->addSql("DROP TABLE IF EXISTS `article`");
        $this->addSql("DROP TABLE IF EXISTS `client`");
        $this->addSql("DROP TABLE IF EXISTS `role_privilege`");
        $this->addSql("DROP TABLE IF EXISTS `privilege`");
        $this->addSql("DROP TABLE IF EXISTS `user`");
        $this->addSql("DROP TABLE IF EXISTS `role`");
        $this->addSql("SET FOREIGN_KEY_CHECKS = 1");
    }
}
