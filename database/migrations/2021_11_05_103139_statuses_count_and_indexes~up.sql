SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Documents
ALTER TABLE `documents`
    ADD COLUMN `statuses_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `contacts_count`;

UPDATE `documents`
SET `statuses_count` = (
    SELECT COUNT(*)
    FROM `document_statuses`
    WHERE `document_statuses`.`document_id` = `documents`.`id`
        and `document_statuses`.`deleted_at` IS NULL
);

ALTER TABLE `documents`
    DROP INDEX `idx__number__deleted_at` ,
    ADD INDEX `idx__number__type_id__deleted_at` (`number` ASC, `type_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__statuses_count__type_id__deleted_at` (`statuses_count` ASC, `type_id` ASC, `deleted_at` ASC) INVISIBLE,
    ADD INDEX `idx__reseller_id__deleted_at` (`reseller_id` ASC, `deleted_at` ASC) INVISIBLE,
    ADD INDEX `idx__reseller_id__type_id__deleted_at` (`reseller_id` ASC, `type_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__statuses_count__deleted_at` (`statuses_count` ASC, `deleted_at` ASC) VISIBLE;

-- Customers
ALTER TABLE `customers`
    ADD COLUMN `statuses_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `contacts_count`,
    CHANGE COLUMN `locations_count` `locations_count` INT UNSIGNED NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `contacts_count` `contacts_count` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE `customers`
SET `statuses_count` = (
    SELECT COUNT(*)
    FROM `customer_statuses`
    WHERE `customer_statuses`.`customer_id` = `customers`.`id`
        and `customer_statuses`.`deleted_at` IS NULL
);

ALTER TABLE `customers`
    ADD INDEX `idx__statuses_count__deleted_at` (`statuses_count` ASC, `deleted_at` ASC) VISIBLE;

-- Resellers
ALTER TABLE `resellers`
    ADD COLUMN `statuses_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `contacts_count`,
    CHANGE COLUMN `customers_count` `customers_count` INT UNSIGNED NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `locations_count` `locations_count` INT UNSIGNED NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `assets_count` `assets_count` INT UNSIGNED NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `contacts_count` `contacts_count` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE `resellers`
SET `statuses_count` = (
    SELECT COUNT(*)
    FROM `reseller_statuses`
    WHERE `reseller_statuses`.`reseller_id` = `resellers`.`id`
        and `reseller_statuses`.`deleted_at` IS NULL
);

ALTER TABLE `resellers`
    ADD INDEX `idx__statuses_count__deleted_at` (`statuses_count` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
