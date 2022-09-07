SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    DROP FOREIGN KEY `fk_document_entries_types1`,
    DROP FOREIGN KEY `fk_document_entries_languages1`;

ALTER TABLE `document_entries`
    DROP INDEX `fk_document_entries_types1_idx`,
    DROP INDEX `fk_document_entries_languages1_idx`,
    DROP COLUMN `asset_type_id`,
    DROP COLUMN `monthly_list_price`,
    DROP COLUMN `monthly_retail_price`,
    DROP COLUMN `oem_said`,
    DROP COLUMN `oem_sar_number`,
    DROP COLUMN `environment_id`,
    DROP COLUMN `equipment_number`,
    DROP COLUMN `language_id`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
