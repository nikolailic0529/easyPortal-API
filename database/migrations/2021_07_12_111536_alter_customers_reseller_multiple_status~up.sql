SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `customers`
    DROP FOREIGN KEY `fk_customers_statuses1`;

ALTER TABLE `resellers`
    DROP FOREIGN KEY `fk_resellers_statuses1`;

ALTER TABLE `customers`
    DROP COLUMN `status_id`,
    DROP INDEX `fk_customers_statuses1_idx`;

ALTER TABLE `resellers`
    DROP COLUMN `status_id`,
    DROP INDEX `fk_resellers_statuses1_idx`;

CREATE TABLE IF NOT EXISTS `customer_statuses` (
    `id`          CHAR(36)  NOT NULL,
    `customer_id` CHAR(36)  NOT NULL,
    `status_id`   CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_customer_statuses_statuses1_idx`(`status_id` ASC) VISIBLE,
    INDEX `fk_customer_statuses_customers1_idx`(`customer_id` ASC) VISIBLE,
    UNIQUE INDEX `unique__customer_id__status_id__deleted_not`(`customer_id` ASC, `status_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_customer_statuses_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_customer_statuses_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `reseller_statuses` (
    `id`          CHAR(36)  NOT NULL,
    `reseller_id` CHAR(36)  NOT NULL,
    `status_id`   CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_reseller_statuses_statuses1_idx`(`status_id` ASC) VISIBLE,
    INDEX `fk_reseller_statuses_resellers1_idx`(`reseller_id` ASC) VISIBLE,
    UNIQUE INDEX `unique__reseller_id__status_id__deleted_not`(`reseller_id` ASC, `status_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_reseller_statuses_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_reseller_statuses_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
