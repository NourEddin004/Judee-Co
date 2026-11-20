// "Track order" (2026-11-20): the popup from the menu, and the /track page. The server sends the
// result already drawn (the same partial the page uses), so both look the same.
import { config, text } from "./core.js";
import { onModalOpen } from "./ui.js";

const NUMBER = /^#?\s*[A-Za-z0-9]{6}\s*$/;

function initForm(form) {
  const input = form.querySelector("[data-track-input]");
  const error = form.querySelector("[data-track-error]");
  const submit = form.querySelector("[data-track-submit]");
  const output = form.parentElement.querySelector("[data-track-output]");

  const showError = (message) => {
    error.textContent = message;
    error.hidden = !message;
    input.setAttribute("aria-invalid", String(Boolean(message)));
  };

  input.addEventListener("input", () => {
    // Order numbers are shown in capitals on the receipt.
    const caret = input.selectionStart;
    input.value = input.value.toUpperCase();
    if (caret !== null) input.setSelectionRange(caret, caret);
    if (!error.hidden) showError("");
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const number = input.value.trim().replace(/^#/, "").replace(/\s+/g, "");
    if (!NUMBER.test(number)) {
      showError(text("trackInvalid"));
      input.focus();
      return;
    }
    showError("");
    submit.disabled = true;
    submit.setAttribute("aria-busy", "true");
    output.innerHTML = "";
    delete output.dataset.drawn;
    output.classList.add("is-loading");
    output.setAttribute("data-loading-text", text("trackSearching"));
    try {
      const url = new URL(config.routes.track, window.location.origin);
      url.searchParams.set("number", number);
      const response = await fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" });
      if (response.status === 429) throw new Error(text("trackBusy"));
      const body = await response.json().catch(() => ({}));
      if (!response.ok || !body.html) throw new Error(body.message || text("trackFailed"));
      // Server-rendered and escaped by Blade, from the store's own address.
      output.innerHTML = body.html;
      output.dataset.drawn = number; // the language switch looks the same order up again
      document.dispatchEvent(new CustomEvent("prices:changed"));
      output.querySelector(".ds-track-result")?.setAttribute("tabindex", "-1");
    } catch (failure) {
      showError(failure.message || text("trackFailed"));
      input.focus();
    } finally {
      submit.disabled = false;
      submit.removeAttribute("aria-busy");
      output.classList.remove("is-loading");
    }
  });
}

export function initTrack() {
  document.querySelectorAll("[data-track-form]").forEach(initForm);
  onModalOpen((id) => {
    if (id !== "track-order") return;
    // The input is the first thing to type in; focus it rather than the close button.
    window.requestAnimationFrame(() => document.querySelector('[data-modal="track-order"] [data-track-input]')?.focus());
  });
}
