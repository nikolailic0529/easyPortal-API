-- MySQL Workbench Synchronization
-- Generated: 2021-03-11 17:25
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD COLUMN `organization_id` CHAR(36) NULL DEFAULT NULL COMMENT 'current' AFTER `type_id`,
    ADD INDEX `fk_assets_organizations1_idx`(`organization_id` ASC) VISIBLE;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
