SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    ADD COLUMN `price_origin` DECIMAL(12, 2) NULL DEFAULT NULL AFTER `price`;

UPDATE `documents`
SET `price_origin` = `price`;

ALTER TABLE `document_entries`
    ADD COLUMN `list_price_origin`           DECIMAL(12, 2) NULL DEFAULT NULL AFTER `list_price`,
    ADD COLUMN `monthly_list_price_origin`   DECIMAL(12, 2) NULL DEFAULT NULL AFTER `monthly_list_price`,
    ADD COLUMN `monthly_retail_price_origin` DECIMAL(12, 2) NULL DEFAULT NULL AFTER `monthly_retail_price`,
    ADD COLUMN `renewal_origin`              DECIMAL(12, 2) NULL DEFAULT NULL AFTER `renewal`;

UPDATE `document_entries`
SET `list_price_origin`           = `list_price`,
    `monthly_list_price_origin`   = `monthly_list_price`,
    `monthly_retail_price_origin` = `monthly_retail_price`,
    `renewal_origin`              = `renewal`;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
