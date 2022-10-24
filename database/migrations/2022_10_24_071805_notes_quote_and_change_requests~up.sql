SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `notes`
    ADD COLUMN `change_request_id` CHAR(36) NULL DEFAULT NULL AFTER `document_id`,
    ADD COLUMN `quote_request_id`  CHAR(36) NULL DEFAULT NULL AFTER `change_request_id`,
    ADD INDEX `fk_notes_change_requests1_idx`(`change_request_id` ASC) VISIBLE,
    ADD INDEX `fk_notes_quote_requests1_idx`(`quote_request_id` ASC) VISIBLE;


ALTER TABLE `notes`
    ADD CONSTRAINT `fk_notes_change_requests1`
        FOREIGN KEY (`change_request_id`)
            REFERENCES `change_requests`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_notes_quote_requests1`
        FOREIGN KEY (`quote_request_id`)
            REFERENCES `quote_requests`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
