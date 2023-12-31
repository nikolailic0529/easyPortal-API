SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    ADD COLUMN `language_id` CHAR(36) NULL DEFAULT NULL AFTER `currency_id`,
    ADD INDEX `fk_document_languages1_idx`(`language_id` ASC) VISIBLE,
    ADD CONSTRAINT `fk_document_languages1`
        FOREIGN KEY (`language_id`)
            REFERENCES `languages`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
