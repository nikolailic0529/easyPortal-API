SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

UPDATE `kpis`
SET `assets_active_on_contract` = 0
WHERE `assets_active_on_contract` < 0;

UPDATE `kpis`
SET `assets_active_on_warranty` = 0
WHERE `assets_active_on_warranty` < 0;

UPDATE `kpis`
SET `assets_active_exposed` = 0
WHERE `assets_active_exposed` < 0;

UPDATE `kpis`
SET `contracts_expired` = 0
WHERE `contracts_expired` < 0;

UPDATE `kpis`
SET `quotes_expired` = 0
WHERE `quotes_expired` < 0;

UPDATE `kpis`
SET `quotes_ordered` = 0
WHERE `quotes_ordered` < 0;

UPDATE `kpis`
SET `quotes_accepted` = 0
WHERE `quotes_accepted` < 0;

UPDATE `kpis`
SET `quotes_requested` = 0
WHERE `quotes_requested` < 0;

UPDATE `kpis`
SET `quotes_received` = 0
WHERE `quotes_received` < 0;

UPDATE `kpis`
SET `quotes_rejected` = 0
WHERE `quotes_rejected` < 0;

UPDATE `kpis`
SET `quotes_awaiting` = 0
WHERE `quotes_awaiting` < 0;

UPDATE `kpis`
SET `service_revenue_total_amount` = 0
WHERE `service_revenue_total_amount` < 0;

ALTER TABLE `kpis`
    CHANGE COLUMN `assets_active_on_contract` `assets_active_on_contract` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `assets_active_on_warranty` `assets_active_on_warranty` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `assets_active_exposed` `assets_active_exposed` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `contracts_expired` `contracts_expired` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_expired` `quotes_expired` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_ordered` `quotes_ordered` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_accepted` `quotes_accepted` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_requested` `quotes_requested` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_received` `quotes_received` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_rejected` `quotes_rejected` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `quotes_awaiting` `quotes_awaiting` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    CHANGE COLUMN `service_revenue_total_amount` `service_revenue_total_amount` DOUBLE UNSIGNED NOT NULL DEFAULT 0;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
