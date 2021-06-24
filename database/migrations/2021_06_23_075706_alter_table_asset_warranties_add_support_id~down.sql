SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_products1`;

ALTER TABLE `asset_warranties`
    DROP INDEX `fk_asset_warranties_products1_idx`,
    DROP COLUMN `support_id`;

ALTER TABLE `asset_warranties`
    CHANGE COLUMN `end` `end` DATE NOT NULL;

ALTER TABLE `asset_warranties`
    DROP INDEX `unique__asset_id__document_id__document_number__deleted_not`,
    DROP COLUMN `document_number`,
    ADD UNIQUE INDEX `unique__asset_id__document_id__deleted_not`(`asset_id` ASC, `document_id` ASC, `deleted_not` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
