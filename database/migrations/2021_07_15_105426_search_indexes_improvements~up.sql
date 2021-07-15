SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `idx__serial_number`,
    ADD INDEX `idx__serial_number__deleted_at`(`serial_number` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `contacts`
    DROP INDEX `idx__object_id__object_type__deleted_not`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `customers`
    DROP INDEX `idx__name`,
    ADD INDEX `idx__name__deleted_at`(`name` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `organizations`
    DROP INDEX `idx__name`,
    ADD INDEX `idx__name__deleted_at`(`name` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `locations`
    DROP INDEX `idx__postcode`,
    ADD INDEX `idx__postcode__deleted_at`(`postcode` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `idx__object_id__object_type__deleted_not`,
    ADD INDEX `idx__object_id__object_type__deleted_at`(`object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `idx__latitude__longitude__deleted_not`,
    ADD INDEX `idx__latitude__longitude__deleted_at`(`latitude` ASC, `longitude` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `products`
    DROP INDEX `idx__sku`,
    ADD INDEX `idx__sku__name__deleted_at`(`sku` ASC, `name` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `idx__name`,
    ADD INDEX `idx__name__deleted_at`(`name` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `documents`
    DROP INDEX `idx__number`,
    ADD INDEX `idx__number__deleted_at`(`number` ASC, `deleted_at` ASC) VISIBLE;
;

ALTER TABLE `resellers`
    DROP INDEX `idx__name`,
    ADD INDEX `idx__name__deleted_at`(`name` ASC, `deleted_at` ASC) VISIBLE;
;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
