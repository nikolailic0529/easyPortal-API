SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    ADD COLUMN `kpi_assets_total`            INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `timezone`,
    ADD COLUMN `kpi_assets_active`           INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_assets_total`,
    ADD COLUMN `kpi_assets_covered`          DOUBLE UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_assets_active`,
    ADD COLUMN `kpi_customers_active`        INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_assets_covered`,
    ADD COLUMN `kpi_customers_active_new`    INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_customers_active`,
    ADD COLUMN `kpi_contracts_active`        INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_customers_active_new`,
    ADD COLUMN `kpi_contracts_active_amount` DOUBLE UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_contracts_active`,
    ADD COLUMN `kpi_contracts_active_new`    INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_amount`,
    ADD COLUMN `kpi_contracts_expiring`      INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_contracts_active_new`,
    ADD COLUMN `kpi_quotes_active`           INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_contracts_expiring`,
    ADD COLUMN `kpi_quotes_active_amount`    DOUBLE UNSIGNED NOT NULL DEFAULT 0 AFTER `kpi_quotes_active`,
    ADD COLUMN `kpi_quotes_active_new`       INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_amount`,
    ADD COLUMN `kpi_quotes_expiring`         INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `kpi_quotes_active_new`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
