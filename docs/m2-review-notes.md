# Milestone 2: admin panel review notes

## How to try it locally

1. `docker compose up -d`, then `docker compose exec app php artisan migrate:fresh --seed`.
2. Open http://localhost:8000/admin.
3. Local accounts (created only in the local environment):
   - Owner: `owner@jude.test` / `password`. Two-step sign-in is required; the local secret is
     `JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP` (add it to any authenticator app).
   - Staff: `staff@jude.test` / `password`.

## What M2 covers

- Sign-in: rate limit, lock for 15 minutes after 5 failed tries, new-device email to the owner,
  no "remember me", owner two-step with recovery codes, password reset.
- Roles (SRS C.1): staff manage products, catalog lists, site texts, reviews, FAQs and terms.
  The owner can also manage the hero rack, delivery, settings, team (up to 3 staff) and the activity log.
- Products: create, edit, duplicate, hide, delete, photos (re-encoded to WebP with 400 and 800 px copies),
  filters and quick toggles.
- Hero rack: add, replace, reorder, resize, delete, with the C.12 image pipeline (trim, scale to rack
  height, hook alignment) and a live preview above the list. A warning shows when an image has no
  see-through background.
- Catalog lists: categories (each new category gets its own page), collections, colours, sizes and size guides.
- Content: site texts in English and Arabic, reviews, FAQs, terms page, SEO descriptions (160 characters).
- Settings: pause store, order email, USD rate, logo, contact details. Delivery cities and areas.
- Activity log of every admin change. Tables show 25 rows per page.
- Design: sidebar and colours from the demo tokens; bottom navigation on phones, like the admin preview.

## Decisions made while building

- Every uploaded image (products, rack, reviews, logo) is resized, re-saved as WebP and given smaller
  copies (400 and 800 px wide; 380 px tall for the rack). The original file is never kept or served.
  Pages list the copies (srcset), so a phone downloads the small copy and a computer the larger one.
  The demo images copied in by the seeders get the same copies. On the home, shop and product pages
  this cut image downloads on a computer from about 2.6 MB to 0.8 MB.
- REQ-UX-27 (decided 2026-10-24): a disabled button gets a short reason line, except where the page
  already shows the reason (for example "Out of stock" on the product page). Built in M3 with checkout.

- CMS-01..24 say "the owner" while C.1 lets staff manage products and content. C.1 was followed:
  the owner can do everything staff can, plus the owner-only pages.
- Delivery sits in the Settings group so the menu order matches the admin preview.
- New categories are served at `/{category}` (and `/ar/{category}`), so REQ-CMS-14 works for any new category.

## Open questions

- Hosting must have the PHP gd (with WebP) and intl extensions and `memory_limit` of at least 256M.

## Not in M2 (later milestones)

Checkout, orders and the dashboard numbers, emails and SEO pages are M3. Security scan, speed,
accessibility and launch prep are M4. See `docs/requirements-status.md`.
