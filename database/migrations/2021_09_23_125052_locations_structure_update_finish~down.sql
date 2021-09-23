SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Restore fields and tables
CREATE TABLE IF NOT EXISTS `locations_outdated` (
    `id`          CHAR(36)       NOT NULL,
    `object_id`   CHAR(36)       NULL,
    `object_type` VARCHAR(255)   NOT NULL,
    `country_id`  CHAR(36)       NOT NULL,
    `city_id`     CHAR(36)       NOT NULL,
    `postcode`    VARCHAR(45)    NOT NULL,
    `state`       VARCHAR(255)   NOT NULL,
    `line_one`    VARCHAR(255)   NOT NULL,
    `line_two`    VARCHAR(255)   NOT NULL,
    `latitude`    DECIMAL(10, 8) NULL,
    `longitude`   DECIMAL(11, 8) NULL,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP      NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    INDEX `fk_locations_countries1_idx`(`country_id` ASC) VISIBLE,
    INDEX `fk_locations_cities1_idx`(`city_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    INDEX `idx__postcode__deleted_at`(`postcode` ASC, `deleted_at` ASC) VISIBLE,
    INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    INDEX `idx__latitude__longitude__deleted_at`(`latitude` ASC, `longitude` ASC, `deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_locations_cities10`
        FOREIGN KEY (`city_id`)
            REFERENCES `cities`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_locations_countries10`
        FOREIGN KEY (`country_id`)
            REFERENCES `countries`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `location_types_outdated` (
    `id`          CHAR(36)  NOT NULL,
    `location_id` CHAR(36)  NOT NULL,
    `type_id`     CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique__location_id__type_id__deleted_not`(`location_id` ASC, `type_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `fk_location_types_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_location_types_locations1_idx`(`location_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_locations_has_types_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations_outdated`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_locations_has_types_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);

ALTER TABLE `assets`
    ADD COLUMN `location_id_outdated` CHAR(36) NULL DEFAULT NULL COMMENT 'current' AFTER `location_id`;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_locations2`;

-- Add helpers
ALTER TABLE `locations_outdated`
    ADD COLUMN `hash` VARCHAR(632) GENERATED ALWAYS AS (CONCAT(country_id, '/', city_id, '/', postcode, '/', line_one, ' ', line_two)) STORED AFTER `deleted_not`,
    ADD INDEX `idx__hash` (`hash` ASC) VISIBLE;

ALTER TABLE `locations`
    ADD COLUMN `hash` VARCHAR(632) GENERATED ALWAYS AS (CONCAT(country_id, '/', city_id, '/', postcode, '/', line_one, ' ', line_two)) STORED AFTER `deleted_not`,
    ADD INDEX `idx__hash` (`hash` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
