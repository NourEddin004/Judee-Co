-- LOCAL DEVELOPMENT ONLY. Never run this on the server.
-- compose.yaml loads it into the local MySQL container on its first start, so `php artisan test`
-- has its own empty database (phpunit.xml uses jude_test).
CREATE DATABASE IF NOT EXISTS jude_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON jude_test.* TO 'jude'@'%';
