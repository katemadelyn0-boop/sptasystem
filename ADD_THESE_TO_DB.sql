
-- ============================================================
-- NEW: Pending registrations table (for email OTP verification)
-- ============================================================
CREATE TABLE IF NOT EXISTS pending_registrations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('staff','spta_officer','parent') NOT NULL,
  student_id    INT NULL,
  otp_code      CHAR(6) NOT NULL,
  expires_at    DATETIME NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add section column to students if not exists
ALTER TABLE students ADD COLUMN IF NOT EXISTS section VARCHAR(100) NULL AFTER grade_id;
