// "Find my size" (2026-11-20): height and weight → the size from the product's size guide.
// Optional help only: the size picker works the same without it. Same rule as App\Domain\Catalog\SizeFit.
import { storage, text } from "./core.js";
import { closeModal, onModalOpen, showToast } from "./ui.js";

const SAVED = "jude.fit.v1";
const outside = (value, min, max) => (value < min ? min - value : value > max ? value - max : 0);

/** The closest row, weight counting twice; ties go to the larger (later) size. */
export function recommendSize(rows, height, weight) {
  let best = null;
  rows.forEach((row) => {
    const distance = 2 * outside(weight, row.weight_min, row.weight_max) + outside(height, row.height_min, row.height_max);
    if (best === null || distance <= best.distance) best = { size: row.size, distance };
  });
  return best === null ? null : { size: best.size, exact: best.distance === 0 };
}

export function initFit() {
  const form = document.querySelector("[data-fit-form]");
  if (!form) return;
  const rows = JSON.parse(form.querySelector("[data-fit-rows]").textContent);
  const heightInput = form.querySelector("[data-fit-height]");
  const weightInput = form.querySelector("[data-fit-weight]");
  const result = document.querySelector("[data-fit-result]");
  const choose = result.querySelector("[data-fit-choose]");
  const badge = document.querySelector("[data-fit-badge]");
  let recommended = null;

  const saved = storage.get(SAVED, null);
  if (saved?.height) heightInput.value = saved.height;
  if (saved?.weight) weightInput.value = saved.weight;

  const setError = (name, message) => {
    const error = form.querySelector(`[data-fit-error="${name}"]`);
    const input = name === "height" ? heightInput : weightInput;
    error.textContent = message;
    error.hidden = !message;
    input.setAttribute("aria-invalid", String(Boolean(message)));
  };

  const show = (focusResult) => {
    const height = Number(heightInput.value);
    const weight = Number(weightInput.value);
    const badHeight = !(height >= 120 && height <= 210);
    const badWeight = !(weight >= 30 && weight <= 180);
    setError("height", badHeight ? text("fitInvalidHeight") : "");
    setError("weight", badWeight ? text("fitInvalidWeight") : "");
    if (badHeight || badWeight) {
      (badHeight ? heightInput : weightInput).focus();
      return;
    }
    storage.set(SAVED, { height, weight });
    recommended = recommendSize(rows, height, weight);
    if (!recommended) return;
    result.hidden = false;
    result.querySelector("[data-fit-size]").textContent = recommended.size;
    result.querySelector("[data-fit-text]").textContent = text(recommended.exact ? "fitExact" : "fitClosest", { size: recommended.size });
    choose.textContent = text("fitChoose", { size: recommended.size });
    if (badge) {
      badge.textContent = recommended.size;
      badge.hidden = false;
    }
    if (focusResult) choose.focus();
  };

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    show(true);
  });
  [heightInput, weightInput].forEach((input) => input.addEventListener("input", () => setError(input === heightInput ? "height" : "weight", "")));

  // "Choose size M" ticks that size in the picker and goes back to the page.
  choose.addEventListener("click", () => {
    if (!recommended) return;
    const radio = [...document.querySelectorAll("[data-size-input]")].find((input) => input.value === recommended.size);
    closeModal();
    if (radio) {
      radio.checked = true;
      radio.dispatchEvent(new Event("change", { bubbles: true }));
      radio.focus();
    }
    showToast(text("fitChosen", { size: recommended.size }));
  });

  onModalOpen((id) => {
    if (id !== "fit-finder") return;
    window.requestAnimationFrame(() => (heightInput.value ? form.querySelector('[type="submit"]') : heightInput).focus());
  });
}
