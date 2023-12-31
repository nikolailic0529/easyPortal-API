SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `customers`
    DROP FOREIGN KEY `fk_customers_kpis1`,
    DROP INDEX `fk_customers_kpis1_idx`,
    DROP COLUMN `kpi_id`;
;

ALTER TABLE `resellers`
    DROP FOREIGN KEY `fk_resellers_kpis1`,
    DROP INDEX `fk_resellers_kpis1_idx`,
    DROP COLUMN `kpi_id`;
;

ALTER TABLE `reseller_customers`
    DROP FOREIGN KEY `fk_reseller_customers_kpis1`,
    DROP INDEX `fk_reseller_customers_kpis1_idx`,
    DROP COLUMN `kpi_id`;
;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
