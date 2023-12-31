SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    ADD COLUMN `locale` CHAR(9) NULL DEFAULT NULL AFTER `name`,
    ADD COLUMN `currency_id` CHAR(36) NULL DEFAULT NULL AFTER `locale`,
    ADD COLUMN `branding_dark_theme` TINYINT(1) NOT NULL DEFAULT 0 AFTER `currency_id`,
    ADD COLUMN `branding_primary_color` CHAR(10) NULL DEFAULT NULL AFTER `branding_dark_theme`,
    ADD COLUMN `branding_secondary_color` CHAR(10) NULL DEFAULT NULL AFTER `branding_primary_color`,
    ADD COLUMN `branding_logo` varchar(255) NULL DEFAULT NULL AFTER `branding_secondary_color`,
    ADD COLUMN `branding_favicon` varchar(255) NULL DEFAULT NULL AFTER `branding_logo`,

    ADD CONSTRAINT `fk_organizations_currencies1`
        FOREIGN KEY (`currency_id`)
            REFERENCES `currencies`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;