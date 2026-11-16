# Deployment files (server-ready, nothing provisioned)

These files describe how the site runs on one Ubuntu 24.04 server behind Cloudflare, with PHP 8.5 and
MySQL 9.7 LTS. No server, domain or paid service has been set up. Each step that costs money or touches
a real server or domain waits for the owner's approval.

**Setting up the server:** follow [HOSTING-CHECKLIST.md](HOSTING-CHECKLIST.md). It lists every package,
setting and permission the site needs, with a command to check each one. Ubuntu 24.04's own PHP (8.3),
MySQL (8.0) and Node (18) are too old, so the checklist says where to get the right versions.

| File | Where it goes | What it is for |
| --- | --- | --- |
| `HOSTING-CHECKLIST.md` | | Step by step server setup, with checks. |
| `env.production.example` | `/var/www/jude/shared/.env` | Production settings. Fill in the secrets on the server only (REQ-SEC-04). Debug is off (REQ-SEC-26). |
| `nginx/jude.conf` | `/etc/nginx/sites-available/jude` | Web server: HTTPS only, `www` to the main address (REQ-SEC-09), uploads can never run as code (REQ-SEC-20), long cache for built files. |
| `nginx/jude-security-headers.conf` | `/etc/nginx/snippets/` | Security headers on the files nginx serves itself (REQ-SEC-18). The site sends its own, with its Content Security Policy (REQ-SEC-17). |
| `nginx/cloudflare-real-ip.conf` | `/etc/nginx/snippets/` | Passes on the visitor's own address from Cloudflare, so the per-visitor limits work. The firewall uses the same list (REQ-SEC-11). |
| `php/jude.ini` | `/etc/php/8.5/fpm/conf.d/99-jude.ini` | Memory 256M for photo resizing, 6M uploads, 60-second limit, OPcache. |
| `mysql/jude.cnf` | `/etc/mysql/mysql.conf.d/jude.cnf` | Local connections only (REQ-DB-02), slow-query log over 1 s that stays on after a restart (REQ-DB-30). |
| `mysql/grants.sql` | run once | The database and two accounts: the app can only read and write rows, and a separate account runs migrations during deploys (REQ-DB-03, 33, 34). |
| `mysql/test-db.sql` | **local only** | The test database for `compose.yaml`. Never run it on the server. |
| `supervisor/jude-queue.conf` | `/etc/supervisor/conf.d/` | Email worker for the order emails (REQ-DB-13). Each email is tried up to 4 times: the first try, then 3 retries after 1, 5 and 15 minutes (REQ-DB-14). |
| `crontab` | `crontab -u www-data` | Laravel's scheduler every minute (REQ-DB-16): the nightly backup and its cleanup, and the hourly disk check (`routes/console.php`). |
| `RESTORE.md` | | How to put a backup back, on the same server or a new one (REQ-SAF-04). |
| `logrotate/jude` | `/etc/logrotate.d/jude` | Weekly rotation of the email worker's log and the slow-query log. |
| `deploy.sh` | `/var/www/jude/deploy.sh` | One deploy: checks the PHP and Node versions, clones a release, installs, builds, migrates with the migration account, caches, switches over and restarts the workers. Keeps the last 5 releases. |

## First deploy

Section 11 of the checklist has the commands. In short:

1. Run `deploy.sh` with `REPO`, `BRANCH` (`main`, created at launch) and the migration account set.
2. **Once only:** run `php artisan db:seed --force` as `www-data`. It adds the settings, delivery cities
   and fees, page texts and the demo's sample products. Running it again would reset the FAQs and terms.
3. Create the owner's account with `php artisan jude:create-owner`.

## Admin panel (from M2)

- The admin panel lives at `/admin`.
- At first login the owner must set up two-step sign-in with an authenticator app and keep the recovery codes.
  The owner's is the only admin account.
- `APP_KEY` must never change after launch: it encrypts the owner's two-step secret.
- `composer install` runs `php artisan filament:upgrade`, which publishes the admin panel's own CSS and JS.
