SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `customers`
    ADD COLUMN `kpi_id` CHAR(36) NULL DEFAULT NULL AFTER `name`,
    ADD INDEX `fk_customers_kpis1_idx`(`kpi_id` ASC) VISIBLE;
;

UPDATE `customers`
SET `kpi_id` = (
    SELECT `id` FROM `kpis` WHERE `kpis`.`object_type` = 'Customer' AND `kpis`.`object_id` = `customers`.`id`
);

ALTER TABLE `resellers`
    ADD COLUMN `kpi_id` CHAR(36) NULL DEFAULT NULL AFTER `name`,
    ADD INDEX `fk_resellers_kpis1_idx`(`kpi_id` ASC) VISIBLE;
;

UPDATE `resellers`
SET `kpi_id` = (
    SELECT `id` FROM `kpis` WHERE `kpis`.`object_type` = 'Reseller' AND `kpis`.`object_id` = `resellers`.`id`
);

ALTER TABLE `reseller_customers`
    ADD COLUMN `kpi_id` CHAR(36) NULL DEFAULT NULL AFTER `customer_id`,
    ADD INDEX `fk_reseller_customers_kpis1_idx`(`kpi_id` ASC) VISIBLE;
;

ALTER TABLE `customers`
    ADD CONSTRAINT `fk_customers_kpis1`
        FOREIGN KEY (`kpi_id`)
            REFERENCES `kpis`(`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION;

ALTER TABLE `resellers`
    ADD CONSTRAINT `fk_resellers_kpis1`
        FOREIGN KEY (`kpi_id`)
            REFERENCES `kpis`(`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION;

ALTER TABLE `reseller_customers`
    ADD CONSTRAINT `fk_reseller_customers_kpis1`
        FOREIGN KEY (`kpi_id`)
            REFERENCES `kpis`(`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
