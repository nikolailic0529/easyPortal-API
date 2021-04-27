-- MySQL Workbench Synchronization
-- Generated: 2021-04-26 12:17
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    ADD COLUMN `currency_id` CHAR(36)       NULL DEFAULT NULL AFTER `quantity`,
    ADD COLUMN `net_price`   DECIMAL(12, 2) NULL DEFAULT NULL AFTER `currency_id`,
    ADD COLUMN `list_price`  DECIMAL(12, 2) NULL DEFAULT NULL AFTER `net_price`,
    ADD COLUMN `discount`    DECIMAL(6, 2)  NULL DEFAULT NULL AFTER `list_price`,
    ADD INDEX `fk_document_entries_currencies1_idx`(`currency_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_currencies1`
        FOREIGN KEY (`currency_id`)
            REFERENCES `currencies`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
