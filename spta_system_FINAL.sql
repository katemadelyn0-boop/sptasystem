-- ============================================================
--  SPTA System — FINAL Database (v3)
--  All columns match PHP code exactly
-- ============================================================
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `spta_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `spta_system`;

-- 1. USERS
CREATE TABLE `users` (
  `user_id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`         VARCHAR(60)  NOT NULL,
  `last_name`          VARCHAR(60)  NOT NULL,
  `email`              VARCHAR(120) NOT NULL,
  `password`           VARCHAR(255) NOT NULL,
  `role`               ENUM('admin','staff','spta_officer','parent') NOT NULL DEFAULT 'parent',
  `verification_token` VARCHAR(64)  DEFAULT NULL,
  `is_verified`        TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `reset_token`        VARCHAR(64)  DEFAULT NULL,
  `reset_expires`      DATETIME     DEFAULT NULL,
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin password: Admin@1234
INSERT INTO `users` (`first_name`,`last_name`,`email`,`password`,`role`,`is_verified`,`is_active`)
VALUES ('System','Admin','admin@deped.gov.ph',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,1);

-- 2. SCHOOL YEARS
CREATE TABLE `school_years` (
  `sy_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sy_label`  VARCHAR(20)  NOT NULL,
  `is_active` TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`sy_id`),
  UNIQUE KEY `sy_label` (`sy_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `school_years` (`sy_label`,`is_active`) VALUES
  ('2023-2024',0),('2024-2025',0),('2025-2026',1);

-- 3. GRADE LEVELS
CREATE TABLE `grade_levels` (
  `grade_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `grade_name` VARCHAR(30)  NOT NULL,
  PRIMARY KEY (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `grade_levels` (`grade_name`) VALUES
  ('Grade 1'),('Grade 2'),('Grade 3'),('Grade 4'),('Grade 5'),('Grade 6');

-- 4. SECTIONS
CREATE TABLE `sections` (
  `section_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_name` VARCHAR(60)  NOT NULL,
  `grade_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`section_id`),
  KEY `grade_id` (`grade_id`),
  CONSTRAINT `fk_sec_grade` FOREIGN KEY (`grade_id`) REFERENCES `grade_levels`(`grade_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. STUDENTS
CREATE TABLE `students` (
  `student_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`  VARCHAR(60)  NOT NULL,
  `middle_name` VARCHAR(60)  DEFAULT NULL,
  `last_name`   VARCHAR(60)  NOT NULL,
  `lrn`         VARCHAR(12)  DEFAULT NULL,
  `gender`      ENUM('male','female') NOT NULL,
  `grade_id`    INT UNSIGNED NOT NULL,
  `sy_id`       INT UNSIGNED NOT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  KEY `grade_id` (`grade_id`),
  KEY `sy_id` (`sy_id`),
  CONSTRAINT `fk_stu_grade` FOREIGN KEY (`grade_id`) REFERENCES `grade_levels`(`grade_id`),
  CONSTRAINT `fk_stu_sy`    FOREIGN KEY (`sy_id`)    REFERENCES `school_years`(`sy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PARENT-STUDENT (parent_id matches PHP code)
CREATE TABLE `parent_student` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`  INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`parent_id`,`student_id`),
  CONSTRAINT `fk_ps_parent`  FOREIGN KEY (`parent_id`)  REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. PAYMENT CATEGORIES (managed_by matches officer/dashboard.php)
CREATE TABLE `payment_categories` (
  `category_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(80)  NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `managed_by`    ENUM('staff','spta_officer') NOT NULL DEFAULT 'staff',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_categories` (`category_name`,`description`,`managed_by`) VALUES
  ('SPTA Fee',      'Annual SPTA membership fee',  'spta_officer'),
  ('Miscellaneous', 'Other school fees',            'staff');

-- 8. PAYMENT REQUIREMENTS
CREATE TABLE `payment_requirements` (
  `requirement_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `category_id`    INT UNSIGNED  NOT NULL,
  `sy_id`          INT UNSIGNED  NOT NULL,
  `amount`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `description`    TEXT          DEFAULT NULL,
  PRIMARY KEY (`requirement_id`),
  KEY `category_id` (`category_id`),
  KEY `sy_id` (`sy_id`),
  CONSTRAINT `fk_pr_cat` FOREIGN KEY (`category_id`) REFERENCES `payment_categories`(`category_id`),
  CONSTRAINT `fk_pr_sy`  FOREIGN KEY (`sy_id`)       REFERENCES `school_years`(`sy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. PAYMENTS
CREATE TABLE `payments` (
  `payment_id`     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`     INT UNSIGNED  NOT NULL,
  `requirement_id` INT UNSIGNED  NOT NULL,
  `amount_paid`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','gcash','maya','bank_transfer') NOT NULL DEFAULT 'cash',
  `payment_date`   DATE          NOT NULL,
  `status`         ENUM('paid','partial','unpaid','overdue')   NOT NULL DEFAULT 'unpaid',
  `reference_no`   VARCHAR(60)   DEFAULT NULL,
  `proof_image`    VARCHAR(255)  DEFAULT NULL,
  `remarks`        TEXT          DEFAULT NULL,
  `recorded_by`    INT UNSIGNED  NOT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `student_id`     (`student_id`),
  KEY `requirement_id` (`requirement_id`),
  KEY `recorded_by`    (`recorded_by`),
  CONSTRAINT `fk_pay_stu`  FOREIGN KEY (`student_id`)     REFERENCES `students`(`student_id`),
  CONSTRAINT `fk_pay_req`  FOREIGN KEY (`requirement_id`) REFERENCES `payment_requirements`(`requirement_id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`recorded_by`)    REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. RECEIPTS
CREATE TABLE `receipts` (
  `receipt_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT UNSIGNED NOT NULL,
  `receipt_no` VARCHAR(30)  NOT NULL,
  `issued_to`  VARCHAR(120) NOT NULL,
  `issued_by`  VARCHAR(120) NOT NULL,
  `issued_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `fk_rec_pay` FOREIGN KEY (`payment_id`) REFERENCES `payments`(`payment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. NOTIFICATIONS (title, type, sent_at match notifications.php)
CREATE TABLE `notifications` (
  `notif_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(120) NOT NULL,
  `message`    TEXT         NOT NULL,
  `type`       ENUM('reminder','overdue','confirmation','announcement') NOT NULL DEFAULT 'announcement',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sent_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notif_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. AUDIT LOG (table_affected, old_value, new_value, ip_address match auth.php)
CREATE TABLE `audit_log` (
  `log_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED DEFAULT NULL,
  `action`         VARCHAR(60)  NOT NULL,
  `table_affected` VARCHAR(60)  DEFAULT NULL,
  `record_id`      INT UNSIGNED DEFAULT NULL,
  `old_value`      TEXT         DEFAULT NULL,
  `new_value`      TEXT         DEFAULT NULL,
  `ip_address`     VARCHAR(45)  DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
-- ============================================================
-- Admin: admin@deped.gov.ph / Admin@1234
-- ============================================================
