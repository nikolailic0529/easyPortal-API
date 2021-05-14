SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `locations`
    DROP INDEX `idx__object_id__object_type`,
    DROP COLUMN `object_id`,
    DROP COLUMN `object_type`;

DROP TABLE IF EXISTS `location_types`;

CREATE TABLE IF NOT EXISTS `customer_locations` (
    `id`          CHAR(36)  NOT NULL,
    `customer_id` CHAR(36)  NOT NULL,
    `location_id` CHAR(36)  NOT NULL,
    `type_id`     CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_customer_locations_customers1_idx`(`customer_id` ASC) VISIBLE,
    INDEX `fk_customer_locations_locations1_idx`(`location_id` ASC) VISIBLE,
    INDEX `fk_customer_locations_types1_idx`(`type_id` ASC) VISIBLE,
    UNIQUE INDEX `unique__customer_id__location_id__type_id`(`customer_id` ASC, `location_id` ASC, `type_id` ASC) VISIBLE,
    CONSTRAINT `fk_customer_locations_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customer_locations_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customer_locations_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
