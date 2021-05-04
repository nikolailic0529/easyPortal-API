-- MySQL Workbench Synchronization
-- Generated: 2021-05-04 11:46
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `languages`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `organizations`
    DROP FOREIGN KEY `fk_organizations_currencies1`;

ALTER TABLE `organizations`
    CHANGE COLUMN `locale` `locale` VARCHAR(8) NULL DEFAULT NULL,
    ADD INDEX `fk_organizations_currencies1_idx`(`currency_id` ASC) VISIBLE,
    DROP INDEX `fk_organizations_currencies1`;

ALTER TABLE `users`
    CHANGE COLUMN `locale` `locale` VARCHAR(8) NULL DEFAULT NULL;

ALTER TABLE `organizations`
    ADD CONSTRAINT `fk_organizations_currencies1`
        FOREIGN KEY (`currency_id`)
            REFERENCES `currencies`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `organizations`
    CHANGE COLUMN `branding_primary_color` `branding_primary_color`     VARCHAR(7) NULL DEFAULT NULL,
    CHANGE COLUMN `branding_secondary_color` `branding_secondary_color` VARCHAR(7) NULL DEFAULT NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
