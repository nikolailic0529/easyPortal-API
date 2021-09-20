SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `teams` (
  `id`         CHAR(36)     NOT NULL,
  `name`       VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP    NULL DEFAULT NULL,
  `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unique__name__deleted_not` (`name` ASC, `deleted_not` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE);

ALTER TABLE `organization_users`
  ADD COLUMN `team_id` CHAR(36) NULL DEFAULT NULL AFTER `user_id`,
  ADD INDEX `fk_organization_users_teams1_idx` (`team_id` ASC) VISIBLE;

ALTER TABLE `organization_users`
  ADD CONSTRAINT `fk_organization_users_teams1`
    FOREIGN KEY (`team_id`)
    REFERENCES `teams` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
