// Shared helpers: the page's settings, browser storage, text, currency, and the bag.
// The bag and currency are kept in the customer's browser (REQ-CHK-01, REQ-DMO-32, REQ-DMO-44).

export const config = JSON.parse(document.getElementById("store-config")?.textContent ?? "{}");

export const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;

export const focusableSelector =
  'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/** "Add :product to cart" with { product: "Rosemary Hijab" } → "Add Rosemary Hijab to cart". */
export const text = (key, values = {}) =>
  String(config.text?.[key] ?? key).replace(/:([a-z]+)/g, (match, name) => (name in values ? String(values[name]) : match));

/** Delivery inside Jordan is free from this subtotal, in JD (null: no free delivery). */
export const freeDeliveryFrom = config.freeDeliveryFrom === null || config.freeDeliveryFrom === undefined ? null : Number(config.freeDeliveryFrom);
export const freeDelivery = (subtotal) => freeDeliveryFrom !== null && subtotal !== null && subtotal >= freeDeliveryFrom;

export const storage = {
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

/* ---------- Currency: prices are stored and charged in JOD (REQ-DMO-45) ---------- */
const CURRENCIES = {
  JOD: { rate: 1, format: (number) => `${number} JD` },
  USD: { rate: Number(config.usdRate) || 1, format: (number) => `$${number}` },
};

export const currency = {
  get code() {
    const saved = storage.get(config.storage.currency, "JOD");
    return saved in CURRENCIES ? saved : "JOD";
  },
  set(code) {
    if (code in CURRENCIES) storage.set(config.storage.currency, code);
  },
  /** "17 JD", "12.50 JD", "$23.97"; whole amounts drop their decimals (REQ-DMO-39 to 43). */
  format(jod) {
    if (jod === null || jod === undefined || jod === "" || Number.isNaN(Number(jod))) return text("pricePending");
    const option = CURRENCIES[currency.code];
    const amount = Math.round((Number(jod) * option.rate + Number.EPSILON) * 100) / 100;
    const number = Number.isInteger(amount)
      ? String(amount)
      : amount.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return option.format(number);
  },
};

/* ---------- Bag: one line per product colour and size (REQ-DMO-27 to 31) ---------- */
const maxQuantity = Number(config.maxQuantity) || 9;
const clampQuantity = (value) => Math.min(maxQuantity, Math.max(0, Math.trunc(Number(value) || 0)));

const readLines = () => {
  const lines = storage.get(config.storage.bag, []);
  return Array.isArray(lines)
    ? lines
        .filter((line) => Number.isInteger(line?.id) && line.id > 0)
        .map((line) => ({ id: line.id, size: typeof line.size === "string" && line.size ? line.size : null, qty: clampQuantity(line.qty) }))
        .filter((line) => line.qty > 0)
    : [];
};
const sameLine = (line, id, size) => line.id === id && line.size === (size || null);

export const bag = {
  maxQuantity,
  lines: readLines,
  count: () => readLines().reduce((total, line) => total + line.qty, 0),
  add(id, qty, size = null) {
    const lines = readLines();
    const index = lines.findIndex((line) => sameLine(line, id, size));
    const current = index >= 0 ? lines[index].qty : 0;
    const next = { id, size: size || null, qty: clampQuantity(current + qty) };
    if (index >= 0) lines[index] = next;
    else lines.push(next);
    storage.set(config.storage.bag, lines);
  },
  /** A quantity of 0 removes the line. */
  update(id, size, qty) {
    const lines = readLines();
    const index = lines.findIndex((line) => sameLine(line, id, size));
    if (index < 0) return;
    if (clampQuantity(qty) === 0) lines.splice(index, 1);
    else lines[index] = { ...lines[index], qty: clampQuantity(qty) };
    storage.set(config.storage.bag, lines);
  },
  /** Drops lines whose product is no longer on sale. */
  keepOnly(ids) {
    const keep = new Set(ids);
    storage.set(config.storage.bag, readLines().filter((line) => keep.has(line.id)));
  },
  /** Drops every line of these products, e.g. the ones checkout could not order. */
  drop(ids) {
    const gone = new Set(ids);
    storage.set(config.storage.bag, readLines().filter((line) => !gone.has(line.id)));
  },
  clear: () => storage.set(config.storage.bag, []),
};

/* ---------- Product data for the bag, from the server ---------- */
export const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";
let productCache = null;

/** The next bag view asks the server again, e.g. after checkout found that something changed. */
export const forgetBagProducts = () => {
  productCache = null;
};

/** Current name, photo, price and stock of the bag's product colours, keyed by id. */
export async function bagProducts() {
  const ids = [...new Set(bag.lines().map((line) => line.id))];
  if (ids.length === 0) return new Map();
  if (productCache && ids.every((id) => productCache.has(id))) return productCache;
  const response = await fetch(config.routes.check, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf(), "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify({ ids }),
  });
  if (!response.ok) throw new Error(`Bag check failed (${response.status})`);
  const { products } = await response.json();
  productCache = new Map(products.map((product) => [product.id, product]));
  bag.keepOnly(products.filter((product) => product.purchasable).map((product) => product.id));
  return productCache;
}

/** Bag lines joined with their products. */
export async function bagItems() {
  const products = await bagProducts();
  return bag
    .lines()
    .map((line) => ({ ...line, product: products.get(line.id) }))
    .filter((item) => item.product);
}

/** Subtotal in JOD, or null when a price is still to be confirmed. */
export const subtotalOf = (items) =>
  items.some((item) => item.product.price === null) ? null : items.reduce((total, item) => total + item.product.price * item.qty, 0);
