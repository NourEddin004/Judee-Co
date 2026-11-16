# Milestone 3: checkout and orders review notes

Scope of M3 (SRS 6.1): sections 3.3 (bag and checkout) and 3.4 (order management), the order emails,
and the search-engine setup. The status of every requirement ID is in
[requirements-status.md](requirements-status.md): 324 done, 70 planned for M4, 3 blocked.

## Run it locally

The quickest way needs only Docker (Docker Desktop or Colima): unzip, open a terminal in the folder and run `bash bin/preview`.
The first run takes a few minutes. It installs everything, fills the database with the demo data and
prints the addresses (http://localhost:8000, /ar and /admin) and the sign-ins. `bin/preview stop` stops it.

The manual steps are the same as in M1 and M2 (`docker compose up -d`, then `php artisan migrate:fresh --seed`), with two additions:

- Order emails wait in a queue. Locally they are written to `storage/logs/laravel.log` instead of being sent.
  Run `docker compose exec app php artisan queue:work --stop-when-empty` after placing an order to "send" them.
- Admin accounts are the M2 ones: `owner@jude.test` and `staff@jude.test`, password `password`. The owner's
  two-step secret is `JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP`.

Tests: `php artisan test` (305 tests) and `npm test` (2 tests) all pass. `composer audit` and `npm audit`
report nothing, and every direct dependency is on its latest release.

## What M3 covers

- **Checkout.** The form checks every field in the browser, then the server checks everything again. Prices
  and the delivery fee come from the database, never from the browser. Out-of-stock and hidden pieces are
  refused. The order and its items are saved in one step, each item keeping a copy of its name, colour,
  size and price. The order gets a 6-character number and a private 64-character link for its
  confirmation page. The bag is emptied after the order.
- **Abuse limits.** At most 5 orders an hour from one connection (refused orders don't count), a hidden
  anti-spam field, orders accepted only from the store's own pages, and fields not on the form are ignored.
- **Emails.** The owner gets every order, with a link to it in the admin. The customer gets a
  confirmation in the language they used, if they gave an email. Both go through the background queue
  and are retried 3 times if the email service fails.
- **Orders in the admin.** The list shows newest first, and it can be searched by order number, name or
  phone (typed in any format). Status chips show counts, and there are date and city filters. The order
  sheet shows the customer, the items, the fee and the total. One tap changes the status. There are team
  notes, a packing slip to print and a CSV export (it follows the chosen chip and filters). The owner and
  staff have the same order tools, as the SRS says.
- **Dashboard.** New orders, sales for the last 7 days and this month, products on the site and out of stock,
  rack items, the 5 latest orders, orders by status, and quick links. All figures are counted in the
  database.
- **Search engines.** There is a `sitemap.xml` of every public page in both languages, and a `robots.txt`
  that keeps out the admin, bag, wishlist, checkout and order pages. Public pages name their own address
  and the same page in the other language. Filtered and searched lists point search engines at the plain
  list. Private pages are marked "noindex" and never pass their address to other sites.
- **Disabled buttons say why (REQ-UX-27, decided 2026-10-24).**
  - Place order shows a reason line while the store is paused.
  - The quantity "+" shows "You can add up to 9 of one piece." when it stops, on the product page and in the bag.
  - A sold-out card's button reads "Out of stock".
  - The admin's hover-photo switch says why it is off for the main photo.
  - No line is added where the reason is already written, such as "Out of stock" on the product page.

## Decisions made while building

- **Note field at checkout.** An optional field under "Street and building". Confirmed on 2026-10-24: it stays.
- **Paused store.** REQ-CHK-17 closes checkout only. Shoppers can still browse and fill their bag, and
  "Place order" is off with the reason under it. This matches the admin switch's help text. If the owner
  pauses while someone is on checkout, the message shows at the top and the button turns off.
- **A piece sells out during checkout.** The piece is taken out of the customer's bag, and the message
  names it so they can check the order and place it again.
- **Sales card.** It is labelled "Sales, last 7 days", as REQ-ORD-27 says; the admin preview says "Sales
  this week". This month's sales sit under it (REQ-ORD-28). Cancelled orders never count as sales.
- **Order design.** C.11 was followed: the order step calls small interfaces (store open, stock, fee,
  prices, payment, saving, emails). C.10 and C.14 also name domain events. The activity log already
  records every change through model events, so no separate event classes were added.
- **CSV phone numbers start with an apostrophe** ('+962 ...). That stops a spreadsheet from reading a
  value as a formula. The number itself is unchanged.
- **Order list layout.** It uses the admin preview's row (photo, number and name, city and date, total
  and status), so it fits a phone without scrolling sideways. Arabic names and cities keep their own
  direction, so the date does not jump around them.
- **Dashboard figures load with the page**, so a phone shows them at once instead of after a second request.
- **Arabic orders** use the Arabic comma between address parts, on the confirmation page and in the email.
- **No extra index on the order date.** The SRS index table (3.7) has none, and the list stays fast at the
  expected number of orders. It is a one-line change if you want it.

## New texts waiting for the owner's approval (REQ-DMO-87)

The SRS has no wording for these, so I wrote English text and an Arabic draft:

- **Checkout:** "Note", its placeholder and its length message.
- **Checkout messages:** "Placing your order", "Something in your bag has changed", "... is no longer
  available", "Too many orders", and "Your order could not be placed".
- **Paused store:** "Orders are paused for now. Please check back soon."
- **Product cards:** "Out of stock" on a sold-out card's button.
- **Quantity limit:** "You can add up to 9 of one piece."
- **Customer email:** the greeting, intro and sign-off around the D.6.7 order details.

## Open questions

1. **Hosting:** the server is not chosen yet. Everything it needs is in
   [deploy/HOSTING-CHECKLIST.md](../deploy/HOSTING-CHECKLIST.md), for whoever sets it up.
2. **Email service:** it is chosen with the client before launch (M4), because it may cost money. The
   From address must be on the store's own domain.
3. **Git history:** two M2 commits contain a personal name, in a test fixture and in one review-note line.
   Both are fixed in the files now. If the repository will be handed to the client, I can rewrite those
   two commits before handover. That changes the history, so it needs your OK.
4. **PHPUnit:** the M1 notes say PHPUnit 12; the project now runs PHPUnit 13.4, the latest release.

## Deploy files (updated after the first M3 package)

Every deploy file was checked against what the code needs, and the server commands were tried on
Ubuntu 24.04 and MySQL 9.7.4. Four problems would have broken the launch, and they are fixed:

- **The database accounts would have been refused.** They are created for `localhost`, but the settings
  connected to `127.0.0.1`, which MySQL 9.7 treats as a different address. The site now connects through
  MySQL's socket.
- **The web server settings would not load** on Ubuntu 24.04's nginx (1.24), which does not know
  `http2 on`. The older form that works on every version is used now.
- **Photos over 2 MB would have failed to upload,** because PHP's default limit is 2 MB. The new
  `deploy/php/jude.ini` allows 6 MB and gives photo resizing 256 MB of memory.
- **Behind Cloudflare, visitors would have shared Cloudflare's addresses,** so many customers would have
  shared one limit of 5 orders an hour. nginx now reads each visitor's own address from Cloudflare, and the
  firewall accepts web traffic only from Cloudflare.

Smaller fixes:

- Built files now get the security headers too.
- The slow-query log stays on after a restart, and MySQL listens on the server only.
- `deploy.sh` stops if PHP or Node is too old, and caches the admin panel as well.
- The README's extension list is corrected, and new logrotate and PHP files were added.
- `mysql/test-db.sql` is marked local only.

The full test suite also passes on MySQL 8.4 LTS, apart from the check that the database is 9.7 LTS
(REQ-DB-01). So 9.7 stays required.

## Not in M3 (by plan)

M4 covers the following: security headers and Content Security Policy, server and firewall setup,
backups, speed checks with 1,000 products, the accessibility and Appendix D audits, the email service,
and launch. `docs/requirements-status.md` lists each one.

## Screenshots

`screenshots/` holds desktop (1440 px) and phone (390 px) captures:

- Checkout, filled in, in English and Arabic.
- The confirmation pages.
- The paused store, and a store paused while someone is on checkout.
- A piece selling out during checkout.
- The admin dashboard, orders list and order sheet, for the owner and staff.
- The packing slip.
