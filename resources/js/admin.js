// JUDE admin script: keeps the owner's place when she switches English / العربية (see keep-place.js).
import { keepPlaceAcrossLanguages } from "./keep-place.js";

const adapter = {
  capture() {
    const sidebar = window.Alpine?.store?.("sidebar");
    return { sidebarOpen: Boolean(sidebar?.isOpen) };
  },
  restore(extra) {
    // On a phone the menu is a drawer; on a computer Filament remembers it by itself.
    if (extra?.sidebarOpen && window.innerWidth < 1024) window.Alpine?.store?.("sidebar")?.open?.();
  },
};

keepPlaceAcrossLanguages({
  links: "[data-language-link]",
  adapter,
  // The switch comes back to the exact address on screen, even after Livewire changed it.
  beforeLeave(link) {
    const url = new URL(link.href, window.location.href);
    url.searchParams.set("back", window.location.href);
    link.href = url.href;
  },
  // Livewire and Alpine must be running before typed values are put back into the fields.
  whenReady(run) {
    const go = () => window.setTimeout(run, 0);
    if (document.readyState === "complete") go();
    else window.addEventListener("load", go, { once: true });
  },
});
