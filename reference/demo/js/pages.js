// Page contents. Each page returns { title, html } for the <main> element,
// or { redirect } to send the visitor elsewhere (e.g. checkout with an empty bag).
(() => {
  "use strict";

  const { site, catalog, currency, cart, wishlist, flash, readShopQuery, esc, fmt, attr, opt, icon } = window.Jude;
  const { productCard, cartLine, emptyState, pagination } = window.Jude.components;
  const params = () => new URLSearchParams(window.location.search);

  /* ---------- Home ---------- */
  const home = () => {
    const { hero, trustItems, about, order, reviews } = site.home;
    const steps = order.steps
      .map(
        (step, index) => `
      <li class="ds-order">
        <span class="ds-order__title">${esc(step.title)}</span>
        <span class="ds-order__detail">${esc(step.text)}</span>
        ${index < order.steps.length - 1 ? `<span class="ds-order__arrow" aria-hidden="true">${icon("arrow-right")}</span>` : ""}
      </li>`,
      )
      .join("");

    const html = `
<section class="ds-hero">
  <div class="ds-hero__content">
    <h1 class="ds-hero__headline"><span class="ds-hero__headline-main">${esc(hero.title)}</span></h1>
    ${hero.body ? `<p class="ds-hero__lead">${esc(hero.body)}</p>` : ""}
    <div class="ds-hero__actions">
      <a href="${esc(hero.ctaUrl)}" class="ds-btn ds-btn--primary ds-btn--pill">${esc(hero.ctaLabel)}</a>
    </div>
  </div>
  <!-- A boutique clothes rack: the sets slide onto the rail, then sway gently on their hangers. -->
  <div class="ds-hero__stage" role="group" aria-label="${esc(hero.rackLabel)}">
    <span class="ds-hero__ground" aria-hidden="true"></span>
    <span class="ds-rack__frame" aria-hidden="true">
      <span class="ds-rack__rail"></span>
      <span class="ds-rack__post ds-rack__post--left"></span>
      <span class="ds-rack__post ds-rack__post--right"></span>
      <span class="ds-rack__foot ds-rack__foot--left"></span>
      <span class="ds-rack__foot ds-rack__foot--right"></span>
    </span>
    <ul class="ds-rack__items">
      ${hero.rack
        .map(
          (garment) => `
      <li class="ds-rack__item">
        <a class="ds-rack__garment" href="${esc(garment.url)}">
          ${icon("hanger", "ds-rack__hanger")}
          <img class="ds-rack__image" src="${esc(garment.src)}" alt="${esc(garment.alt)}" />
          <span class="ds-rack__tag">${esc(garment.name)}</span>
        </a>
      </li>`,
        )
        .join("")}
    </ul>
  </div>
</section>

<section class="ds-trust" aria-label="Store information">
  ${trustItems
    .map(
      (item) => `
  <article class="ds-trust__item">
    ${item.image ? `<img class="ds-trust__image" src="${esc(item.image)}" alt="${esc(item.imageAlt ?? "")}" />` : icon(item.icon, "ds-icon--lg ds-icon--light")}
    <div>
      <h2 class="ds-trust__title">${esc(item.title)}</h2>
      <p class="ds-trust__text">
        ${esc(item.text)}
        ${item.linkUrl ? `<a class="ds-trust__link" href="${esc(item.linkUrl)}" target="_blank" rel="noopener">${esc(item.linkLabel)}</a>` : ""}
      </p>
    </div>
  </article>`,
    )
    .join("")}
</section>

<section class="ds-callout">
  <div class="ds-callout__looks">
    ${about.looks
      .slice(0, 3)
      .map(
        (look) => `
    <a class="ds-callout__look" href="${esc(look.url)}">
      <span class="ds-callout__look-media">
        <img class="ds-callout__look-image" src="${esc(look.src)}" alt="${esc(look.alt)}" loading="lazy" />
      </span>
      <span class="ds-callout__look-caption">${esc(fmt(about.lookLabel, look.name))}</span>
    </a>`,
      )
      .join("")}
  </div>
  <div class="ds-callout__copy">
    <p class="ds-eyebrow">${esc(about.kicker)}</p>
    <h2 class="ds-callout__title">${esc(about.title)}</h2>
    <p class="ds-callout__body">${esc(about.body)}</p>
    <a href="${esc(about.ctaUrl)}" class="ds-btn ds-btn--primary ds-btn--pill">${esc(about.ctaLabel)}</a>
  </div>
</section>

<section class="ds-stores" id="order" aria-labelledby="order-title">
  <div class="ds-stores__intro">
    <h2 class="ds-stores__title" id="order-title">
      ${esc(order.title)}
      ${order.titleNote ? `<span class="ds-stores__title-note">${esc(order.titleNote)}</span>` : ""}
    </h2>
  </div>
  <ol class="ds-order-grid">${steps}</ol>
  <div class="ds-order-cta">
    <a href="${esc(order.ctaUrl)}" class="ds-btn ds-btn--primary ds-btn--pill">${esc(order.ctaLabel)}</a>
  </div>
</section>

${
  reviews.items.length > 0
    ? `<section class="ds-testimonials" id="reviews" aria-labelledby="reviews-title">
  <div class="ds-testimonials__header">
    <h2 class="ds-testimonials__title" id="reviews-title">${esc(reviews.title)}</h2>
  </div>
  <div class="ds-testimonials__grid">
    ${reviews.items
      .map(
        (review) => `
    <figure class="ds-testimonial">
      ${review.image ? `<img class="ds-testimonial__image" src="${esc(review.image)}" alt="${esc(review.imageAlt ?? "")}" loading="lazy" />` : ""}
      <figcaption class="ds-testimonial__footer">
        <span class="ds-testimonial__name">
          ${esc(review.name)}
          ${review.flag ? `<img class="ds-testimonial__flag" src="${esc(review.flag)}" alt="${esc(review.country)}" title="${esc(review.country)}" />` : ""}
        </span>
      </figcaption>
    </figure>`,
      )
      .join("")}
  </div>
</section>`
    : ""
}`;
    return { title: null, html };
  };

  /* ---------- Shop (all / sets / hijabs / bundles) ---------- */
  const shop = (categoryKey) => {
    const result = catalog.query(categoryKey, readShopQuery());
    const category = result.category;
    const slug = category.slug;

    const shades =
      result.availableShades.length > 0
        ? `<fieldset class="ds-filters__section">
          <legend class="ds-filters__label">${esc(category.shadeHeading)}</legend>
          <label class="ds-option">
            <input class="ds-option__control" type="checkbox"${attr("checked", result.selectedShades.length === 0)} data-filter-all-shades />
            <span>${esc(category.allShadesLabel)}</span>
          </label>
          ${result.availableShades
            .map(
              (shade) => `
          <label class="ds-option">
            <input class="ds-option__control" type="checkbox" name="shade" value="${esc(shade.id)}"${attr("checked", result.selectedShades.includes(shade.id))} data-filter-input data-filter-shade />
            <span class="ds-swatch ds-swatch--sm" data-shade="${esc(shade.id)}" aria-hidden="true"></span>
            <span>${esc(catalog.labelFor(category, shade))}</span>
          </label>`,
            )
            .join("")}
        </fieldset>`
        : "";

    const toolbar =
      result.categoryTotal > 0
        ? `<div class="ds-results__toolbar">
        <div class="ds-results__search" role="search">
          <label class="ds-searchbar">
            ${icon("search")}
            <input class="ds-searchbar__input" type="search" name="q" value="${esc(result.search)}" placeholder="Search products" aria-label="Search products" autocomplete="off" form="shop-filters" />
          </label>
          <button type="submit" class="ds-btn ds-btn--primary ds-searchbar__submit" form="shop-filters">Search</button>
        </div>
        <nav class="ds-category-tabs" aria-label="Categories">
          ${catalog.categories
            .map(
              (option) => `
          <a href="${esc(result.urlForCategory(option))}" class="ds-category-tabs__tab"${opt("aria-current", option.key === category.key ? "page" : null)}>
            ${esc(option.filterLabel)} <span class="ds-category-tabs__count">${catalog.countIn(option)}</span>
          </a>`,
            )
            .join("")}
        </nav>
        <div class="ds-results__meta">
          ${
            result.search
              ? `<a class="ds-results__chip" href="${esc(result.urlWithoutSearch)}" aria-label="Clear search for ${esc(result.search)}">“${esc(result.search)}” ${icon("x", "ds-icon--sm")}</a>`
              : ""
          }
          <button type="button" class="ds-results__filters-btn" aria-controls="shop-filters" aria-expanded="false" data-filters-open>
            ${icon("sliders")} Filters
            ${result.selectedShades.length > 0 ? `<span class="ds-results__filters-count">${result.selectedShades.length}</span>` : ""}
          </button>
        </div>
      </div>`
        : "";

    let list;
    if (result.products.length === 0) {
      list =
        result.categoryTotal === 0 && category.emptyTitle
          ? emptyState("sparkles", category.emptyTitle, category.emptyBody ?? "", site.shop.emptyCta, "shop.html")
          : emptyState("search", site.shop.emptyTitle, site.shop.emptyBody, "Clear filters", `${slug}.html`);
    } else {
      list = `<div class="ds-grid ds-grid--catalog">${result.products.map(productCard).join("")}</div>
      ${pagination(result.page, result.pageCount, result.urlFor)}`;
    }

    const html = `
<section class="ds-collection-intro">
  <h1 class="ds-collection-intro__title">${esc(category.title)}</h1>
</section>

<div class="ds-shop ${result.categoryTotal === 0 ? "ds-shop--empty" : ""}">
  <form id="shop-filters" class="ds-filters" action="${esc(slug)}.html" method="get" aria-label="Product filters"${attr("hidden", result.categoryTotal === 0)} data-filters>
    <div class="ds-filters__head">
      <h2 class="ds-filters__title">Filters</h2>
      <a href="${esc(slug)}.html" class="ds-filters__clear">Clear</a>
      <button type="button" class="ds-icon-btn ds-filters__close" aria-label="Close filters" data-filters-close>${icon("x")}</button>
    </div>
    <div class="ds-filters__scroll">${shades}</div>
    <div class="ds-filters__actions">
      <a href="${esc(slug)}.html" class="ds-btn ds-btn--secondary">Clear all</a>
      <button type="submit" class="ds-btn ds-btn--primary">Apply filters</button>
    </div>
  </form>

  <section class="ds-results" id="results">
    ${toolbar}
    ${list}
  </section>
</div>`;
    return { title: category.pageTitle, html };
  };

  /* ---------- Product ---------- */
  const product = () => {
    const item = catalog.findBySlug(params().get("slug"));
    if (!item) return notFound();

    const category = catalog.getCategory(item.category);
    const shade = catalog.shades.find((option) => option.id === item.shadeFamily);
    const content = site.product;
    const images = item.images;
    const saved = wishlist.contains(item.id);
    const related = catalog.related(item);
    const colors = item.style
      ? catalog.all.filter((other) => other.category === item.category && other.style === item.style && other.purchasable)
      : [];
    const sizes = category.sizes;
    const guide = category.sizeGuide;

    const html = `
<nav class="ds-back" aria-label="Breadcrumb">
  <a href="${esc(category.slug)}.html" class="ds-back__link" data-back>
    ${icon("chevron-left", "ds-icon--sm")} ${esc(fmt(content.backLabel, category.title))}
  </a>
</nav>
<section class="ds-pdp">
  <div class="ds-pdp__media" data-gallery>
    <div class="ds-pdp__frame">
      <img class="ds-pdp__image" src="${esc(images[0])}" alt="${esc(item.name)} product and styling view" fetchpriority="high" data-gallery-main />
    </div>
    ${
      images.length > 1
        ? `<div class="ds-pdp__thumbs" aria-label="Product gallery">
      ${images
        .map(
          (image, index) => `
      <button type="button" class="ds-pdp__thumb ${index === 0 ? "is-active" : ""}" aria-label="View image ${index + 1}" data-gallery-thumb="${esc(image)}">
        <img class="ds-pdp__thumb-image" src="${esc(image)}" alt="" loading="lazy" />
      </button>`,
        )
        .join("")}
    </div>`
        : ""
    }
  </div>

  <div class="ds-pdp__purchase">
    <div class="ds-pdp__title-row">
      <h1 class="ds-pdp__title">${esc(item.name)}</h1>
      <form data-ajax="wishlist">
        <input type="hidden" name="productId" value="${esc(item.id)}" />
        <button type="submit" class="ds-icon-btn ds-icon-btn--toggle" aria-pressed="${saved}" aria-label="Save to wishlist" data-wishlist-button="${esc(item.id)}">
          ${icon("heart")}
        </button>
      </form>
    </div>
    <p class="ds-pdp__price">${esc(currency.format(item.price))}</p>
    <p class="ds-pdp__lead">${esc(item.shortDescription)}</p>
    <p class="ds-pdp__facts">
      <span>${esc(content.colorLabel)}: <strong>${esc(category.labelByColor ? item.colorName : shade?.label)}</strong></span>
      <span class="ds-pdp__stock">${icon("check", "ds-icon--sm")} ${esc(content.availability)}</span>
    </p>
    ${item.note ? `<p class="ds-pdp__note">${esc(item.note)}</p>` : ""}

    <form class="ds-pdp__buy" data-ajax="cart-add">
      <input type="hidden" name="productId" value="${esc(item.id)}" />
      ${
        colors.length > 1
          ? `<div class="ds-color-picker">
        <p class="ds-color-picker__legend">${esc(content.colorsLabel)}</p>
        <div class="ds-color-picker__options">
          ${colors
            .map(
              (color) => `
          <a href="product.html?slug=${encodeURIComponent(color.slug)}" class="ds-color-picker__option" title="${esc(color.colorName)}"
             aria-label="${esc(color.colorName)}"${opt("aria-current", color.id === item.id ? "true" : null)}>
            <span class="ds-swatch ds-color-picker__swatch" data-shade="${esc(color.shadeFamily)}" aria-hidden="true"></span>
          </a>`,
            )
            .join("")}
        </div>
      </div>`
          : ""
      }
      ${
        sizes.length > 0
          ? `<fieldset class="ds-size-picker">
        <legend class="ds-size-picker__legend">${esc(content.sizeLabel)} <a href="#size-guide" class="ds-size-picker__guide">${esc(content.sizeGuideTitle)}</a></legend>
        <div class="ds-size-picker__options">
          ${sizes
            .map(
              (size) => `
          <label class="ds-size-picker__option">
            <input class="ds-size-picker__control" type="radio" name="size" value="${esc(size)}" required data-size-input />
            <span class="ds-size-picker__label">${esc(size)}</span>
          </label>`,
            )
            .join("")}
        </div>
        <p class="ds-size-picker__error" role="alert" hidden data-size-error>${esc(content.sizeRequired)}</p>
      </fieldset>`
          : ""
      }
      <div class="ds-pdp__quantity">
        <label for="quantity">Quantity</label>
        <div class="ds-stepper" data-stepper>
          <button type="button" class="ds-stepper__btn" aria-label="Decrease quantity" data-stepper-step="-1">${icon("minus")}</button>
          <input id="quantity" class="ds-stepper__input" type="number" name="quantity" value="1" min="1" max="${cart.maxQuantity}" inputmode="numeric" />
          <button type="button" class="ds-stepper__btn" aria-label="Increase quantity" data-stepper-step="1">${icon("plus")}</button>
        </div>
      </div>
      <button type="submit" class="ds-btn ds-btn--primary ds-btn--block"${attr("disabled", !item.purchasable)}>
        Add to bag ${icon("shopping-bag")}
      </button>
    </form>
    ${
      content.perks.length > 0
        ? `<ul class="ds-pdp__perks">${content.perks.map((perk) => `<li class="ds-pdp__perk">${icon("check", "ds-icon--sm")} ${esc(perk)}</li>`).join("")}</ul>`
        : ""
    }
    ${
      guide && guide.rows.length > 0
        ? `<section class="ds-info-card" id="size-guide" aria-labelledby="size-guide-title">
      <h2 class="ds-info-card__title" id="size-guide-title">${esc(content.sizeGuideTitle)}</h2>
      <p class="ds-info-card__note">${esc(content.sizeGuideNote)}</p>
      <div class="ds-size-table__wrap">
        <table class="ds-size-table">
          <thead><tr>${guide.columns.map((column) => `<th scope="col">${esc(column)}</th>`).join("")}</tr></thead>
          <tbody>
            ${guide.rows.map((row) => `<tr><th scope="row">${esc(row[0])}</th>${row.slice(1).map((cell) => `<td>${esc(cell)}</td>`).join("")}</tr>`).join("")}
          </tbody>
        </table>
      </div>
    </section>`
        : ""
    }
  </div>
</section>

${
  related.length > 0
    ? `<section class="ds-related">
  <div class="ds-section-heading">
    <h2 class="ds-section-heading__title">Related products</h2>
    <span class="ds-section-heading__rule"></span>
  </div>
  <div class="ds-grid ds-grid--rail">${related.map(productCard).join("")}</div>
</section>`
    : ""
}`;
    return { title: item.name, html };
  };

  /* ---------- Cart ---------- */
  const cartPage = () => {
    const content = site.cart;
    const items = cart.items();
    const count = items.reduce((total, item) => total + item.quantity, 0);
    const html = `
<div class="ds-section">
  <header class="ds-list-head">
    <h1 class="ds-list-head__title">${esc(content.title)}</h1>
    <span class="ds-list-head__count">${esc(fmt(count === 1 ? content.itemCountOne : content.itemCountMany, count))}</span>
    ${items.length > 0 ? `<a href="shop.html" class="ds-list-head__link">${esc(content.continueLabel)}</a>` : ""}
  </header>
  ${
    items.length === 0
      ? emptyState("shopping-bag", content.emptyTitle, content.emptyBody, content.emptyCta, "shop.html")
      : `<div class="ds-cart">
    <div class="ds-cart__lines">
      <div class="ds-cart__columns" aria-hidden="true">
        <span>${esc(content.productColumn)}</span>
        <span>${esc(content.quantityColumn)}</span>
        <span>${esc(content.totalColumn)}</span>
      </div>
      ${items.map((item) => cartLine(item, false)).join("")}
    </div>
    <aside class="ds-summary ds-summary--sticky">
      <h2 class="ds-summary__title">${esc(content.summaryTitle)}</h2>
      <div class="ds-summary__row"><span>${esc(content.itemsLabel)}</span><strong>${count}</strong></div>
      <div class="ds-summary__row"><span>${esc(content.subtotalLabel)}</span><strong>${esc(currency.subtotal(items))}</strong></div>
      <div class="ds-summary__row"><span>${esc(content.shippingLabel)}</span><span>${esc(content.shippingNote)}</span></div>
      <a href="checkout.html" class="ds-btn ds-btn--primary ds-btn--block">${esc(content.checkoutLabel)}</a>
      <p class="ds-summary__note">${esc(content.summaryNote)}</p>
    </aside>
  </div>`
  }
</div>`;
    return { title: site.titles.cart, html };
  };

  /* ---------- Wishlist ---------- */
  const wishlistPage = () => {
    const content = site.wishlist;
    const saved = wishlist.ids().map(catalog.findById).filter(Boolean);
    const html = `
<div class="ds-section">
  <header class="ds-list-head">
    <h1 class="ds-list-head__title">${esc(content.title)}</h1>
    <span class="ds-list-head__count">${esc(fmt(saved.length === 1 ? content.countOne : content.countMany, saved.length))}</span>
    ${saved.length > 0 ? `<a href="shop.html" class="ds-list-head__link">${esc(content.continueLabel)}</a>` : ""}
  </header>
  ${
    saved.length === 0
      ? emptyState("heart", content.emptyTitle, content.emptyBody, content.emptyCta, "shop.html")
      : `<div class="ds-grid ds-grid--wishlist">${saved.map(productCard).join("")}</div>`
  }
</div>`;
    return { title: site.titles.wishlist, html };
  };

  /* ---------- Checkout ---------- */
  const checkoutContent = site.checkout;
  const findCity = (key) => checkoutContent.cities.find((city) => city.key === key) ?? null;

  /** form: the values typed so far; errors: field name → message. */
  const checkout = (form = null, errors = {}) => {
    const items = cart.items();
    if (items.length === 0) return { redirect: "cart.html" };

    const content = checkoutContent;
    const fields = content.fields;
    const values = form ?? { FullName: "", Country: content.phoneCountries[0]?.code ?? "", Phone: "", Email: "", City: "", Area: "", AreaOther: "", Address: "" };
    const error = (field) => errors[field] ?? null;
    const invalid = (field) => (error(field) ? "true" : "false");
    const errorText = (field) => (error(field) ? `<span class="ds-field__error">${esc(error(field))}</span>` : "");
    const country = content.phoneCountries.find((option) => option.code === values.Country) ?? content.phoneCountries[0];
    const city = findCity(values.City);
    const areaIsOther = city !== null && values.AreaOther !== "" && !city.areas.includes(values.Area);
    const areas = Object.fromEntries(content.cities.map((option) => [option.key, option.areas]));
    const html = `
<div class="ds-section">
  <header class="ds-page-heading ds-page-heading--start">
    <p class="ds-eyebrow">${esc(content.kicker)}</p>
    <h1 class="ds-page-heading__title">${esc(content.title)}</h1>
    <p class="ds-page-heading__lead">${esc(content.lead)}</p>
  </header>

  <div class="ds-checkout-layout">
    <form class="ds-checkout" novalidate data-checkout>
      ${Object.keys(errors).length > 0 ? `<p class="ds-checkout__alert" role="alert">${esc(content.errorSummary)}</p>` : ""}

      <fieldset class="ds-checkout__group">
        <legend class="ds-checkout__legend">${esc(content.contactTitle)}</legend>
        <div class="ds-checkout__grid">
          <label class="ds-field ds-checkout__wide">
            <span class="ds-field__label">${esc(fields.fullName)}</span>
            <input class="ds-field__input" name="FullName" value="${esc(values.FullName)}" autocomplete="name" required aria-invalid="${invalid("FullName")}" />
            ${errorText("FullName")}
          </label>

          <div class="ds-field">
            <label class="ds-field__label" for="phone">${esc(fields.phone)}</label>
            <div class="ds-phone" dir="ltr">
              <label class="ds-phone__country">
                <span class="ds-phone__flag" aria-hidden="true" data-phone-flag>${esc(country?.flag)}</span>
                <span class="ds-phone__dial" aria-hidden="true" data-phone-dial>+${esc(country?.dial)}</span>
                ${icon("chevron-down", "ds-icon--sm")}
                <select class="ds-phone__select" name="Country" aria-label="${esc(fields.countryLabel)}" data-phone-country>
                  ${content.phoneCountries
                    .map(
                      (option) =>
                        `<option value="${esc(option.code)}"${attr("selected", option.code === country?.code)} data-flag="${esc(option.flag)}" data-dial="${esc(option.dial)}" data-placeholder="${esc(option.placeholder)}">${esc(option.flag)} ${esc(option.name)} (+${esc(option.dial)})</option>`,
                    )
                    .join("")}
                </select>
              </label>
              <input id="phone" class="ds-phone__input" name="Phone" type="tel" value="${esc(values.Phone)}" placeholder="${esc(country?.placeholder)}" autocomplete="tel-national" inputmode="tel" required
                     aria-invalid="${invalid("Phone")}" aria-describedby="phone-hint" data-phone-input />
            </div>
            ${
              error("Phone")
                ? `<span class="ds-field__error" id="phone-hint">${esc(error("Phone"))}</span>`
                : `<span class="ds-field__hint" id="phone-hint">${esc(fields.phoneHint)}</span>`
            }
          </div>

          <label class="ds-field">
            <span class="ds-field__label">${esc(fields.email)} <span class="ds-field__optional">${esc(fields.optional)}</span></span>
            <input class="ds-field__input" name="Email" type="email" value="${esc(values.Email)}" placeholder="${esc(fields.emailPlaceholder)}" autocomplete="email" aria-invalid="${invalid("Email")}" />
            ${errorText("Email")}
          </label>
        </div>
      </fieldset>

      <fieldset class="ds-checkout__group">
        <legend class="ds-checkout__legend">${esc(content.addressTitle)}</legend>
        <div class="ds-checkout__grid">
          <label class="ds-field">
            <span class="ds-field__label">${esc(fields.city)}</span>
            <span class="ds-field__select">
              <select class="ds-field__input" name="City" required aria-invalid="${invalid("City")}" data-city-select>
                <option value="">${esc(fields.cityChoose)}</option>
                ${content.cities
                  .map(
                    (option) =>
                      `<option value="${esc(option.key)}"${attr("selected", option.key === values.City)} data-shipping="${esc(currency.format(option.cost))}" data-total="${esc(currency.total(items, option.cost))}">${esc(option.label)}</option>`,
                  )
                  .join("")}
              </select>
              ${icon("chevron-down", "ds-icon--sm")}
            </span>
            ${errorText("City")}
          </label>

          <div class="ds-field">
            <label class="ds-field__label" for="area">${esc(fields.area)}</label>
            <span class="ds-field__select" data-area-native>
              <select id="area" class="ds-field__input" name="Area" aria-invalid="${invalid("Area")}" data-area-select
                      data-choose="${esc(fields.areaChoose)}" data-city-first="${esc(fields.areaCityFirst)}" data-other="${esc(fields.areaOther)}">
                <option value="">${esc(city === null ? fields.areaCityFirst : fields.areaChoose)}</option>
                ${content.cities
                  .map(
                    (option) => `<optgroup label="${esc(option.label)}">${option.areas
                      .map((area) => `<option value="${esc(area)}"${attr("selected", option.key === values.City && area === values.Area)}>${esc(area)}</option>`)
                      .join("")}</optgroup>`,
                  )
                  .join("")}
                <option value="other"${attr("selected", areaIsOther)}>${esc(fields.areaOther)}</option>
              </select>
              ${icon("chevron-down", "ds-icon--sm")}
            </span>
            <div class="ds-picker" hidden data-area-picker>
              <button type="button" class="ds-picker__trigger" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="area-picker-label" aria-invalid="${invalid("Area")}" data-picker-trigger>
                <span class="ds-picker__value" id="area-picker-label" data-picker-value>${esc(fields.areaCityFirst)}</span>
                ${icon("chevron-down", "ds-icon--sm")}
              </button>
              <div class="ds-picker__panel" hidden data-picker-panel>
                <label class="ds-picker__search">
                  ${icon("search", "ds-icon--sm")}
                  <input class="ds-picker__search-input" type="search" placeholder="${esc(fields.areaSearch)}" aria-label="${esc(fields.areaSearch)}" autocomplete="off" data-picker-search />
                </label>
                <ul class="ds-picker__list" role="listbox" aria-label="${esc(fields.area)}" data-picker-list></ul>
                <p class="ds-picker__empty" hidden data-picker-empty>${esc(fields.areaEmpty)}</p>
              </div>
            </div>
            ${errorText("Area")}
          </div>

          <label class="ds-field ds-checkout__wide"${attr("hidden", !areaIsOther)} data-area-other>
            <span class="ds-field__label">${esc(fields.areaOtherLabel)}</span>
            <input class="ds-field__input" name="AreaOther" value="${esc(values.AreaOther)}" placeholder="${esc(fields.areaOtherPlaceholder)}" autocomplete="address-level3" />
          </label>

          <label class="ds-field ds-checkout__wide">
            <span class="ds-field__label">${esc(fields.address)}</span>
            <input class="ds-field__input" name="Address" value="${esc(values.Address)}" placeholder="${esc(fields.addressPlaceholder)}" autocomplete="street-address" required aria-invalid="${invalid("Address")}" />
            ${errorText("Address")}
          </label>
        </div>
      </fieldset>

      <button type="submit" class="ds-btn ds-btn--primary ds-btn--block">${esc(content.placeOrder)}</button>
      <script type="application/json" data-areas>${JSON.stringify(areas).replace(/</g, "\\u003c")}</script>
    </form>

    <aside class="ds-summary ds-checkout-layout__summary" aria-labelledby="summary-title">
      <h2 class="ds-summary__title" id="summary-title">${esc(content.summaryTitle)}</h2>
      <ul class="ds-summary__lines">
        ${items
          .map(
            (item) => `
        <li class="ds-summary__line">
          <img class="ds-summary__thumb" src="${esc(item.product.image)}" alt="" loading="lazy" />
          <span class="ds-summary__name">
            ${esc(item.product.name)} <span class="ds-summary__qty">${esc(fmt(content.quantityFormat, item.quantity))}</span>
            ${item.size !== null ? `<span class="ds-summary__size">${esc(fmt(content.sizeFormat, item.size))}</span>` : ""}
          </span>
          <span>${esc(currency.format(item.product.price === null ? null : item.product.price * item.quantity))}</span>
        </li>`,
          )
          .join("")}
      </ul>
      <div class="ds-summary__row"><span>${esc(content.subtotalLabel)}</span><strong>${esc(currency.subtotal(items))}</strong></div>
      <div class="ds-summary__row">
        <span>${esc(content.shippingLabel)}</span>
        <strong data-checkout-shipping data-pending="${esc(content.shippingPending)}">${esc(city ? currency.format(city.cost) : content.shippingPending)}</strong>
      </div>
      <div class="ds-summary__row ds-summary__row--total">
        <span>${esc(content.totalLabel)}</span>
        <strong data-checkout-total data-pending="${esc(currency.subtotal(items))}">${esc(city ? currency.total(items, city.cost) : currency.subtotal(items))}</strong>
      </div>
      <div class="ds-pay-note">
        <p class="ds-pay-note__title">${icon("check", "ds-icon--sm")} ${esc(content.paymentTitle)}: ${esc(content.paymentMethod)}</p>
        <p class="ds-pay-note__body">${esc(content.paymentNote)} <a class="ds-pay-note__link" href="${esc(content.paymentLinkUrl)}">${esc(content.paymentLinkLabel)}</a></p>
      </div>
    </aside>
  </div>
</div>`;
    return { title: site.titles.checkout, html };
  };

  /* ---------- Order confirmation ---------- */
  const confirmation = () => {
    const order = flash.take("order");
    if (!order) return { redirect: "shop.html" };
    const content = checkoutContent;
    const html = `
<div class="ds-section">
  <div class="ds-confirmation">
    <span class="ds-confirmation__icon" aria-hidden="true">${icon("check", "ds-icon--lg")}</span>
    <header class="ds-confirmation__head">
      <p class="ds-eyebrow">${esc(content.confirmationKicker)}</p>
      <h1 class="ds-confirmation__title">${esc(content.confirmationTitle)}</h1>
      <p class="ds-confirmation__number">${esc(content.orderNumberLabel)} #${esc(order.number)}</p>
      ${order.emailSent ? `<p class="ds-confirmation__note">${esc(fmt(content.emailSentNote, order.email))}</p>` : ""}
    </header>

    <div class="ds-summary ds-confirmation__summary">
      <h2 class="ds-summary__title">${esc(content.summaryTitle)}</h2>
      <ul class="ds-summary__lines">
        ${order.lines
          .map(
            (line) => `
        <li class="ds-summary__line">
          ${line.image ? `<img class="ds-summary__thumb" src="${esc(line.image)}" alt="" loading="lazy" />` : ""}
          <span class="ds-summary__name">
            ${esc(line.name)} <span class="ds-summary__qty">${esc(fmt(content.quantityFormat, line.quantity))}</span>
            ${line.size !== null ? `<span class="ds-summary__size">${esc(fmt(content.sizeFormat, line.size))}</span>` : ""}
          </span>
          <span>${esc(line.price)}</span>
        </li>`,
          )
          .join("")}
      </ul>
      <div class="ds-summary__row"><span>${esc(content.subtotalLabel)}</span><strong>${esc(order.subtotal)}</strong></div>
      <div class="ds-summary__row"><span>${esc(content.shippingLabel)}</span><strong>${esc(order.shipping)}</strong></div>
      <div class="ds-summary__row ds-summary__row--total"><span>${esc(content.totalLabel)}</span><strong>${esc(order.total)}</strong></div>
      <dl class="ds-confirmation__details">
        <div class="ds-confirmation__detail"><dt>${esc(content.deliverToLabel)}</dt><dd>${esc(order.fullName)}, ${esc(order.deliverTo)}</dd></div>
        <div class="ds-confirmation__detail"><dt>${esc(content.phoneLabel)}</dt><dd dir="ltr">${esc(order.phone)}</dd></div>
        <div class="ds-confirmation__detail"><dt>${esc(content.paymentLabel)}</dt><dd>${esc(order.payment)}</dd></div>
      </dl>
      <a href="shop.html" class="ds-btn ds-btn--primary ds-btn--block">${esc(content.continueLabel)}</a>
    </div>
  </div>
</div>`;
    return { title: site.titles.orderConfirmed, html };
  };

  /* ---------- Contact, terms, not found ---------- */
  const contact = () => {
    const content = site.contact;
    const phone = site.phone;
    const html = `
<div class="ds-contact">
  <header class="ds-contact__header">
    <p class="ds-eyebrow">${esc(content.kicker)}</p>
    <h1 class="ds-contact__title">${esc(content.title)}</h1>
    <p class="ds-contact__intro">${esc(content.intro)}</p>
  </header>
  <div class="ds-contact__layout">
    <aside class="ds-contact__card">
      ${icon("instagram", "ds-icon--lg")}
      <h2 class="ds-contact__card-title">${esc(content.instagramTitle)}</h2>
      <p class="ds-contact__card-text">${esc(site.instagram.handle)}</p>
      <a href="${esc(site.instagram.url)}" target="_blank" rel="noreferrer" class="ds-btn ds-btn--primary">${esc(content.instagramCta)}</a>
      ${icon("phone", "ds-icon--lg ds-contact__card-icon")}
      <h3 class="ds-contact__card-subtitle">${esc(content.phoneTitle)}</h3>
      <p class="ds-contact__card-text">${esc(phone.display)}</p>
      <div class="ds-contact__actions">
        <a href="https://wa.me/${esc(phone.international)}" target="_blank" rel="noreferrer" class="ds-btn ds-btn--primary"><img class="ds-contact__logo" src="${esc(phone.whatsAppLogo)}" alt="" /> ${esc(phone.whatsAppLabel)}</a>
        <a href="tel:+${esc(phone.international)}" class="ds-btn ds-btn--secondary">${icon("phone")} ${esc(phone.callLabel)}</a>
      </div>
    </aside>
    <div class="ds-faq">
      ${content.faqs
        .map(
          (faq) => `
      <details class="ds-faq__item">
        <summary class="ds-faq__question">${esc(faq.question)} ${icon("plus")}</summary>
        <p class="ds-faq__answer">${esc(faq.answer)}</p>
      </details>`,
        )
        .join("")}
    </div>
  </div>
</div>`;
    return { title: site.titles.contact, html };
  };

  const terms = () => {
    const content = site.terms;
    const html = `
<article class="ds-prose">
  <h1 class="ds-prose__title">${esc(content.title)}</h1>
  <p class="ds-prose__intro">${esc(content.intro)}</p>
  ${content.sections
    .map(
      (section) => `
  <section class="ds-prose__section">
    <h2 class="ds-prose__heading">${esc(section.title)}</h2>
    <p class="ds-prose__body">${esc(section.body)}</p>
  </section>`,
    )
    .join("")}
</article>`;
    return { title: site.titles.terms, html };
  };

  function notFound() {
    const content = site.notFound;
    return {
      title: site.titles.notFound,
      html: `<div class="ds-section">${emptyState("sparkles", content.title, content.body, "Go home", "index.html")}</div>`,
    };
  }

  /** Search lives on the shop page; search.html?q= links still work. */
  const search = () => {
    const q = (params().get("q") ?? "").trim();
    return { redirect: q ? `shop.html?q=${encodeURIComponent(q)}` : "shop.html" };
  };

  window.Jude.pages = {
    home,
    shop: () => shop("all"),
    sets: () => shop("sets"),
    hijabs: () => shop("hijabs"),
    bundles: () => shop("bundles"),
    product,
    cart: cartPage,
    wishlist: wishlistPage,
    checkout,
    confirmation,
    contact,
    terms,
    search,
    "not-found": notFound,
  };
  window.Jude.findCity = findCity;
})();
