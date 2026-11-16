// Core: helpers, catalog queries, currency and the bag / wishlist stores.
// The bag, wishlist and currency are kept in the visitor's browser (localStorage).
(() => {
  "use strict";

  const site = window.JUDE_SITE;
  const data = window.JUDE_CATALOG;
  const icons = window.JUDE_ICONS;

  /* ---------- Helpers ---------- */
  const escapeMap = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };
  const esc = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => escapeMap[char]);
  /** "{0} added to bag." → "Rosemary Hijab added to bag." */
  const fmt = (template, ...args) => String(template ?? "").replace(/\{(\d+)\}/g, (_, index) => String(args[index] ?? ""));
  /** Boolean attribute: attr("hidden", true) → " hidden". */
  const attr = (name, on) => (on ? ` ${name}` : "");
  /** Optional attribute with a value; omitted when the value is null/undefined. */
  const opt = (name, value) => (value === null || value === undefined ? "" : ` ${name}="${esc(value)}"`);
  const compareText = (a, b) => String(a).localeCompare(String(b), undefined, { sensitivity: "base" });

  /** Inline SVG from the icon set; icons are decorative (aria-hidden). */
  const icon = (name, extraClass) => {
    const svg = icons[name];
    if (!svg) throw new Error(`Unknown icon '${name}'.`);
    const classes = extraClass ? `ds-icon ${extraClass}` : "ds-icon";
    return svg.replace("<svg ", `<svg class="${classes}" aria-hidden="true" focusable="false" `);
  };

  const storage = {
    get(key, fallback) {
      try {
        const value = window.localStorage.getItem(key);
        return value === null ? fallback : JSON.parse(value);
      } catch {
        return fallback;
      }
    },
    set(key, value) {
      try {
        window.localStorage.setItem(key, JSON.stringify(value));
      } catch {
        /* storage blocked: the change only lasts for this page */
      }
    },
  };

  /* ---------- Catalog ---------- */
  const ALL = "all";
  const DEFAULT_SORT = "featured";

  const products = data.products
    .map((product) => ({
      alternateImage: null,
      gallery: [],
      badge: null,
      price: null,
      featured: false,
      newlyFeatured: false,
      note: null,
      collections: [],
      displayOrder: 0,
      purchasable: true,
      colorName: "",
      style: null,
      ...product,
    }))
    .map((product) => ({ ...product, images: product.gallery.length > 0 ? product.gallery : [product.image] }))
    .sort((a, b) => a.displayOrder - b.displayOrder || compareText(a.name, b.name));

  const categories = data.categories.map((category) => ({
    shadeLabelOverrides: {},
    sizes: [],
    sizeGuide: null,
    emptyTitle: null,
    emptyBody: null,
    ...category,
    isAll: category.key === ALL,
  }));

  const catalog = {
    all: products,
    categories,
    shades: data.shades,
    sortOptions: data.sortOptions,
    findById: (id) => products.find((product) => product.id === id) ?? null,
    findBySlug: (slug) => products.find((product) => product.slug.toLowerCase() === String(slug ?? "").toLowerCase()) ?? null,
    getCategory: (key) => categories.find((category) => category.key === key) ?? categories.find((category) => category.isAll),
    labelFor: (category, shade) => category.shadeLabelOverrides[shade.id] ?? shade.label,
    countIn: (category) => products.filter((product) => category.isAll || product.category === category.key).length,

    /** Filter, search, sort and paginate one category (same rules as the shop page). */
    query(categoryKey, query) {
      const category = catalog.getCategory(categoryKey);
      const inCategory = products.filter((product) => category.isAll || product.category === category.key);
      const availableShades = data.shades.filter((shade) => inCategory.some((product) => product.shadeFamily === shade.id));
      const selectedShades = [...new Set(query.shades.filter((id) => availableShades.some((shade) => shade.id === id)))];
      const sort = data.sortOptions.some((option) => option.key === query.sort) ? query.sort : DEFAULT_SORT;

      let results = inCategory;
      if (selectedShades.length > 0) results = results.filter((product) => selectedShades.includes(product.shadeFamily));

      const term = (query.q ?? "").trim();
      if (term) {
        const needle = term.toLowerCase();
        results = results.filter((product) =>
          `${product.name} ${product.shortDescription} ${product.category} ${product.shadeFamily}`.toLowerCase().includes(needle),
        );
      }

      if (sort === "new") results = [...results].sort((a, b) => Number(b.newlyFeatured) - Number(a.newlyFeatured) || a.displayOrder - b.displayOrder);
      else if (sort === "name-asc") results = [...results].sort((a, b) => compareText(a.name, b.name));
      else if (sort === "name-desc") results = [...results].sort((a, b) => compareText(b.name, a.name));

      const perPage = Math.max(1, site.store.productsPerPage);
      const pageCount = Math.max(1, Math.ceil(results.length / perPage));
      const page = Math.min(Math.max(query.page, 1), pageCount);
      const pageProducts = results.slice((page - 1) * perPage, page * perPage);
      const pageStart = results.length === 0 ? 0 : (page - 1) * perPage + 1;

      const filters = { shades: selectedShades, search: term };
      return {
        category,
        products: pageProducts,
        totalMatches: results.length,
        categoryTotal: inCategory.length,
        page,
        pageCount,
        pageStart,
        pageEnd: pageStart === 0 ? 0 : pageStart + pageProducts.length - 1,
        availableShades,
        selectedShades,
        sort,
        search: term,
        urlFor: (number) => shopUrl(category.slug, filters, sort, number),
        urlWithoutSearch: shopUrl(category.slug, { ...filters, search: "" }, sort, 1),
        // The same search in another category; color filters are dropped because each category has its own colors.
        urlForCategory: (other) => shopUrl(other.slug, { shades: [], search: term }, null, 1),
      };
    },

    /** Products of the same category, closest color and collection first. */
    related(product) {
      const collections = new Set(product.collections);
      const score = (candidate) =>
        (candidate.shadeFamily === product.shadeFamily ? 4 : 0) +
        (candidate.featured ? 2 : 0) +
        candidate.collections.filter((name) => collections.has(name)).length;
      return products
        .filter((candidate) => candidate.id !== product.id && candidate.category === product.category)
        .sort((a, b) => score(b) - score(a) || a.displayOrder - b.displayOrder || compareText(a.name, b.name))
        .slice(0, site.store.relatedProductsLimit);
    },
  };

  /** Canonical shop links such as hijabs.html?shade=brown%2Cgray&sort=name-asc&page=2. */
  const shopUrl = (slug, filters, sort, page) => {
    const params = new URLSearchParams();
    if (filters.search && filters.search.trim()) params.set("q", filters.search.trim());
    if (filters.shades.length > 0) params.set("shade", filters.shades.join(","));
    if (sort && sort !== DEFAULT_SORT) params.set("sort", sort);
    if (page > 1) params.set("page", String(page));
    const query = params.toString();
    return `${slug}.html${query ? `?${query}` : ""}`;
  };

  /** Reads ?shade=a&shade=b or ?shade=a,b, ?q=, ?sort= and ?page= from the address bar. */
  const readShopQuery = (search = window.location.search) => {
    const params = new URLSearchParams(search);
    const page = Number.parseInt(params.get("page") ?? "1", 10);
    return {
      shades: params.getAll("shade").flatMap((value) => value.split(",").map((part) => part.trim()).filter(Boolean)),
      q: params.get("q") ?? "",
      sort: params.get("sort"),
      page: Number.isFinite(page) ? page : 1,
    };
  };

  /* ---------- Currency (prices are stored in the base currency) ---------- */
  const currencySettings = site.currency;
  const findCurrency = (code) =>
    code ? currencySettings.options.find((option) => option.code.toLowerCase() === String(code).toLowerCase()) ?? null : null;

  const currency = {
    label: currencySettings.label,
    options: currencySettings.options,
    get current() {
      return findCurrency(storage.get(currencySettings.storageKey, null)) ?? findCurrency(currencySettings.default) ?? currencySettings.options[0];
    },
    set(code) {
      const option = findCurrency(code);
      if (option) storage.set(currencySettings.storageKey, option.code);
    },
    /** A base-currency price in the visitor's currency, or the "price to be confirmed" label. */
    format(basePrice) {
      if (basePrice === null || basePrice === undefined || Number.isNaN(basePrice)) return currencySettings.pricePending;
      const option = currency.current;
      const factor = 10 ** option.decimals;
      const amount = Math.round((basePrice * option.rate + Number.EPSILON) * factor) / factor;
      // Whole amounts drop their decimals ("18 JD", not "18.00 JD").
      const number = Number.isInteger(amount)
        ? String(amount)
        : amount.toLocaleString("en-US", { minimumFractionDigits: option.decimals, maximumFractionDigits: option.decimals });
      return option.symbolFirst ? `${option.symbol}${number}` : `${number} ${option.symbol}`;
    },
    sum: (items) => items.reduce((total, item) => total + item.product.price * item.quantity, 0),
    subtotal(items) {
      if (items.some((item) => item.product.price === null)) return currencySettings.pricePending;
      return currency.format(currency.sum(items));
    },
    total(items, shipping) {
      if (items.some((item) => item.product.price === null)) return currencySettings.pricePending;
      return currency.format(currency.sum(items) + shipping);
    },
  };

  /* ---------- Bag: a line per product and size ---------- */
  const CART_KEY = "jude.cart";
  const maxQuantity = site.store.maxQuantity;
  const readLines = () => {
    const lines = storage.get(CART_KEY, []);
    return Array.isArray(lines) ? lines : [];
  };

  const cart = {
    maxQuantity,
    lines: readLines,
    /** Lines joined with their products (unknown ids are skipped). */
    items: () =>
      readLines()
        .map((line) => ({ product: catalog.findById(line.productId), quantity: line.quantity, size: line.size ?? null }))
        .filter((item) => item.product !== null),
    count: () => readLines().reduce((total, line) => total + line.quantity, 0),
    /** False when nothing was added (unknown product, or a sized product without a valid size). */
    add(productId, quantity, size) {
      const product = catalog.findById(productId);
      if (!product || !product.purchasable || !(quantity >= 1)) return false;
      // Sized products (sets) need one of their category's sizes; unsized products never carry one.
      const sizes = catalog.getCategory(product.category).sizes;
      const picked = sizes.find((option) => option.toLowerCase() === String(size ?? "").trim().toLowerCase()) ?? null;
      if (sizes.length > 0 && picked === null) return false;

      const lines = readLines();
      const index = lines.findIndex((line) => line.productId === productId && (line.size ?? null) === picked);
      const current = index >= 0 ? lines[index].quantity : 0;
      const next = { productId, quantity: Math.min(maxQuantity, current + quantity), size: picked };
      if (index >= 0) lines[index] = next;
      else lines.push(next);
      storage.set(CART_KEY, lines);
      return true;
    },
    update(productId, quantity, size) {
      const lines = readLines();
      const wanted = size ? size : null;
      const index = lines.findIndex((line) => line.productId === productId && (line.size ?? null) === wanted);
      if (index < 0) return;
      if (quantity <= 0) lines.splice(index, 1);
      else lines[index] = { ...lines[index], quantity: Math.min(maxQuantity, quantity) };
      storage.set(CART_KEY, lines);
    },
    clear: () => storage.set(CART_KEY, []),
  };

  /* ---------- Wishlist ---------- */
  const WISHLIST_KEY = "jude.wishlist";
  const readIds = () => {
    const ids = storage.get(WISHLIST_KEY, []);
    return Array.isArray(ids) ? ids : [];
  };
  const wishlist = {
    ids: readIds,
    count: () => readIds().length,
    contains: (id) => readIds().includes(id),
    /** True when the product is now saved, false when it was removed. */
    toggle(id) {
      const ids = readIds();
      const index = ids.indexOf(id);
      if (index >= 0) {
        ids.splice(index, 1);
        storage.set(WISHLIST_KEY, ids);
        return false;
      }
      if (!catalog.findById(id)) return false;
      ids.push(id);
      storage.set(WISHLIST_KEY, ids);
      return true;
    },
  };

  /* ---------- One-time values carried to the next page (order confirmation, toasts) ---------- */
  const flash = {
    set(key, value) {
      try {
        window.sessionStorage.setItem(`jude.${key}`, JSON.stringify(value));
      } catch {
        /* ignore */
      }
    },
    /** Reads and forgets the value, so a refresh doesn't show it again. */
    take(key) {
      try {
        const value = window.sessionStorage.getItem(`jude.${key}`);
        window.sessionStorage.removeItem(`jude.${key}`);
        return value === null ? null : JSON.parse(value);
      } catch {
        return null;
      }
    },
  };

  window.Jude = { site, catalog, currency, cart, wishlist, flash, storage, shopUrl, readShopQuery, esc, fmt, attr, opt, icon };
})();
