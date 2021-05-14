SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `asset_warranty_products` (
    `asset_warranty_id` CHAR(36)  NOT NULL,
    `product_id`  CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    PRIMARY KEY (`asset_warranty_id`, `product_id`),
    INDEX `fk_asset_warranties_products_products1_idx`(`product_id` ASC) VISIBLE,
    INDEX `fk_asset_warranties_products_asset_warranties1_idx`(`asset_warranty_id` ASC) VISIBLE,
    CONSTRAINT `fk_asset_warranties_products_asset_warranties1`
        FOREIGN KEY (`asset_warranty_id`)
            REFERENCES `asset_warranties`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_warranties_products_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
