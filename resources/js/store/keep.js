// The store's side of keeping the visitor's place across the language switch (see ../keep-place.js):
// the open popup (menu, bag, track order, find my size), the filter sheet, and a looked-up order.
import { keepPlaceAcrossLanguages } from "../keep-place.js";
import { showModal } from "./ui.js";

const adapter = {
  capture() {
    const modal = document.querySelector("[data-modal]:not([hidden])")?.dataset.modal ?? null;
    const filters = document.querySelector("[data-filters]")?.classList.contains("is-open") ?? false;
    // An order found with "Track order" is drawn by the page, so it is looked up again in the new language.
    const tracked = [...document.querySelectorAll("[data-track-form]")]
      .map((form, index) => (form.parentElement.querySelector("[data-track-output]")?.dataset.drawn ? index : null))
      .filter((index) => index !== null);
    return { modal, filters, tracked };
  },
  restore(extra) {
    if (!extra) return;
    if (extra.filters) document.querySelector("[data-filters-open]")?.click();
    if (extra.modal) showModal(extra.modal);
    const forms = [...document.querySelectorAll("[data-track-form]")];
    extra.tracked?.forEach((index) => forms[index]?.requestSubmit());
  },
};

export function initKeepPlace() {
  keepPlaceAcrossLanguages({ links: "[data-language-link]", adapter });
}
