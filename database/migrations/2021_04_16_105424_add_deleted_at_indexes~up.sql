SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `cities`
    DROP INDEX `unique__name__country_id`,
    ADD UNIQUE INDEX `unique__name__country_id__deleted_at`(`name` ASC, `country_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `countries`
    DROP INDEX `unique_code`,
    ADD UNIQUE INDEX `unique__code__deleted_at`(`code` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `assets`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `currencies`
    DROP INDEX `unique_code`,
    ADD UNIQUE INDEX `unique__code__deleted_at`(`code` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `contacts`
    DROP INDEX `idx__object_id__object_type`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `customers`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `oems`
    DROP INDEX `unique__abbr`,
    ADD UNIQUE INDEX `unique__abbr__deleted_at`(`abbr` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `organizations`
    ADD UNIQUE INDEX `unique__subdomain__deleted_at`(`subdomain` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `locations`
    DROP INDEX `idx__object_id__object_type`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `products`
    DROP INDEX `unique__sku__oem_id`,
    ADD UNIQUE INDEX `unique__sku__oem_id__deleted_at`(`sku` ASC, `oem_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `statuses`
    DROP INDEX `unique__object_type__key`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_at`(`object_type` ASC, `key` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `types`
    DROP INDEX `unique__object_type__key`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_at`(`object_type` ASC, `key` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `users`
    DROP INDEX `unique_email`,
    ADD UNIQUE INDEX `unique__email__deleted_at`(`email` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique_sub`,
    ADD UNIQUE INDEX `unique__sub__deleted_at`(`sub` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `documents`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `asset_warranties`
    DROP INDEX `unique__asset_id__document_id`,
    ADD UNIQUE INDEX `unique__asset_id__document_id__deleted_at`(`asset_id` ASC, `document_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `resellers`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `document_entries`
    DROP INDEX `unique__asset_id__document_id__product_id`,
    ADD UNIQUE INDEX `unique__asset_id__document_id__product_id__deleted_at`(`asset_id` ASC, `document_id` ASC, `product_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

ALTER TABLE `contact_types`
    ADD COLUMN `id` CHAR(45) NOT NULL FIRST,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

UPDATE `contact_types`
SET `id` = UUID();

ALTER TABLE `contact_types`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`);

ALTER TABLE `location_types`
    ADD COLUMN `id` CHAR(36) NOT NULL FIRST,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

UPDATE `location_types`
SET `id` = UUID();

ALTER TABLE `location_types`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`);

ALTER TABLE `reseller_customers`
    ADD COLUMN `id` CHAR(36) NOT NULL FIRST,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

UPDATE `reseller_customers`
SET `id` = UUID();

ALTER TABLE `reseller_customers`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`);

ALTER TABLE `asset_warranty_products`
    ADD COLUMN `id` CHAR(36) NOT NULL FIRST,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE;

UPDATE `asset_warranty_products`
SET `id` = UUID();

ALTER TABLE `asset_warranty_products`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`);

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
