SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    DROP FOREIGN KEY `fk_documents_service_groups1`;

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_service_groups1`;

ALTER TABLE `document_entries`
    DROP FOREIGN KEY `fk_document_entries_service_levels1`;

ALTER TABLE `asset_warranty_services`
    DROP FOREIGN KEY `fk_asset_warranty_services_service_levels1`;

ALTER TABLE `products`
    ADD COLUMN `type` ENUM('asset', 'support', 'service') NOT NULL DEFAULT 'asset' AFTER `oem_id`,
    ADD UNIQUE INDEX `unique__sku__oem_id__type__deleted_not` (`sku` ASC, `oem_id` ASC, `type` ASC, `deleted_not` ASC) VISIBLE,
    DROP INDEX `unique__sku__oem_id__deleted_not` ;
;

ALTER TABLE `documents`
    DROP COLUMN `service_group_id`,
    ADD COLUMN `support_id` CHAR(36) NULL DEFAULT NULL AFTER `number`,
    ADD INDEX `fk_documents_products1_idx` (`support_id` ASC) VISIBLE,
    DROP INDEX `fk_documents_service_groups1_idx` ;
;

ALTER TABLE `asset_warranties`
    DROP COLUMN `service_group_id`,
    ADD COLUMN `support_id` CHAR(36) NULL DEFAULT NULL AFTER `document_number`,
    ADD INDEX `fk_asset_warranties_products1_idx` (`support_id` ASC) VISIBLE,
    DROP INDEX `fk_asset_warranties_service_groups1_idx` ;
;

ALTER TABLE `document_entries`
    DROP COLUMN `service_level_id`,
    ADD COLUMN `service_id` CHAR(36) NULL DEFAULT NULL AFTER `asset_id`,
    ADD INDEX `fk_document_entries_products1_idx` (`service_id` ASC) VISIBLE,
    DROP INDEX `fk_document_entries_service_levels1_idx` ;
;

ALTER TABLE `asset_warranty_services`
    DROP COLUMN `service_level_id`,
    ADD COLUMN `service_id` CHAR(36) NOT NULL AFTER `asset_warranty_id`,
    DROP INDEX `unique__asset_warranty_id__service_id__deleted_not` ,
    ADD UNIQUE INDEX `unique__asset_warranty_id__service_id__deleted_not` (`asset_warranty_id` ASC, `service_id` ASC, `deleted_not` ASC) VISIBLE,
    ADD INDEX `fk_asset_warranties_services_products1_idx` (`service_id` ASC) VISIBLE,
    DROP INDEX `fk_asset_warranty_services_service_levels1_idx` ;
;

DROP TABLE IF EXISTS `service_levels` ;

DROP TABLE IF EXISTS `service_groups` ;

ALTER TABLE `documents`
    ADD CONSTRAINT `fk_documents_products1`
        FOREIGN KEY (`support_id`)
            REFERENCES `products` (`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_products1`
        FOREIGN KEY (`support_id`)
            REFERENCES `products` (`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_products1`
        FOREIGN KEY (`service_id`)
            REFERENCES `products` (`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranty_services`
    ADD CONSTRAINT `fk_asset_warranties_services_products1`
        FOREIGN KEY (`service_id`)
            REFERENCES `products` (`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
