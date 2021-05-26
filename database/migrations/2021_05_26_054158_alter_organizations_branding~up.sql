SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    ADD COLUMN `branding_favicon_url`                               VARCHAR(2048) NULL DEFAULT NULL AFTER `branding_logo_url`,
    ADD COLUMN `branding_default_main_color`                        VARCHAR(7)    NULL DEFAULT NULL AFTER `branding_favicon_url`,
    ADD COLUMN `branding_default_secondary_color`                   VARCHAR(7)    NULL DEFAULT NULL AFTER `branding_default_main_color`,
    ADD COLUMN `branding_default_logo_url`                          VARCHAR(2048) NULL DEFAULT NULL AFTER `branding_default_secondary_color`,
    ADD COLUMN `branding_welcome_image_url`                         VARCHAR(2048) NULL DEFAULT NULL AFTER `branding_default_favicon_url`,
    ADD COLUMN `branding_welcome_heading`                           TEXT          NULL DEFAULT NULL AFTER `branding_welcome_image_url`,
    ADD COLUMN `branding_welcome_underline`                         TEXT          NULL DEFAULT NULL AFTER `branding_welcome_heading`,
    CHANGE COLUMN `branding_primary_color` `branding_main_color`    VARCHAR(7)    NULL DEFAULT NULL,
    CHANGE COLUMN `branding_logo` `branding_logo_url`               VARCHAR(2048) NULL DEFAULT NULL,
    CHANGE COLUMN `branding_favicon` `branding_default_favicon_url` VARCHAR(2048) NULL DEFAULT NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
