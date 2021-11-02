SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD COLUMN `coverages_count` INT(11) NOT NULL DEFAULT '0' AFTER `contacts_count`;

UPDATE `assets`
    SET `coverages_count` = (
        SELECT COUNT(*)
        FROM `asset_coverages`
        WHERE `asset_coverages`.`asset_id` = `assets`.`id`
            and `asset_coverages`.`deleted_at` IS NULL
    );

ALTER TABLE `assets`
    ADD INDEX `idx__coverages_count__deleted_at`(`coverages_count` ASC, `deleted_at` ASC) VISIBLE;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
