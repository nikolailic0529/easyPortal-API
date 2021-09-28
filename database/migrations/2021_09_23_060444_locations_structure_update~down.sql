SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- DROP tables
DROP TABLE IF EXISTS `customer_location_types`;
DROP TABLE IF EXISTS `customer_locations`;
DROP TABLE IF EXISTS `reseller_location_types`;
DROP TABLE IF EXISTS `reseller_locations`;
DROP TABLE IF EXISTS `locations`;

-- DROP helpers
ALTER TABLE `locations_outdated`
    DROP INDEX `idx__hash`,
    DROP COLUMN `hash`;

-- Rename
ALTER TABLE `location_types_outdated`
    DROP FOREIGN KEY `fk_locations_has_types_locations1`;

ALTER TABLE `location_types_outdated`
    RENAME TO `location_types`;

ALTER TABLE `locations_outdated`
    RENAME TO `locations`;

ALTER TABLE `location_types`
    ADD CONSTRAINT `fk_locations_has_types_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

-- Assets
ALTER TABLE `assets`
    RENAME INDEX `fk_assets_locations2_idx` TO `fk_assets_locations1_idx`;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
