// Checkout (D.5.10, D.6.6): summary, phone country and number, delivery fee by city, area picker, the
// checks of REQ-DMO-56 to 72, and sending the order to the server (REQ-DMO-73 to 75, C.6).
import { bag, config, csrf, currency, forgetBagProducts, reducedMotion, subtotalOf, text } from "./core.js";
import { BLANK_IMAGE, markPlaceholder, renderBag } from "./bag.js";
import { onCurrencyChange } from "./ui.js";
import { editPhone, fitPhone, isValidPhone } from "./phone.js";

const OTHER = "__other";

/** The summary's lines; an item without its product yet is a grey stand-in of the same size (REQ-UX-38). */
function renderSummary(form, items) {
  const list = document.querySelector("[data-checkout-lines]");
  const template = document.getElementById("summary-line-template");
  list.replaceChildren(
    ...items.map((item) => {
      const node = template.content.firstElementChild.cloneNode(true);
      const image = node.querySelector("[data-line-image]");
      const name = node.querySelector("[data-line-name]");
      const lineTotal = node.querySelector("[data-line-total]");
      if (item.product) {
        image.src = item.product.image ?? "";
        name.textContent = item.product.name;
        lineTotal.textContent = currency.format(item.product.price === null ? null : item.product.price * item.qty);
      } else {
        node.setAttribute("aria-hidden", "true");
        image.src = BLANK_IMAGE;
        name.textContent = "\u2007".repeat(16);
        lineTotal.textContent = currency.format(0);
        [image, name, lineTotal].forEach((part) => markPlaceholder(part, true));
      }
      node.querySelector("[data-line-qty]").textContent = text("qty", { qty: item.qty });
      const size = node.querySelector("[data-line-size]");
      size.hidden = item.size === null;
      size.textContent = item.size === null ? "" : text("size", { size: item.size });
      return node;
    }),
  );
}

export function initCheckout() {
  const form = document.querySelector("[data-checkout]");
  if (!form) return;

  // An empty bag goes to the bag page (D.6.6).
  if (bag.count() === 0) {
    window.location.replace(config.routes.cart);
    return;
  }

  let items = [];
  const city = form.querySelector("[data-city-select]");
  // Jordan, or a Gulf country with its own fee (REQ-CHK-26, 31).
  const destination = form.querySelector("[data-destination]");
  const isJordan = () => !destination || destination.value === "jordan";
  const shipping = document.querySelector("[data-checkout-shipping]");
  const total = document.querySelector("[data-checkout-total]");

  let waiting = false; // the bag's prices are still on their way

  // Delivery and Total follow the chosen city at once (REQ-DMO-59).
  const updateTotals = () => {
    const fee = !isJordan()
      ? Number(destination.selectedOptions[0].dataset.fee)
      : city.value ? Number(city.selectedOptions[0].dataset.fee) : null;
    const subtotal = subtotalOf(items);
    shipping.textContent = fee === null ? text("choose_city") : currency.format(fee);
    if (waiting) return; // the total waits for the bag's prices
    total.textContent = currency.format(subtotal === null ? null : subtotal + (fee ?? 0));
    markPlaceholder(total, false);
  };

  document.addEventListener("bag:waiting", (event) => {
    waiting = true;
    renderSummary(form, event.detail.lines);
    markPlaceholder(total, true);
  });

  document.addEventListener("bag:rendered", (event) => {
    waiting = false;
    items = event.detail.items;
    if (items.length === 0) {
      window.location.replace(config.routes.cart);
      return;
    }
    renderSummary(form, items);
    updateTotals();
  });
  onCurrencyChange(updateTotals);

  /* Phone: the picked country's flag, code and example number, and digits only, up to the
     country's length (REQ-DMO-89 to 91). */
  const country = form.querySelector("[data-phone-country]");
  const phone = form.querySelector("[data-phone-input]");
  const phoneCountry = () => {
    const option = country.selectedOptions[0];
    return { dial: option.dataset.dial, digits: option.dataset.digits.split(" ").map(Number) };
  };
  let phoneBefore = (phone.value = fitPhone(phone.value, phoneCountry()));
  const onPhoneInput = (event) => {
    if (event.isComposing) return; // the keyboard is still putting the character together
    const edit = editPhone(phoneBefore, phone.value, phone.selectionStart ?? phone.value.length, phoneCountry());
    if (edit.value !== phone.value) {
      phone.value = edit.value;
      phone.setSelectionRange(edit.caret, edit.caret);
    }
    phoneBefore = edit.value;
  };
  phone.addEventListener("input", onPhoneInput);
  phone.addEventListener("compositionend", onPhoneInput);
  country.addEventListener("change", () => {
    const option = country.selectedOptions[0];
    form.querySelector("[data-phone-flag]").textContent = option.dataset.flag;
    form.querySelector("[data-phone-dial]").textContent = `+${option.dataset.dial}`;
    phone.placeholder = option.dataset.placeholder;
    phoneBefore = phone.value = fitPhone(phone.value, phoneCountry());
    clearError("phone"); // its message named the other country
    phone.focus();
  });

  /* Area picker: disabled until a city is chosen, searchable, keyboard driven (REQ-DMO-60 to 65). */
  const areas = JSON.parse(form.querySelector("[data-areas]").textContent);
  const areaValue = form.querySelector("[data-area-value]");
  const picker = form.querySelector("[data-area-picker]");
  const trigger = picker.querySelector("[data-picker-trigger]");
  const valueLabel = picker.querySelector("[data-picker-value]");
  const panel = picker.querySelector("[data-picker-panel]");
  const search = picker.querySelector("[data-picker-search]");
  const list = picker.querySelector("[data-picker-list]");
  const empty = picker.querySelector("[data-picker-empty]");
  const otherField = form.querySelector("[data-area-other]");
  const otherInput = otherField.querySelector("input");
  let options = [];
  let active = -1;

  const setArea = (value, { focusOther = false } = {}) => {
    areaValue.value = value;
    const placeholder = city.value ? text("choose_area") : text("city_first");
    valueLabel.textContent = value === OTHER ? text("area_other") : value || placeholder;
    valueLabel.classList.toggle("is-placeholder", !value);
    otherField.hidden = value !== OTHER;
    if (value === OTHER && focusOther) otherInput.focus();
  };

  const setActive = (index) => {
    options.forEach((option, i) => option.classList.toggle("is-active", i === index));
    active = index;
    if (index >= 0) {
      search.setAttribute("aria-activedescendant", options[index].id);
      options[index].scrollIntoView({ block: "nearest" });
    } else {
      search.removeAttribute("aria-activedescendant");
    }
  };

  const renderList = () => {
    const term = search.value.trim().toLocaleLowerCase();
    const matches = (areas[city.value] ?? []).filter((area) => area.toLocaleLowerCase().includes(term)); // REQ-DMO-61
    const makeOption = (value, label, index) => {
      const item = document.createElement("li");
      item.className = value === OTHER ? "ds-picker__option ds-picker__option--other" : "ds-picker__option";
      item.id = `area-option-${index}`;
      item.setAttribute("role", "option");
      item.setAttribute("aria-selected", String(areaValue.value === value));
      item.dataset.value = value;
      item.textContent = label;
      return item;
    };
    options = [...matches.map((area, i) => makeOption(area, area, i)), makeOption(OTHER, text("area_other"), matches.length)];
    list.replaceChildren(...options);
    empty.hidden = matches.length > 0;
    setActive(term ? 0 : options.findIndex((option) => option.dataset.value === areaValue.value));
  };

  const openPanel = (open) => {
    panel.hidden = !open;
    trigger.setAttribute("aria-expanded", String(open));
    if (open) {
      search.value = "";
      renderList();
      search.focus();
    }
  };

  const choose = (value) => {
    setArea(value, { focusOther: true });
    clearError("area");
    openPanel(false);
    if (value !== OTHER) trigger.focus();
  };

  trigger.addEventListener("click", () => openPanel(panel.hidden));
  search.addEventListener("input", renderList);
  list.addEventListener("click", (event) => {
    const option = event.target.closest("[data-value]");
    if (option) choose(option.dataset.value);
  });
  search.addEventListener("keydown", (event) => {
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
      event.preventDefault(); // REQ-DMO-62
      const step = event.key === "ArrowDown" ? 1 : -1;
      setActive(Math.min(options.length - 1, Math.max(0, active + step)));
    } else if (event.key === "Enter") {
      event.preventDefault(); // REQ-DMO-63
      if (options[active]) choose(options[active].dataset.value);
    } else if (event.key === "Escape") {
      event.preventDefault(); // REQ-DMO-64
      openPanel(false);
      trigger.focus();
    }
  });
  document.addEventListener("click", (event) => {
    if (!panel.hidden && !picker.contains(event.target)) openPanel(false);
  });

  city.addEventListener("change", () => {
    trigger.disabled = !city.value;
    const current = areaValue.value;
    setArea((areas[city.value] ?? []).includes(current) || (current === OTHER && city.value) ? current : "");
    updateTotals();
  });
  trigger.disabled = !city.value;
  setArea("");

  // A Gulf country swaps the city list and area picker for two typed fields (REQ-CHK-28).
  const showDestination = () => {
    const jordan = isJordan();
    form.querySelectorAll("[data-jordan-field]").forEach((field) => (field.hidden = !jordan));
    form.querySelectorAll("[data-gulf-field]").forEach((field) => (field.hidden = jordan));
    otherField.hidden = !jordan || areaValue.value !== OTHER;
    if (!jordan) openPanel(false);
    ["city", "area", "area_other", "gulf_city", "gulf_area"].forEach(clearError);
    updateTotals();
  };
  destination?.addEventListener("change", showDestination);

  /* Checks (REQ-DMO-66 to 72) */
  const control = (name) => (name === "area" ? trigger : form.querySelector(`[name="${name}"]`));
  function clearError(name) {
    control(name)?.setAttribute("aria-invalid", "false");
    const error = form.querySelector(`[data-error-for="${name}"]`);
    if (error) error.hidden = true;
    const hint = form.querySelector(`[data-hint-for="${name}"]`);
    if (hint) hint.hidden = false;
  }
  const showError = (name, message) => {
    control(name)?.setAttribute("aria-invalid", "true");
    const error = form.querySelector(`[data-error-for="${name}"]`);
    error.textContent = message;
    error.hidden = false;
    const hint = form.querySelector(`[data-hint-for="${name}"]`);
    if (hint) hint.hidden = true;
  };
  ["input", "change"].forEach((type) =>
    form.addEventListener(type, (event) => {
      if (event.target.name) clearError(event.target.name);
    }),
  );

  const value = (name) => String(form.querySelector(`[name="${name}"]`).value ?? "").trim();

  /** The message for one field, or null when it is fine (REQ-DMO-66 to 71). */
  const fieldError = (name) => {
    switch (name) {
      case "full_name":
      case "address":
        return value(name) ? null : text("required", { field: control(name).dataset.fieldName });
      case "phone":
        if (!value("phone")) return text("required", { field: control("phone").dataset.fieldName });
        return isValidPhone(value("phone"), phoneCountry()) ? null : country.selectedOptions[0].dataset.invalid; // REQ-DMO-70
      case "email":
        return !value("email") || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value("email")) ? null : text("invalid_email");
      case "city":
        return !isJordan() || city.value ? null : text("invalid_city");
      case "area":
        return !isJordan() || !city.value || areaValue.value ? null : text("invalid_area");
      case "area_other":
        return !isJordan() || areaValue.value !== OTHER || otherInput.value.trim() ? null : text("invalid_area");
      case "gulf_city":
      case "gulf_area":
        return isJordan() || value(name).length >= 2 ? null : text(`invalid_${name}`);
      default:
        return null;
    }
  };
  const FIELDS = ["full_name", "phone", "email", "city", "area", "area_other", "gulf_city", "gulf_area", "address"];

  const validate = () =>
    Object.fromEntries(FIELDS.map((name) => [name, fieldError(name)]).filter(([, message]) => message !== null));

  // A field's error shows as soon as the customer leaves it (REQ-UX-43).
  form.addEventListener("focusout", (event) => {
    const name = event.target.name;
    if (!["full_name", "phone", "email", "area_other", "gulf_city", "gulf_area", "address"].includes(name) || !value(name) && name !== "email") return;
    const message = fieldError(name);
    if (message) showError(name, message);
  });

  if (destination) showDestination(); // after the checks above exist; the browser may have kept a Gulf choice

  /* Sending the order (C.6) */
  const alert = form.querySelector("[data-checkout-alert]");
  const summaryText = alert.textContent;
  const button = form.querySelector("[data-place-order]");
  const reason = form.querySelector("[data-place-order-reason]");
  const status = form.querySelector("[data-place-order-status]");
  let sending = false;

  // A failed submit keeps everything typed, shows the summary and scrolls to it (REQ-DMO-72).
  const showAlert = (message) => {
    alert.textContent = message;
    alert.hidden = false;
    alert.scrollIntoView({ behavior: reducedMotion() ? "auto" : "smooth", block: "center" });
    alert.focus({ preventScroll: true });
  };

  // The spinner takes the label's place and the button keeps its width (REQ-UX-28).
  const setBusy = (busy) => {
    sending = busy;
    button.classList.toggle("is-busy", busy);
    button.setAttribute("aria-busy", String(busy));
    status.textContent = busy ? text("placing") : "";
  };

  // Only the form's fields and the bag's lines go; prices and fees are worked out on the server (REQ-CHK-12, 13).
  const order = () => ({
    full_name: value("full_name"),
    country: country.value,
    phone: value("phone"),
    email: value("email"),
    destination: destination?.value ?? "jordan",
    city: isJordan() ? city.value : "",
    area: isJordan() ? areaValue.value : "",
    area_other: isJordan() && areaValue.value === OTHER ? otherInput.value.trim() : "",
    gulf_city: isJordan() ? "" : value("gulf_city"),
    gulf_area: isJordan() ? "" : value("gulf_area"),
    address: value("address"),
    note: value("note"),
    website: form.querySelector('[name="website"]').value,
    lines: bag.lines(),
  });

  // The bag views ask the server again, so they show what can be ordered now.
  const refreshBag = () => {
    forgetBagProducts();
    renderBag();
  };

  const refused = ({ message, reason: why, ids = [] }) => {
    if (why === "paused") {
      // The summary above already says why, so no second line under the button (REQ-UX-27).
      button.disabled = true;
      button.setAttribute("aria-describedby", alert.id);
      reason.hidden = true;
    } else if (why === "unavailable") {
      bag.drop(ids);
    }
    if (why !== "paused") refreshBag();
    showAlert(message ?? text("bag_changed"));
  };

  const invalid = (errors) => {
    const lines = Object.keys(errors).some((name) => name === "lines" || name.startsWith("lines."));
    if (lines) {
      refreshBag();
      showAlert(text("bag_changed"));
      return;
    }
    if ("website" in errors) {
      showAlert(text("failed"));
      return;
    }
    Object.entries(errors).forEach(([name, [message]]) => {
      const field = name === "country" ? "phone" : name;
      if (form.querySelector(`[data-error-for="${field}"]`)) showError(field, message);
    });
    showAlert(summaryText);
  };

  const place = async () => {
    setBusy(true);
    try {
      const response = await fetch(form.action, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf(), "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify(order()),
      });
      const body = await response.json().catch(() => ({}));
      if (response.status === 201 && body.redirect) {
        // The order is saved: empty the bag and show its confirmation (REQ-DMO-74, REQ-DMO-75).
        bag.clear();
        window.location.assign(body.redirect);
        return;
      }
      setBusy(false);
      if (response.status === 422 && body.errors) invalid(body.errors);
      else if (response.status === 422 && body.reason) refused(body);
      else showAlert(response.status === 429 && body.message ? body.message : text("failed"));
    } catch {
      setBusy(false);
      showAlert(text("failed"));
    }
  };

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (sending || button.disabled) return;
    [...FIELDS, "note"].forEach(clearError);
    const errors = validate();
    if (Object.keys(errors).length > 0) {
      Object.entries(errors).forEach(([name, message]) => showError(name, message));
      showAlert(summaryText);
      return;
    }
    alert.hidden = true;
    place();
  });
}
