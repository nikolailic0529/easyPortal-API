SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_products1`;

ALTER TABLE `document_entries`
    DROP FOREIGN KEY `fk_document_entries_products2`;

ALTER TABLE `assets`
    CHANGE COLUMN `product_id` `product_id` CHAR(36) NOT NULL;

ALTER TABLE `document_entries`
    CHANGE COLUMN `product_id` `product_id` CHAR(36) NOT NULL;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_products2`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
