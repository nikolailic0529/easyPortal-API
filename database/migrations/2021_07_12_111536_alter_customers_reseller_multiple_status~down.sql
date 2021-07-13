SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `customers`
    ADD COLUMN `status_id` CHAR(36) NOT NULL AFTER `type_id`,
    ADD INDEX `fk_customers_statuses1_idx`(`status_id` ASC) VISIBLE;

ALTER TABLE `resellers`
    ADD COLUMN `status_id` CHAR(36) NULL DEFAULT NULL AFTER `type_id`,
    ADD INDEX `fk_resellers_statuses1_idx`(`status_id` ASC) VISIBLE;

DROP TABLE IF EXISTS `reseller_statuses`;

DROP TABLE IF EXISTS `customer_statuses`;

ALTER TABLE `customers`
    ADD CONSTRAINT `fk_customers_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `resellers`
    ADD CONSTRAINT `fk_resellers_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
