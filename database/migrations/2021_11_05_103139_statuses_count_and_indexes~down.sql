SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    ADD INDEX `idx__number__deleted_at` (`number` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `idx__number__type_id__deleted_at`,
    DROP INDEX `idx__statuses_count__type_id__deleted_at`,
    DROP INDEX `idx__reseller_id__deleted_at`,
    DROP INDEX `idx__reseller_id__type_id__deleted_at`,
    DROP INDEX `idx__statuses_count__deleted_at`,
    DROP COLUMN `statuses_count`;

ALTER TABLE `customers`
    DROP COLUMN `statuses_count`,
    CHANGE COLUMN `locations_count` `locations_count` INT(11) NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `contacts_count` `contacts_count` INT(11) NOT NULL DEFAULT '0' ,
    DROP INDEX `idx__statuses_count__deleted_at` ;

ALTER TABLE `resellers`
    DROP COLUMN `statuses_count`,
    CHANGE COLUMN `customers_count` `customers_count` INT(11) NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `locations_count` `locations_count` INT(11) NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `assets_count` `assets_count` INT(11) NOT NULL DEFAULT 0 ,
    CHANGE COLUMN `contacts_count` `contacts_count` INT(11) NOT NULL DEFAULT '0' ,
    DROP INDEX `idx__statuses_count__deleted_at` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
