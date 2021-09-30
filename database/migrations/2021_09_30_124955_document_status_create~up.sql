SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `document_statuses` (
    `id`          CHAR(36)  NOT NULL,
    `document_id` CHAR(36)  NOT NULL,
    `status_id`   CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    INDEX `fk_document_statuses_statuses1_idx`(`status_id` ASC) VISIBLE,
    INDEX `fk_document_statuses_documents1_idx`(`document_id` ASC) VISIBLE,
    PRIMARY KEY (`id`),
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    UNIQUE INDEX `unique_status`(`document_id` ASC, `status_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_document_statuses_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_document_statuses_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
