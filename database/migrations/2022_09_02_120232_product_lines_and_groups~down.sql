SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    DROP FOREIGN KEY `fk_document_entries_product_groups1`,
    DROP FOREIGN KEY `fk_document_entries_product_lines1`;

ALTER TABLE `document_entries`
    DROP INDEX `fk_document_entries_product_groups1_idx`,
    DROP INDEX `fk_document_entries_product_lines1_idx`,
    DROP COLUMN `product_line_id`,
    DROP COLUMN `product_group_id`;


DROP TABLE IF EXISTS `product_lines`;

DROP TABLE IF EXISTS `product_groups`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
