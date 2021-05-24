SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranty_products`
    DROP FOREIGN KEY `fk_asset_warranties_products_asset_warranties1`,
    DROP FOREIGN KEY `fk_asset_warranties_products_products1`;

ALTER TABLE `asset_warranty_products`
    CHANGE COLUMN `product_id` `service_id` CHAR(36) NOT NULL,
    DROP INDEX `fk_asset_warranties_products_products1_idx`,
    ADD INDEX `fk_asset_warranties_services_products1_idx`(`service_id` ASC) VISIBLE;

ALTER TABLE `asset_warranty_products`
    RENAME TO `asset_warranty_services`;

ALTER TABLE `asset_warranty_services`
    RENAME INDEX `fk_asset_warranties_products_asset_warranties1_idx` TO `fk_asset_warranties_services_asset_warranties1_idx`;

ALTER TABLE `asset_warranty_services`
    ALTER INDEX `fk_asset_warranties_services_asset_warranties1_idx` VISIBLE;

ALTER TABLE `asset_warranty_services`
    ADD CONSTRAINT `fk_asset_warranties_services_asset_warranties1`
        FOREIGN KEY (`asset_warranty_id`)
            REFERENCES `asset_warranties`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_asset_warranties_services_products1`
        FOREIGN KEY (`service_id`)
            REFERENCES `products`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
