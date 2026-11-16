import { test } from "node:test";
import assert from "node:assert/strict";
import { linkAllBox } from "../../resources/js/store/filters.js";

const boxes = (...checked) => checked.map((value) => ({ checked: value }));

test("REQ-DMO-16, REQ-DMO-94: checking the All box clears every choice", () => {
  const all = { checked: true };
  const sizes = boxes(true, false, true);
  linkAllBox(all, sizes, all);
  assert.deepEqual(sizes.map((box) => box.checked), [false, false, false]);
  assert.equal(all.checked, true);
});

test("REQ-DMO-17, REQ-DMO-94: unchecking the last choice checks the All box", () => {
  const all = { checked: false };
  const sizes = boxes(false, false);
  linkAllBox(all, sizes, sizes[1]);
  assert.equal(all.checked, true);
});

test("REQ-DMO-94: a choice unchecks the All box", () => {
  const all = { checked: true };
  const sizes = boxes(false, true);
  linkAllBox(all, sizes, sizes[1]);
  assert.equal(all.checked, false);
});

test("Boxes of another filter are left alone", () => {
  const all = { checked: true };
  const sizes = boxes(false);
  linkAllBox(all, sizes, { checked: true });
  assert.equal(all.checked, true);
  linkAllBox(null, sizes, sizes[0]);
});
