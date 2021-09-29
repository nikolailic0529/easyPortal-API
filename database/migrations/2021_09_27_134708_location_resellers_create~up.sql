SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `location_resellers` (
    `id`              CHAR(36)         NOT NULL,
    `location_id`     CHAR(36)         NOT NULL,
    `reseller_id`     CHAR(36)         NOT NULL,
    `customers_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `assets_count`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP        NULL     DEFAULT NULL,
    `deleted_not`     TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    INDEX `fk_location_resellers_resellers1_idx`(`reseller_id` ASC) VISIBLE,
    INDEX `fk_location_resellers_locations1_idx`(`location_id` ASC) INVISIBLE,
    PRIMARY KEY (`id`),
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unuqie__reseller`(`location_id` ASC, `reseller_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_location_resellers_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_location_resellers_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
