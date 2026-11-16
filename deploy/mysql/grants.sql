-- MySQL 9.7 LTS. Run once as the MySQL administrator. Replace the two passwords; never commit real ones.
-- Both accounts connect through the socket (DB_HOST=localhost in .env), never over TCP.
CREATE DATABASE IF NOT EXISTS jude CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- The application: rows only, local connections only (REQ-DB-02, 03, 33).
CREATE USER IF NOT EXISTS 'jude_app'@'localhost' IDENTIFIED BY 'CHANGE_ME_APP';
GRANT SELECT, INSERT, UPDATE, DELETE ON jude.* TO 'jude_app'@'localhost';

-- Deployment only: may change the structure (REQ-DB-34).
CREATE USER IF NOT EXISTS 'jude_migrate'@'localhost' IDENTIFIED BY 'CHANGE_ME_MIGRATE';
GRANT ALL PRIVILEGES ON jude.* TO 'jude_migrate'@'localhost';

FLUSH PRIVILEGES;

-- Log every query slower than 1 second (REQ-DB-30). deploy/mysql/jude.cnf keeps this on after a restart.
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
