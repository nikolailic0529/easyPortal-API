-- MySQL Workbench Synchronization
-- Generated: 2021-03-22 10:24
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `products`
    CHANGE COLUMN `type` `type` ENUM ('asset', 'support', 'service') NOT NULL DEFAULT 'asset';

ALTER TABLE `documents`
    ADD COLUMN `product_id` CHAR(36) NOT NULL COMMENT 'Support Level' AFTER `number`,
    ADD INDEX `fk_documents_products1_idx`(`product_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    CHANGE COLUMN `product_id` `product_id` CHAR(36) NOT NULL COMMENT 'Service';

ALTER TABLE `documents`
    ADD CONSTRAINT `fk_documents_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
