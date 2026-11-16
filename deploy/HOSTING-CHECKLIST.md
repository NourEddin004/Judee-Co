# Hosting checklist

This is for whoever sets up the server. It lists everything the JUDE Boutique site needs from the
hosting, with a command to check each item. The files it mentions are in this `deploy/` folder.

- **Nothing has been bought or set up yet.** The server plan, the domain, the email service, a server
  manager and backup storage all cost money. Each one waits for approval before anyone pays.
- **The accounts belong to the client.** Hosting, domain and email accounts are opened in the client's
  name (SRS 2.5). Billing alerts go to the client's email (REQ-CST-07).
- `example.com` below means the store's domain. `deploy` means the server user that deploys the site.

The commands were tried on Ubuntu 24.04 and MySQL 9.7.4.

## 1. The server plan (needs approval)

- [ ] **Fixed monthly price.** No pay-per-use billing, and no automatic upgrade to a bigger plan
      (REQ-CST-01, 02, 03).
- [ ] **Ubuntu 24.04 LTS**, 64-bit.
- [ ] **Memory: 2 GB at least.** 4 GB is comfortable, because MySQL, photo resizing and the email
      worker run on the same server.
- [ ] **Disk: 40 GB or more.** That covers the photos, the last 5 releases and the logs. Backups go to
      separate storage.
- [ ] **Traffic:** the included monthly traffic is at least 10 times the store's expected traffic
      (REQ-CST-04). Most plans include 1 TB or more, which is far more than that.
- [ ] **Billing alerts** are on and go to the client's email.
- [ ] **Decide on a server manager.** The SRS (2.1) suggests one, such as Laravel Forge or Ploi. It is a
      monthly cost, so it needs approval too. A manager does most of sections 3, 6 and 8 for you.
      Check that it installs PHP 8.5 and MySQL 9.7 LTS. If it offers only an older MySQL, tell
      RafaqTech before going ahead, because the SRS requires 9.7 LTS (REQ-DB-01). The settings files in
      sections 4 to 9 still apply.

## 2. Base setup

- [ ] **SSH accepts keys only (REQ-SEC-13).** Create `/etc/ssh/sshd_config.d/99-jude.conf`:
      ```
      PasswordAuthentication no
      KbdInteractiveAuthentication no
      PermitRootLogin prohibit-password
      ```
      Run `sudo systemctl reload ssh`. Before you log out, check from a second terminal that your key still works.
      Check: `sudo sshd -T | grep -E '^(passwordauthentication|kbdinteractiveauthentication|permitrootlogin) '`
      shows `no`, `no` and `without-password`.
- [ ] **A deploy user.** Create it with your SSH key and add it to the `www-data` group:
      `sudo adduser deploy && sudo usermod -aG www-data deploy`.
- [ ] **Automatic security updates (REQ-SEC-14).** Run
      `sudo apt install unattended-upgrades && sudo dpkg-reconfigure -plow unattended-upgrades`.
      Check: both lines in `/etc/apt/apt.conf.d/20auto-upgrades` end in `"1";`.
- [ ] **Time zone and clock.** Run `sudo timedatectl set-timezone Asia/Amman`.
      Check: `timedatectl` shows `System clock synchronized: yes`. The owner's two-step sign-in codes
      fail if the clock drifts.
- [ ] **Firewall (REQ-SEC-11, 12).** Only SSH is open to everyone. Ports 80 and 443 are open only to
      Cloudflare's addresses, which are listed in `nginx/cloudflare-real-ip.conf`. Run these from a copy of this folder:
      ```
      sudo ufw default deny incoming
      sudo ufw default allow outgoing
      sudo ufw allow OpenSSH
      grep -oP 'set_real_ip_from \K[^;]+' nginx/cloudflare-real-ip.conf | while read -r range; do
        sudo ufw allow proto tcp from "$range" to any port 80,443
      done
      sudo ufw enable
      ```
      Check: `sudo ufw status` lists OpenSSH plus the Cloudflare ranges for 80,443, and nothing else.
      If the provider has its own firewall in its control panel, give it the same rules.

## 3. Software

Ubuntu 24.04's own PHP (8.3), MySQL (8.0) and Node (18) are too old for this project. Install these
versions instead.

- [ ] **PHP 8.5** from the ondrej PPA:
      ```
      sudo add-apt-repository ppa:ondrej/php
      sudo apt install php8.5-fpm php8.5-cli php8.5-common php8.5-mysql php8.5-intl php8.5-zip \
        php8.5-xml php8.5-gd php8.5-mbstring php8.5-curl
      ```
      Check: `php -v` shows 8.5, and `sudo -u www-data php -v` shows 8.5 too. If not, run
      `sudo update-alternatives --set php /usr/bin/php8.5`. `deploy.sh` stops if `php` is older than 8.5.
- [ ] **MySQL 9.7 LTS** from Oracle's APT repository (https://dev.mysql.com/downloads/repo/apt/). Choose
      the 9.7 LTS series when it asks. Check: `mysql --version` shows 9.7.
- [ ] **nginx:** Ubuntu's own package (1.24) is fine. Run `sudo apt install nginx`.
- [ ] **Node 22 or 24 LTS** for the deploy user, from NodeSource or nvm. It only builds the styles and
      scripts during a deploy. Check: `node -v` shows v22.12 or newer (v20.19 or newer also works).
      `deploy.sh` stops if Node is older.
- [ ] **Composer 2** from https://getcomposer.org/download/. Don't use the apt `composer` package: it
      installs PHP 8.3 alongside. Check: `composer --version`.
- [ ] **Other tools:** run `sudo apt install git unzip supervisor curl acl`.

## 4. PHP settings

- [ ] **PHP extensions,** for the command line and for the web (php-fpm):
      ```
      php -m | grep -icE '^(intl|zip|xml|dom|xmlreader|gd|pdo_mysql|mysqlnd|mbstring|fileinfo|openssl|iconv|ctype|tokenizer|curl|zlib)$'
      php-fpm8.5 -m | grep -icE '^(intl|zip|xml|dom|xmlreader|gd|pdo_mysql|mysqlnd|mbstring|fileinfo|openssl|iconv|ctype|tokenizer|curl|zlib)$'
      ```
      Both commands must print `16`. Composer does not check pdo_mysql or zlib, so a missing one only
      shows up when someone opens a page or downloads a receipt (zlib keeps the PDF receipt small).
- [ ] **Photo formats.** gd must read JPEG and PNG and write WebP (REQ-DB-15):
      ```
      php -r '$i = gd_info(); echo $i["JPEG Support"] && $i["PNG Support"] && $i["WebP Support"] ? "gd ok\n" : "gd is missing JPEG, PNG or WebP\n";'
      ```
- [ ] **Site settings.** Copy `php/jude.ini` to `/etc/php/8.5/fpm/conf.d/99-jude.ini`, then run
      `sudo systemctl reload php8.5-fpm`. The file sets memory 256M, uploads 6M, a 60-second limit and
      OPcache. Check: `php-fpm8.5 -i | grep -E '^(memory_limit|upload_max_filesize|post_max_size|max_execution_time) '`
      shows 256M, 6M, 8M and 60.
- [ ] **No disabled functions** that the site uses: `proc_open`, `symlink`, `ini_set` and
      `stream_socket_client`. Some hosting images disable them. Check: `php-fpm8.5 -i | grep disable_functions`
      and `php -r 'echo ini_get("disable_functions") ?: "none", PHP_EOL;'` must not list any of them.
- [ ] **Recommended: worker count.** In `/etc/php/8.5/fpm/pool.d/www.conf`, set `pm.max_children = 10`
      on 2 GB of memory, or 20 on 4 GB.

## 5. MySQL

- [ ] **Settings.** Copy `mysql/jude.cnf` to `/etc/mysql/mysql.conf.d/jude.cnf`, then run
      `sudo systemctl restart mysql`. MySQL then listens on this server only, and the slow-query log
      stays on (REQ-DB-02, 30). Check: `mysql -uroot -p -e "SELECT @@bind_address, @@slow_query_log, @@long_query_time"`
      shows `127.0.0.1  1  1.000000`, and `sudo ss -ltnp | grep mysqld` shows only 127.0.0.1.
- [ ] **Database and accounts.** In a copy of `mysql/grants.sql`, replace both passwords with long
      random ones. Keep them in a password manager, never in the repository or a chat. Then run
      `mysql -uroot -p < grants.sql` once. It creates the `jude` database, an app account that can only
      read and write rows, and a migration account for deploys (REQ-DB-03, 33, 34).
- [ ] **Account checks.** Both accounts connect through the socket:
      - `mysql -ujude_app -p -h localhost jude -e "SELECT CURRENT_USER()"` shows `jude_app@localhost`.
      - `mysql -ujude_app -p -h localhost jude -e "CREATE TABLE t (id int)"` is refused with
        `CREATE command denied`.
- [ ] **Never run `mysql/test-db.sql` on the server.** It is for local development only.

## 6. Domain, Cloudflare and nginx (the domain needs approval)

- [ ] **Domain on Cloudflare.** The domain is in the client's name, with its DNS on Cloudflare's free
      plan. Add an A record for `example.com` and `www`, pointing to the server, both **proxied**
      (orange cloud) (REQ-SEC-10). Add AAAA records too if the server has IPv6.
- [ ] **Cloudflare SSL/TLS** is set to **Full (strict)**, with **Always Use HTTPS** on.
- [ ] **Origin certificate.** Under Cloudflare > SSL/TLS > Origin Server, create one for `example.com`
      and `*.example.com`. Save it as `/etc/ssl/cloudflare/origin.pem` and the key as
      `/etc/ssl/cloudflare/origin.key`. The key is owned by root, mode 600.
- [ ] **nginx snippets.** Copy `nginx/cloudflare-real-ip.conf` and `nginx/jude-security-headers.conf`
      to `/etc/nginx/snippets/`. Compare the address list with https://www.cloudflare.com/ips/. Without
      it, many visitors would share one set of limits (5 orders an hour, sign-in tries).
- [ ] **The site.** Copy `nginx/jude.conf` to `/etc/nginx/sites-available/jude` and set the domain:
      `sudo sed -i 's/example\.com/THE-DOMAIN/g' /etc/nginx/sites-available/jude`. Then link it in and
      remove the default site:
      ```
      sudo ln -s /etc/nginx/sites-available/jude /etc/nginx/sites-enabled/jude
      sudo rm /etc/nginx/sites-enabled/default
      sudo nginx -t && sudo systemctl reload nginx
      ```
- [ ] **Static files** (REQ-CST-05). Cloudflare caches images, styles, scripts and fonts by file type on
      its own, and nginx marks them to keep. No page rules are needed.

## 7. Folders, settings file and permissions

- [ ] **Folders.** Create the release and shared folders, and give both users write access to storage:
      ```
      sudo mkdir -p /var/www/jude/releases /var/www/jude/shared/storage/app/public \
        /var/www/jude/shared/storage/framework/{cache/data,sessions,views} /var/www/jude/shared/storage/logs
      sudo chown -R deploy:www-data /var/www/jude
      sudo setfacl -R -m u:www-data:rwX -m u:deploy:rwX /var/www/jude/shared/storage
      sudo setfacl -dR -m u:www-data:rwX -m u:deploy:rwX /var/www/jude/shared/storage
      ```
      Check: `sudo -u www-data touch /var/www/jude/shared/storage/logs/check && sudo -u deploy rm /var/www/jude/shared/storage/logs/check`
      runs without an error.
- [ ] **Settings file.** Copy `env.production.example` to `/var/www/jude/shared/.env`, then fill in:
      - `APP_URL`: `https://example.com`.
      - `DB_PASSWORD`: the app account's password from section 5.
      - `APP_KEY`: create it once with `echo "base64:$(openssl rand -base64 32)"`. **Never change it
        afterwards.** It encrypts the sessions and the owner's two-step sign-in secret, so a new key locks
        the owner out.
      - Leave the `MAIL_` lines empty until the email service is chosen (section 9).

      Then lock the file: `sudo chown deploy:www-data /var/www/jude/shared/.env && sudo chmod 640 /var/www/jude/shared/.env`.
- [ ] **Deploy script.** Copy `deploy.sh` to `/var/www/jude/deploy.sh`.

## 8. Email worker, scheduler and restarts

- [ ] **Email worker.** Copy `supervisor/jude-queue.conf` to `/etc/supervisor/conf.d/`, then run
      `sudo supervisorctl reread && sudo supervisorctl update`. It sends the order emails and retries
      them if the email service fails (REQ-DB-13, 14). It starts properly after the first deploy.
- [ ] **Scheduler.** Run `sudo crontab -u www-data crontab` from this folder. Check:
      `sudo crontab -u www-data -l`. It runs the nightly backup (section 10) and the hourly disk check.
- [ ] **Restarts without a password.** The deploy user may restart exactly two things. Put this line in
      `/etc/sudoers.d/jude-deploy` (mode 440):
      ```
      deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.5-fpm, /usr/bin/supervisorctl restart jude-queue
      ```
      Check: `sudo visudo -cf /etc/sudoers.d/jude-deploy` says `parsed OK`.
- [ ] **Log rotation.** Copy `logrotate/jude` to `/etc/logrotate.d/jude`. It rotates the email
      worker's log and MySQL's slow-query log weekly. Check: `sudo logrotate -d /etc/logrotate.d/jude`
      shows no errors.

## 9. Outgoing email (the service needs approval)

- [ ] **Port 587 is open outgoing.** Some providers block outgoing email ports on new servers until you
      ask them. Check: `nc -vz smtp.gmail.com 587` says `succeeded`.
- [ ] **Choose the email service with the client in M4.** It must have a daily sending cap within its plan
      (REQ-CST-06). Then fill the `MAIL_` lines in `.env`. `MAIL_FROM_ADDRESS` must be on the store's
      own domain.
- [ ] **DNS records.** Add the SPF, DKIM and DMARC records from the email service's setup page in
      Cloudflare as **DNS only** (grey cloud), so the order emails don't land in spam.

## 10. Backups (the storage needs approval)

Every night the site backs up the database and the photos, encrypted, to separate storage, and keeps
each backup for 14 days (REQ-SAF-01 to 03, REQ-SEC-28). [RESTORE.md](RESTORE.md) says how to put one back.

- [ ] **Backup storage** on a fixed monthly plan that accepts SFTP, such as a storage box, in the client's
      name (REQ-CST-01, 02). A few GB is plenty for now.
- [ ] **A key for it.** The scheduler runs as `www-data`, so the key belongs to that user:
      ```
      sudo ssh-keygen -t ed25519 -N '' -C jude-backups -f /var/www/jude/shared/backup_key
      sudo chown www-data:www-data /var/www/jude/shared/backup_key /var/www/jude/shared/backup_key.pub
      sudo chmod 600 /var/www/jude/shared/backup_key
      ```
      Add `backup_key.pub` to the storage's authorised keys.
- [ ] **mysqldump** comes with Oracle's MySQL packages. Check: `mysqldump --version` shows 9.7.
- [ ] **Settings.** Fill in the `BACKUP_` lines in `.env`: the storage's address, port and user, and a
      long random `BACKUP_ARCHIVE_PASSWORD`. Without that password, no backup is made. Keep a copy of the
      password in a password manager, off the server.
- [ ] **Check.** Run `cd /var/www/jude/current && sudo -u www-data php artisan backup:run`, then
      `sudo -u www-data php artisan backup:list`. It shows one backup on the `backups` disk, marked healthy.
- [ ] **Try a restore once** while the site still holds only the demo data (RESTORE.md, part A).

## 11. First deploy

RafaqTech can do this with you. It is listed so that nothing is missed.

1. Log in as `deploy` and set the four values (the migration password stays on the server):
   ```
   export REPO=<git address of the project> BRANCH=main
   export MIGRATE_DB_USERNAME=jude_migrate MIGRATE_DB_PASSWORD=<migration password>
   ```
2. Run `cd /var/www/jude && bash deploy.sh`.
3. **First deploy only:** fill the store's starting content with
   `cd /var/www/jude/current && sudo -u www-data php artisan db:seed --force`. This adds the settings, the
   delivery cities and fees, the page texts and the demo's sample products. Run it once only: running it
   again resets the FAQs and terms. Which sample products stay for launch is settled in M4.
4. Create the owner's account: `sudo -u www-data php artisan jude:create-owner`. The owner sets up
   two-step sign-in the first time they sign in, and keeps the recovery codes.
5. Check: `sudo supervisorctl status jude-queue` shows `RUNNING`.

## 12. Final checks

- [ ] `https://example.com/up` answers 200.
- [ ] The home page (in both languages, with the العربية button) and `/admin/login` open over HTTPS. `http://` and `www.` both redirect to
      `https://example.com`.
- [ ] `curl -sI https://example.com | grep -icE '^(strict-transport-security|x-frame-options|x-content-type-options|referrer-policy):'`
      prints `4`.
- [ ] `cd /var/www/jude/current && php artisan about --only=environment` shows `production` and Debug Mode `OFF`.
- [ ] A wrong address, such as `/no-such-page`, shows the store's own error page, with no technical details.
- [ ] Upload a phone photo of about 4.5 MB to a product in the admin. It saves, and the photo shows on
      the product page. This checks gd, memory, upload size and timeouts in one go.
- [ ] Once email is set up, place a test order. The owner's and the customer's emails both arrive.
      Then set the test order to Cancelled.

## Still to come in M4 (RafaqTech)

- An uptime monitor that emails the owner within 5 minutes when the site is down (REQ-QLT-06).
- App-level security work and an OWASP ZAP scan of the live site (REQ-SEC-30).
- The list of every paid service with its exact price, for the client (REQ-CST-11).

## Send back to RafaqTech

When you're done, send RafaqTech:

- The server's address, the provider, and the plan with its monthly price.
- The deploy user's name.
- The MySQL and PHP versions installed.
- Any item above that you could not do.
