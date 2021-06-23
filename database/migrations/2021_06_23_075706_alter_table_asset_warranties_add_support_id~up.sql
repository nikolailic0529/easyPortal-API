SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Update schema

ALTER TABLE `asset_warranties`
    ADD COLUMN `support_id` CHAR(36) NULL DEFAULT NULL AFTER `document_id`,
    ADD INDEX `fk_asset_warranties_products1_idx`(`support_id` ASC) VISIBLE;

ALTER TABLE `asset_warranties`
    ADD COLUMN `document_number` VARCHAR(64) NULL DEFAULT NULL AFTER `document_id`,
    DROP INDEX `unique__asset_id__document_id__deleted_not`;

ALTER TABLE `asset_warranties`
    CHANGE COLUMN `end` `end` DATE NULL DEFAULT NULL;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_products1`
        FOREIGN KEY (`support_id`)
            REFERENCES `products`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

-- Update data

UPDATE `asset_warranties` aw
    LEFT JOIN `documents` d ON d.`id` = aw.`document_id`
    LEFT JOIN `products` p ON p.`id` = d.`support_id`
SET aw.`support_id` = p.`id`, aw.`document_number` = d.`number`;

-- Add unique index

ALTER TABLE `asset_warranties`
    ADD UNIQUE INDEX `unique__asset_id__document_id__document_number__deleted_not`(`asset_id` ASC, `document_id` ASC, `document_number` ASC, `deleted_not` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
