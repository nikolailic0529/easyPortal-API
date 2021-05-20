SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    DROP FOREIGN KEY `fk_documents_resellers1`;

ALTER TABLE `documents`
    CHANGE COLUMN `reseller_id` `reseller_id` CHAR(36) NOT NULL;

ALTER TABLE `documents`
    ADD CONSTRAINT `fk_documents_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_resellers1`;

ALTER TABLE `asset_warranties`
    CHANGE COLUMN `reseller_id` `reseller_id` CHAR(36) NOT NULL;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
