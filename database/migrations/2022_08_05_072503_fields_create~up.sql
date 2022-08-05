SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `fields` (
    `id`          CHAR(36)     NOT NULL,
    `object_type` VARCHAR(255) NOT NULL,
    `key`         VARCHAR(255) NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique__object_type__key__deleted_not`(`object_type` ASC, `key` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE
);

CREATE TABLE IF NOT EXISTS `document_entry_fields` (
    `id`                CHAR(36)      NOT NULL,
    `document_entry_id` CHAR(36)      NOT NULL,
    `field_id`          CHAR(36)      NOT NULL,
    `value`             VARCHAR(2048) NOT NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`        TIMESTAMP     NULL     DEFAULT NULL,
    `deleted_not`       TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_document_entry_fields_document_entries1_idx`(`document_entry_id` ASC) VISIBLE,
    INDEX `fk_document_entry_fields_fields1_idx`(`field_id` ASC) INVISIBLE,
    UNIQUE INDEX `unique__field`(`document_entry_id` ASC, `field_id` ASC, `deleted_not` ASC) INVISIBLE,
    CONSTRAINT `fk_document_entry_fields_document_entries1`
        FOREIGN KEY (`document_entry_id`)
            REFERENCES `document_entries`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_document_entry_fields_fields1`
        FOREIGN KEY (`field_id`)
            REFERENCES `fields`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
