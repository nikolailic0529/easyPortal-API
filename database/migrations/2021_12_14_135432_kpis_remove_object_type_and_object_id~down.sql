SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `kpis`
    ADD COLUMN `object_id`   CHAR(36)     NOT NULL AFTER `id`,
    ADD COLUMN `object_type` VARCHAR(255) NOT NULL AFTER `object_id`,
    ADD INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE;
;

UPDATE `kpis`
    JOIN `customers` ON `customers`.`kpi_id` = `kpis`.`id`
SET `kpis`.`object_type` = 'Customer', `kpis`.`object_id` = `customers`.`id`;

UPDATE `kpis`
    JOIN `resellers` ON `resellers`.`kpi_id` = `kpis`.`id`
SET `kpis`.`object_type` = 'Reseller', `kpis`.`object_id` = `resellers`.`id`;

UPDATE `kpis`
    JOIN `reseller_customers` ON `reseller_customers`.`kpi_id` = `kpis`.`id`
SET `kpis`.`object_type` = 'ResellerCustomer', `kpis`.`object_id` = `reseller_customers`.`id`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
