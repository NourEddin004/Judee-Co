// Shared page parts: header, mobile menu, footer, cart drawer, product card, cart line,
// empty state and pagination. Each returns an HTML string.
(() => {
  "use strict";

  const { site, catalog, currency, cart, wishlist, esc, fmt, attr, opt, icon } = window.Jude;

  /** Brand logo (header, mobile menu, footer); falls back to the text wordmark. */
  const brandLockup = () => {
    const brand = site.brand;
    return `<span class="ds-brand">${
      brand.logo
        ? `<img class="ds-brand__logo" src="${esc(brand.logo)}" alt="${esc(brand.fullName)}" />`
        : `<span class="ds-brand__wordmark"><span class="ds-brand__name">${esc(brand.name)}</span><span class="ds-brand__subtitle">${esc(brand.subtitle)}</span></span>`
    }</span>`;
  };

  const isCurrent = (item, page) => item.match.includes(page);

  const header = (page) => {
    const current = currency.current;
    const cartCount = cart.count();
    const wishlistCount = wishlist.count();
    const nav = (linkClass) =>
      site.navigation
        .map((item) => `<a href="${esc(item.url)}" class="${linkClass}"${opt("aria-current", isCurrent(item, page) ? "page" : null)}>${esc(item.label)}</a>`)
        .join("");

    return `
<header class="ds-header">
  <div class="ds-header__bar">
    <button type="button" class="ds-icon-btn ds-header__menu" aria-label="Open menu" aria-controls="mobile-menu" aria-expanded="false" data-modal-open="mobile-menu">
      ${icon("menu")}
    </button>
    <a href="index.html" class="ds-header__logo" aria-label="${esc(site.brand.fullName)} home">${brandLockup()}</a>
    <nav class="ds-header__nav" aria-label="Main">${nav("ds-header__link")}</nav>
    <div class="ds-header__tools">
      <button type="button" class="ds-text-btn ds-header__desktop-only" data-notice="${esc(site.notices.arabic)}">العربية</button>
      <div class="ds-dropdown ds-header__desktop-only" data-dropdown>
        <button type="button" class="ds-text-btn ds-currency__toggle" aria-haspopup="menu" aria-expanded="false" aria-label="${esc(currency.label)}: ${esc(current.name)}" data-dropdown-toggle>
          ${esc(current.code)} ${icon("chevron-down", "ds-icon--sm")}
        </button>
        <form class="ds-dropdown__menu ds-dropdown__menu--end" role="menu" hidden data-dropdown-menu data-ajax="currency">
          ${currency.options
            .map(
              (option) => `
          <button type="submit" name="code" value="${esc(option.code)}" role="menuitemradio" aria-checked="${option.code === current.code}" class="ds-dropdown__item">
            <span>${esc(option.code)} <span class="ds-currency__name">${esc(option.name)}</span></span>
            ${option.code === current.code ? icon("check", "ds-icon--sm") : ""}
          </button>`,
            )
            .join("")}
        </form>
      </div>
      <a href="wishlist.html" class="ds-icon-btn ds-header__desktop-only" aria-label="Wishlist">
        ${icon("heart")}
        <span class="ds-count" data-count="wishlist"${attr("hidden", wishlistCount === 0)}>${wishlistCount}</span>
      </a>
      <a href="cart.html" class="ds-icon-btn" aria-label="Open bag" aria-controls="cart-drawer" aria-expanded="false" data-modal-open="cart-drawer">
        ${icon("shopping-bag")}
        <span class="ds-count" data-count="cart"${attr("hidden", cartCount === 0)}>${cartCount}</span>
      </a>
    </div>
  </div>
</header>

<div class="ds-modal ds-modal--menu" data-modal="mobile-menu" hidden>
  <div class="ds-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile navigation">
    <div class="ds-menu__head">
      ${brandLockup()}
      <button type="button" class="ds-icon-btn" aria-label="Close menu" data-modal-close>${icon("x")}</button>
    </div>
    <nav class="ds-menu__nav" aria-label="Main">${nav("ds-menu__link")}</nav>
    <div class="ds-menu__utilities">
      <a href="wishlist.html" class="ds-menu__utility">${icon("heart")} Wishlist <span class="ds-menu__count" data-count-text="wishlist">${wishlistCount}</span></a>
      <a href="cart.html" class="ds-menu__utility">${icon("shopping-bag")} Cart <span class="ds-menu__count" data-count-text="cart">${cartCount}</span></a>
    </div>
    <div class="ds-menu__languages">
      <button type="button" class="ds-menu__language" data-notice="${esc(site.notices.arabic)}">العربية</button>
      <form class="ds-menu__currency" aria-label="${esc(currency.label)}" data-ajax="currency">
        ${currency.options
          .map(
            (option) =>
              `<button type="submit" name="code" value="${esc(option.code)}" class="ds-menu__currency-option" aria-pressed="${option.code === current.code}" title="${esc(option.name)}">${esc(option.code)}</button>`,
          )
          .join("")}
      </form>
    </div>
  </div>
</div>`;
  };

  const footer = () => {
    const { instagram, phone, footer: content } = site;
    const whatsAppUrl = `https://wa.me/${phone.international}`;
    return `
<footer class="ds-footer">
  <div class="ds-footer__grid">
    <div class="ds-footer__brand">
      <a href="index.html" class="ds-footer__logo" aria-label="${esc(site.brand.fullName)} home">${brandLockup()}</a>
      <a href="${esc(instagram.url)}" target="_blank" rel="noreferrer" class="ds-footer__link ds-footer__link--brand">
        <img class="ds-footer__brand-icon" src="${esc(instagram.logo)}" alt="" /> ${esc(instagram.handle)}
      </a>
      <a href="${esc(whatsAppUrl)}" target="_blank" rel="noreferrer" class="ds-footer__link ds-footer__link--brand" aria-label="${esc(phone.whatsAppLabel)} ${esc(phone.display)}">
        <img class="ds-footer__brand-icon" src="${esc(phone.whatsAppLogo)}" alt="" /> ${esc(phone.display)}
      </a>
    </div>
    <nav class="ds-footer__nav" aria-label="Footer navigation">
      ${content.columns
        .map(
          (column) => `
      <div class="ds-footer__column">
        <h2 class="ds-footer__heading">${esc(column.title)}</h2>
        <ul class="ds-footer__list">
          ${column.links.map((link) => `<li><a href="${esc(link.url)}" class="ds-footer__link">${esc(link.label)}</a></li>`).join("")}
        </ul>
      </div>`,
        )
        .join("")}
    </nav>
  </div>
  <div class="ds-footer__bottom">
    <div class="ds-footer__legal">
      <p>${esc(content.copyright)}</p>
      <p>${esc(content.legal)}</p>
    </div>
  </div>
</footer>`;
  };

  /** One bag line; in the drawer it updates in place, on the cart page the page re-renders. */
  const cartLine = (item, inDrawer) => {
    const { product, quantity, size } = item;
    const action = inDrawer ? "cart-update" : "cart-page-update";
    const updateForm = (value, button, extraClass = "") => `
      <form${extraClass ? ` class="${extraClass}"` : ""} data-ajax="${action}">
        <input type="hidden" name="productId" value="${esc(product.id)}" />
        <input type="hidden" name="size" value="${esc(size ?? "")}" />
        <input type="hidden" name="quantity" value="${value}" />
        ${button}
      </form>`;
    return `
<article class="ds-cart-line ${inDrawer ? "" : "ds-cart-line--page"}">
  <img class="ds-cart-line__image" src="${esc(product.image)}" alt="${esc(product.name)}" loading="lazy" />
  <div class="ds-cart-line__copy">
    <a href="product.html?slug=${encodeURIComponent(product.slug)}" class="ds-cart-line__name">${esc(product.name)}</a>
    ${size !== null ? `<span class="ds-cart-line__meta">Size ${esc(size)}</span>` : ""}
  </div>
  <div class="ds-stepper ds-stepper--compact ds-cart-line__qty">
    ${updateForm(quantity - 1, `<button type="submit" class="ds-stepper__btn" aria-label="Decrease ${esc(product.name)} quantity">${icon("minus")}</button>`)}
    <span class="ds-stepper__value">${quantity}</span>
    ${updateForm(
      Math.min(cart.maxQuantity, quantity + 1),
      `<button type="submit" class="ds-stepper__btn" aria-label="Increase ${esc(product.name)} quantity"${attr("disabled", quantity >= cart.maxQuantity)}>${icon("plus")}</button>`,
    )}
  </div>
  <p class="ds-cart-line__total">${esc(currency.format(product.price === null ? null : product.price * quantity))}</p>
  ${updateForm(0, `<button type="submit" class="ds-icon-btn" aria-label="Remove ${esc(product.name)}">${icon("trash")}</button>`, "ds-cart-line__remove")}
</article>`;
  };

  const cartDrawerBody = () => {
    const items = cart.items();
    const count = items.reduce((total, item) => total + item.quantity, 0);
    const content = site.cart;
    return `
<div class="ds-drawer__head">
  <div>
    <h2 class="ds-drawer__title">Shopping Cart</h2>
    <p class="ds-drawer__meta">${count} ${count === 1 ? "item" : "items"}</p>
  </div>
  <button type="button" class="ds-icon-btn" aria-label="Close cart" data-modal-close>${icon("x")}</button>
</div>
<div class="ds-drawer__body">
  ${
    items.length === 0
      ? `<div class="ds-drawer__empty">
    ${icon("shopping-bag", "ds-icon--xl")}
    <h3 class="ds-drawer__empty-title">${esc(content.drawerEmptyTitle)}</h3>
    <p class="ds-drawer__empty-body">${esc(content.drawerEmptyBody)}</p>
  </div>`
      : items.map((item) => cartLine(item, true)).join("")
  }
</div>
<div class="ds-drawer__footer">
  <div class="ds-drawer__row">
    <span>Subtotal</span>
    <strong>${esc(currency.subtotal(items))}</strong>
  </div>
  ${
    items.length === 0
      ? `<button type="button" class="ds-btn ds-btn--primary ds-btn--block" disabled>${esc(content.checkoutLabel)}</button>`
      : `<a href="checkout.html" class="ds-btn ds-btn--primary ds-btn--block">${esc(content.checkoutLabel)}</a>`
  }
  <a href="cart.html" class="ds-btn ds-btn--secondary ds-btn--block">Go to cart</a>
</div>`;
  };

  const cartDrawer = () => `
<div class="ds-modal ds-modal--drawer" data-modal="cart-drawer" hidden>
  <div class="ds-drawer" id="cart-drawer" role="dialog" aria-modal="true" aria-label="Shopping cart" data-cart-drawer>
    ${cartDrawerBody()}
  </div>
</div>`;

  const productCard = (product) => {
    const saved = wishlist.contains(product.id);
    // Sized pieces (sets) can't go in the bag from a card: the button opens the product page to pick a size.
    const needsSize = catalog.getCategory(product.category).sizes.length > 0;
    const url = `product.html?slug=${encodeURIComponent(product.slug)}`;
    const chooseSize = site.product.chooseSize;
    return `
<article class="ds-card ${needsSize ? "ds-card--fit" : ""}">
  <div class="ds-card__media">
    <a href="${url}" class="ds-card__image-link" aria-label="View ${esc(product.name)}">
      <img class="ds-card__image ${product.alternateImage === null ? "" : "ds-card__image--primary"}" src="${esc(product.image)}" alt="${esc(product.name)} product look" loading="lazy" />
      ${product.alternateImage !== null ? `<img class="ds-card__image ds-card__image--secondary" src="${esc(product.alternateImage)}" alt="" loading="lazy" />` : ""}
    </a>
    ${product.badge !== null ? `<span class="ds-badge ds-card__badge">${esc(product.badge)}</span>` : ""}
    <form class="ds-card__wishlist" data-ajax="wishlist">
      <input type="hidden" name="productId" value="${esc(product.id)}" />
      <button type="submit" class="ds-heart-btn" aria-pressed="${saved}" aria-label="Save ${esc(product.name)} to wishlist" data-wishlist-button="${esc(product.id)}">
        ${icon("heart")}
      </button>
    </form>
  </div>
  <div class="ds-card__body">
    <div class="ds-card__heading">
      <a href="${url}" class="ds-card__title">${esc(product.name)}</a>
      <p class="ds-card__price">${esc(currency.format(product.price))}</p>
    </div>
    ${
      needsSize
        ? `<div class="ds-card__actions">
      <a href="${url}" class="ds-btn ds-btn--secondary ds-btn--block ds-btn--compact" aria-label="${esc(chooseSize)} for ${esc(product.name)}">${esc(chooseSize)}</a>
    </div>`
        : `<form class="ds-card__actions" data-ajax="cart-add">
      <input type="hidden" name="productId" value="${esc(product.id)}" />
      <input type="hidden" name="quantity" value="1" />
      <button type="submit" class="ds-btn ds-btn--primary ds-btn--block ds-btn--compact"${attr("disabled", !product.purchasable)}
              aria-label="${esc(product.purchasable ? `Add ${product.name} to cart` : `${product.name} is not available to add yet`)}">
        Add to cart
      </button>
    </form>`
    }
  </div>
</article>`;
  };

  const emptyState = (iconName, title, body, actionLabel, actionUrl) => `
<div class="ds-empty">
  <span class="ds-empty__icon">${icon(iconName)}</span>
  <h2 class="ds-empty__title">${esc(title)}</h2>
  <p class="ds-empty__body">${esc(body)}</p>
  <a href="${esc(actionUrl)}" class="ds-btn ds-btn--primary">${esc(actionLabel)}</a>
</div>`;

  /**
   * Page numbers with null for a gap: three pages ending at the current one, then the last page.
   * Page 1 shows 1 2 3 … 7, page 4 shows 2 3 4 … 7, page 7 shows 5 6 7.
   */
  const paginationItems = (page, pageCount) => {
    const windowSize = 3;
    const start = Math.min(Math.max(page - (windowSize - 1), 1), Math.max(1, pageCount - (windowSize - 1)));
    const end = Math.min(pageCount, start + windowSize - 1);
    const items = [];
    for (let number = start; number <= end; number++) items.push(number);
    if (end < pageCount - 1) items.push(null);
    if (end < pageCount) items.push(pageCount);
    return items;
  };

  const pagination = (page, pageCount, urlFor) => {
    if (pageCount <= 1) return "";
    const prev =
      page > 1
        ? `<a href="${esc(urlFor(page - 1))}" class="ds-pagination__item" aria-label="Previous page">${icon("chevron-left")}</a>`
        : `<span class="ds-pagination__item is-disabled" aria-hidden="true">${icon("chevron-left")}</span>`;
    const next =
      page < pageCount
        ? `<a href="${esc(urlFor(page + 1))}" class="ds-pagination__item" aria-label="Next page">${icon("chevron-right")}</a>`
        : `<span class="ds-pagination__item is-disabled" aria-hidden="true">${icon("chevron-right")}</span>`;
    const numbers = paginationItems(page, pageCount)
      .map((number) =>
        number === null
          ? `<span class="ds-pagination__gap" aria-hidden="true">…</span>`
          : `<a href="${esc(urlFor(number))}" class="ds-pagination__item ${number === page ? "is-current" : ""}" aria-label="Page ${number}"${opt("aria-current", number === page ? "page" : null)}>${number}</a>`,
      )
      .join("");
    return `<nav class="ds-pagination" aria-label="Product pages">${prev}${numbers}${next}</nav>`;
  };

  const layout = () => `
<button type="button" class="ds-back-to-top" data-back-to-top aria-label="Back to top" hidden>${icon("arrow-up")}</button>
<div class="ds-toast" role="status" aria-live="polite" data-toast>
  ${icon("check")}
  <span data-toast-message></span>
</div>`;

  window.Jude.components = { brandLockup, header, footer, cartLine, cartDrawer, cartDrawerBody, productCard, emptyState, pagination, layout, fmt };
})();
