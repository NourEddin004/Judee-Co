// The bag drawer, the bag page and "Add to cart" on cards.
import { bag, bagItems, currency, subtotalOf, text } from "./core.js";
import { onCurrencyChange, onModalOpen, refreshCounts, showToast } from "./ui.js";

const lineTotal = (item) => currency.format(item.product.price === null ? null : item.product.price * item.qty);

/** One bag line from the template in the page (D.5.9). */
function renderLine(item, onPage) {
  const node = document.getElementById("bag-line-template").content.firstElementChild.cloneNode(true);
  const name = item.product.name;
  node.classList.toggle("ds-cart-line--page", onPage);
  node.dataset.id = String(item.id);
  node.dataset.size = item.size ?? "";
  const image = node.querySelector("[data-line-image]");
  image.src = item.product.image ?? "";
  image.alt = name;
  const link = node.querySelector("[data-line-name]");
  link.href = item.product.url;
  link.textContent = name;
  const size = node.querySelector("[data-line-size]");
  size.hidden = item.size === null;
  size.textContent = item.size === null ? "" : text("size", { size: item.size });
  node.querySelector("[data-line-qty]").textContent = String(item.qty);
  const [minus, plus] = node.querySelectorAll("[data-line-step]");
  minus.setAttribute("aria-label", text("decrease", { product: name }));
  plus.setAttribute("aria-label", text("increase", { product: name }));
  plus.disabled = item.qty >= bag.maxQuantity;
  // A plus that stopped at 9 says why, right below the line's quantity (REQ-UX-27).
  const reason = node.querySelector("[data-line-reason]");
  reason.id = `line-reason-${item.id}-${item.size ?? "one"}-${onPage ? "page" : "drawer"}`;
  reason.hidden = !plus.disabled;
  reason.textContent = plus.disabled ? text("maxQuantity", { max: bag.maxQuantity }) : "";
  if (plus.disabled) plus.setAttribute("aria-describedby", reason.id);
  node.querySelector("[data-line-remove]").setAttribute("aria-label", text("remove", { product: name }));
  node.querySelector("[data-line-total]").textContent = lineTotal(item);
  return node;
}

const piecesOf = (items) => items.reduce((total, item) => total + item.qty, 0);

/** Content that takes longer than this to load shows a placeholder of the same size (REQ-UX-38). */
export const PLACEHOLDER_AFTER_MS = 300;

/** A grey stand-in for what is still loading: the text keeps its space but is not shown. */
export const markPlaceholder = (node, on) => node.classList.toggle("ds-placeholder", on);

/** An empty photo, so a stand-in shows its grey block instead of a broken-image icon. */
export const BLANK_IMAGE = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";

/** A bag line from the same template, with grey blocks where the photo, name and price go. */
function placeholderLine(line, onPage) {
  const node = document.getElementById("bag-line-template").content.firstElementChild.cloneNode(true);
  node.classList.toggle("ds-cart-line--page", onPage);
  node.removeAttribute("data-bag-line");
  node.inert = true;
  node.setAttribute("aria-hidden", "true");
  node.querySelector("[data-line-image]").src = BLANK_IMAGE;
  node.querySelector("[data-line-name]").textContent = "\u2007".repeat(16);
  node.querySelector("[data-line-total]").textContent = currency.format(0);
  node.querySelectorAll("[data-line-image], [data-line-name], [data-line-total]").forEach((part) => markPlaceholder(part, true));
  const size = node.querySelector("[data-line-size]");
  size.hidden = line.size === null;
  size.textContent = line.size === null ? "" : text("size", { size: line.size });
  node.querySelector("[data-line-qty]").textContent = String(line.qty);
  return node;
}

/** While the server is asked about the bag's products, its views show grey lines, never "Your bag is empty". */
function renderPlaceholders(lines) {
  const pieces = piecesOf(lines);
  document.querySelectorAll("[data-bag-subtotal]").forEach((node) => markPlaceholder(node, true));
  const drawerLines = document.querySelector('[data-bag-lines="drawer"]');
  if (drawerLines) {
    const empty = drawerLines.querySelector("[data-bag-empty]");
    drawerLines.replaceChildren(empty, ...lines.map((line) => placeholderLine(line, false)));
    empty.hidden = true;
    document.querySelector("[data-bag-meta]").textContent = text(pieces === 1 ? "item" : "items", { count: pieces });
  }
  const page = document.querySelector("[data-cart-page]");
  if (page) {
    page.querySelector('[data-bag-lines="page"]').replaceChildren(...lines.map((line) => placeholderLine(line, true)));
    page.querySelector("[data-bag-empty-page]").hidden = true;
    page.querySelectorAll("[data-bag-filled]").forEach((node) => (node.hidden = false));
    page.querySelector("[data-bag-count-label]").textContent = text(pieces === 1 ? "cartItem" : "cartItems", { count: pieces });
    page.querySelector("[data-bag-pieces]").textContent = String(pieces);
  }
  document.dispatchEvent(new CustomEvent("bag:waiting", { detail: { lines } }));
}

let firstRender = true;

/** Fills every bag view on the page: drawer, bag page, checkout summary. */
export async function renderBag() {
  refreshCounts();
  const lines = bag.lines();
  const loading = bagItems();
  // The page starts out showing an empty bag, so the first time the stand-ins come at once.
  const wait = lines.length > 0 ? setTimeout(() => renderPlaceholders(lines), firstRender ? 0 : PLACEHOLDER_AFTER_MS) : null;
  firstRender = false;
  let items;
  try {
    items = await loading;
  } catch {
    return; // keep what is on screen; the next change retries
  } finally {
    clearTimeout(wait);
  }
  refreshCounts();
  const pieces = piecesOf(items);
  const subtotal = currency.format(subtotalOf(items));
  document.querySelectorAll("[data-bag-subtotal]").forEach((node) => {
    node.textContent = subtotal;
    markPlaceholder(node, false);
  });

  // Drawer
  const drawerLines = document.querySelector('[data-bag-lines="drawer"]');
  if (drawerLines) {
    const empty = drawerLines.querySelector("[data-bag-empty]");
    drawerLines.replaceChildren(empty, ...items.map((item) => renderLine(item, false)));
    empty.hidden = items.length > 0;
    document.querySelector("[data-bag-meta]").textContent = text(pieces === 1 ? "item" : "items", { count: pieces });
    const checkout = document.querySelector("[data-bag-checkout]");
    checkout.setAttribute("aria-disabled", String(items.length === 0));
    checkout.classList.toggle("is-disabled", items.length === 0);
  }

  // Bag page
  const page = document.querySelector("[data-cart-page]");
  if (page) {
    page.querySelector('[data-bag-lines="page"]').replaceChildren(...items.map((item) => renderLine(item, true)));
    page.querySelector("[data-bag-empty-page]").hidden = items.length > 0;
    page.querySelectorAll("[data-bag-filled]").forEach((node) => (node.hidden = items.length === 0));
    page.querySelector("[data-bag-count-label]").textContent = text(pieces === 1 ? "cartItem" : "cartItems", { count: pieces });
    page.querySelector("[data-bag-pieces]").textContent = String(pieces);
  }

  document.dispatchEvent(new CustomEvent("bag:rendered", { detail: { items } }));
}

function initBagActions() {
  document.addEventListener("click", (event) => {
    // "Add to cart" on a card adds one piece and updates the count; the drawer stays closed (REQ-DMO-34).
    const add = event.target.closest("[data-add-to-bag]");
    if (add && !add.disabled) {
      bag.add(Number(add.dataset.addToBag), 1);
      showToast(text("added", { product: add.dataset.name }), { opensBag: true });
      renderBag();
      return;
    }

    const line = event.target.closest("[data-bag-line]");
    if (!line) return;
    const id = Number(line.dataset.id);
    const size = line.dataset.size || null;
    const current = bag.lines().find((entry) => entry.id === id && entry.size === size)?.qty ?? 0;
    const step = event.target.closest("[data-line-step]");
    const remove = event.target.closest("[data-line-remove]");
    if (!step && !remove) return;
    // Minus at 1 removes the line (REQ-DMO-31).
    bag.update(id, size, remove ? 0 : current + Number(step.dataset.lineStep));
    const onPage = Boolean(line.closest("[data-cart-page]"));
    renderBag().then(() => {
      if (onPage) showToast(text("updated")); // REQ-DMO-36
      // The first close control is the X in the head; "Continue shopping" closes the drawer too.
      else document.querySelector("[data-cart-drawer] [data-modal-close]")?.focus();
    });
  });

  onModalOpen((id) => {
    if (id === "cart-drawer") renderBag();
  });
  onCurrencyChange(renderBag);
  window.addEventListener("storage", renderBag);
}

export function initBag() {
  initBagActions();
  if (document.querySelector("[data-cart-page], [data-checkout-page]")) renderBag();
}
