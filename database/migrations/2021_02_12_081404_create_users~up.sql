SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `users` (
    `id`                CHAR(36)      NOT NULL,
    `organization_id`   CHAR(36)      NOT NULL,
    `sub`               VARCHAR(255)  NULL COMMENT 'Auth0 User ID',
    `blocked`           TINYINT(1)    NOT NULL DEFAULT 1,
    `given_name`        VARCHAR(255)  NOT NULL,
    `family_name`       VARCHAR(255)  NOT NULL,
    `email`             VARCHAR(255)  NOT NULL,
    `email_verified_at` TIMESTAMP     NULL,
    `phone`             VARCHAR(16)   NOT NULL,
    `phone_verified_at` TIMESTAMP     NULL,
    `photo`             VARCHAR(1024) NULL,
    `permissions`       JSON          NOT NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`        TIMESTAMP     NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique_email`(`email` ASC) VISIBLE,
    UNIQUE INDEX `unique_sub`(`sub` ASC) VISIBLE,
    INDEX `fk_users_organizations1_idx`(`organization_id` ASC) VISIBLE,
    CONSTRAINT `fk_users_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
