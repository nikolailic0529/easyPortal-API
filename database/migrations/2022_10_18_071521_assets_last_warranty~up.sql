SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD COLUMN `warranty_service_group_id` CHAR(36) NULL DEFAULT NULL AFTER `warranty_changed_at`,
    ADD COLUMN `warranty_service_level_id` CHAR(36) NULL DEFAULT NULL AFTER `warranty_service_group_id`,
    ADD INDEX `fk_assets_service_groups1_idx`(`warranty_service_group_id` ASC) VISIBLE,
    ADD INDEX `fk_assets_service_levels1_idx`(`warranty_service_level_id` ASC) VISIBLE;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_service_groups1`
        FOREIGN KEY (`warranty_service_group_id`)
            REFERENCES `service_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_service_levels1`
        FOREIGN KEY (`warranty_service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
