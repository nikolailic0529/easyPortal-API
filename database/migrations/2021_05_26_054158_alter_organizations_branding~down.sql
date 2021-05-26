SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    DROP COLUMN `branding_welcome_underline`,
    DROP COLUMN `branding_welcome_heading`,
    DROP COLUMN `branding_welcome_image_url`,
    DROP COLUMN `branding_default_favicon_url`,
    DROP COLUMN `branding_default_logo_url`,
    DROP COLUMN `branding_default_secondary_color`,
    DROP COLUMN `branding_default_main_color`,
    CHANGE COLUMN `branding_main_color` `branding_primary_color` VARCHAR(7)   NULL DEFAULT NULL AFTER `branding_dark_theme`,
    CHANGE COLUMN `branding_logo_url` `branding_logo`            VARCHAR(255) NULL DEFAULT NULL AFTER `branding_secondary_color`,
    CHANGE COLUMN `branding_favicon_url` `branding_favicon`      VARCHAR(255) NULL DEFAULT NULL AFTER `branding_logo`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
