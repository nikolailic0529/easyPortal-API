SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `contacts`
    DROP FOREIGN KEY `fk_contacts_types1`;

ALTER TABLE `contacts`
    DROP COLUMN `type_id`,
    DROP INDEX `fk_contacts_types1_idx`;

CREATE TABLE IF NOT EXISTS `contact_types` (
    `contact_id` CHAR(36)  NOT NULL,
    `type_id`    CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    PRIMARY KEY (`contact_id`, `type_id`),
    INDEX `fk_contact_types_types1_idx`(`type_id` ASC) VISIBLE,
    INDEX `fk_contact_types_contacts1_idx`(`contact_id` ASC) VISIBLE,
    CONSTRAINT `fk_contact_types_contacts1`
        FOREIGN KEY (`contact_id`)
            REFERENCES `contacts`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_contact_types_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
