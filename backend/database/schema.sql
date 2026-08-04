-- ============================================================
-- Sheet Music Vault - database schema
-- Run with: mysql -u root -p < database/schema.sql
-- (creates the database, the table, and sample rows)
-- ============================================================

CREATE DATABASE IF NOT EXISTS sheet_music_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sheet_music_db;

DROP TABLE IF EXISTS sheet_music;

CREATE TABLE sheet_music (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    subtitle    VARCHAR(255) NULL,
    composer    VARCHAR(255) NOT NULL,
    arranger    VARCHAR(255) NULL,
    year        INT UNSIGNED NOT NULL,
    genre       VARCHAR(80)  NOT NULL,
    score_img   VARCHAR(255) NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_genre (genre),
    KEY idx_composer (composer)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'guest') NOT NULL DEFAULT 'guest',
    api_token     VARCHAR(64)  NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_token (api_token)
) ENGINE=InnoDB;

-- Sample data
INSERT INTO sheet_music (title, subtitle, composer, arranger, year, genre) VALUES
('Symphony No. 5', 'Allegro con brio', 'Ludwig van Beethoven', NULL, 1808, 'Classical (1750 - 1820)'),
('Piano Sonata No. 14', 'Moonlight', 'Ludwig van Beethoven', 'Franz Liszt', 1801, 'Classical (1750 - 1820)'),
('The Four Seasons', 'La Primavera', 'Antonio Vivaldi', NULL, 1725, 'Baroque (1600 - 1750)'),
('Carnaval', 'Scenes mignonnes sur quatre notes', 'Robert Schumann', NULL, 1835, 'Romantic, Modern & Contemporary (1820 - now)'),
('O Fortuna', 'Carmina Burana', 'Carl Orff', NULL, 1936, 'Romantic, Modern & Contemporary (1820 - now)');