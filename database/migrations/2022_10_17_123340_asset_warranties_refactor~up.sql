SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranties`
    ADD COLUMN `service_level_id` CHAR(36) NULL DEFAULT NULL AFTER `service_group_id`,
    CHANGE COLUMN `description` `description` TEXT NULL DEFAULT NULL AFTER `end`,
    ADD INDEX `fk_asset_warranties_service_levels1_idx`(`service_level_id` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at__asset_id`(`deleted_at` ASC, `asset_id` ASC) VISIBLE,
    ADD INDEX `idx__end__deleted_at__asset_id`(`end` ASC, `deleted_at` ASC, `asset_id` ASC) VISIBLE,
    DROP INDEX `idx__asset_id__end__deleted_at`,
    DROP INDEX `idx__asset_id__deleted_at`,
    DROP INDEX `idx__end__deleted_at`,
    DROP INDEX `idx__deleted_at`;

UPDATE `asset_warranties`
    INNER JOIN `asset_warranty_service_levels` ON `asset_warranty_service_levels`.asset_warranty_id = `asset_warranties`.`id`
SET `asset_warranties`.`service_level_id` = `asset_warranty_service_levels`.`service_level_id`,
    `asset_warranties`.`deleted_at`       = `asset_warranty_service_levels`.`deleted_at`
WHERE `asset_warranty_service_levels`.`deleted_at` IS NULL;

DROP TABLE IF EXISTS `asset_warranty_service_levels`;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
