-- MySQL Workbench Synchronization
-- Generated: 2021-03-03 19:27
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `locations`
    ADD COLUMN `object_id`   CHAR(36)     NOT NULL AFTER `id`,
    ADD COLUMN `object_type` VARCHAR(255) NOT NULL AFTER `object_id`,
    ADD INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) VISIBLE;

CREATE TABLE IF NOT EXISTS `location_types` (
    `location_id` CHAR(36)  NOT NULL,
    `type_id`     CHAR(36)  NOT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP NULL     DEFAULT NULL,
    PRIMARY KEY (`location_id`, `type_id`),
    INDEX `fk_location_types_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_location_types_locations1_idx`(`location_id` ASC) VISIBLE,
    CONSTRAINT `fk_locations_has_types_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_locations_has_types_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);

DROP TABLE IF EXISTS `customer_locations`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
