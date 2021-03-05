-- MySQL Workbench Synchronization
-- Generated: 2021-03-05 15:13
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_types1`;

ALTER TABLE `assets`
    DROP COLUMN `type_id`,
    DROP INDEX `fk_assets_types1_idx` ;

ALTER TABLE `products`
    ADD COLUMN `type_id` CHAR(36) NOT NULL AFTER `oem_id`,
    ADD INDEX `fk_products_types1_idx` (`type_id` ASC) VISIBLE;

ALTER TABLE `products`
    ADD CONSTRAINT `fk_products_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types` (`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
