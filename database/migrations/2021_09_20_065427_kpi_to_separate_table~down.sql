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
SET `customers`.`kpi_assets_total` = `kpis`.`assets_total`,
    `customers`.`kpi_assets_active` = `kpis`.`assets_active`,
    `customers`.`kpi_assets_covered` = `kpis`.`assets_covered`,
    `customers`.`kpi_customers_active` = `kpis`.`customers_active`,
    `customers`.`kpi_customers_active_new` = `kpis`.`customers_active_new`,
    `customers`.`kpi_contracts_active` = `kpis`.`contracts_active`,
    `customers`.`kpi_contracts_active_amount` = `kpis`.`contracts_active_amount`,
    `customers`.`kpi_contracts_active_new` = `kpis`.`contracts_active_new`,
    `customers`.`kpi_contracts_expiring` =  `kpis`.`contracts_expiring`,
    `customers`.`kpi_quotes_active` = `kpis`.`quotes_active`,
    `customers`.`kpi_quotes_active_amount` = `kpis`.`quotes_active_amount`,
    `customers`.`kpi_quotes_active_new` = `kpis`.`quotes_active_new`,
    `customers`.`kpi_quotes_expiring` = `kpis`.`quotes_expiring`;

UPDATE `organizations`
    LEFT JOIN `kpis` ON `kpis`.`object_type` = 'Reseller' AND `kpis`.`object_id` = `organizations`.`id`
SET `organizations`.`kpi_assets_total` = `kpis`.`assets_total`,
    `organizations`.`kpi_assets_active` = `kpis`.`assets_active`,
    `organizations`.`kpi_assets_covered` = `kpis`.`assets_covered`,
    `organizations`.`kpi_customers_active` = `kpis`.`customers_active`,
    `organizations`.`kpi_customers_active_new` = `kpis`.`customers_active_new`,
    `organizations`.`kpi_contracts_active` = `kpis`.`contracts_active`,
    `organizations`.`kpi_contracts_active_amount` = `kpis`.`contracts_active_amount`,
    `organizations`.`kpi_contracts_active_new` = `kpis`.`contracts_active_new`,
    `organizations`.`kpi_contracts_expiring` =  `kpis`.`contracts_expiring`,
    `organizations`.`kpi_quotes_active` = `kpis`.`quotes_active`,
    `organizations`.`kpi_quotes_active_amount` = `kpis`.`quotes_active_amount`,
    `organizations`.`kpi_quotes_active_new` = `kpis`.`quotes_active_new`,
    `organizations`.`kpi_quotes_expiring` = `kpis`.`quotes_expiring`;

-- Remove table
DROP TABLE IF EXISTS `kpis`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
