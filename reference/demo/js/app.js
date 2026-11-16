// Builds each page from the data files and wires up its behavior: dialogs, cart drawer,
// wishlist, currency, shop filters, product gallery, size picker and checkout.
// Styling is never set here; only `is-*` state classes and the `hidden` attribute.
(() => {
  "use strict";

  const Jude = window.Jude;
  const { site, catalog, currency, cart, wishlist, flash, fmt, components, pages } = Jude;
  const body = document.body;
  const page = body.dataset.page || "not-found";
  const settings = { toastDuration: site.store.toastDurationMs, backToTopOffset: site.store.backToTopOffset };
  const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const focusableSelector =
    'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  /* ---------- Render the page ---------- */
  const renderPage = pages[page] ?? pages["not-found"];
  const first = renderPage();
  if (first.redirect) {
    window.location.replace(first.redirect);
    return;
  }

  const setTitle = (title) => {
    document.title = title ? `${title} | ${site.brand.fullName}` : `${site.brand.fullName} | ${site.brand.tagline}`;
  };
  setTitle(first.title);

  body.insertAdjacentHTML(
    "afterbegin",
    `<a href="#main-content" class="ds-skip-link">Skip to content</a>
<div class="ds-page-shell" data-page-shell>
  ${components.header(page)}
  <main id="main-content" class="ds-main" tabindex="-1">${first.html}</main>
  ${components.footer()}
</div>
${components.cartDrawer()}
${components.layout()}`,
  );
  const main = document.getElementById("main-content");

  /** Re-renders <main> (cart page after a change, checkout after a failed submit). */
  const rerender = (result = renderPage()) => {
    if (result.redirect) {
      window.location.assign(result.redirect);
      return;
    }
    main.innerHTML = result.html;
    enhance(main);
  };

  /* ---------- Toast ---------- */
  const toast = document.querySelector("[data-toast]");
  const toastMessage = toast.querySelector("[data-toast-message]");
  let toastTimer = 0;
  const showToast = (message) => {
    if (!message) return;
    toastMessage.textContent = message;
    toast.classList.add("is-visible");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove("is-visible"), settings.toastDuration);
  };
  showToast(flash.take("toast"));

  /* ---------- Dialogs ---------- */
  const pageShell = document.querySelector("[data-page-shell]");
  // Dialogs must live outside the page shell, which becomes inert while one is open.
  document.querySelectorAll("[data-modal]").forEach((modal) => {
    if (pageShell.contains(modal)) pageShell.after(modal);
  });
  let openModal = null;
  let returnFocus = null;

  const setOpenersExpanded = (id, expanded) =>
    document.querySelectorAll(`[data-modal-open="${id}"]`).forEach((opener) => opener.setAttribute("aria-expanded", String(expanded)));

  const lockPage = (locked) => {
    body.classList.toggle("is-locked", locked);
    pageShell.inert = locked;
  };

  const closeModal = () => {
    if (!openModal) return;
    openModal.hidden = true;
    setOpenersExpanded(openModal.dataset.modal, false);
    openModal = null;
    lockPage(false);
    returnFocus?.focus();
  };

  const showModal = (id) => {
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
    window.requestAnimationFrame(() => modal.querySelector(focusableSelector)?.focus());
  };

  document.addEventListener("click", (event) => {
    const opener = event.target.closest("[data-modal-open]");
    if (opener) {
      event.preventDefault();
      showModal(opener.dataset.modalOpen);
      return;
    }
    if (event.target.closest("[data-modal-close]") || event.target === openModal) closeModal();
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
    const firstItem = focusable[0];
    const lastItem = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === firstItem) {
      event.preventDefault();
      lastItem.focus();
    } else if (!event.shiftKey && document.activeElement === lastItem) {
      event.preventDefault();
      firstItem.focus();
    }
  });

  /* ---------- Small notices (language) ---------- */
  document.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-notice]");
    if (trigger) showToast(trigger.dataset.notice);
  });

  /* ---------- Counts, cart drawer, wishlist, currency ---------- */
  const setCount = (name, count) => {
    document.querySelectorAll(`[data-count="${name}"]`).forEach((badge) => {
      badge.textContent = String(count);
      badge.hidden = count === 0;
    });
    document.querySelectorAll(`[data-count-text="${name}"]`).forEach((label) => {
      label.textContent = String(count);
    });
  };

  const cartDrawer = document.querySelector("[data-cart-drawer]");
  const refreshCart = () => {
    setCount("cart", cart.count());
    cartDrawer.innerHTML = components.cartDrawerBody();
    if (page === "cart") rerender();
  };

  const field = (form, name) => form.querySelector(`[name="${name}"]`)?.value ?? "";
  const quantityOf = (form) => {
    const value = Number.parseInt(field(form, "quantity"), 10);
    return Number.isFinite(value) ? value : 0;
  };
  const checkedSize = (form) => form.querySelector('[name="size"]:checked')?.value ?? field(form, "size");

  const actions = {
    "cart-add": (form) => {
      const productId = field(form, "productId");
      const product = catalog.findById(productId);
      if (product && !cart.add(productId, quantityOf(form), checkedSize(form) || null)) {
        // Only a missing size can stop a known product here.
        showToast(site.notices.sizeRequired);
        return;
      }
      refreshCart();
      showToast(product ? fmt(site.notices.addedToCart, product.name) : "");
      showModal("cart-drawer");
    },
    // Lines in the drawer update in place.
    "cart-update": (form) => {
      cart.update(field(form, "productId"), quantityOf(form), field(form, "size"));
      refreshCart();
      cartDrawer.querySelector(focusableSelector)?.focus();
    },
    // Lines on the cart page.
    "cart-page-update": (form) => {
      cart.update(field(form, "productId"), quantityOf(form), field(form, "size"));
      refreshCart();
      showToast(site.notices.cartUpdated);
    },
    wishlist: (form) => {
      const id = field(form, "productId");
      const saved = wishlist.toggle(id);
      const product = catalog.findById(id);
      setCount("wishlist", wishlist.count());
      document.querySelectorAll(`[data-wishlist-button="${CSS.escape(id)}"]`).forEach((button) => {
        button.setAttribute("aria-pressed", String(saved));
      });
      showToast(product ? fmt(saved ? site.notices.wishlistSaved : site.notices.wishlistRemoved, product.name) : "");
    },
    // Header currency picker: remember the choice and show the same page again.
    currency: (form, submitter) => {
      currency.set(submitter?.value);
      window.location.reload();
    },
  };

  document.addEventListener("submit", (event) => {
    const form = event.target;
    const action = actions[form.dataset.ajax];
    if (!action) return;
    event.preventDefault();
    action(form, event.submitter);
  });

  /* ---------- Dropdown menus ---------- */
  document.querySelectorAll("[data-dropdown]").forEach((dropdown) => {
    const toggle = dropdown.querySelector("[data-dropdown-toggle]");
    const menu = dropdown.querySelector("[data-dropdown-menu]");
    const setOpen = (open) => {
      menu.hidden = !open;
      toggle.setAttribute("aria-expanded", String(open));
      if (open) menu.querySelector("a, button")?.focus();
    };
    toggle.addEventListener("click", () => setOpen(menu.hidden));
    document.addEventListener("click", (event) => {
      if (!dropdown.contains(event.target)) setOpen(false);
    });
    dropdown.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        setOpen(false);
        toggle.focus();
      }
    });
  });

  /* ---------- Page behaviors (run again whenever <main> re-renders) ---------- */
  function enhance(root) {
    enhanceFilters(root);
    enhanceProduct(root);
    enhanceCheckout(root);
  }

  /* Shop filters: a sheet on phones/tablets, a sidebar that applies instantly on desktop. */
  function enhanceFilters(root) {
    const filters = root.querySelector("[data-filters]");
    if (!filters) return;
    const closeButton = filters.querySelector("[data-filters-close]");
    // The close button only shows when filters are a sheet; as a sidebar they apply instantly.
    const appliesInstantly = () => closeButton && window.getComputedStyle(closeButton).display === "none";
    const allShades = filters.querySelector("[data-filter-all-shades]");
    const shadeBoxes = [...filters.querySelectorAll("[data-filter-shade]")];

    document.addEventListener("change", (event) => {
      const input = event.target;
      if (input.form !== filters) return;
      if (input === allShades) {
        shadeBoxes.forEach((box) => (box.checked = false));
        allShades.checked = true;
      } else if (shadeBoxes.includes(input)) {
        allShades.checked = !shadeBoxes.some((box) => box.checked);
      }
      if (input.name === "q") return;
      if (appliesInstantly()) filters.requestSubmit();
    });

    const setSheet = (open) => {
      filters.classList.toggle("is-open", open);
      document.querySelectorAll("[data-filters-open]").forEach((button) => button.setAttribute("aria-expanded", String(open)));
      // The sheet lives inside the page shell, so only the scroll is locked.
      body.classList.toggle("is-locked", open);
      if (open) filters.querySelector(focusableSelector)?.focus();
    };
    root.querySelectorAll("[data-filters-open]").forEach((button) => button.addEventListener("click", () => setSheet(true)));
    closeButton?.addEventListener("click", () => setSheet(false));
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && filters.classList.contains("is-open")) setSheet(false);
    });
  }

  /* Product page: gallery, quantity stepper, required size, back link. */
  function enhanceProduct(root) {
    root.querySelectorAll("[data-gallery]").forEach((gallery) => {
      const mainImage = gallery.querySelector("[data-gallery-main]");
      const thumbs = gallery.querySelectorAll("[data-gallery-thumb]");
      thumbs.forEach((thumb) =>
        thumb.addEventListener("click", () => {
          mainImage.src = thumb.dataset.galleryThumb;
          thumbs.forEach((other) => other.classList.toggle("is-active", other === thumb));
        }),
      );
    });

    root.querySelectorAll("[data-stepper]").forEach((stepper) => {
      const input = stepper.querySelector("input");
      stepper.querySelectorAll("[data-stepper-step]").forEach((button) =>
        button.addEventListener("click", () => {
          const next = Number(input.value) + Number(button.dataset.stepperStep);
          input.value = String(Math.min(Number(input.max), Math.max(Number(input.min), next)));
        }),
      );
    });

    // A set can't go in the bag without a size.
    root.querySelectorAll(".ds-size-picker").forEach((picker) => {
      const error = picker.querySelector("[data-size-error]");
      const inputs = picker.querySelectorAll("[data-size-input]");
      const setInvalid = (invalid) => {
        picker.classList.toggle("is-invalid", invalid);
        error.hidden = !invalid;
      };
      inputs.forEach((input) => {
        // Show our own message instead of the browser's bubble.
        input.addEventListener("invalid", (event) => {
          event.preventDefault();
          setInvalid(true);
          if (input === inputs[0]) input.focus();
        });
        input.addEventListener("change", () => setInvalid(false));
      });
    });

    // Return to the exact list (filters, page) when we came from this site.
    root.querySelectorAll("[data-back]").forEach((link) =>
      link.addEventListener("click", (event) => {
        if (document.referrer.startsWith(window.location.origin) && window.history.length > 1) {
          event.preventDefault();
          window.history.back();
        }
      }),
    );
  }

  /* Checkout: country code, delivery fee by city, searchable area picker, validation, placing the order. */
  function enhanceCheckout(root) {
    const checkout = root.querySelector("[data-checkout]");
    if (!checkout) return;

    // Phone: show the picked country's flag and code, and an example number.
    const country = checkout.querySelector("[data-phone-country]");
    const phoneInput = checkout.querySelector("[data-phone-input]");
    country.addEventListener("change", () => {
      const option = country.selectedOptions[0];
      checkout.querySelector("[data-phone-flag]").textContent = option.dataset.flag;
      checkout.querySelector("[data-phone-dial]").textContent = `+${option.dataset.dial}`;
      phoneInput.placeholder = option.dataset.placeholder;
      phoneInput.focus();
    });

    const areas = JSON.parse(checkout.querySelector("[data-areas]").textContent);
    const city = checkout.querySelector("[data-city-select]");
    const select = checkout.querySelector("[data-area-select]");
    const picker = checkout.querySelector("[data-area-picker]");
    const trigger = picker.querySelector("[data-picker-trigger]");
    const valueLabel = picker.querySelector("[data-picker-value]");
    const panel = picker.querySelector("[data-picker-panel]");
    const search = picker.querySelector("[data-picker-search]");
    const list = picker.querySelector("[data-picker-list]");
    const empty = picker.querySelector("[data-picker-empty]");
    const otherField = checkout.querySelector("[data-area-other]");
    const shipping = root.querySelector("[data-checkout-shipping]");
    const total = root.querySelector("[data-checkout-total]");
    const OTHER = "other";
    let options = [];
    let active = -1;

    // A field's error from the last submit goes away as soon as the shopper fixes it.
    const clearError = (control) => {
      control.setAttribute("aria-invalid", "false");
      const error = control.closest(".ds-field")?.querySelector(".ds-field__error");
      if (error) error.hidden = true;
    };
    ["input", "change"].forEach((type) => checkout.addEventListener(type, (event) => clearError(event.target)));

    checkout.querySelector("[data-area-native]").hidden = true;
    picker.hidden = false;

    const setArea = (value, { focusOther = false } = {}) => {
      select.value = value;
      const placeholder = city.value ? select.dataset.choose : select.dataset.cityFirst;
      valueLabel.textContent = value === OTHER ? select.dataset.other : value || placeholder;
      valueLabel.classList.toggle("is-placeholder", !value);
      otherField.hidden = value !== OTHER;
      if (value === OTHER && focusOther) otherField.querySelector("input").focus();
    };

    // Only the chosen city's areas go in the real select, so the value always belongs to that city.
    const fillAreas = () => {
      const current = select.value;
      const cityAreas = areas[city.value] ?? [];
      select.replaceChildren(new Option("", ""), ...cityAreas.map((area) => new Option(area, area)), new Option(select.dataset.other, OTHER));
      trigger.disabled = !city.value;
      setArea(cityAreas.includes(current) || (current === OTHER && city.value) ? current : "");
    };

    const setActive = (index) => {
      options.forEach((option, i) => option.classList.toggle("is-active", i === index));
      active = index;
      if (index >= 0) {
        search.setAttribute("aria-activedescendant", options[index].id);
        options[index].scrollIntoView({ block: "nearest" });
      } else search.removeAttribute("aria-activedescendant");
    };

    const renderList = () => {
      const term = search.value.trim().toLowerCase();
      const matches = (areas[city.value] ?? []).filter((area) => area.toLowerCase().includes(term));
      const makeOption = (value, label, index) => {
        const item = document.createElement("li");
        item.className = value === OTHER ? "ds-picker__option ds-picker__option--other" : "ds-picker__option";
        item.id = `area-option-${index}`;
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", String(select.value === value));
        item.dataset.value = value;
        item.textContent = label;
        return item;
      };
      options = [...matches.map((area, i) => makeOption(area, area, i)), makeOption(OTHER, select.dataset.other, matches.length)];
      list.replaceChildren(...options);
      empty.hidden = matches.length > 0;
      setActive(term ? 0 : options.findIndex((option) => option.dataset.value === select.value));
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
      clearError(trigger);
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
        event.preventDefault();
        const step = event.key === "ArrowDown" ? 1 : -1;
        setActive(Math.min(options.length - 1, Math.max(0, active + step)));
      } else if (event.key === "Enter") {
        event.preventDefault();
        if (options[active]) choose(options[active].dataset.value);
      } else if (event.key === "Escape") {
        event.preventDefault();
        openPanel(false);
        trigger.focus();
      }
    });
    document.addEventListener("click", (event) => {
      if (!panel.hidden && !picker.contains(event.target)) openPanel(false);
    });

    city.addEventListener("change", () => {
      const option = city.selectedOptions[0];
      shipping.textContent = city.value ? option.dataset.shipping : shipping.dataset.pending;
      total.textContent = city.value ? option.dataset.total : total.dataset.pending;
      fillAreas();
    });
    fillAreas();

    checkout.addEventListener("submit", async (event) => {
      event.preventDefault();
      const values = Object.fromEntries(
        ["FullName", "Country", "Phone", "Email", "City", "Area", "AreaOther", "Address"].map((name) => [name, field(checkout, name)]),
      );
      const { errors, phone } = validate(values);
      if (Object.keys(errors).length > 0) {
        rerender(pages.checkout(values, errors));
        window.scrollTo({ top: 0, behavior: reducedMotion() ? "auto" : "smooth" });
        return;
      }
      checkout.querySelector('[type="submit"]').disabled = true;
      await placeOrder(values, phone);
    });
  }

  /* ---------- Checkout validation and order ---------- */
  const MIN_PHONE_DIGITS = 7;
  const MAX_PHONE_DIGITS = 12;
  const content = site.checkout;

  /** The picked area, or the typed one when the shopper's area is not listed. */
  const areaName = (values) => {
    const city = Jude.findCity(values.City);
    const listed = city?.areas.find((area) => area === values.Area);
    return listed ?? values.AreaOther.trim();
  };

  const validate = (values) => {
    const errors = {};
    const labels = content.fields;
    const require = (name, label) => {
      if (!values[name].trim()) errors[name] = fmt(content.requiredMessage, label.toLowerCase());
    };
    require("FullName", labels.fullName);
    require("Phone", labels.phone);
    require("Address", labels.address);

    // The number is typed without the country code; a leading trunk 0 (079…) is dropped.
    const country = content.phoneCountries.find((option) => option.code === values.Country) ?? content.phoneCountries[0];
    let digits = values.Phone.replace(/\D/g, "").replace(/^0+/, "");
    if (country && digits.startsWith(country.dial) && digits.length > MAX_PHONE_DIGITS - 1) digits = digits.slice(country.dial.length);
    const phone = country ? `+${country.dial} ${digits}` : digits;
    if (!errors.Phone && (digits.length < MIN_PHONE_DIGITS || digits.length > MAX_PHONE_DIGITS)) errors.Phone = content.phoneMessage;

    if (values.Email.trim() && !/^[^\s@]+@[^\s@]+$/.test(values.Email.trim())) errors.Email = content.emailMessage;

    const city = Jude.findCity(values.City);
    if (!city) errors.City = content.cityMessage;
    else if (!areaName(values)) errors.Area = content.areaMessage;

    return { errors, phone };
  };

  const orderNumber = () => {
    const alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    const bytes = new Uint32Array(6);
    window.crypto.getRandomValues(bytes);
    return [...bytes].map((value) => alphabet[value % alphabet.length]).join("");
  };

  const placeOrder = async (values, phone) => {
    const items = cart.items();
    const city = Jude.findCity(values.City);
    const order = {
      number: orderNumber(),
      fullName: values.FullName.trim(),
      phone,
      email: values.Email.trim(),
      emailSent: false,
      deliverTo: [values.Address.trim(), areaName(values), city.label].filter(Boolean).join(", "),
      payment: content.paymentMethod,
      lines: items.map((item) => ({
        name: item.product.name,
        quantity: item.quantity,
        price: currency.format(item.product.price === null ? null : item.product.price * item.quantity),
        size: item.size,
        image: item.product.image,
      })),
      subtotal: currency.subtotal(items),
      shipping: currency.format(city.cost),
      total: currency.total(items, city.cost),
    };
    if (order.email) order.emailSent = await sendConfirmationEmail(order);
    cart.clear();
    flash.set("order", order);
    window.location.assign("confirmation.html");
  };

  /** Plain-text copy of the order for the customer's email. */
  const emailBody = (order) => {
    const copy = content.email;
    const lines = order.lines.map((line) => {
      const size = line.size === null ? "" : ` (${fmt(content.sizeFormat, line.size)})`;
      return `${line.name}${size} ${fmt(content.quantityFormat, line.quantity)}    ${line.price}`;
    });
    return [
      fmt(copy.greeting, order.fullName),
      "",
      fmt(copy.intro, order.number),
      "",
      ...lines,
      "",
      `${content.subtotalLabel}: ${order.subtotal}`,
      `${content.shippingLabel}: ${order.shipping}`,
      `${content.totalLabel}: ${order.total}`,
      "",
      `${content.deliverToLabel}: ${order.fullName}, ${order.deliverTo}`,
      `${content.phoneLabel}: ${order.phone}`,
      `${content.paymentLabel}: ${order.payment}`,
      "",
      copy.signOff,
    ].join("\n");
  };

  /** Sends the order through EmailJS when site.email is filled in; true when it was accepted. */
  const sendConfirmationEmail = async (order) => {
    const settings = site.email ?? {};
    if (!settings.serviceId || !settings.templateId || !settings.publicKey) return false;
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), 10000);
    try {
      const response = await fetch("https://api.emailjs.com/api/v1.0/email/send", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        signal: controller.signal,
        body: JSON.stringify({
          service_id: settings.serviceId,
          template_id: settings.templateId,
          user_id: settings.publicKey,
          template_params: {
            to_email: order.email,
            to_name: order.fullName,
            from_name: settings.fromName,
            subject: fmt(content.email.subject, order.number),
            order_number: order.number,
            message: emailBody(order),
          },
        }),
      });
      return response.ok;
    } catch {
      return false;
    } finally {
      window.clearTimeout(timer);
    }
  };

  /* ---------- Back to top ---------- */
  const backToTop = document.querySelector("[data-back-to-top]");
  const updateBackToTop = () => (backToTop.hidden = window.scrollY <= settings.backToTopOffset);
  window.addEventListener("scroll", updateBackToTop, { passive: true });
  updateBackToTop();
  backToTop.addEventListener("click", () => window.scrollTo({ top: 0, behavior: reducedMotion() ? "auto" : "smooth" }));

  enhance(main);
})();
