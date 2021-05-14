SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `locations` (
    `id`         CHAR(36)       NOT NULL,
    `country_id` CHAR(36)       NOT NULL,
    `city_id`    CHAR(36)       NOT NULL,
    `postcode`   VARCHAR(45)    NOT NULL,
    `state`      VARCHAR(255)   NOT NULL,
    `line_one`   VARCHAR(255)   NOT NULL,
    `line_two`   VARCHAR(255)   NOT NULL,
    `lat`        DECIMAL(10, 8) NULL     DEFAULT NULL,
    `lng`        DECIMAL(11, 8) NULL     DEFAULT NULL,
    `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP      NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_locations_countries1_idx`(`country_id` ASC) VISIBLE,
    INDEX `fk_locations_cities1_idx`(`city_id` ASC) VISIBLE,
    INDEX `idx__postcode`(`postcode` ASC) VISIBLE,
    CONSTRAINT `fk_locations_countries10`
        FOREIGN KEY (`country_id`)
            REFERENCES `countries`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_locations_cities10`
        FOREIGN KEY (`city_id`)
            REFERENCES `cities`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
