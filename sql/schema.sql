-- =====================================================
-- New Peel Tamil Teachers Association - Canada
-- Database Schema
-- =====================================================
CREATE DATABASE IF NOT EXISTS nptta_db CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE nptta_db;

-- ---------------------------------------------------
-- Users Table (all roles: super_admin, manager, teacher, student)
-- AUTO_INCREMENT starts at 100000 so manual 5-digit IDs can coexist
-- ---------------------------------------------------
CREATE TABLE
    users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM ('super_admin', 'manager', 'teacher', 'student') NOT NULL,
        school_id INT UNSIGNED NULL,
        teacher_id INT UNSIGNED NULL,
        guardian_first_name VARCHAR(100) NULL,
        guardian_last_name VARCHAR(100) NULL,
        first_login TINYINT (1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB AUTO_INCREMENT = 100000 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Schools
-- ---------------------------------------------------
CREATE TABLE
    schools (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Foreign keys for users -> schools, users -> teacher
ALTER TABLE users ADD CONSTRAINT fk_users_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE SET NULL;

ALTER TABLE users ADD CONSTRAINT fk_users_teacher FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE SET NULL;

-- ---------------------------------------------------
-- Exams
-- ---------------------------------------------------
CREATE TABLE
    exams (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        registration_start_date DATE NOT NULL,
        registration_end_date DATE NOT NULL,
        exam_date DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Exam Types
-- ---------------------------------------------------
CREATE TABLE
    exam_types (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Exam Registrations
-- ---------------------------------------------------
CREATE TABLE
    exam_registrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        exam_id INT UNSIGNED NOT NULL,
        student_id INT UNSIGNED NOT NULL,
        grade VARCHAR(5) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE,
        UNIQUE KEY uq_exam_student (exam_id, student_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Exam Registration ↔ Exam Types (many-to-many)
-- ---------------------------------------------------
CREATE TABLE
    exam_registration_types (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        registration_id INT UNSIGNED NOT NULL,
        exam_type_id INT UNSIGNED NOT NULL,
        FOREIGN KEY (registration_id) REFERENCES exam_registrations (id) ON DELETE CASCADE,
        FOREIGN KEY (exam_type_id) REFERENCES exam_types (id) ON DELETE CASCADE,
        UNIQUE KEY uq_reg_type (registration_id, exam_type_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Results (per registration per exam type)
-- ---------------------------------------------------
CREATE TABLE
    results (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        registration_id INT UNSIGNED NOT NULL,
        exam_type_id INT UNSIGNED NOT NULL,
        marks DECIMAL(6, 2) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (registration_id) REFERENCES exam_registrations (id) ON DELETE CASCADE,
        FOREIGN KEY (exam_type_id) REFERENCES exam_types (id) ON DELETE CASCADE,
        UNIQUE KEY uq_result (registration_id, exam_type_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Notices (text only)
-- ---------------------------------------------------
CREATE TABLE
    notices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Posters (single image)
-- ---------------------------------------------------
CREATE TABLE
    posters (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Password Resets
-- ---------------------------------------------------
CREATE TABLE
    password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT (1) NOT NULL DEFAULT 0,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Seed: default super admin  (password: Admin@123)
-- $2y$12$ hash generated by password_hash('Admin@123', PASSWORD_BCRYPT)
-- ---------------------------------------------------
INSERT INTO
    users (
        id,
        first_name,
        last_name,
        email,
        password,
        role,
        first_login
    )
VALUES
    (
        1,
        'Super',
        'Admin',
        'admin@nptta.ca',
        '$2y$12$LJ3m4yPnMDAkqOQvYQBuku0F5ch0M0aNqfDfGKLkBa6bMSCJHMhWu',
        'super_admin',
        0
    );