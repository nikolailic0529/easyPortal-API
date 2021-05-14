SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `assets` (
    `id`            CHAR(36)     NOT NULL,
    `oem_id`        CHAR(36)     NOT NULL,
    `product_id`    CHAR(36)     NOT NULL,
    `customer_id`   CHAR(36)     NULL COMMENT 'current',
    `location_id`   CHAR(36)     NULL COMMENT 'current',
    `serial_number` VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`    TIMESTAMP    NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_assets_oems1_idx`(`oem_id` ASC) VISIBLE,
    INDEX `fk_assets_products1_idx`(`product_id` ASC) VISIBLE,
    INDEX `fk_assets_customers1_idx`(`customer_id` ASC) VISIBLE,
    INDEX `fk_assets_locations1_idx`(`location_id` ASC) VISIBLE,
    CONSTRAINT `fk_assets_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_assets_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_assets_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_assets_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);

ALTER TABLE `customers`
    ADD COLUMN `assets_count` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0 AFTER `name`;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
