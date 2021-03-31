-- MySQL Workbench Synchronization
-- Generated: 2021-03-23 17:05
-- Model: New Model
-- Version: 1.0
-- Project: Easy portal
-- Author: Mohamed

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    DROP FOREIGN KEY `fk_organizations_currencies1`,
    DROP COLUMN `locale`,
    DROP COLUMN `currency_id`,
    DROP COLUMN `branding_dark_theme`,
    DROP COLUMN `branding_primary_color`,
    DROP COLUMN `branding_secondary_color`,
    DROP COLUMN `branding_logo`,
    DROP COLUMN `branding_fav_icon`;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
