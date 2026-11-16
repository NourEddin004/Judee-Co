# Milestone 1 review notes

Scope of M1: server-ready setup, the database (SRS 3.7), and the storefront pages with the design tokens.
The status of every requirement ID is in [requirements-status.md](requirements-status.md).

## Run it locally

The project runs on PHP 8.5 and MySQL 9.7 LTS. `compose.yaml` starts both with Docker:

```bash
composer install && npm install && npm run build
cp .env.example .env                                # DB_HOST=127.0.0.1, DB_PORT=3307
docker compose up -d                                # site on http://127.0.0.1:8000 and /ar
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed   # demo catalogue, rack, delivery list, texts
docker compose exec app php artisan test && npm test       # 100 PHP tests and 2 JS tests
```

## What is in M1

- Latest versions throughout: Laravel 13.38, PHP 8.5.12, Filament 5.10 (its screens come in M2), MySQL 9.7 LTS, PHPUnit 12, Vite 8. `composer audit` and `npm audit` report nothing.
- All tables of SRS 3.7 with their indexes and foreign keys, including the order tables M3 uses.
- Seed data taken from the demo: 39 products, 4 categories, the 5 rack garments, 12 delivery cities with 215 areas, FAQs, terms and every editable text.
- Storefront pages in English (/) and Arabic (/ar), right to left: home, shop, sets, hijabs, bundles, product, bag, wishlist, checkout, contact, terms, not found. The old demo .html addresses redirect with 301.
- The browser script: bag and wishlist in the browser, bag drawer, toasts, JOD/USD, filters, sorting, gallery, size check, quantity, and every checkout check of D.7.
- Deploy files in `deploy/` (Nginx, MySQL grants, queue worker, scheduler, deploy script). Nothing has been provisioned.
- `reference/demo` is an unchanged copy of the demo.

## Decisions taken while building

- **Related products: 6.** You chose REQ-DMO-26 over REQ-STR-13's 4.
- **Everything on the latest versions** (your decision): Laravel 13, Filament 5, PHP 8.5, MySQL 9.7 LTS, PHPUnit 12, Vite 8. MySQL 9.7 is the newest long-term release, newer than the 8.4 LTS.
- **2 px paddings.** D.5 lists 2 px paddings on the count bubble and size tag, but the D.4.1 map turns 2 px into 4 px. I used 4 px.
- **Field errors on leaving a field (REQ-UX-43).** An error shows when the customer leaves a field they filled in wrongly. Empty fields are flagged on "Place order", so tabbing through the form does not turn every field red.
- **Shade swatches** read their colour from the database, so colours the owner adds in M2 show correctly.

## Not in M1 (by plan)

- "Place order" checks every field in the browser, then stops. Saving the order, the order number, the confirmation page and the emails are M3.
- The admin panel screens are M2. Security headers, CSP, speed and accessibility audits are M4.

## Waiting on someone

- **Arabic font (REQ-STR-22):** IBM Plex Sans Arabic is a placeholder until the client approves a font.
- **Arabic texts (REQ-DMO-87):** every text has an Arabic draft written for women readers; the owner needs to approve them.
- **Terms sections:** four sections still hold the demo's instructions (Product presentation, Returns and exchanges, Fabric and care, Privacy) until the owner supplies the text.
- **REQ-UX-27 is unclear:** it asks every disabled control to show its reason. The size picker does; should the out-of-stock "Add to bag" button also show a line such as "Out of stock" next to it (the facts row already says so)?

## Screenshots

`screenshots/` holds desktop (1440 px) and phone (390 px) captures of each page in English and Arabic, plus the bag drawer, the size error, checkout errors and the area picker.
