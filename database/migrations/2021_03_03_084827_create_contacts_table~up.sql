SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `contacts` (
    `id`           CHAR(36)     NOT NULL,
    `object_id`    CHAR(36)     NOT NULL,
    `object_type`  VARCHAR(255) NOT NULL,
    `type_id`      CHAR(36)     NOT NULL,
    `name`         VARCHAR(255) NULL,
    `email`        VARCHAR(255) NULL,
    `phone_number` VARCHAR(16)  NULL,
    `phone_valid`  TINYINT(1)   NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP    NULL,
    PRIMARY KEY (`id`),
    INDEX `idx__object_id__object_type`(`object_id` ASC, `object_type` ASC) VISIBLE,
    UNIQUE INDEX `unique__email__object_id__object_type`(`email` ASC, `object_id` ASC, `object_type` ASC) INVISIBLE,
    UNIQUE INDEX `unique__phone__object_id__object_type`(`phone_number` ASC, `object_id` ASC, `object_type` ASC) VISIBLE,
    INDEX `fk_contacts_types1_idx`(`type_id` ASC) VISIBLE,
    CONSTRAINT `fk_contacts_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
