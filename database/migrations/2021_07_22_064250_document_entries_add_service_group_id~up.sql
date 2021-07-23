SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    ADD COLUMN `service_group_id` CHAR(36) NULL DEFAULT NULL AFTER `asset_id`,
    ADD INDEX `fk_document_entries_service_groups1_idx`(`service_group_id` ASC) VISIBLE;
;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_service_groups1`
        FOREIGN KEY (`service_group_id`)
            REFERENCES `service_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;