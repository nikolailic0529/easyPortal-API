-- MySQL Workbench Synchronization
-- Generated: 2021-02-23 10:32
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `product_categories` (
    `id`         CHAR(36)     NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `products` (
    `id`          CHAR(36)     NOT NULL,
    `oem_id`      CHAR(36)     NOT NULL,
    `category_id` CHAR(36)     NOT NULL,
    `sku`         VARCHAR(64)  NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_products_product_categories1_idx`(`category_id` ASC) VISIBLE,
    INDEX `fk_products_oems1_idx`(`oem_id` ASC) VISIBLE,
    CONSTRAINT `fk_products_product_categories1`
        FOREIGN KEY (`category_id`)
            REFERENCES `product_categories`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_products_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
