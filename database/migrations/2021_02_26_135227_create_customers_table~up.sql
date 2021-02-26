-- MySQL Workbench Synchronization
-- Generated: 2021-02-26 17:36
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `customers` (
    `id`         CHAR(36)     NOT NULL,
    `type_id`    CHAR(36)     NOT NULL,
    `status_id`  CHAR(36)     NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP    NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_customers_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_customers_statuses1_idx`(`status_id` ASC) VISIBLE,
    CONSTRAINT `fk_customers_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customers_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
