SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `kpis`
    ADD COLUMN `assets_active_on_contract`           INT(11) NOT NULL DEFAULT 0 AFTER `assets_active_percent`,
    ADD COLUMN `assets_active_on_warranty`           INT(11) NOT NULL DEFAULT 0 AFTER `assets_active_on_contract`,
    ADD COLUMN `assets_active_exposed`               INT(11) NOT NULL DEFAULT 0 AFTER `assets_active_on_warranty`,
    ADD COLUMN `contracts_expired`                   INT(11) NOT NULL DEFAULT 0 AFTER `contracts_expiring`,
    ADD COLUMN `quotes_expired`                      INT(11) NOT NULL DEFAULT 0 AFTER `quotes_expiring`,
    ADD COLUMN `quotes_ordered`                      INT(11) NOT NULL DEFAULT 0 AFTER `quotes_expired`,
    ADD COLUMN `quotes_accepted`                     INT(11) NOT NULL DEFAULT 0 AFTER `quotes_ordered`,
    ADD COLUMN `quotes_requested`                    INT(11) NOT NULL DEFAULT 0 AFTER `quotes_accepted`,
    ADD COLUMN `quotes_received`                     INT(11) NOT NULL DEFAULT 0 AFTER `quotes_requested`,
    ADD COLUMN `quotes_rejected`                     INT(11) NOT NULL DEFAULT 0 AFTER `quotes_received`,
    ADD COLUMN `quotes_awaiting`                     INT(11) NOT NULL DEFAULT 0 AFTER `quotes_rejected`,
    ADD COLUMN `service_revenue_total_amount`        DOUBLE  NOT NULL DEFAULT 0 AFTER `quotes_awaiting`,
    ADD COLUMN `service_revenue_total_amount_change` DOUBLE  NOT NULL DEFAULT 0 AFTER `service_revenue_total_amount`,
    CHANGE COLUMN `assets_covered` `assets_active_percent` DOUBLE UNSIGNED NOT NULL DEFAULT 0;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
