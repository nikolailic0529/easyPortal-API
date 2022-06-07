SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Restore fields
ALTER TABLE `customers`
    ADD COLUMN `kpi_assets_total`            INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `contacts_count`,
    ADD COLUMN `kpi_assets_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_assets_total`,
    ADD COLUMN `kpi_assets_covered`          DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_assets_active`,
    ADD COLUMN `kpi_customers_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_assets_covered`,
    ADD COLUMN `kpi_customers_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_customers_active`,
    ADD COLUMN `kpi_contracts_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_customers_active_new`,
    ADD COLUMN `kpi_contracts_active_amount` DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_contracts_active`,
    ADD COLUMN `kpi_contracts_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_amount`,
    ADD COLUMN `kpi_contracts_expiring`      INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_new`,
    ADD COLUMN `kpi_quotes_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_expiring`,
    ADD COLUMN `kpi_quotes_active_amount`    DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_quotes_active`,
    ADD COLUMN `kpi_quotes_active_new`       INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_amount`,
    ADD COLUMN `kpi_quotes_expiring`         INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_new`;

ALTER TABLE `organizations`
    ADD COLUMN `kpi_assets_total`            INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `timezone`,
    ADD COLUMN `kpi_assets_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_assets_total`,
    ADD COLUMN `kpi_assets_covered`          DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_assets_active`,
    ADD COLUMN `kpi_customers_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_assets_covered`,
    ADD COLUMN `kpi_customers_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_customers_active`,
    ADD COLUMN `kpi_contracts_active`        INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_customers_active_new`,
    ADD COLUMN `kpi_contracts_active_amount` DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_contracts_active`,
    ADD COLUMN `kpi_contracts_active_new`    INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_amount`,
    ADD COLUMN `kpi_contracts_expiring`      INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_new`,
    ADD COLUMN `kpi_quotes_active`           INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_expiring`,
    ADD COLUMN `kpi_quotes_active_amount`    DOUBLE UNSIGNED  NOT NULL DEFAULT 0 AFTER `kpi_quotes_active`,
    ADD COLUMN `kpi_quotes_active_new`       INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_amount`,
    ADD COLUMN `kpi_quotes_expiring`         INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_new`;

-- Move existing data into `customers` and `organizations` tables
UPDATE `customers`
    LEFT JOIN `kpis` ON `kpis`.`object_type` = 'Customer' AND `kpis`.`object_id` = `customers`.`id`
SET `customers`.`kpi_assets_total` = IFNULL(`kpis`.`assets_total`, 0),
    `customers`.`kpi_assets_active` = IFNULL(`kpis`.`assets_active`, 0),
    `customers`.`kpi_assets_covered` = IFNULL(`kpis`.`assets_covered`, 0),
    `customers`.`kpi_customers_active` = IFNULL(`kpis`.`customers_active`, 0),
    `customers`.`kpi_customers_active_new` = IFNULL(`kpis`.`customers_active_new`, 0),
    `customers`.`kpi_contracts_active` = IFNULL(`kpis`.`contracts_active`, 0),
    `customers`.`kpi_contracts_active_amount` = IFNULL(`kpis`.`contracts_active_amount`, 0),
    `customers`.`kpi_contracts_active_new` = IFNULL(`kpis`.`contracts_active_new`, 0),
    `customers`.`kpi_contracts_expiring` = IFNULL(`kpis`.`contracts_expiring`, 0),
    `customers`.`kpi_quotes_active` = IFNULL(`kpis`.`quotes_active`, 0),
    `customers`.`kpi_quotes_active_amount` = IFNULL(`kpis`.`quotes_active_amount`, 0),
    `customers`.`kpi_quotes_active_new` = IFNULL(`kpis`.`quotes_active_new`, 0),
    `customers`.`kpi_quotes_expiring` = IFNULL(`kpis`.`quotes_expiring`, 0);

UPDATE `organizations`
    LEFT JOIN `kpis` ON `kpis`.`object_type` = 'Reseller' AND `kpis`.`object_id` = `organizations`.`id`
SET `organizations`.`kpi_assets_total` = IFNULL(`kpis`.`assets_total`, 0),
    `organizations`.`kpi_assets_active` = IFNULL(`kpis`.`assets_active`, 0),
    `organizations`.`kpi_assets_covered` = IFNULL(`kpis`.`assets_covered`, 0),
    `organizations`.`kpi_customers_active` = IFNULL(`kpis`.`customers_active`, 0),
    `organizations`.`kpi_customers_active_new` = IFNULL(`kpis`.`customers_active_new`, 0),
    `organizations`.`kpi_contracts_active` = IFNULL(`kpis`.`contracts_active`, 0),
    `organizations`.`kpi_contracts_active_amount` = IFNULL(`kpis`.`contracts_active_amount`, 0),
    `organizations`.`kpi_contracts_active_new` = IFNULL(`kpis`.`contracts_active_new`, 0),
    `organizations`.`kpi_contracts_expiring` = IFNULL(`kpis`.`contracts_expiring`, 0),
    `organizations`.`kpi_quotes_active` = IFNULL(`kpis`.`quotes_active`, 0),
    `organizations`.`kpi_quotes_active_amount` = IFNULL(`kpis`.`quotes_active_amount`, 0),
    `organizations`.`kpi_quotes_active_new` = IFNULL(`kpis`.`quotes_active_new`, 0),
    `organizations`.`kpi_quotes_expiring` = IFNULL(`kpis`.`quotes_expiring`, 0);

-- Remove table
DROP TABLE IF EXISTS `kpis`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
