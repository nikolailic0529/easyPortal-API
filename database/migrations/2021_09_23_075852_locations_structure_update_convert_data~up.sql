SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Create Locations
INSERT INTO `locations` (`id`,
                         `country_id`,
                         `city_id`,
                         `postcode`,
                         `state`,
                         `line_one`,
                         `line_two`,
                         `latitude`,
                         `longitude`)
SELECT UUID(),
       `country_id`,
       `city_id`,
       `postcode`,
       `state`,
       `line_one`,
       `line_two`,
       `latitude`,
       `longitude`
FROM `locations_outdated`
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

-- Customers locations
INSERT INTO `customer_locations` (`id`, `customer_id`, `location_id`)
SELECT `locations_outdated`.`id`, `locations_outdated`.`object_id`, `locations`.`id`
FROM `locations_outdated`
    LEFT JOIN `locations` ON `locations`.`hash` = `locations_outdated`.`hash`
WHERE 1=1
    AND `locations_outdated`.`object_type` = 'Customer'
    AND `locations_outdated`.`deleted_at` IS NULL;

INSERT INTO `customer_location_types` (`id`, `customer_location_id`, `type_id`)
SELECT `location_types_outdated`.`id`, `location_types_outdated`.`location_id`, `location_types_outdated`.`type_id`
FROM `location_types_outdated`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `location_types_outdated`.`location_id`
WHERE 1=1
    AND `locations_outdated`.`deleted_at` IS NULL
    AND `locations_outdated`.`object_type` = 'Customer'
    AND `locations_outdated`.`deleted_at` IS NULL;

-- Resellers locations
INSERT INTO `reseller_locations` (`id`, `reseller_id`, `location_id`)
SELECT `locations_outdated`.`id`, `locations_outdated`.`object_id`, `locations`.`id`
FROM `locations_outdated`
LEFT JOIN `locations` ON `locations`.`hash` = `locations_outdated`.`hash`
WHERE 1=1
    AND `locations_outdated`.`object_type` = 'Reseller'
    AND `locations_outdated`.`deleted_at` IS NULL;

INSERT INTO `reseller_location_types` (`id`, `reseller_location_id`, `type_id`)
SELECT `location_types_outdated`.`id`, `location_types_outdated`.`location_id`, `location_types_outdated`.`type_id`
FROM `location_types_outdated`
LEFT JOIN `locations_outdated` ON `locations_outdated`.`id` = `location_types_outdated`.`location_id`
WHERE 1=1
    AND `locations_outdated`.`deleted_at` IS NULL
    AND `locations_outdated`.`object_type` = 'Reseller'
    AND `locations_outdated`.`deleted_at` IS NULL;

-- Assets locations
-- (assets locations doesn't have `type`)
UPDATE `assets`
SET `assets`.`location_id` = (
    SELECT `locations`.`id`
    FROM `locations`
    LEFT JOIN `locations_outdated` ON `locations_outdated`.`hash` = `locations`.`hash`
    WHERE `locations_outdated`.`id` = `assets`.`location_id_outdated`
)
WHERE `assets`.`location_id_outdated` IS NOT NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
