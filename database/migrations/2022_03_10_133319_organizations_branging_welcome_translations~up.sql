SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

UPDATE `organizations`
SET `branding_welcome_heading` = JSON_OBJECT('en_GB', `branding_welcome_heading`)
WHERE `branding_welcome_heading` is not null;

UPDATE `organizations`
SET `branding_welcome_underline` = JSON_OBJECT('en_GB', `branding_welcome_underline`)
WHERE `branding_welcome_underline` is not null;

ALTER TABLE `organizations`
    CHANGE COLUMN `branding_welcome_heading` `branding_welcome_heading` JSON NULL,
    CHANGE COLUMN `branding_welcome_underline` `branding_welcome_underline` JSON NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
