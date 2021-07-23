SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranty_services`
    DROP FOREIGN KEY `fk_asset_warranties_services_asset_warranties1`,
    DROP FOREIGN KEY `fk_asset_warranty_services_service_levels1`;

ALTER TABLE `asset_warranty_services`
    DROP INDEX `unique__asset_warranty_id__service_id__deleted_not`,
    ADD UNIQUE INDEX `unique__asset_warranty_id__service_level_id__deleted_not`(`asset_warranty_id` ASC, `service_level_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `asset_warranty_services` RENAME INDEX `fk_asset_warranties_services_asset_warranties1_idx` TO `fk_asset_warranty_service_levels_asset_warranties1_idx`;
ALTER TABLE `asset_warranty_services`
    ALTER INDEX `fk_asset_warranty_service_levels_asset_warranties1_idx` VISIBLE;

ALTER TABLE `asset_warranty_services` RENAME INDEX `fk_asset_warranty_services_service_levels1_idx` TO `fk_asset_warranty_service_levels_service_levels1_idx`;
ALTER TABLE `asset_warranty_services`
    ALTER INDEX `fk_asset_warranty_service_levels_service_levels1_idx` INVISIBLE;

ALTER TABLE `asset_warranty_services`
    ADD CONSTRAINT `fk_asset_warranty_service_levels_asset_warranties1`
        FOREIGN KEY (`asset_warranty_id`)
            REFERENCES `asset_warranties`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_asset_warranty_service_levels_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranty_services`
    RENAME TO `asset_warranty_service_levels`;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
