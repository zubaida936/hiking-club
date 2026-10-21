-- Separate database for the automated test suite so tests never touch dev data.
CREATE DATABASE IF NOT EXISTS hiking_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON hiking_testing.* TO 'hiking'@'%';
FLUSH PRIVILEGES;
