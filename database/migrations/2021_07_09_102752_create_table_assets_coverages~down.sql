SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

DROP TABLE IF EXISTS `asset_coverages`;

ALTER TABLE `assets`
    ADD COLUMN `coverage_id` CHAR(36) NULL DEFAULT NULL AFTER `status_id`,
    ADD INDEX `fk_assets_coverages1_idx`(`coverage_id` ASC) VISIBLE,
    ADD CONSTRAINT `fk_assets_coverages1`
        FOREIGN KEY (`coverage_id`)
            REFERENCES `coverages`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;