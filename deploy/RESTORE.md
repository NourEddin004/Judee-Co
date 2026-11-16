# Restoring from a backup

The site must be back within 4 hours (REQ-SAF-04). Every night at 01:30 (Jordan time) the server backs
up the database and the uploaded photos. Each backup is encrypted and copied to separate storage, and
kept for 14 days (`config/backup.php`).

One command puts a backup back: `php artisan jude:restore-backup`. It replaces the whole database and
every uploaded photo with the chosen backup, then clears the caches. It took 3 seconds for the demo
store. Most of the 4 hours is there for setting up a new server, if the old one is gone.

## Keep these off the server

Store them in a password manager, because without them a backup cannot be used:

- `BACKUP_ARCHIVE_PASSWORD`. Every backup is encrypted with it.
- The backup storage's address, user name and SSH key.
- `APP_KEY`. Without it, the owner has to set up two-step sign-in again, and everyone is signed out.

## A. The server works, but the data is wrong

For example, something was deleted by mistake, or the database is damaged.

1. Note the orders placed since the last backup. The owner's order emails list each one. A restore
   brings the database back to the night of the backup, so later orders are lost.
2. Log in with an account that can use `sudo`. Every command below runs as `www-data`, the user that
   owns the photos and the backup storage's key. Take the site offline:
   `cd /var/www/jude/current && sudo -u www-data php artisan down`
3. List the backups, and pick one: `sudo -u www-data php artisan jude:restore-backup --list`
4. Restore it: `sudo -u www-data php artisan jude:restore-backup <file name>`. Leave out the file name
   to restore the newest backup. It asks for the migration account (`jude_migrate`) and its password,
   because a restore rebuilds the tables, which the site's own account may not do. Then answer yes.
5. Bring the site back: `sudo -u www-data php artisan up`
6. Check the home page, a product page, the admin sign-in and the latest orders. Enter again any orders
   noted in step 1.

## B. The server is gone

1. Set up a new server with [HOSTING-CHECKLIST.md](HOSTING-CHECKLIST.md), sections 1 to 10. In `.env`,
   use the same `APP_KEY` and the same `BACKUP_` values as before.
2. Deploy as in section 11, steps 1 and 2 only. Skip `db:seed` and `jude:create-owner`, because the
   backup holds the content and the accounts.
3. Restore as in part A, steps 2 to 6.
4. Point Cloudflare's DNS records at the new server's address.

## Checking the backups

- `sudo -u www-data php artisan backup:list` shows the newest backup, how many there are and whether they are healthy.
- When a backup fails, or none is made for a day, an email goes to `BACKUP_NOTIFY_EMAIL`.
- Try part A once on the server before launch, while it still holds only the demo data.
