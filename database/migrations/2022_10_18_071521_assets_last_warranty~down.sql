SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_asset_warranties1`,
    DROP FOREIGN KEY `fk_assets_service_groups1`,
    DROP FOREIGN KEY `fk_assets_service_levels1`;

ALTER TABLE `assets`
    DROP INDEX `fk_assets_asset_warranties1_idx`,
    DROP INDEX `fk_assets_service_groups1_idx`,
    DROP INDEX `fk_assets_service_levels1_idx`,
    DROP COLUMN `warranty_id`,
    DROP COLUMN `warranty_service_group_id`,
    DROP COLUMN `warranty_service_level_id`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
