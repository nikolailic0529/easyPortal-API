SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD COLUMN `warranty_changed_at` TIMESTAMP NULL DEFAULT NULL AFTER `warranty_end`;

ALTER TABLE `asset_warranties`
    ADD COLUMN `type_id`     CHAR(36) NULL DEFAULT NULL AFTER `asset_id`,
    ADD COLUMN `status_id`   CHAR(36) NULL DEFAULT NULL AFTER `type_id`,
    ADD COLUMN `description` TEXT     NULL DEFAULT NULL AFTER `deleted_at`,
    ADD INDEX `fk_asset_warranties_statuses1_idx`(`status_id` ASC) VISIBLE,
    ADD INDEX `fk_asset_warranties_types1_idx`(`type_id` ASC) VISIBLE,
    DROP COLUMN `note`;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_asset_warranties_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;