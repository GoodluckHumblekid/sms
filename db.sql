-- SQL for creating sms_db and tables (MySQL / MariaDB)
-- Run this in phpMyAdmin or mysql CLI

CREATE DATABASE IF NOT EXISTS sms_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_db;

-- Users table (if you want to mirror SQLite users into MySQL)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_number VARCHAR(32) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  second_name VARCHAR(100),
  last_name VARCHAR(100) NOT NULL,
  dob DATE,
  gender ENUM('Male','Female','Other') DEFAULT NULL,
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100),
  phone VARCHAR(50),
  password_hash VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin credential
-- This example uses SHA2() to store a hash directly from SQL.
-- NOTE: it's recommended to generate a PHP `password_hash()` value and store that instead.
-- To generate a PHP bcrypt hash run (in XAMPP shell or php CLI):
-- php -r "echo password_hash('Humblekid@2026', PASSWORD_DEFAULT) . PHP_EOL;"
-- Then replace the SHA2(...) value below with the generated hash string (including $2y$...)

INSERT INTO admins (email, password_hash, role)
VALUES (
  'info@humblekid.com',
  SHA2('Humblekid@2026', 256),
  'admin'
);

-- If you want to use a bcrypt hash produced by PHP, run the php command above,
-- then execute an INSERT like this (replace PASTE_HASH_HERE with the php output):
-- INSERT INTO admins (email, password_hash, role) VALUES ('info@humblekid.com','PASTE_HASH_HERE','admin');
CREATE DATABASE IF NOT EXISTS sms_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_number VARCHAR(32) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  second_name VARCHAR(100),
  last_name VARCHAR(100) NOT NULL,
  dob DATE,
  gender ENUM('Male','Female','Other') DEFAULT NULL,
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100),
  phone VARCHAR(50),
  password_hash VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

