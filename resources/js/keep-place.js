// Keeps the visitor's place when the language changes (2026-11-20). The language button still opens
// the same page again in the other language, but just before it goes this saves where the visitor
// was: the scroll position, any scrolled panel, the open popup or filter sheet, open <details>,
// what they typed or picked in a form, and the focused field. The new page puts all of it back,
// so only the words change. Shared by the store (app.js) and the admin (admin.js).

const KEY = "jude:keep-place";
/** A saved place older than this is ignored, e.g. after the visitor closed the tab mid-switch. */
const MAX_AGE_MS = 20000;
/** The point on screen whose element is kept in the same place: the middle, a third of the way down. */
const anchorPoint = () => [window.innerWidth / 2, Math.round(window.innerHeight / 3)];

const SKIPPED_TYPES = new Set(["hidden", "password", "file", "submit", "button", "reset", "image"]);

/* ---------- Finding the same element on the next page ---------- */

/** Where an element sits under <body>: the index among its siblings at each level. */
function pathOf(node) {
  const path = [];
  while (node && node !== document.body && node.parentElement) {
    path.unshift(Array.prototype.indexOf.call(node.parentElement.children, node));
    node = node.parentElement;
  }
  return node === document.body ? path : null;
}

function atPath(path) {
  if (!Array.isArray(path)) return null;
  let node = document.body;
  for (const index of path) {
    node = node?.children[index];
    if (!node) return null;
  }
  return node;
}

const describe = (node) => ({ path: pathOf(node), tag: node.tagName });
const find = (saved) => {
  const node = atPath(saved?.path);
  return node && node.tagName === saved.tag ? node : null;
};

/* ---------- Form fields ---------- */

const fieldsIn = (root = document) =>
  [...root.querySelectorAll("input, select, textarea")].filter(
    (field) => !SKIPPED_TYPES.has(field.type) && !field.disabled && !field.closest("[data-keep-place-skip]"),
  );

/** Only what the visitor changed: a field still at its page value is left to the new page. */
function changedValue(field) {
  if (field.type === "checkbox" || field.type === "radio") {
    return field.checked !== field.defaultChecked ? { checked: field.checked } : null;
  }
  if (field.tagName === "SELECT") {
    const changed = [...field.options].some((option) => option.selected !== option.defaultSelected);
    return changed ? { values: [...field.selectedOptions].map((option) => option.value) } : null;
  }
  return field.value !== field.defaultValue ? { value: field.value } : null;
}

function fieldKey(field) {
  // An id or a name is steadier than the position; the position also tells two same-named fields apart.
  return { id: field.id || null, name: field.name || null, type: field.type, ...describe(field) };
}

function findField(saved) {
  const byPath = find(saved);
  if (byPath && (byPath.id || null) === saved.id && (byPath.name || null) === saved.name) return byPath;
  if (saved.id) {
    const byId = document.getElementById(saved.id);
    if (byId && byId.type === saved.type) return byId;
  }
  return byPath && byPath.type === saved.type ? byPath : null;
}

function setField(field, saved) {
  if ("checked" in saved) {
    if (field.checked === saved.checked) return;
    field.checked = saved.checked;
  } else if ("values" in saved) {
    [...field.options].forEach((option) => (option.selected = saved.values.includes(option.value)));
  } else {
    if (field.value === saved.value) return;
    field.value = saved.value;
  }
  // The page's own scripts (and Livewire in the admin) hear the change as if it was typed.
  field.dispatchEvent(new Event("input", { bubbles: true }));
  field.dispatchEvent(new Event("change", { bubbles: true }));
}

/* ---------- Saving ---------- */

function insideFixed(node) {
  for (; node && node !== document.body; node = node.parentElement) {
    const position = window.getComputedStyle(node).position;
    if (position === "fixed" || position === "sticky") return true;
  }
  return false;
}

function capture(adapter) {
  const [x, y] = anchorPoint();
  const anchor = document.elementFromPoint(x, y);
  // Anything in a fixed bar or an open sheet stays put while the page moves under it: no anchor
  // then, the plain scroll position is used.
  const anchorSaved = anchor && anchor !== document.body && !insideFixed(anchor) ? { ...describe(anchor), top: anchor.getBoundingClientRect().top } : null;

  const panels = [...document.querySelectorAll("body *")]
    .filter((node) => node.scrollTop > 0 && node.scrollHeight > node.clientHeight)
    .slice(0, 20)
    .map((node) => ({ ...describe(node), scrollTop: node.scrollTop }));

  const fields = fieldsIn()
    .map((field) => {
      const value = changedValue(field);
      return value ? { ...fieldKey(field), ...value } : null;
    })
    .filter(Boolean);

  const active = document.activeElement;
  const focus = active && active !== document.body && active.matches("input, select, textarea") ? fieldKey(active) : null;

  return {
    at: Date.now(),
    page: window.location.pathname + window.location.search.replace(/([?&])lang=[^&]*&?/, "$1").replace(/[?&]$/, ""),
    scrollY: window.scrollY,
    scrollX: window.scrollX,
    anchor: anchorSaved,
    panels,
    details: [...document.querySelectorAll("details[open]")].map(describe),
    fields,
    focus,
    extra: adapter.capture?.() ?? null,
  };
}

function save(adapter) {
  try {
    window.sessionStorage.setItem(KEY, JSON.stringify(capture(adapter)));
  } catch {
    /* storage blocked: the switch still works, it just starts at the top */
  }
}

/* ---------- Putting it back ---------- */

function take(page) {
  try {
    const raw = window.sessionStorage.getItem(KEY);
    window.sessionStorage.removeItem(KEY);
    const saved = raw ? JSON.parse(raw) : null;
    return saved && saved.page === page && Date.now() - saved.at < MAX_AGE_MS ? saved : null;
  } catch {
    return null;
  }
}

function scrollBack(saved) {
  const anchor = saved.anchor && find(saved.anchor);
  // The other language can make text taller or shorter above; the anchor keeps the same spot in view.
  const top = anchor ? window.scrollY + anchor.getBoundingClientRect().top - saved.anchor.top : saved.scrollY;
  window.scrollTo({ top: Math.max(0, top), left: 0, behavior: "instant" });
  saved.panels.forEach((panel) => {
    const node = find(panel);
    if (node) node.scrollTop = panel.scrollTop;
  });
}

function restore(saved, adapter) {
  saved.details.forEach((detail) => {
    const node = find(detail);
    if (node) node.open = true;
  });
  saved.fields.forEach((field) => {
    const node = findField(field);
    if (node) setField(node, field);
  });
  // Scroll first: an open popup locks the page, and a locked page may not scroll any more.
  scrollBack(saved);
  adapter.restore?.(saved.extra);

  const focus = saved.focus && findField(saved.focus);
  if (focus) focus.focus({ preventScroll: true });

  // Photos and fonts that load later move the page; follow them until the visitor scrolls.
  let touched = false;
  const stop = () => (touched = true);
  ["wheel", "touchstart", "keydown", "mousedown"].forEach((type) => window.addEventListener(type, stop, { once: true, passive: true }));
  const again = () => !touched && scrollBack(saved);
  if (document.readyState !== "complete") window.addEventListener("load", again, { once: true });
  window.setTimeout(again, 400);
}

/**
 * @param {object} options
 * @param {string} options.links  the language buttons
 * @param {() => string} [options.page]  this page's key; the address without ?lang by default
 * @param {(link: HTMLAnchorElement) => void} [options.beforeLeave]  e.g. add where to come back to
 * @param {{capture?: () => any, restore?: (extra: any) => void}} [options.adapter]  open popups and the like
 * @param {(run: () => void) => void} [options.whenReady]  when the page's own scripts are ready
 */
export function keepPlaceAcrossLanguages({ links, page, beforeLeave, adapter = {}, whenReady = (run) => run() }) {
  if ("scrollRestoration" in window.history) {
    // Our own scroll wins over the browser's on the page we come back to.
    const saved = (() => {
      try {
        return window.sessionStorage.getItem(KEY);
      } catch {
        return null;
      }
    })();
    if (saved) window.history.scrollRestoration = "manual";
  }

  document.addEventListener(
    "click",
    (event) => {
      const link = event.target.closest(links);
      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      beforeLeave?.(link);
      save(adapter);
    },
    true,
  );

  whenReady(() => {
    const key = page ? page() : window.location.pathname + window.location.search;
    const saved = take(key);
    if (saved) restore(saved, adapter);
  });
}
