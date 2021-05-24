SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    ADD COLUMN `product_id`    CHAR(36)     NULL DEFAULT NULL AFTER `service_id`,
    ADD COLUMN `serial_number` VARCHAR(255) NULL DEFAULT NULL AFTER `product_id`;

UPDATE `document_entries`
LEFT JOIN `assets` ON `assets`.`id` = `document_entries`.`asset_id`
SET
    `document_entries`.`product_id` = `assets`.`product_id`,
    `document_entries`.`serial_number` = `assets`.`serial_number`;

ALTER TABLE `document_entries`
    CHANGE COLUMN `product_id` `product_id` CHAR(36) NOT NULL,
    ADD INDEX `fk_document_entries_products2_idx`(`product_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_products2`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
