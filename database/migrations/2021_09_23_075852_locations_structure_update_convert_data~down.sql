SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Restore Customers Locations
INSERT INTO `locations_outdated` (
    `id`,
    `object_type`,
    `object_id`,
    `country_id`,
    `city_id`,
    `postcode`,
    `state`,
    `line_one`,
    `line_two`,
    `latitude`,
    `longitude`
)
SELECT
   `customer_locations`.`id`,
   'Customer',
   `customer_locations`.`customer_id`,
   `locations`.`country_id`,
   `locations`.`city_id`,
   `locations`.`postcode`,
   `locations`.`state`,
   `locations`.`line_one`,
   `locations`.`line_two`,
   `locations`.`latitude`,
   `locations`.`longitude`
FROM `locations`
LEFT JOIN `customer_locations` ON `customer_locations`.`location_id` = `locations`.`id`
WHERE `customer_locations`.`id` IS NOT NULL;

INSERT INTO `location_types_outdated` (
   `id`,
   `type_id`,
   `location_id`
)
SELECT
    UUID(),
    `customer_location_types`.`type_id`,
    `locations_outdated`.`id`
FROM `customer_location_types`
LEFT JOIN `customer_locations` ON `customer_locations`.`id` = `customer_location_types`.`customer_location_id`
LEFT JOIN `locations` ON `locations`.`id` = `customer_locations`.`location_id`
LEFT JOIN `locations_outdated`
    ON `locations_outdated`.`hash` = `locations`.`hash`
        AND `locations_outdated`.`object_type` = 'Customer'
        AND `locations_outdated`.`object_id` = `customer_locations`.`customer_id`;

-- Restore Resellers Locations
INSERT INTO `locations_outdated` (
    `id`,
    `object_type`,
    `object_id`,
    `country_id`,
    `city_id`,
    `postcode`,
    `state`,
    `line_one`,
    `line_two`,
    `latitude`,
    `longitude`
)
SELECT
    `reseller_locations`.`id`,
    'Reseller',
    `reseller_locations`.`reseller_id`,
    `locations`.`country_id`,
    `locations`.`city_id`,
    `locations`.`postcode`,
    `locations`.`state`,
    `locations`.`line_one`,
    `locations`.`line_two`,
    `locations`.`latitude`,
    `locations`.`longitude`
FROM `locations`
LEFT JOIN `reseller_locations` ON `reseller_locations`.`location_id` = `locations`.`id`
WHERE `reseller_locations`.`id` IS NOT NULL;

INSERT INTO `location_types_outdated` (
    `id`,
    `type_id`,
    `location_id`
)
SELECT
    UUID(),
    `reseller_location_types`.`type_id`,
    `locations_outdated`.`id`
FROM `reseller_location_types`
LEFT JOIN `reseller_locations` ON `reseller_locations`.`id` = `reseller_location_types`.`reseller_location_id`
LEFT JOIN `locations` ON `locations`.`id` = `reseller_locations`.`location_id`
LEFT JOIN `locations_outdated`
    ON `locations_outdated`.`hash` = `locations`.`hash`
        AND `locations_outdated`.`object_type` = 'Reseller'
        AND `locations_outdated`.`object_id` = `reseller_locations`.`reseller_id`;

-- Assets locations
INSERT INTO `locations_outdated` (
    `id`,
    `object_type`,
    `object_id`,
    `country_id`,
    `city_id`,
    `postcode`,
    `state`,
    `line_one`,
    `line_two`,
    `latitude`,
    `longitude`
)
SELECT DISTINCT
    `locations`.`id`,
    'Asset',
    NULL,
    `locations`.`country_id`,
    `locations`.`city_id`,
    `locations`.`postcode`,
    `locations`.`state`,
    `locations`.`line_one`,
    `locations`.`line_two`,
    `locations`.`latitude`,
    `locations`.`longitude`
FROM `assets`
LEFT JOIN `locations` ON `locations`.`id` = `assets`.`location_id`
LEFT JOIN `reseller_locations` ON `reseller_locations`.`location_id` = `assets`.`location_id`
LEFT JOIN `customer_locations` ON `customer_locations`.`location_id` = `assets`.`location_id`
WHERE 1=1
    AND `assets`.`location_id` IS NOT NULL
    AND `reseller_locations`.`id` IS NULL
    AND `customer_locations`.`id` IS NULL
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

UPDATE `assets`
SET `assets`.`location_id_outdated` = (
    SELECT `locations_outdated`.`id`
    FROM `locations_outdated`
    WHERE `locations_outdated`.`id` = `assets`.`location_id`
)
WHERE 1=1
    AND `assets`.`location_id` IS NOT NULL;

UPDATE `assets`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `assets`.`location_id`
SET `assets`.`location_id_outdated` = COALESCE(
    (
        SELECT `customer_locations`.`id` FROM `customer_locations`
        WHERE 1=1
            AND `customer_locations`.`location_id` = `assets`.`location_id`
            AND `customer_locations`.`customer_id` = `assets`.`customer_id`
        LIMIT 1
    ),
    (
        SELECT `reseller_locations`.`id` FROM `reseller_locations`
        WHERE 1=1
            AND `reseller_locations`.`location_id` = `assets`.`location_id`
            AND `reseller_locations`.`reseller_id` = `assets`.`reseller_id`
        LIMIT 1
    )
)
WHERE 1=1
    AND `assets`.`location_id` IS NOT NULL
    AND (`assets`.`reseller_id` IS NOT NULL OR `assets`.`customer_id` IS NOT NULL)
    AND (`locations_outdated`.`object_type` != 'Asset' OR `locations_outdated`.`object_type` IS NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
