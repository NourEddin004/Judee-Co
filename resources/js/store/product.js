// Product page: gallery, colours, quantity stepper, required size, "Add to bag", back link (D.5.8).
import { bag, text } from "./core.js";
import { showToast } from "./ui.js";
import { showAdded } from "./bag.js";

/** A thumbnail shows its photo in the frame (REQ-DMO-54). The gallery listens, so a colour's photos put in later work too. */
function initGallery() {
  document.querySelectorAll("[data-gallery]").forEach((gallery) =>
    gallery.addEventListener("click", (event) => {
      const thumb = event.target.closest("[data-gallery-thumb]");
      if (!thumb) return;
      const main = gallery.querySelector("[data-gallery-main]");
      main.srcset = thumb.dataset.gallerySrcset;
      main.src = thumb.dataset.galleryThumb;
      gallery.querySelectorAll("[data-gallery-thumb]").forEach((other) => {
        other.classList.toggle("is-active", other === thumb);
        if (other === thumb) other.setAttribute("aria-current", "true");
        else other.removeAttribute("aria-current");
      });
    }),
  );
}

/**
 * Choosing a colour shows its photos, name and stock without reloading, and puts ?color= in the
 * address. An out-of-stock colour can be chosen; "Add to bag" is then disabled (REQ-STR-25, REQ-DMO-99, 100).
 */
function initColors() {
  const form = document.querySelector("[data-buy]");
  const gallery = document.querySelector("[data-gallery]");
  const facts = document.querySelector("[data-color-facts]");
  if (!form || !gallery || !facts) return;
  const show = (input) => {
    const photos = document.querySelector(`[data-gallery-for="${input.value}"]`);
    if (photos) gallery.replaceChildren(photos.content.cloneNode(true));
    const inStock = input.dataset.inStock === "1";
    facts.querySelector("[data-color-name]").textContent = input.dataset.colorLabel;
    facts.querySelector("[data-stock-in]").hidden = !inStock;
    facts.querySelector("[data-stock-out]").hidden = inStock;
    form.querySelector("[data-add-button]").disabled = !inStock;
    form.dataset.buy = input.value;
    form.dataset.name = input.dataset.itemName;
    const url = new URL(window.location.href);
    url.searchParams.set("color", input.dataset.colorSlug);
    window.history.replaceState(window.history.state, "", url);
    // The other language opens on the same colour (REQ-DMO-84).
    document.querySelectorAll("[data-language-link]").forEach((link) => {
      const target = new URL(url);
      target.searchParams.set("lang", new URL(link.href).searchParams.get("lang"));
      link.href = target.href;
    });
  };
  form.addEventListener("change", (event) => {
    if (event.target.name === "color") show(event.target);
  });
  // A browser that restores form choices on reload may check another colour than the page shows.
  const checked = form.querySelector('input[name="color"]:checked');
  if (checked && checked.value !== form.dataset.buy) show(checked);
}

/** Quantity stays between 1 and 9; a button at its limit is disabled (REQ-DMO-55). */
function initSteppers() {
  document.querySelectorAll("[data-stepper]").forEach((stepper) => {
    const input = stepper.querySelector("input");
    const [minus, plus] = stepper.querySelectorAll("[data-stepper-step]");
    const min = Number(input.min) || 1;
    const max = Number(input.max) || bag.maxQuantity;
    // Plus at 9 says why it stopped (REQ-UX-27); minus at 1 needs no line, the 1 is on screen.
    const reason = stepper.parentElement.querySelector("[data-stepper-reason]");
    const set = (value) => {
      const number = Math.min(max, Math.max(min, Math.trunc(Number(value)) || min));
      input.value = String(number);
      minus.disabled = number <= min;
      plus.disabled = number >= max;
      if (reason) reason.hidden = !plus.disabled;
    };
    [minus, plus].forEach((button) => button.addEventListener("click", () => set(Number(input.value) + Number(button.dataset.stepperStep))));
    input.addEventListener("change", () => set(input.value));
    set(input.value);
  });
}

function initBuyForm() {
  const form = document.querySelector("[data-buy]");
  if (!form) return;
  const picker = form.querySelector(".ds-size-picker");
  const sizeInputs = [...form.querySelectorAll("[data-size-input]")];
  const setInvalid = (invalid) => {
    if (!picker) return;
    picker.classList.toggle("is-invalid", invalid);
    picker.querySelector("[data-size-error]").hidden = !invalid;
  };
  sizeInputs.forEach((input) => input.addEventListener("change", () => setInvalid(false)));

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (form.querySelector("[data-add-button]")?.disabled) return; // out of stock
    const size = sizeInputs.find((input) => input.checked)?.value ?? null;
    // A sized product cannot go in the bag without a size (REQ-STR-14, REQ-DMO-30).
    if (sizeInputs.length > 0 && size === null) {
      setInvalid(true);
      sizeInputs[0].focus();
      showToast(text("sizeFirst"));
      return;
    }
    const quantity = Math.min(bag.maxQuantity, Math.max(1, Number(form.querySelector('[name="quantity"]').value) || 1));
    bag.add(Number(form.dataset.buy), quantity, size);
    // The bag opens with the new line marked, so it is plain that it went in (2026-11-20).
    showAdded(Number(form.dataset.buy), size);
    const button = form.querySelector("[data-add-button]");
    button.classList.add("is-added");
    window.setTimeout(() => button.classList.remove("is-added"), 1800);
  });
}

/** Back in history when the previous page is on the store, else the category page (REQ-DMO-78). */
function initBackLink() {
  document.querySelectorAll("[data-back]").forEach((link) =>
    link.addEventListener("click", (event) => {
      let sameSite = false;
      try {
        sameSite = document.referrer !== "" && new URL(document.referrer).origin === window.location.origin;
      } catch {
        sameSite = false;
      }
      if (sameSite && window.history.length > 1) {
        event.preventDefault();
        window.history.back();
      }
    }),
  );
}

export function initProduct() {
  initGallery();
  initColors();
  initSteppers();
  initBuyForm();
  initBackLink();
}
