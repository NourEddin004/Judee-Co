# JUDE — storefront (HTML / CSS / JS)

The JUDE boutique site as plain static files: no server, no build step, no framework.
Open `index.html` in a browser, or upload the folder to any static host (GitHub Pages,
Netlify, Cloudflare Pages…).

Features (same as the ASP.NET version): catalog with category tabs (All, Sets, Hijabs,
Bundles), color filters, search and pagination; product pages with gallery, color options,
required size + size guide for sets, quantity and related products; wishlist and shopping bag;
cart drawer and toasts; JOD / USD prices; checkout with phone country code, city → area →
street address, delivery priced by city, cash on delivery and an optional confirmation
email; contact + FAQs, terms and a not-found page.

## Files

| Path | What it holds |
| --- | --- |
| `*.html` | One small file per page (`index`, `shop`, `sets`, `hijabs`, `bundles`, `product`, `cart`, `wishlist`, `checkout`, `confirmation`, `contact`, `terms`, `search`, `404`) |
| `js/data/site.js` | All page copy, links and store settings (products per page, max quantity, delivery cities…) |
| `js/data/catalog.js` | Products, color families, categories, size guide, sort options |
| `js/core.js` | Catalog filtering/sorting, currency formatting, bag + wishlist (saved in the browser's localStorage) |
| `js/components.js` | Header, menu, footer, cart drawer, product card, pagination |
| `js/pages.js` | Each page's content |
| `js/app.js` | Builds the page and runs dialogs, drawer, filters, gallery, size picker and checkout |
| `js/icons.js` | SVG icons |
| `css/tokens.css` | Every color, size, font and timing — change the look here |
| `css/components/*.css` | One file per component family; `css/main.css` loads them all |
| `brand-assets/` | Logo, product photos, hero rack images, Instagram / WhatsApp logos |

## Editing

- **Text, prices of delivery, FAQs, terms** → `js/data/site.js`
- **Products** → `js/data/catalog.js` (`image` is the card photo, `alternateImage` shows on hover,
  `gallery` is the product page). Put photos in `brand-assets/products/`.
- **Colors / fonts** → `css/tokens.css`

Links use `.html` pages: a product is `product.html?slug=black-wrap-set`, a filtered list is
`hijabs.html?shade=brown,gray&page=2`.

## Order emails (optional)

The old site sent the customer a copy of the order over SMTP. A static site can't hold SMTP
passwords, so it uses [EmailJS](https://www.emailjs.com) instead. Create a service and a
template whose "To email" is `{{to_email}}`, subject `{{subject}}` and body `{{message}}`
(also available: `{{to_name}}`, `{{order_number}}`, `{{from_name}}`), then fill in
`email.serviceId`, `email.templateId` and `email.publicKey` at the end of `js/data/site.js`.
Left blank, checkout still works and the confirmation page simply doesn't mention an email.

Orders themselves aren't sent anywhere — same as before, the order lives on the
confirmation page (and in the customer's email when set up).
