# Milestone 4: security, speed and launch review notes

Scope of M4 (SRS 6.1): security hardening and the ZAP scan (L), speed and accessibility checks (M),
fixes from the client's review (N), the launch on the client's domain (P), and the training and admin
guide (Q). The status of every requirement ID is in [requirements-status.md](requirements-status.md).

This file grows as M4 goes on. Done so far: security, speed, accessibility and design, the check
against the design values of Appendix D, browser support, taking the wishlist out (SRS version 1.1), and
the client's changes of SRS version 1.2 (see the last section). Every check in this file was run again
after the version 1.2 changes.

## Security headers and Content Security Policy (REQ-SEC-17, 18)

- Every response from the site sends a Content Security Policy, HSTS, X-Frame-Options,
  X-Content-Type-Options and Referrer-Policy (`app/Http/Middleware/SecurityHeaders.php`). nginx adds
  the same headers to the files it serves itself, such as photos and the built styles
  (`deploy/nginx/jude-security-headers.conf`).
- **Store pages:** scripts and styles run only from the site itself, or inline when they carry a
  new random code (a nonce) given with each page. Nothing else can run, so injected code is blocked.
  To make this possible, the colour swatches and the hero rack's size adjustments no longer use inline
  `style` attributes.
- **Admin panel:** the admin panel's framework (Filament) needs two allowances that the store pages
  don't get. See "Scan findings accepted for the admin panel" below.

## The security scan (REQ-SEC-30)

OWASP ZAP 2.17 (baseline scan) was run against the local copy of the site. It reports no high-risk
finding. The live site is scanned again after launch, as the SRS requires.

### Scan findings accepted for the admin panel

These two medium findings are on the admin panel only (`/admin`). The owner agreed to accept them on
24 October 2026. The store pages that customers see have neither.

| Finding | Why it stays |
| --- | --- |
| CSP: script-src `unsafe-eval` | The admin panel's framework (Filament, with Livewire and Alpine.js) builds parts of its forms and tables from code it creates while the page runs. Without this allowance the admin panel stops working. |
| CSP: style-src `unsafe-inline` | The same framework sets sizes and colours with inline styles, which cannot carry a nonce. Without this allowance the admin panel's layout breaks. |

What limits the risk: the admin panel is reached only by signing in, the owner must use two-step
sign-in, every admin page escapes what it shows, and scripts still load only from the site itself.

### Low and informational findings

| Finding | Why it is fine |
| --- | --- |
| Cookie without HttpOnly | This is Laravel's `XSRF-TOKEN` cookie, a copy of the form protection code that is meant to be readable. The session cookie is HttpOnly, Secure and SameSite (REQ-SEC-21). |
| X-Content-Type-Options missing on images, styles and icons | Only on the local test server. On the live server nginx adds it to every file (tested). |
| X-Powered-By header | Only on the local test server. The live server's PHP settings turn it off (`expose_php = Off`). |
| Big redirect, timestamp in a script, modern web app, session cookie found, user text in a page attribute | Information only. Redirects contain only the next address, the timestamps are version numbers inside the admin framework's own script, and search text shown back on the shop page is escaped. |

## Code audit (REQ-SEC-07, 08, 15, 16, 42, REQ-DB-35, REQ-CST-08)

Every controller, view, script, settings file and deploy file was read against these requirements,
and the local site was probed with crafted addresses and form values. Each finding was then checked
again separately before it was accepted.

- **Customer details** are shown only to the signed-in owner (the only admin account since SRS version
  1.2), or to the customer through their order's private 64-character link. The order export and the
  packing slip need the owner's sign-in; the receipt opens only with the order's link (REQ-SEC-43).
  Search engines see only the catalogue and content pages.
- **Outside services:** customer details go only to the order email service. Nothing else is called:
  no analytics, no fonts or scripts from other sites, no paid APIs. Service keys stay in the server's
  settings file and never reach the browser.
- **Database and escaping:** no query puts typed-in text into SQL; search and filters go through bound
  values or fixed lists. Everything shown on a page is escaped, so text such as
  `<script>` in a search shows as text.

The audit found 5 smaller gaps in the new M4 code. All are fixed and tested:

| Gap | Fix |
| --- | --- |
| A few rare answers that Laravel gives before the site's code runs (a malformed address, the maintenance page) had no security headers. | The headers are now added outermost, so every answer gets them. |
| The admin panel's two allowances were chosen by address, so the store's "Page not found" page at an address starting with `/admin` or `/livewire` got them too. | They now follow the admin panel's own pages. |
| nginx's own error pages (a refused hidden file, the site being down) had no security headers. | nginx adds them to everything, replacing the site's copies on pages so each is sent once. |
| If the backup storage could not be reached, the restore said there were no backups. | It now says the storage cannot be reached. The restore runs as `www-data`, the user that owns the storage key. |
| Restoring a database-only backup (made by hand with `--only-db`) would have removed the photos. | Such a backup now leaves the photos as they are. |

One low item is left as it is, because no requirement covers it: the admin panel's "forgot password"
page says whether an email address has an admin account. Sign-in attempts are limited and the owner
must use two-step sign-in, so knowing the address is not enough to get in.

## Other security settings

- Session cookies are Secure, HttpOnly and SameSite=Lax on the live site (REQ-SEC-21).
- Debug mode is off on the live site, and error pages show no technical details (REQ-SEC-26, 27).
- The checkout form sends by POST even before its script loads, so an order's details never appear in
  an address.

## Backups (REQ-SAF-01 to 04, REQ-SEC-28)

- Every night at 01:30 (Jordan time) the server backs up the database and the uploaded photos. Each
  backup is encrypted with AES-256 and copied to separate storage over SFTP. Backups are kept 14 days.
- The owner gets an email if a backup fails or none is made for a day.
- Without the backup password set, no backup is made, so an unencrypted backup can never be stored.
- One command restores a backup: `php artisan jude:restore-backup`. It asks before it replaces anything.
  [deploy/RESTORE.md](../deploy/RESTORE.md) has the steps, for the same server or a new one.
- **Tested for real with MySQL 9.7:** a backup of the demo store (11.6 MB) was made with the site's own
  limited database account, then restored with the migration account in 3 seconds. The data and the
  photos came back as they were.
- The separate storage (an SFTP storage box) costs money, so it waits on approval. Until then, backups
  go to a folder on the same server.

## Disk space (REQ-CST-09, 10)

- Every hour the server checks its disk. At 80% full the owner gets an email, at most once a day
  while it stays full.
- When a photo is replaced or deleted, or its product is deleted, all its sizes are deleted too (tested).

## Speed (REQ-PRF-01 to 04, REQ-DB-24)

Measured on a copy of the store set up like the live server (nginx with HTTPS and HTTP/2, production
settings, caches on), filled with **1,000 products and 10,000 orders**. `SpeedTestSeeder` makes that
data on a throwaway copy.

Everything below was measured again on 25 October 2026, after the version 1.2 changes, with the products
held as styles with their colours (1,000 products with 2,426 colours).

**Store pages, Google Lighthouse 13.5, mobile (slow 4G, slower phone), 3 runs each:**

| Page | Performance (85 needed) | Main content shown (2.5 s needed) |
| --- | --- | --- |
| Home | 96 to 98 | 2.1 to 2.3 s |
| Shop | 98 to 99 | 2.0 to 2.3 s |
| Product with one colour (Rosemary Hijab) | 98 | 2.3 s |
| Product with 4 colours (Wrap Set) | 98 to 99 | 1.8 to 2.0 s |
| Home, Arabic | 99 to 100 | 1.5 to 2.0 s |
| Shop, Arabic | 98 to 99 | 1.9 to 2.1 s |
| Product with one colour, Arabic | 97 to 98 | 2.3 to 2.4 s |
| Product with 4 colours, Arabic | 98 to 99 | 1.9 to 2.0 s |

Accessibility, best practices and search engine scores were 100 on every run. A product with several
colours keeps each colour's photos in the page, but they load only when that colour is chosen, so it is as
fast as a product with one colour. The live site adds the distance between Cloudflare and the server, so
the pages are measured again after launch.

To get there, M4 changed how photos and fonts load, without changing how anything looks:

- The first row of shop photos loads at once; photos further down still load only when scrolled near.
- The home page fetches its headline font together with the stylesheet.
- The hero rack and product photos tell the browser their real size on screen, so a phone downloads
  the small copies. On the home page alone this saves about 110 KB.
- Every uploaded photo now also gets 200 and 600 px copies. Product thumbnails use the 200 px ones.
- Photo copies are saved at WebP quality 75 instead of 82. Side by side they look the same, and the
  files are about a quarter smaller.
- nginx compresses styles and scripts.

**Admin, with 1,000 products and 10,000 orders (2 s needed):** the server answered every admin page in
0.36 s or less, and every page was ready in the browser within 0.84 s. The dashboard, the product and order
lists, search, an order, the packing slip and the settings pages were fully loaded, with nothing left to
download, in 1.5 s or less. Switching the orders list to another status took 0.22 s.

**A product's form** is ready to use within 1.5 s. Its photo previews then appear one by one, because each
photo field asks the server for its file: on a product with 6 or 8 photos the last one shows after about
2 s, and on the Plain Hijab, with 11 colours, after about 3 s. Since version 1.2 the previews load the
400 px copy of each photo instead of the full photo, which is about 7 times smaller, so the owner's phone
downloads much less on mobile data. If every preview has to show within 2 s, the photo fields need a
different design; that is waiting on a decision.

**Database (100 ms per query allowed):** every query behind the store pages was logged while every
kind of page was opened: shop pages, the last page and one past it, each category, colour and size filters
alone and together, searches in English and Arabic, products with one and with several colours, an old
product address, the bag check with 50 lines, the bag, checkout, an order's confirmation page and its
receipt, and the sitemap. On a warmed-up copy the slowest of 2,755 queries took 20 ms. The first run,
straight after the data was loaded, had one slower query: a size and a colour filter together took 80 ms,
still within the limit; the same query takes 3 ms when run again.

**Found while testing, and fixed:** shop addresses with a list where text belongs (such as
`?q[]=x`), and form or search text with bytes that are not valid text, gave an error page
instead of the shop or a normal answer. Both now work, and are tested.

## Accessibility and design (REQ-QLT-02, REQ-UX-01 to 56)

**Automatic scan:** axe-core 4.14 with the WCAG 2.1 A and AA rules, on 16 pages and states in English and
Arabic (32 page views): every shop page, a product with and without sizes, the bag and checkout
(empty and filled), contact, terms, the order confirmation and "Page not found", plus the open menu, the
open bag drawer, the size guide and the checkout's error messages. Each page view was also measured at 9
widths from 360 to 1920 px for sideways scrolling, text under 11 px, heading line height, the 16 px side
margin, image ratios set before loading and the text colours allowed on blush-200. **Result: no
violations.**

**Colour contrast the scan cannot judge** (text on the see-through header, the hero gradient and photo
badges) was measured pixel by pixel while pages scrolled under the header. Badges are 6.5:1 or better and
the hero text 6.2:1 or better. The header links fell to about 3.4:1 over dark or red photos with the demo's
glass, so the owner chose a whiter glass on 24 October 2026; they now stay at 5.2:1 or better.

**Fixed:**

| Found | Fix |
| --- | --- |
| Some buttons were named differently from the words they show ("Add Rosemary Hijab to cart" on a button that says "Add to cart"), which breaks voice control (WCAG 2.5.3). The bag icon did not say its count. | Names now start with the shown words ("Add to cart: Rosemary Hijab"), and the bag icon says "Open bag, 3 items". |
| The checkout's phone field pushed the page sideways on phones 360 to 414 px wide, worst in Arabic. | The form's column can no longer grow past the screen. |
| Several headings used line height 1.5 or 1.1. | Headings use 1.2; only the display titles Appendix D.3, D.5.12 and D.6 name keep theirs. The trust strip text now uses 1.3 as Appendix D.6.1 says. |
| On a slow connection the bag and checkout showed "Your bag is empty" or 0 JD until the store answered (REQ-UX-38). | Grey placeholders of the same size show instead: at once when the page opens, and after 300 ms for later changes. |
| The sort menu was hidden on computers, as in the demo, where that row held only the phone filter button (REQ-STR-06). | The row showed on every screen size. Version 1.2 then took the sort menu out; the row now shows only when it holds something: the Filters button below 1024 px, or a search chip. |
| The gallery thumbnails' name was on an element without a role. | They are a named group. |

**Checked in the browser:** 14 kinds of tap (menu, add to cart, bag drawer, filters, size, quantity, add to
bag, gallery, bag line plus and remove, place order, another colour, Continue shopping in the bag drawer, FAQ)
each change the screen within 37 ms with the processor slowed 4 times (REQ-UX-29); a colour or size filter on
a computer applies at once by opening the filtered page. Place order keeps its size while its spinner shows
(REQ-UX-28).

**CSS review, as tests** (`StorefrontCssTest`): transitions last 150, 200 or 300 ms with the standard easing,
only the hero rack animates longer, animations only move or fade, headings use line height 1.2, and the shop
has no sort menu, its toolbar hiding only when it is empty.

**Error messages** (REQ-UX-40, 41): each one names the problem and says how to fix it. The checkout ones
use the SRS's own wording.

**Before and after for the client** (REQ-UX-55): the 21 visible changes from the demo, each with a
screenshot of the demo next to the new store, are in the document "JUDE store: changes from the demo".
Changes 17 to 21 are the client's version 1.2 requests. The images are also in the M4 package, in
`before-after/`.

## Design values of Appendix D (REQ-DMO-01, 02)

**How it was checked:** `tools/checks/appendix-d.cjs` opens the store in Chromium and measures 426 values
from Appendix D.5 and D.6 on 45 page views: every page from 360 to 1920 px wide, with the menu, currency
menu, bag drawer, filter panel, area picker, toast and error messages open, and hover and pressed states.
For version 1.2 it also measures the Size filter, the colour dots on product cards, the colour choice on
the product page and the "Download receipt" button, and checks that the shop has no sort menu.
`AppendixDTokensTest` checks every colour, size, spacing step, radius, shadow, motion and layer token
against the tables of D.2 to D.4. **Result: every value matches.**

**Found and fixed:**

| Found | Fix |
| --- | --- |
| The sort menu sat at the left of its row. | It moved to the right of the toolbar, as D.5.7 then said. Version 1.2 took it out. |
| The hero started 112 px from the top on tablets and computers. | 104 px (72 + 32), as D.6.1 says. |
| On phones the badge sat 7 px from the photo's corner. | 8 px, as D.5.2 says. |
| Trust strip text line height and the sort menu hidden on computers. | Fixed earlier, see "Accessibility and design". |
| Version 1.2: the Size filter section started 33 px under the last colour. | The colours end 24 px above the line that starts the Size section, as D.5.7 says, and the Size label sits 16 px under that line. |

**Where the appendix disagrees with itself:** in 6 spots the value written for a part differs from what
the spacing map (D.4.1) makes of the demo's value. The written values are used; the owner is confirming.

| Part | Written (used) | Demo, converted |
| --- | --- | --- |
| Product card text area, bottom padding | 16 px on phones, 24 px from 769 px | 12 px, 16 px |
| Product page, space under the back link | 48 px | 16 px |
| Bag page lines, gap between columns | 24 px | 16 px |
| FAQ question height from 769 px | 64 px | 70 px |
| Terms page bottom padding on phones | 96 px | 64 px |
| Contact card icons | 19 px | 24 px |

**Read this way:** where D.5 gives a value that is not one of the 10 spacing steps (2 px paddings, the
20 px above the confirmation's "Thank you"), the spacing map applies, as decided in M1. Where D.5 gives
one padding for a part the demo pads on two sides only (bag page column titles, area options), it is
applied on those two sides, so the columns and the text stay lined up.

## Browser support (REQ-QLT-01)

The target is the last two versions of Chrome, Safari, Firefox, Edge and Samsung Internet. In October 2026
that is Chrome 155 and 156, Safari 26.6 and 27 (Mac and iPhone), Firefox 157 and 158, Edge 154 and 155,
and Samsung Internet 29 and 30.

**Code check:** every style and script feature the store uses was looked up for those versions in MDN's
browser data (8.1.7) and caniuse (doiuse 6). Nothing the store needs is missing in any of them. Some
small things behave differently and do no harm:

- On iPhones, selected text keeps the system's blue highlight instead of rose.
- On Android, the filter checkboxes may keep the system's colour instead of rose-strong.
- Cursor styles (such as "not-allowed") do not apply on touch screens, which have no cursor.

**Tested in a browser:** every automatic check in this file (the accessibility scan, Appendix D, speed,
taps) ran in Chromium 153, the engine of Chrome, Edge and Samsung Internet.

**Still to do by hand, during the client review:** Safari and Firefox use their own engines, which cannot
run here. These 9 steps take about 10 minutes per browser, on an iPhone (Safari), a Mac (Safari) and any
computer with Firefox:

1. Home: the hero rack slides in and sways; the header stays readable while scrolling over photos.
2. Shop: pick two colours and a size, go to page 2. On a phone, open Filters and tap Apply filters.
3. Search "olive", then clear the search with the chip.
4. Product page with sizes: choose another colour (the photos, name and address change), tap Add to bag without a size (message shows), choose M, set 3, add.
5. Open the bag: change the amount, remove a line, tap Continue shopping (the bag closes), open it again and tap Proceed to checkout.
6. Checkout: submit empty (errors and the summary at the top show), pick a city, search and choose an area, type a phone number (letters are not taken, and it stops at the country's length), place the order, tap Download receipt.
7. Switch to USD and back; prices change everywhere.
8. Open العربية: the page reads right to left, prices and sizes keep their order.
9. Contact: open both FAQ answers; the WhatsApp and Call buttons open the right apps.


## Featuring a product on the home page (REQ-CMS-09, REQ-DMO-25)

Found while writing the admin guide: the product form's switch "Feature on the home page" did not change the
home page. The home page shows products only as the three looks beside the about text, and those are picked
in Site texts (Appendix D, "Home about (editable)"). The switch only ranks a product higher in "Related
products" (REQ-DMO-25).

The switch is now called **Featured** and says what it does; the CMS-09 test now features a product by
picking it as one of the three looks. Waiting on the owner's choice: keep picking the looks in Site texts
(done this way now), or let the switch pick them (the first three featured products by display order).

## Wishlist taken out (SRS version 1.1)

On 24 October 2026 the owner took the wishlist out, because customers have no accounts. The SRS and the
scope document are now version 1.1. Requirement and section numbers were kept, so REQ-CHK-02, REQ-DMO-35
and D.6.5 no longer appear, and Appendix D.9 lists it among the differences from the demo.

- Gone from the store: the hearts on product cards and the product page, the wishlist page (/wishlist and
  /ar/wishlist), and the wishlist links in the header, the phone menu and the footer. /wishlist and the
  demo address wishlist.html now answer "Page not found".
- The phone menu's row under the links has one cell, Cart (D.5.4), and the footer's "Your bag" column
  lists Cart only (D.5.5).
- Also removed: the address that sent the wishlist its product cards (/bag/cards), the scripts, styles and
  texts in both languages, the heart icon, and the "Wishlist page" search engine text in Store settings.
  A database made before this change may still hold that empty text; nothing reads it, and it was left in
  place because removing data needs the owner's go-ahead.
- Kept: the bag's empty texts "Start with a favorite from the collection." and "Explore the collection and
  add your favorites here." are the SRS's own wording (D.8.1) and do not refer to the wishlist.
- Before and after pictures 1, 2, 4, 10, 11 and 15 were taken again without the hearts, and number 16
  (no wishlist) was added.
- Tests: `BagOnlyTest` checks that no page has a heart or a wishlist link, that the wishlist addresses are
  gone, that the menu and footer link only to the bag, and that the bag is the only list kept in the
  browser (REQ-DMO-32). The accessibility scan, the Appendix D check and the tap timings above were run
  again after the change.

## The client's changes (SRS version 1.2)

On 25 October 2026 the owner asked for seven changes. The SRS and the scope document are now version 1.2,
with the same price and dates (SRS 6.4). Requirement and section numbers were kept, so the IDs that were
taken out (sorting and the staff account) no longer appear; [requirements-status.md](requirements-status.md)
names them. Every check in this file was run again after the changes.

| Asked for | What the store does now | Requirements |
| --- | --- | --- |
| A receipt to download after ordering | "Download receipt" on the confirmation page, above "Continue shopping", gives a PDF named `JUDE-order-{number}.pdf`: the order, each item with its size, quantity and price, the amounts and the payment method, in the order's language (right to left in Arabic). It opens only with the order's private link, and no cache keeps a copy. | REQ-CHK-23 to 25, REQ-SEC-43, REQ-DMO-102, D.6.12 |
| A size filter | The filter panel has a Size section under Color with the sizes of the category's products, in size order. A size and a colour together show only products that have both. | REQ-STR-23, REQ-DMO-92 to 95, D.5.7 |
| No sort menu | Products show in the owner's display order, then by name. A `?sort=` in an old address is ignored. | REQ-DMO-08 |
| Bag drawer buttons | "Proceed to checkout" and "Continue shopping". The second closes the drawer and puts the focus back on the bag button. The cart page is still linked from the phone menu and the footer. | REQ-DMO-101, D.5.9 |
| No staff side in the admin | Only the owner signs in, always with two-step sign-in. The Team page and the staff role are gone, and every admin page and action checks on the server that the user is the owner. | REQ-USR-01, 03, REQ-SEC-39 |
| Phone number: digits only, the country's exact length | The field keeps only digits (Arabic-Indic digits become 0 to 9) and stops at the country's longest number. Checkout takes a number only with the digits that country uses, for example 9 after +962, not counting a 0 at the start. | REQ-CHK-21, 22, REQ-DMO-56, 57, 89 to 91, D.8.3 |
| Products as styles with colours | One product holds all its colours in one form, each with its own photos, stock state and swatch, in the owner's order. The shop shows one card per product with a dot for each colour. The product page changes colour without reloading and puts `?color=` in the address. | REQ-STR-24, 25, REQ-CMS-43 to 46, REQ-DB-37, REQ-DMO-96 to 100, 103 |

**Decisions and things to know:**

- **No account was deleted.** A staff account made before the change stays in the activity log, so its
  past changes still show who made them, but it can no longer sign in. Deleting it needs the owner's
  go-ahead.
- **Existing products are moved, not lost.** The database update turns the products of one category that
  share a style into the colours of one product, named without the colour ("Navy Wrap Set" becomes "Wrap
  Set"). Orders, rack garments, collections and sizes follow, and each old product address redirects to its
  colour (REQ-DMO-103). The update can be undone with `php artisan migrate:rollback`, which splits the
  colours back into products. Both ways were tried on a copy of the M4 data.
- **Names with colours.** Wherever one piece is meant (the bag, checkout, the confirmation page, emails,
  the receipt and toasts), a product with two or more colours is named with its colour: "Navy Wrap Set",
  and in Arabic the colour after the product name. A product with one colour keeps its own name.
- **The bag in the browser** now holds colours, under a new name (`jude.bag.v2`), so a bag filled before
  the update starts empty once. The store is not live yet, so only test browsers see this.
- **The receipt library.** The server makes the PDF with mPDF 8.3, which is free and open source under the
  GNU GPL version 2. It was chosen because it lays out Arabic correctly (joined letters, right to left),
  which REQ-CHK-25 needs. The GPL sets conditions only when the code itself is passed to someone else:
  whoever receives a copy also gets the source and may share it on the same terms. Running the store and
  giving customers their PDF does not pass the code on, and the client receives the full source anyway.
- **Tests and checks:** each new requirement ID has a test with the ID in its name. In a browser,
  `tools/checks/version-1-2.cjs` chooses colours on the product page (no reload, `?color=` in the address,
  the other language keeps the colour) and uses "Continue shopping" in the bag drawer, in English and Arabic
  at 1440 and 390 px. The Appendix D check measures the new parts, and the accessibility scan and tap
  timings above include them.

## One address for both languages (SRS version 1.3)

On 2 November 2026 the owner asked for each page to keep the same address in English and Arabic, with
the language button changing only the language.

- **How it works.** Every page has one address, such as `/shop` or `/product/wrap-set?color=navy`. The
  language button opens the same address with `?lang=ar` (or `?lang=en`); the server saves the choice in a
  cookie called `jude_lang` for one year and sends the browser straight back to the address without
  `?lang`, so the address bar shows the same address. A visitor with no saved choice sees English. The
  cookie holds only `en` or `ar` and is not encrypted, so the Not Found page can read it too.
- **Old Arabic addresses.** `/ar` and every `/ar/...` address save Arabic and redirect to the same page
  without `/ar`, with the same query string (a temporary 302 redirect, because it also saves a choice).
- **Search engines.** One address can be listed by Google in one language only, and Google reads pages
  without cookies, so it will see and list the English text. The sitemap now lists each page once, and
  the `hreflang` links are gone because there is no separate Arabic address to point to. Customers who
  search in Arabic are less likely to find the store; a separate Arabic address is the only way to get
  Arabic pages listed. The owner chose the single address knowing this.
- **Caching.** Pages now send `Vary: Cookie`, so no cache between the store and the customer keeps one
  language for everybody.
- **Unchanged.** Orders still keep the language the customer used at checkout; the order email and the
  receipt PDF use it, whatever the language of the browser that opens the receipt.
- **Tests and checks:** `PagesTest` covers the button, the saved choice, the query string and the old
  `/ar` addresses; `SearchEngineTest` covers the one-address sitemap and the canonical link.
  `tools/checks/version-1-2.cjs` and `accessibility.cjs` now set the language with the cookie.
