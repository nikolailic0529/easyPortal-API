SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `asset_change_requests` (
  `id`              CHAR(36)     NOT NULL,
  `subject`         VARCHAR(255) NOT NULL,
  `from`            VARCHAR(255) NOT NULL,
  `message`         TEXT         NOT NULL,
  `cc`              JSON         NULL       DEFAULT NULL,
  `bcc`             JSON         NULL       DEFAULT NULL,
  `organization_id` CHAR(36)     NOT NULL,
  `user_id`         CHAR(36)     NOT NULL,
  `asset_id`        CHAR(36)     NOT NULL,
  `created_at`      TIMESTAMP    NOT NULL,
  `updated_at`      TIMESTAMP    NOT NULL,
  `deleted_at`      TIMESTAMP    NULL       DEFAULT NULL,
  `deleted_not`     TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  INDEX `fk_asset_change_requests_organizations1_idx` (`organization_id` ASC) VISIBLE,
  INDEX `fk_asset_change_requests_users1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_asset_change_requests_assets1_idx` (`asset_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  CONSTRAINT `fk_asset_change_requests_assets1`
    FOREIGN KEY (`asset_id`)
    REFERENCES `assets` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_change_requests_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
