SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    ADD COLUMN `asset_type_id`        CHAR(36)       NULL DEFAULT NULL AFTER `asset_id`,
    ADD COLUMN `monthly_list_price`   DECIMAL(12, 2) NULL DEFAULT NULL AFTER `list_price`,
    ADD COLUMN `monthly_retail_price` DECIMAL(12, 2) NULL DEFAULT NULL AFTER `monthly_list_price`,
    ADD COLUMN `oem_said`             VARCHAR(1024)  NULL DEFAULT NULL AFTER `renewal`,
    ADD COLUMN `oem_sar_number`       VARCHAR(1024)  NULL DEFAULT NULL AFTER `oem_said`,
    ADD COLUMN `environment_id`       VARCHAR(255)   NULL DEFAULT NULL AFTER `oem_sar_number`,
    ADD COLUMN `equipment_number`     VARCHAR(255)   NULL DEFAULT NULL AFTER `environment_id`,
    ADD COLUMN `language_id`          CHAR(36)       NULL DEFAULT NULL AFTER `equipment_number`,
    ADD INDEX `fk_document_entries_types1_idx`(`asset_type_id` ASC) VISIBLE,
    ADD INDEX `fk_document_entries_languages1_idx`(`language_id` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_types1`
        FOREIGN KEY (`asset_type_id`)
            REFERENCES `types`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_languages1`
        FOREIGN KEY (`language_id`)
            REFERENCES `languages`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
