SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organizations`
    ADD COLUMN `customers_count` INT(11) NOT NULL DEFAULT 0 AFTER `name`,
    ADD COLUMN `locations_count` INT(11) NOT NULL DEFAULT 0 AFTER `customers_count`,
    ADD COLUMN `assets_count`    INT(11) NOT NULL DEFAULT 0 AFTER `locations_count`;

INSERT INTO `organizations` (id, subdomain, name, customers_count, locations_count, assets_count, created_at, updated_at, deleted_at)
SELECT id, NULL, name, customers_count, locations_count, assets_count, created_at, updated_at, deleted_at
FROM `resellers`;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_resellers1`;

ALTER TABLE `assets`
    CHANGE COLUMN `reseller_id` `organization_id` CHAR(36) NULL DEFAULT NULL COMMENT 'current' AFTER `type_id`,
    RENAME INDEX `fk_assets_resellers1_idx` TO `fk_assets_organizations1_idx`;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

DROP TABLE IF EXISTS `resellers`;

UPDATE `contacts` SET `object_type` = 'organization' WHERE `object_type` = 'reseller';
UPDATE `locations` SET `object_type` = 'organization' WHERE `object_type` = 'reseller';
UPDATE `statuses` SET `object_type` = 'organization' WHERE `object_type` = 'reseller';
UPDATE `types` SET `object_type` = 'organization' WHERE `object_type` = 'reseller';

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
