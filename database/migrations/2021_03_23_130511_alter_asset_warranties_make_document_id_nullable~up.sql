SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_documents1`;

ALTER TABLE `asset_warranties`
    CHANGE COLUMN `document_id` `document_id` CHAR(36) NULL DEFAULT NULL;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranties`
    ADD UNIQUE INDEX `unique__asset_id__document_id`(`asset_id` ASC, `document_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD UNIQUE INDEX `unique__asset_id__document_id__product_id`(`asset_id` ASC, `document_id` ASC, `product_id` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
