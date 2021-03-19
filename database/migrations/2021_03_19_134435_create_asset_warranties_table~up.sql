-- MySQL Workbench Synchronization
-- Generated: 2021-03-19 15:59
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `asset_warranties` (
    `id`          CHAR(36)  NOT NULL,
    `asset_id`    CHAR(36)  NOT NULL,
    `reseller_id` CHAR(36)  NOT NULL,
    `customer_id` CHAR(36)  NOT NULL,
    `document_id` CHAR(36)  NOT NULL,
    `start`       TIMESTAMP NULL,
    `end`         TIMESTAMP NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    `note`        TEXT      NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_asset_warranties_assets1_idx`(`asset_id` ASC) VISIBLE,
    INDEX `fk_asset_warranties_documents1_idx`(`document_id` ASC) VISIBLE,
    INDEX `fk_asset_warranties_resellers1_idx`(`reseller_id` ASC) VISIBLE,
    INDEX `fk_asset_warranties_customers1_idx`(`customer_id` ASC) VISIBLE,
    CONSTRAINT `fk_asset_warranties_assets1`
        FOREIGN KEY (`asset_id`)
            REFERENCES `assets`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_warranties_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_warranties_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_warranties_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
