-- NextChapter - base de dados MySQL / MariaDB
-- Importe este ficheiro no phpMyAdmin (XAMPP) ou execute: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS nextchapter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nextchapter;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80)  NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until  INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simulations (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  type       VARCHAR(20)  NOT NULL,
  title      VARCHAR(120) NOT NULL,
  inputs     MEDIUMTEXT   NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sim_user (user_id),
  CONSTRAINT fk_sim_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  id         VARCHAR(128) NOT NULL PRIMARY KEY,
  data       MEDIUMTEXT   NOT NULL,
  updated_at INT UNSIGNED NOT NULL,
  INDEX idx_sess_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
