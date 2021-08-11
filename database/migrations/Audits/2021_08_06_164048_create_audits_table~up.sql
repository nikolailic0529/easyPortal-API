SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `audits` (
  `id`              CHAR(36)     NOT NULL,
  `user_id`         CHAR(36)     NULL DEFAULT NULL,
  `organization_id` CHAR(36)     NULL DEFAULT NULL,
  `action`          VARCHAR(255) NOT NULL,
  `object_type`     VARCHAR(255) NULL DEFAULT NULL,
  `object_id`       CHAR(36)     NULL DEFAULT NULL,
  `context`         JSON         NULL DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx__user_id` (`user_id` ASC) VISIBLE,
  INDEX `idx__organization_id__user_id` (`organization_id` ASC, `user_id` ASC) VISIBLE,
  INDEX `idx__object_id__object_type` (`object_id` ASC, `object_type` ASC) VISIBLE,
  INDEX `idx__action__organization_id` (`action` ASC, `organization_id` ASC) VISIBLE
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
