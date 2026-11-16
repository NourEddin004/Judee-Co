// Shop filter rules without the page, so they can be tested on their own (tests/js/filters.test.mjs).

/**
 * An "All colors" or "All sizes" box and the boxes it stands for: checking it clears them, and
 * clearing the last one checks it again (REQ-DMO-16, REQ-DMO-17, REQ-DMO-94).
 */
export function linkAllBox(all, boxes, changed) {
  if (!all) return;
  if (changed === all) {
    boxes.forEach((box) => (box.checked = false));
    all.checked = true;
  } else if (boxes.includes(changed)) {
    all.checked = !boxes.some((box) => box.checked);
  }
}
