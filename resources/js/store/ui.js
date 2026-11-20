// Page-wide parts: toast, dialogs (menu, bag drawer), dropdowns, counts, prices and back to top.
import { bag, config, currency, focusableSelector, reducedMotion, text } from "./core.js";

const body = document.body;

/* ---------- Toast: one at a time, 2800 ms (REQ-DMO-37, REQ-DMO-38) ---------- */
let toastTimer = 0;
export function showToast(message, { opensBag = false } = {}) {
  const toast = document.querySelector("[data-toast]");
  if (!toast || !message) return;
  toast.querySelector("[data-toast-message]").textContent = message;
  // Tapping an "added to bag" toast opens the bag (REQ-CHK-06).
  toast.classList.toggle("is-action", opensBag);
  toast.classList.add("is-visible");
  window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => toast.classList.remove("is-visible"), Number(config.toastMs) || 2800);
}

/* ---------- Dialogs: scroll lock, focus kept inside, Escape, focus returned (REQ-DMO-48 to 51) ---------- */
const pageShell = () => document.querySelector("[data-page-shell]");
let openModal = null;
let returnFocus = null;
const modalListeners = new Set();

export const onModalOpen = (listener) => modalListeners.add(listener);

const setOpenersExpanded = (id, expanded) =>
  document.querySelectorAll(`[data-modal-open="${id}"]`).forEach((opener) => opener.setAttribute("aria-expanded", String(expanded)));

const lockPage = (locked) => {
  body.classList.toggle("is-locked", locked);
  const shell = pageShell();
  if (shell) shell.inert = locked;
};

export function closeModal() {
  if (!openModal) return;
  openModal.hidden = true;
  setOpenersExpanded(openModal.dataset.modal, false);
  openModal = null;
  lockPage(false);
  returnFocus?.focus();
}

export function showModal(id) {
  const modal = document.querySelector(`[data-modal="${id}"]`);
  if (!modal) return;
  if (openModal && openModal !== modal) {
    openModal.hidden = true;
    setOpenersExpanded(openModal.dataset.modal, false);
  } else {
    returnFocus = document.activeElement;
  }
  openModal = modal;
  modal.hidden = false;
  setOpenersExpanded(id, true);
  lockPage(true);
  modalListeners.forEach((listener) => listener(id));
  window.requestAnimationFrame(() => modal.querySelector(focusableSelector)?.focus());
}

function initModals() {
  document.querySelector("[data-toast]")?.addEventListener("click", (event) => {
    if (!event.currentTarget.classList.contains("is-action")) return;
    event.currentTarget.classList.remove("is-visible");
    showModal("cart-drawer");
  });

  document.addEventListener("click", (event) => {
    const opener = event.target.closest("[data-modal-open]");
    if (opener) {
      event.preventDefault();
      showModal(opener.dataset.modalOpen);
      return;
    }
    if (event.target.closest("[data-modal-close]") || (openModal && event.target === openModal)) closeModal();
  });

  document.addEventListener("keydown", (event) => {
    if (!openModal) return;
    if (event.key === "Escape") {
      event.preventDefault();
      closeModal();
      return;
    }
    if (event.key !== "Tab") return;
    const focusable = [...openModal.querySelectorAll(focusableSelector)].filter((node) => node.offsetParent !== null);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
}

/* ---------- Dropdowns: Escape and a click outside close them (REQ-DMO-46, REQ-DMO-47) ---------- */
function initDropdowns() {
  document.querySelectorAll("[data-dropdown]").forEach((dropdown) => {
    const toggle = dropdown.querySelector("[data-dropdown-toggle]");
    const menu = dropdown.querySelector("[data-dropdown-menu]");
    const setOpen = (open) => {
      menu.hidden = !open;
      toggle.setAttribute("aria-expanded", String(open));
      if (open) menu.querySelector("button, a")?.focus();
    };
    toggle.addEventListener("click", () => setOpen(menu.hidden));
    document.addEventListener("click", (event) => {
      if (!dropdown.contains(event.target)) setOpen(false);
    });
    dropdown.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !menu.hidden) {
        setOpen(false);
        toggle.focus();
      }
    });
    menu.addEventListener("click", (event) => {
      if (event.target.closest("[data-currency]")) {
        setOpen(false);
        toggle.focus();
      }
    });
  });
}

/* ---------- Count on the bag icon ---------- */
export function refreshCounts() {
  const count = bag.count();
  document.querySelectorAll('[data-count="cart"]').forEach((badge) => {
    badge.textContent = String(count);
    badge.hidden = count === 0;
  });
  document.querySelectorAll('[data-count-text="cart"]').forEach((label) => (label.textContent = String(count)));
  // The bag link's name includes the number on its badge: "Open bag, 2 items" (WCAG 2.5.3).
  document.querySelectorAll('[data-count-label="cart"]').forEach((link) => {
    link.dataset.label ??= link.getAttribute("aria-label");
    link.setAttribute("aria-label", count ? `${link.dataset.label}, ${text(count === 1 ? "cartItem" : "cartItems", { count })}` : link.dataset.label);
  });
}

/** The bag badge grows for a moment, so a new piece in the bag is seen (2026-11-20). */
export function bumpBagBadge() {
  document.querySelectorAll('[data-count="cart"]').forEach((badge) => {
    badge.classList.remove("is-bumped");
    void badge.offsetWidth; // restart the animation
    badge.classList.add("is-bumped");
  });
}

/* ---------- Prices in the chosen currency (REQ-STR-16, REQ-STR-17) ---------- */
function applyCurrency() {
  document.querySelectorAll("[data-price]").forEach((node) => {
    node.textContent = currency.format(node.dataset.price === "" ? null : node.dataset.price);
  });
  const code = currency.code;
  document.querySelectorAll("[data-currency-code]").forEach((node) => (node.textContent = code));
  document.querySelectorAll("[data-currency-toggle]").forEach((toggle) => {
    const name = document.querySelector(`[data-currency="${code}"][data-currency-name]`)?.dataset.currencyName ?? code;
    // The name starts from the code shown on the button, so speech control users can say it (WCAG 2.5.3).
    toggle.setAttribute("aria-label", `${text("currency")}: ${code}, ${name}`);
  });
  document.querySelectorAll("[data-currency]").forEach((option) => {
    const current = option.dataset.currency === code;
    option.setAttribute(option.getAttribute("role") === "menuitemradio" ? "aria-checked" : "aria-pressed", String(current));
    const check = option.querySelector("[data-currency-check]");
    if (check) check.hidden = !current;
  });
}

const currencyListeners = new Set();
export const onCurrencyChange = (listener) => currencyListeners.add(listener);

function initCurrency() {
  document.addEventListener("click", (event) => {
    const option = event.target.closest("[data-currency]");
    if (!option) return;
    currency.set(option.dataset.currency);
    applyCurrency();
    currencyListeners.forEach((listener) => listener());
  });
  applyCurrency();
  // Prices drawn later (an order found by "Track order") follow the chosen currency too.
  document.addEventListener("prices:changed", applyCurrency);
}

/* ---------- Back to top: after 600 px, smooth unless reduced motion (REQ-DMO-52, REQ-DMO-53) ---------- */
function initBackToTop() {
  const button = document.querySelector("[data-back-to-top]");
  if (!button) return;
  const offset = Number(config.backToTopOffset) || 600;
  const update = () => (button.hidden = window.scrollY <= offset);
  window.addEventListener("scroll", update, { passive: true });
  update();
  button.addEventListener("click", () => window.scrollTo({ top: 0, behavior: reducedMotion() ? "auto" : "smooth" }));
}

export function initUi() {
  initModals();
  initDropdowns();
  initCurrency();
  initBackToTop();
  refreshCounts();
  // Another tab changed the bag.
  window.addEventListener("storage", refreshCounts);
}
