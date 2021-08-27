SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

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


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
