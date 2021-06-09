SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `cities`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__name__country_id__deleted_at`,
    ADD UNIQUE INDEX `unique__name__country_id__deleted_not`(`name` ASC, `country_id` ASC, `deleted_not` ASC) INVISIBLE;

ALTER TABLE `countries`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__code__deleted_at`,
    ADD UNIQUE INDEX `unique__code__deleted_not`(`code` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `currencies`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__code__deleted_at`,
    ADD UNIQUE INDEX `unique__code__deleted_not`(`code` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `contacts`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__name__phone__email__object_id__object_type__deleted_at`,
    ADD UNIQUE INDEX `unique__name__phone__email__object_id__object_type__deleted_not`(`name` ASC, `phone_number` ASC, `email` ASC, `object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE,
    DROP INDEX `unique__email__object_id__object_type__deleted_at`;

ALTER TABLE `oems`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__abbr__deleted_at`,
    ADD UNIQUE INDEX `unique__abbr__deleted_not`(`abbr` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `languages`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__code__deleted_at`,
    ADD UNIQUE INDEX `unique__code__deleted_not`(`code` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `organizations`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__keycloak_group_id__deleted_at`,
    ADD UNIQUE INDEX `unique__keycloak_group_id__deleted_not`(`keycloak_group_id` ASC, `deleted_not` ASC) VISIBLE,
    DROP INDEX `unique__keycloak_scope__deleted_at`,
    ADD UNIQUE INDEX `unique__keycloak_scope__deleted_not`(`keycloak_scope` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `products`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__sku__oem_id__deleted_at`,
    ADD UNIQUE INDEX `unique__sku__oem_id__type__deleted_not`(`sku` ASC, `oem_id` ASC, `type` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `tags`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__name__deleted_at`,
    ADD UNIQUE INDEX `unique__name__deleted_not`(`name` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `statuses`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__object_type__key__deleted_at`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_not`(`object_type` ASC, `key` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `types`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__object_type__key__deleted_at`,
    ADD UNIQUE INDEX `unique__object_type__key__deleted_not`(`object_type` ASC, `key` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `users`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__email__deleted_at`,
    ADD UNIQUE INDEX `unique__email__deleted_not`(`email` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `asset_warranties`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__asset_id__document_id__deleted_at`,
    ADD UNIQUE INDEX `unique__asset_id__document_id__deleted_not`(`asset_id` ASC, `document_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `reseller_customers`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    ADD UNIQUE INDEX `unique__reseller_id__customer_id__deleted_not`(`reseller_id` ASC, `customer_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `asset_coverages`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (If((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `unique__key__deleted_at`,
    ADD UNIQUE INDEX `unique__key__deleted_not`(`key` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `contact_types`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL AFTER `deleted_at`,
    ADD UNIQUE INDEX `unique__contact_id__type_id__deleted_not`(`contact_id` ASC, `type_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `location_types`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    ADD UNIQUE INDEX `unique__location_id__type_id__deleted_not`(`location_id` ASC, `type_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `asset_warranty_services`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    ADD UNIQUE INDEX `unique__asset_warranty_id__service_id__deleted_not`(`asset_warranty_id` ASC, `service_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `asset_tags`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    ADD UNIQUE INDEX `unique__tag_id__asset_id__deleted_not`(`tag_id` ASC, `asset_id` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `contacts`
    DROP INDEX `idx__object_id__object_type__deleted_at`,
    ADD INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE;

ALTER TABLE `locations`
    ADD COLUMN `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED AFTER `deleted_at`,
    DROP INDEX `idx__object_id__object_type__deleted_at`,
    ADD INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
