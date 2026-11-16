# Setup notes for developers

These notes are for a developer who takes over the JUDE Boutique store. They cover how the code is
organised, how to run it on a computer, how to test it, and where the server notes are. The
requirements are in the signed SRS; [requirements-status.md](requirements-status.md) lists every
requirement ID with the test or check that covers it.

## What it is built with

| Part | Version | Notes |
| --- | --- | --- |
| PHP | 8.5 | `composer.json` pins the platform to 8.5.12. |
| Laravel | 13 | Standard structure (REQ-QLT-04). |
| Filament | 5 | The admin panel at `/admin`. |
| MySQL | 9.7 LTS | The only database the code is tested on (REQ-DB-01). |
| Node | 22 or 24 | Only to build the styles and scripts with Vite 8. Nothing runs on Node in production. |
| spatie/laravel-backup | 10 | Nightly encrypted backups to SFTP storage. |
| mpdf/mpdf | 8.3 | The order receipt PDF, in English or Arabic (REQ-CHK-24, 25). Its licence is the GNU GPL version 2; see the version 1.2 section of [m4-review-notes.md](m4-review-notes.md). |

The store has no outside services except the order email service. There are no analytics, no fonts or
scripts from other sites and no paid APIs.

## How the code is organised

| Folder | What is in it |
| --- | --- |
| `app/Domain/*` | The business rules, one folder per area: Catalog (shop pages, search and filters, related products), Checkout (placing an order, delivery fees, phone numbers), Orders, Rack (the home page rack and its image fitting), Content, Delivery, Media (photo sizes), Settings, Activity (who changed what), Auth, Monitoring. Controllers and admin pages call these and hold no rules themselves (Appendix C.10 to C.14). |
| `app/Http/Controllers/Storefront` | The shop pages, bag and checkout endpoints. |
| `app/Filament` | The admin panel: one resource per kind of record (products, orders, rack items, FAQs and so on), plus the dashboard widgets and settings pages. |
| `app/Http/Middleware/SecurityHeaders.php` | The Content Security Policy and other security headers. Store pages allow only scripts with the page's nonce. |
| `resources/views/storefront` | The store's pages (Blade). They follow the demo's markup and class names. |
| `resources/css` | `tokens.css` holds every colour, size and spacing value (REQ-UX-01). The files in `components/` mirror the demo's. Never put a raw value outside `tokens.css`. |
| `resources/js/store` | The store's scripts: the bag (kept in the browser), shop filters, product page, checkout, menus and dialogs. |
| `lang/en`, `lang/ar` | The interface text. Texts the owner edits live in the database instead. |
| `database/seeders` | Settings, delivery cities and areas, page texts and the demo's sample products. `SpeedTestSeeder` fills a throwaway copy up to 1,000 products and 10,000 orders. |
| `reference/demo` | An untouched copy of the demo the store was built from (REQ-DMO-03). |
| `deploy` | Everything the server needs. Start with [deploy/README.md](../deploy/README.md). |
| `tools/checks` | The browser checks described below. |

## Running it on a computer

**Quickest:** with Docker installed, run `bin/preview`. It starts PHP 8.5 and MySQL 9.7 in containers,
builds the assets, fills the database with the demo data and opens http://localhost:8000. `bin/preview
stop` stops it and keeps the data; `bin/preview reset` starts again with fresh data. Use
`JUDE_PORT=8080 bin/preview` if port 8000 is taken.

**By hand** (the same steps as `compose.yaml`):

```sh
cp .env.example .env                      # then set DB_PASSWORD=secret
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link
npm ci && npm run build
```

On a computer, the seed also adds the owner's sign-in account, `owner@jude.test` with the password
`password`. The owner is the only account, and sets up two-step sign-in at the first sign-in. Order emails go to
`storage/logs/laravel.log` instead of being sent.

## Tests

```sh
docker compose exec app php artisan test   # PHP: about 430 tests
npm test                                   # the phone field and filter rules of the store's scripts
```

Each test names the requirement ID it covers, for example `req_dmo_23_pagination_shows_...`. The tests
use the `jude_test` database that `compose.yaml` creates. After adding or renaming a test, regenerate
[requirements-status.md](requirements-status.md) so it stays true.

## Checks in a browser

Some requirements can only be measured in a browser. The scripts are in `tools/checks` and use
Playwright with Chromium. Install them once, outside the project's dependencies:

```sh
npm install --no-save playwright axe-core
npx playwright install chromium
```

Run them against a store filled with the demo data. `TOKEN` is the `link_token` of any order in the
`orders` table (place one in the store first); it opens that order's confirmation page.

| Check | Command | What it measures |
| --- | --- | --- |
| Appendix D design values | `BASE=http://localhost:8000 TOKEN=... OUT=appendix-d.json node tools/checks/appendix-d.cjs` | 426 values from SRS Appendix D.5 and D.6 on 45 page views (REQ-DMO-01). It prints each value that does not match. |
| Accessibility | `BASE=http://localhost:8000 AXE=node_modules/axe-core/axe.min.js TOKEN=... OUT=a11y.json node tools/checks/accessibility.cjs` | axe-core WCAG 2.1 AA on 16 pages and states in English and Arabic, plus sideways scrolling, text size, side margins and image ratios at 9 widths (REQ-QLT-02, REQ-UX-*). |
| Version 1.2 behaviour | `BASE=http://localhost:8000 node tools/checks/version-1-2.cjs` | Choosing a colour without a reload (REQ-DMO-99) and "Continue shopping" in the bag drawer (REQ-DMO-101), in English and Arabic at 1440 and 390 px. |
| Speed | Google Lighthouse (`npx lighthouse <url> --only-categories=performance`), mobile settings, 3 runs per page | Needs a copy set up like the live server (nginx, HTTPS, production settings) filled by `SpeedTestSeeder` (REQ-PRF-01 to 04). See "Speed" in [m4-review-notes.md](m4-review-notes.md). |

## Settings that matter

- `APP_KEY` must never change after launch: it encrypts the owner's two-step sign-in secret.
- `BACKUP_ARCHIVE_PASSWORD` must be set on the server; without it no backup is made.
- The store's own settings (delivery fees, USD rate, contact details, the order email address, pausing
  the store) are in the admin panel under Settings, not in `.env`.
- Fixed numbers from the SRS sit next to the code that uses them, with the requirement ID: for example
  `config/store.php` (the most of one piece in the bag, the toast time, the phone countries) and
  `ShopQuery::PER_PAGE` (6 products per shop page).

## Server, deploys and backups

- Setting up a server: [deploy/HOSTING-CHECKLIST.md](../deploy/HOSTING-CHECKLIST.md).
- Each deploy: `deploy/deploy.sh` (keeps the last 5 releases).
- Putting a backup back: [deploy/RESTORE.md](../deploy/RESTORE.md), or `php artisan jude:restore-backup`.
- The first owner account on a server: `php artisan jude:create-owner`.
- Scheduled jobs (backups, the disk check) are in `routes/console.php`; the server's crontab runs them.

## Decisions to know about

The review notes of each milestone ([M1](m1-review-notes.md), [M2](m2-review-notes.md),
[M3](m3-review-notes.md), [M4](m4-review-notes.md)) list every place where the SRS needed a decision and
what was chosen, for example the spacing map, the whiter header glass and the two allowances the admin
panel's Content Security Policy needs.
