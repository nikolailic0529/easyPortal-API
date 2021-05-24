SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranty_services`
    DROP FOREIGN KEY `fk_asset_warranties_services_asset_warranties1`,
    DROP FOREIGN KEY `fk_asset_warranties_services_products1`;

ALTER TABLE `asset_warranty_services`
    CHANGE COLUMN `service_id` `product_id` CHAR(36) NOT NULL,
    DROP INDEX `fk_asset_warranties_services_products1_idx`,
    ADD INDEX `fk_asset_warranties_products_products1_idx`(`product_id` ASC) VISIBLE;

ALTER TABLE `asset_warranty_services`
    RENAME INDEX `fk_asset_warranties_services_asset_warranties1_idx` TO `fk_asset_warranties_products_asset_warranties1_idx`;

ALTER TABLE `asset_warranty_services`
    ALTER INDEX `fk_asset_warranties_products_asset_warranties1_idx` VISIBLE,
    RENAME TO `asset_warranty_products`;

ALTER TABLE `asset_warranty_products`
    ADD CONSTRAINT `fk_asset_warranties_products_asset_warranties1`
        FOREIGN KEY (`asset_warranty_id`)
            REFERENCES `asset_warranties`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_asset_warranties_products_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
