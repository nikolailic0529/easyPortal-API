-- MySQL Workbench Synchronization
-- Generated: 2021-03-23 17:05
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranties`
    DROP INDEX `unique__asset_id__document_id`;

ALTER TABLE `document_entries`
    DROP INDEX `unique__asset_id__document_id__product_id`;

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_documents1`;

ALTER TABLE `asset_warranties`
    CHANGE COLUMN `document_id` `document_id` CHAR(36) NOT NULL;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
