// Shop filters (D.5.7): a full-screen sheet below 1024 px applied with "Apply filters",
// a side column from 1024 px where each change applies at once (REQ-DMO-16 to 20).
import { focusableSelector } from "./core.js";
import { linkAllBox } from "./filters.js";

/** Clean address: ?q=…&shade=black,brown&size=m,xl, defaults left out (D.6.2). */
function filtersUrl(form) {
  const data = new FormData(form);
  const params = new URLSearchParams();
  const q = String(data.get("q") ?? "").trim();
  if (q) params.set("q", q);
  for (const name of ["shade", "size"]) {
    const values = data.getAll(`${name}[]`).map(String);
    if (values.length) params.set(name, values.join(","));
  }
  const query = params.toString();
  return `${form.action}${query ? `?${query}` : ""}`;
}

export function initShop() {
  const filters = document.querySelector("[data-filters]");
  if (!filters) return;
  const body = document.body;
  const closeButton = filters.querySelector("[data-filters-close]");
  // The close button only shows while the filters are a sheet.
  const appliesInstantly = () => !closeButton || window.getComputedStyle(closeButton).display === "none";
  const allShades = filters.querySelector("[data-filter-all-shades]");
  const shadeBoxes = [...filters.querySelectorAll("[data-filter-shade]")];
  const allSizes = filters.querySelector("[data-filter-all-sizes]");
  const sizeBoxes = [...filters.querySelectorAll("[data-filter-size]")];

  filters.addEventListener("submit", (event) => {
    event.preventDefault();
    window.location.assign(filtersUrl(filters));
  });

  document.addEventListener("change", (event) => {
    const input = event.target;
    if (input.form !== filters) return;
    linkAllBox(allShades, shadeBoxes, input); // REQ-DMO-16, REQ-DMO-17
    linkAllBox(allSizes, sizeBoxes, input); // REQ-DMO-94
    if (input.name === "q") return;
    // Colours and sizes apply at once only from 1024 px (REQ-DMO-18, REQ-DMO-19).
    if (appliesInstantly()) filters.requestSubmit();
  });

  const setSheet = (open) => {
    filters.classList.toggle("is-open", open);
    document.querySelectorAll("[data-filters-open]").forEach((button) => button.setAttribute("aria-expanded", String(open)));
    body.classList.toggle("is-locked", open);
    if (open) filters.querySelector(focusableSelector)?.focus();
  };
  document.querySelectorAll("[data-filters-open]").forEach((button) => button.addEventListener("click", () => setSheet(true)));
  closeButton?.addEventListener("click", () => setSheet(false));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && filters.classList.contains("is-open")) setSheet(false); // REQ-DMO-20
  });
}
