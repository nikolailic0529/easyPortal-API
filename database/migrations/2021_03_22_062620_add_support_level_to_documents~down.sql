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
    CHANGE COLUMN `type` `type` ENUM ('asset', 'service') NOT NULL DEFAULT 'asset';

ALTER TABLE `documents`
    DROP FOREIGN KEY `fk_documents_products1`;

ALTER TABLE `documents`
    DROP INDEX `fk_documents_products1_idx`,
    DROP COLUMN `product_id`;

ALTER TABLE `document_entries`
    CHANGE COLUMN `product_id` `product_id` CHAR(36) NOT NULL COMMENT '';


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
