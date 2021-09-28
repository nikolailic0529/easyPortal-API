SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Mark existing tables as outdated
ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_locations1`;

ALTER TABLE `location_types`
    DROP FOREIGN KEY `fk_locations_has_types_locations1`;

ALTER TABLE `assets`
    CHANGE COLUMN `location_id` `location_id_outdated` CHAR(36) NULL DEFAULT NULL COMMENT 'current';

ALTER TABLE `location_types`
    RENAME TO `location_types_outdated`;

ALTER TABLE `locations`
    RENAME TO `locations_outdated`;

-- Create new tables
CREATE TABLE IF NOT EXISTS `locations` (
    `id`              CHAR(36)         NOT NULL,
    `country_id`      CHAR(36)         NOT NULL,
    `city_id`         CHAR(36)         NOT NULL,
    `postcode`        VARCHAR(45)      NOT NULL,
    `state`           VARCHAR(255)     NOT NULL,
    `line_one`        VARCHAR(255)     NOT NULL,
    `line_two`        VARCHAR(255)     NOT NULL,
    `latitude`        DECIMAL(10, 8)   NULL     DEFAULT NULL,
    `longitude`       DECIMAL(11, 8)   NULL     DEFAULT NULL,
    `customers_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `assets_count`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP        NULL     DEFAULT NULL,
    `deleted_not`     TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    INDEX `fk_locations_countries1_idx`(`country_id` ASC) VISIBLE,
    INDEX `fk_locations_cities1_idx`(`city_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    INDEX `idx__postcode__deleted_at`(`postcode` ASC, `deleted_at` ASC) VISIBLE,
    INDEX `idx__latitude__longitude__deleted_at`(`latitude` ASC, `longitude` ASC, `deleted_at` ASC) VISIBLE,
    INDEX `idx__longitude__latitude__deleted_at`(`longitude` ASC, `latitude` ASC, `deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique__location`(`country_id` ASC, `city_id` ASC, `postcode` ASC, `line_one` ASC, `line_two` ASC) VISIBLE,
    CONSTRAINT `fk_locations_cities100`
        FOREIGN KEY (`city_id`)
            REFERENCES `cities`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_locations_countries100`
        FOREIGN KEY (`country_id`)
            REFERENCES `countries`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `customer_locations` (
    `id`           CHAR(36)         NOT NULL,
    `customer_id`  CHAR(36)         NOT NULL,
    `location_id`  CHAR(36)         NOT NULL,
    `assets_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP        NULL     DEFAULT NULL,
    `deleted_not`  TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_customer_locations_customers1_idx`(`customer_id` ASC) VISIBLE,
    INDEX `fk_customer_locations_locations1_idx`(`location_id` ASC) VISIBLE,
    UNIQUE INDEX `unique__location`(`customer_id` ASC, `location_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_customer_locations_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customer_locations_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `customer_location_types` (
    `id`                   CHAR(36)  NOT NULL,
    `customer_location_id` CHAR(36)  NOT NULL,
    `type_id`              CHAR(36)  NOT NULL,
    `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`           TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not`          TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    INDEX `fk_customer_location_types_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_customer_location_types_customer_locations1_idx`(`customer_location_id` ASC) VISIBLE,
    PRIMARY KEY (`id`),
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique__type`(`customer_location_id` ASC, `type_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_customer_location_types_customer_locations1`
        FOREIGN KEY (`customer_location_id`)
            REFERENCES `customer_locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customer_location_types_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `reseller_locations` (
    `id`              CHAR(36)         NOT NULL,
    `reseller_id`     CHAR(36)         NOT NULL,
    `location_id`     CHAR(36)         NOT NULL,
    `customers_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `assets_count`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP        NULL     DEFAULT NULL,
    `deleted_not`     TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_reseller_locations_locations1_idx`(`location_id` ASC) VISIBLE,
    INDEX `fk_reseller_locations_resellers1_idx`(`reseller_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique__location`(`reseller_id` ASC, `location_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_reseller_locations_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_reseller_locations_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `reseller_location_types` (
    `id`                   CHAR(36)  NOT NULL,
    `reseller_location_id` CHAR(36)  NOT NULL,
    `type_id`              CHAR(36)  NOT NULL,
    `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`           TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not`          TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_reseller_location_types_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_reseller_location_types_reseller_locations1_idx`(`reseller_location_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique__type`(`reseller_location_id` ASC, `type_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_reseller_location_types_reseller_locations1`
        FOREIGN KEY (`reseller_location_id`)
            REFERENCES `reseller_locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_reseller_location_types_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

ALTER TABLE `assets`
    ADD COLUMN `location_id` CHAR(36) NULL DEFAULT NULL COMMENT 'current' AFTER `customer_id`,
    ADD INDEX `fk_assets_locations2_idx`(`location_id` ASC) VISIBLE;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_locations2`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

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
