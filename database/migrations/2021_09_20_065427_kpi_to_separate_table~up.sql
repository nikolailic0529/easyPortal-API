SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Create table
CREATE TABLE IF NOT EXISTS `kpis` (
    `id`                      CHAR(36)         NOT NULL,
    `object_id`               CHAR(36)         NOT NULL,
    `object_type`             VARCHAR(255)     NOT NULL,
    `assets_total`            INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `assets_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `assets_covered`          DOUBLE UNSIGNED  NOT NULL DEFAULT 0,
    `customers_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `customers_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `contracts_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `contracts_active_amount` DOUBLE UNSIGNED  NOT NULL DEFAULT 0,
    `contracts_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `contracts_expiring`      INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `quotes_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `quotes_active_amount`    DOUBLE UNSIGNED  NOT NULL DEFAULT 0,
    `quotes_active_new`       INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `quotes_expiring`         INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`              TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`              TIMESTAMP        NULL     DEFAULT NULL,
    `deleted_not`             TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE
);

-- Move existing values into new table
INSERT INTO `kpis` (`id`,
                    `object_id`,
                    `object_type`,
                    `assets_total`,
                    `assets_active`,
                    `assets_covered`,
                    `customers_active`,
                    `customers_active_new`,
                    `contracts_active`,
                    `contracts_active_amount`,
                    `contracts_active_new`,
                    `contracts_expiring`,
                    `quotes_active`,
                    `quotes_active_amount`,
                    `quotes_active_new`,
                    `quotes_expiring`)
SELECT UUID(),
       `customers`.`id`,
       'Customer',
       `customers`.`kpi_assets_total`,
       `customers`.`kpi_assets_active`,
       `customers`.`kpi_assets_covered`,
       `customers`.`kpi_customers_active`,
       `customers`.`kpi_customers_active_new`,
       `customers`.`kpi_contracts_active`,
       `customers`.`kpi_contracts_active_amount`,
       `customers`.`kpi_contracts_active_new`,
       `customers`.`kpi_contracts_expiring`,
       `customers`.`kpi_quotes_active`,
       `customers`.`kpi_quotes_active_amount`,
       `customers`.`kpi_quotes_active_new`,
       `customers`.`kpi_quotes_expiring`
FROM `customers`
UNION
SELECT UUID(),
       `organizations`.`id`,
       'Reseller',
       `organizations`.`kpi_assets_total`,
       `organizations`.`kpi_assets_active`,
       `organizations`.`kpi_assets_covered`,
       `organizations`.`kpi_customers_active`,
       `organizations`.`kpi_customers_active_new`,
       `organizations`.`kpi_contracts_active`,
       `organizations`.`kpi_contracts_active_amount`,
       `organizations`.`kpi_contracts_active_new`,
       `organizations`.`kpi_contracts_expiring`,
       `organizations`.`kpi_quotes_active`,
       `organizations`.`kpi_quotes_active_amount`,
       `organizations`.`kpi_quotes_active_new`,
       `organizations`.`kpi_quotes_expiring`
FROM `organizations`;

-- Remove old fields
ALTER TABLE `customers`
    DROP COLUMN `kpi_quotes_expiring`,
    DROP COLUMN `kpi_quotes_active_new`,
    DROP COLUMN `kpi_quotes_active_amount`,
    DROP COLUMN `kpi_quotes_active`,
    DROP COLUMN `kpi_contracts_expiring`,
    DROP COLUMN `kpi_contracts_active_new`,
    DROP COLUMN `kpi_contracts_active_amount`,
    DROP COLUMN `kpi_contracts_active`,
    DROP COLUMN `kpi_customers_active_new`,
    DROP COLUMN `kpi_customers_active`,
    DROP COLUMN `kpi_assets_covered`,
    DROP COLUMN `kpi_assets_active`,
    DROP COLUMN `kpi_assets_total`;

ALTER TABLE `organizations`
    DROP COLUMN `kpi_quotes_expiring`,
    DROP COLUMN `kpi_quotes_active_new`,
    DROP COLUMN `kpi_quotes_active_amount`,
    DROP COLUMN `kpi_quotes_active`,
    DROP COLUMN `kpi_contracts_expiring`,
    DROP COLUMN `kpi_contracts_active_new`,
    DROP COLUMN `kpi_contracts_active_amount`,
    DROP COLUMN `kpi_contracts_active`,
    DROP COLUMN `kpi_customers_active_new`,
    DROP COLUMN `kpi_customers_active`,
    DROP COLUMN `kpi_assets_covered`,
    DROP COLUMN `kpi_assets_active`,
    DROP COLUMN `kpi_assets_total`;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
