SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `cities`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__name__country_id__deleted_at`(`name` ASC, `country_id` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__name__country_id__deleted_not`;

ALTER TABLE `countries`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__code__deleted_at`(`code` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__code__deleted_not`;

ALTER TABLE `currencies`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__code__deleted_at`(`code` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__code__deleted_not`;

ALTER TABLE `contacts`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__name__phone__email__object_id__object_type__deleted_at`(`name` ASC, `phone_number` ASC, `email` ASC, `object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `unique__email__object_id__object_type__deleted_at`(`name` ASC, `object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__name__phone__email__object_id__object_type__deleted_not`;

ALTER TABLE `oems`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__abbr__deleted_at`(`abbr` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__abbr__deleted_not`;

ALTER TABLE `languages`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__code__deleted_at`(`code` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__code__deleted_not`;

ALTER TABLE `organizations`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__keycloak_group_id__deleted_at`(`keycloak_group_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD UNIQUE INDEX `unique__keycloak_scope__deleted_at`(`keycloak_scope` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__keycloak_scope__deleted_not`,
    DROP INDEX `unique__keycloak_group_id__deleted_not`;

ALTER TABLE `products`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__sku__oem_id__deleted_at`(`sku` ASC, `oem_id` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__sku__oem_id__type__deleted_not`;

ALTER TABLE `tags`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__name__deleted_at`(`name` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__name__deleted_not`;

ALTER TABLE `statuses`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_at`(`object_type` ASC, `key` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__object_type__key__deleted_not`;

ALTER TABLE `types`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_at`(`object_type` ASC, `key` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__object_type__key__deleted_not`;

ALTER TABLE `users`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__email__deleted_at`(`email` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__email__deleted_not`;

ALTER TABLE `asset_warranties`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__asset_id__document_id__deleted_at`(`asset_id` ASC, `document_id` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__asset_id__document_id__deleted_not`;

ALTER TABLE `reseller_customers`
    DROP COLUMN `deleted_not`,
    DROP INDEX `unique__reseller_id__customer_id__deleted_not`;

ALTER TABLE `asset_coverages`
    DROP COLUMN `deleted_not`,
    ADD UNIQUE INDEX `unique__key__deleted_at`(`key` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `unique__key__deleted_not`;

ALTER TABLE `contact_types`
    DROP INDEX `unique__contact_id__type_id__deleted_not`,
    DROP COLUMN `deleted_not`;

ALTER TABLE `location_types`
    DROP INDEX `unique__location_id__type_id__deleted_not`,
    DROP COLUMN `deleted_not`;

ALTER TABLE `asset_warranty_services`
    DROP INDEX `unique__asset_warranty_id__service_id__deleted_not`,
    DROP COLUMN `deleted_not`;

ALTER TABLE `asset_tags`
    DROP INDEX `unique__tag_id__asset_id__deleted_not`,
    DROP COLUMN `deleted_not`;

ALTER TABLE `contacts`
    DROP INDEX `idx__object_id__object_type__deleted_not`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `locations`
    DROP INDEX `idx__object_id__object_type__deleted_not`,
    DROP COLUMN `deleted_not`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
