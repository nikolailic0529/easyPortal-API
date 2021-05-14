SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `cities`
    ADD UNIQUE INDEX `unique__name__country_id`(`name` ASC, `country_id` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__name__country_id__deleted_at`;

ALTER TABLE `countries`
    ADD UNIQUE INDEX `unique_code`(`code` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__code__deleted_at`;

ALTER TABLE `assets`
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `contact_types`
    DROP COLUMN `id`,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`contact_id`, `type_id`),
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `currencies`
    ADD UNIQUE INDEX `unique_code`(`code` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__code__deleted_at`;

ALTER TABLE `contacts`
    ADD INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `idx__object_id__object_type__deleted_at`;

ALTER TABLE `customers`
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `oems`
    ADD UNIQUE INDEX `unique__abbr`(`abbr` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__abbr__deleted_at`;

ALTER TABLE `location_types`
    DROP COLUMN `id`,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`location_id`, `type_id`),
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `organizations`
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__subdomain__deleted_at`;

ALTER TABLE `locations`
    ADD INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `idx__object_id__object_type__deleted_at`;

ALTER TABLE `products`
    ADD UNIQUE INDEX `unique__sku__oem_id`(`sku` ASC, `oem_id` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__sku__oem_id__deleted_at`;

ALTER TABLE `statuses`
    ADD UNIQUE INDEX `unique__object_type__key`(`object_type` ASC, `key` ASC) INVISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__object_type__key__deleted_at`;

ALTER TABLE `types`
    ADD UNIQUE INDEX `unique__object_type__key`(`object_type` ASC, `key` ASC) INVISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__object_type__key__deleted_at`;

ALTER TABLE `users`
    ADD UNIQUE INDEX `unique_email`(`email` ASC) VISIBLE,
    ADD UNIQUE INDEX `unique_sub`(`sub` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__sub__deleted_at`,
    DROP INDEX `unique__email__deleted_at`;

ALTER TABLE `documents`
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `asset_warranties`
    ADD UNIQUE INDEX `unique__asset_id__document_id`(`asset_id` ASC, `document_id` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__asset_id__document_id__deleted_at`;

ALTER TABLE `resellers`
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `document_entries`
    ADD UNIQUE INDEX `unique__asset_id__document_id__product_id`(`asset_id` ASC, `document_id` ASC, `product_id` ASC) VISIBLE,
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `unique__asset_id__document_id__product_id__deleted_at`;

ALTER TABLE `reseller_customers`
    DROP COLUMN `id`,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`reseller_id`, `customer_id`),
    DROP INDEX `idx__deleted_at`;

ALTER TABLE `asset_warranty_products`
    DROP COLUMN `id`,
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`asset_warranty_id`, `product_id`),
    DROP INDEX `idx__deleted_at`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
