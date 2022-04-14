SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `quote_request_documents` (
    `id`          CHAR(36)  NOT NULL,
    `request_id`  CHAR(36)  NOT NULL,
    `document_id` CHAR(36)  NOT NULL,
    `duration_id` CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_quote_request_documents_quote_requests1_idx`(`request_id` ASC) VISIBLE,
    INDEX `fk_quote_request_documents_quote_request_durations1_idx`(`duration_id` ASC) VISIBLE,
    INDEX `fk_quote_request_documents_documents1_idx`(`document_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    CONSTRAINT `fk_quote_request_documents_quote_requests1`
        FOREIGN KEY (`request_id`)
            REFERENCES `quote_requests`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_quote_request_documents_quote_request_durations1`
        FOREIGN KEY (`duration_id`)
            REFERENCES `quote_request_durations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_quote_request_documents_documents1`
        FOREIGN KEY (`document_id`)
            REFERENCES `documents`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
