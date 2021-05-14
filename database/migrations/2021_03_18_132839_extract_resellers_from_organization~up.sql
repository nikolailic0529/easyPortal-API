SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `resellers` (
    `id`              CHAR(36)     NOT NULL,
    `name`            VARCHAR(255) NOT NULL,
    `customers_count` INT(11)      NOT NULL DEFAULT 0,
    `locations_count` INT(11)      NOT NULL DEFAULT 0,
    `assets_count`    INT(11)      NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP    NULL     DEFAULT NULL,
    PRIMARY KEY (`id`)
);

INSERT INTO `resellers` (id, name, customers_count, locations_count, assets_count, created_at, updated_at, deleted_at)
SELECT id, name, customers_count, locations_count, assets_count, created_at, updated_at, deleted_at
FROM `organizations`
WHERE `organizations`.`subdomain` IS NULL;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_organizations1`;

ALTER TABLE `assets`
    CHANGE COLUMN `organization_id` `reseller_id` CHAR(36) NULL DEFAULT NULL COMMENT 'current' AFTER `type_id`,
    RENAME INDEX `fk_assets_organizations1_idx` TO `fk_assets_resellers1_idx`;

ALTER TABLE `organizations`
    DROP COLUMN `assets_count`,
    DROP COLUMN `locations_count`,
    DROP COLUMN `customers_count`;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_resellers1`
        FOREIGN KEY (`reseller_id`)
            REFERENCES `resellers`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

DELETE
FROM `organizations`
WHERE `subdomain` IS NULL;

UPDATE `contacts` SET `object_type` = 'reseller' WHERE `object_type` = 'organization';
UPDATE `locations` SET `object_type` = 'reseller' WHERE `object_type` = 'organization';
UPDATE `statuses` SET `object_type` = 'reseller' WHERE `object_type` = 'organization';
UPDATE `types` SET `object_type` = 'reseller' WHERE `object_type` = 'organization';

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
