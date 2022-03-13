SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    CHANGE COLUMN `branding_welcome_heading` `branding_welcome_heading` TEXT NULL,
    CHANGE COLUMN `branding_welcome_underline` `branding_welcome_underline` TEXT NULL;

UPDATE `organizations`
SET `branding_welcome_heading` = `branding_welcome_heading`->>"$.en_GB"
WHERE `branding_welcome_heading` is not null;

UPDATE `organizations`
SET `branding_welcome_underline` = `branding_welcome_underline`->>"$.en_GB"
WHERE `branding_welcome_underline` is not null;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
