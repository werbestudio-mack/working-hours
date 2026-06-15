-- Arbeitszeiterfassung – Datenbankschema
-- Erstellt mit MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS working_hours
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE working_hours;

-- ─────────────────────────────────────────
-- Benutzer
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id                   INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(150)    NOT NULL,
    email                VARCHAR(255)    NOT NULL UNIQUE,
    password_hash        VARCHAR(255)    NOT NULL,
    is_admin             TINYINT(1)      NOT NULL DEFAULT 0,
    weekly_hours         DECIMAL(5,2)    NOT NULL DEFAULT 40.00,
    vacation_days        DECIMAL(5,1)    NOT NULL DEFAULT 30.0,
    must_change_password TINYINT(1)      NOT NULL DEFAULT 1,
    is_active            TINYINT(1)      NOT NULL DEFAULT 1,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Feiertage
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS public_holidays (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)    NOT NULL,
    date        DATE            NOT NULL,
    is_half_day TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_holiday_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Zeiteinträge
-- type: work, vacation, sick, compensatory
-- work + compensatory: started_at / ended_at (DATETIME mit Uhrzeit)
-- vacation + sick:     date_start / date_end (nur Datum)
-- half_day: 0=kein, 1=Vormittag, 2=Nachmittag (nur vacation)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS time_entries (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    type        ENUM('work','vacation','sick','compensatory') NOT NULL,
    started_at  DATETIME        NULL,
    ended_at    DATETIME        NULL,
    date_start  DATE            NULL,
    date_end    DATE            NULL,
    half_day    TINYINT(1)      NOT NULL DEFAULT 0,
    notes       TEXT            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_te_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_te_user_type  (user_id, type),
    INDEX idx_te_started    (started_at),
    INDEX idx_te_date_start (date_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Urlaubsanpassungen (Resturlaub, Sondertage)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vacation_adjustments (
    id          INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED        NOT NULL,
    year        SMALLINT UNSIGNED   NOT NULL,
    carry_over  DECIMAL(5,1)        NOT NULL DEFAULT 0.0,
    bonus_days  DECIMAL(5,1)        NOT NULL DEFAULT 0.0,
    note        VARCHAR(255)        NULL,
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_va_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_va_user_year (user_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Passwort-Reset-Token
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED    NOT NULL,
    token      CHAR(64)        NOT NULL,
    expires_at DATETIME        NOT NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pr_token (token),
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
