SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `locations`
    ADD COLUMN `assets_count` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `longitude`;

UPDATE `locations`
    SET `assets_count` = (SELECT COUNT(*) FROM `assets` WHERE `assets`.`location_id` = `locations`.`id`);

ALTER TABLE `locations`
    ADD INDEX `idx__longitude__latitude__deleted_at`(`longitude` ASC, `latitude` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
