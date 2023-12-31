SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


ALTER TABLE `files`
    DROP INDEX `idx__object_id__object_type__deleted_at`,
    DROP COLUMN `object_type`,
    CHANGE COLUMN `object_id` `note_id` CHAR(36) NOT NULL AFTER `id`;

ALTER TABLE `files`
    ADD INDEX `fk_files_notes1_idx`(`note_id` ASC) VISIBLE,
    ADD CONSTRAINT `fk_files_notes1`
        FOREIGN KEY (`note_id`)
            REFERENCES `notes`(`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
