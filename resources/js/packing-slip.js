// The packing slip's Print button (REQ-ORD-22). A file of its own, so the page runs no inline script.
document.querySelector("[data-print]")?.addEventListener("click", () => window.print());
