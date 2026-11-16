import { test } from "node:test";
import assert from "node:assert/strict";
import { editPhone, fitPhone, isValidPhone, onlyDigits, phoneDigits } from "../../resources/js/store/phone.js";

const JORDAN = { dial: "962", digits: [9] };
const KUWAIT = { dial: "965", digits: [8] };
const LEBANON = { dial: "961", digits: [7, 8] };
const GERMANY = { dial: "49", digits: [10, 11] };

/** Types each character at the end of the field, as a customer would. */
const type = (text, country, start = "") =>
  [...text].reduce((field, character) => editPhone(field, field + character, field.length + 1, country).value, start);

test("REQ-CHK-22, REQ-DMO-56: exactly the country's digits, not counting a 0 at the start", () => {
  assert.equal(isValidPhone("791234567", JORDAN), true);
  assert.equal(isValidPhone("0791234567", JORDAN), true);
  assert.equal(isValidPhone("79123456", JORDAN), false);
  assert.equal(isValidPhone("7912345678", JORDAN), false);
  assert.equal(isValidPhone("50012345", KUWAIT), true);
  assert.equal(isValidPhone("500123456", KUWAIT), false);
  assert.equal(isValidPhone("3123456", LEBANON), true);
  assert.equal(isValidPhone("71123456", LEBANON), true);
  assert.equal(isValidPhone("1512345678", GERMANY), true);
  assert.equal(isValidPhone("15123456789", GERMANY), true);
  assert.equal(isValidPhone("151234567", GERMANY), false);
});

test("REQ-DMO-57: a too long number starting with the dial code, after +, 00 or nothing, loses it once", () => {
  assert.equal(phoneDigits("+962 79 123 4567", JORDAN), "791234567");
  assert.equal(phoneDigits("00962 79 123 4567", JORDAN), "791234567");
  assert.equal(phoneDigits("962791234567", JORDAN), "791234567");
  assert.equal(phoneDigits("962791234", JORDAN), "962791234", "not too long, so kept");
  assert.equal(phoneDigits("0096279123", JORDAN), "79123", "00 and the code are not taken as a 0 at the start");
  assert.equal(phoneDigits("96512345", KUWAIT), "96512345", "a Kuwaiti number may start with 965");
  assert.equal(phoneDigits("+965 9651 2345", KUWAIT), "96512345", "only once");
});

test("REQ-CHK-21, REQ-DMO-89: the field keeps only digits, wherever they are typed", () => {
  assert.equal(type("79a1-2", JORDAN), "7912");
  assert.deepEqual(editPhone("7912", "79x12", 3, JORDAN), { value: "7912", caret: 2 });
  assert.deepEqual(editPhone("7912", "79 3 12", 5, JORDAN), { value: "79312", caret: 3 });
});

test("REQ-DMO-90: Arabic-Indic and Persian digits become 0 to 9", () => {
  assert.equal(onlyDigits("٠١٢٣٤٥٦٧٨٩"), "0123456789");
  assert.equal(onlyDigits("۰۱۲۳۴۵۶۷۸۹"), "0123456789");
  assert.equal(type("٧٩١", JORDAN), "791");
});

test("REQ-DMO-91: the field stops at the country's longest number, or one more after a 0", () => {
  assert.equal(type("7912345678", JORDAN), "791234567");
  assert.equal(type("07912345678", JORDAN), "0791234567");
  assert.equal(type("501234567", KUWAIT), "50123456");
  assert.equal(type("151234567890", GERMANY), "15123456789");
  assert.deepEqual(editPhone("791234567", "7912345678", 10, JORDAN), { value: "791234567", caret: 9 }, "a tenth digit is refused");
  assert.deepEqual(editPhone("791234567", "7591234567", 2, JORDAN), { value: "791234567", caret: 1 }, "also in the middle");
  assert.deepEqual(editPhone("", "79123456789", 11, JORDAN), { value: "791234567", caret: 9 }, "a long paste keeps what fits");
});

test("REQ-DMO-57: a pasted or typed dial code is taken off once the number is too long", () => {
  assert.deepEqual(editPhone("", "+962 79 123 4567", 16, JORDAN), { value: "791234567", caret: 9 });
  assert.deepEqual(editPhone("", "00962791234567", 14, JORDAN), { value: "791234567", caret: 9 });
  assert.equal(type("+962791234567", JORDAN), "791234567");
  assert.equal(type("00962791234567", JORDAN), "791234567");
  assert.deepEqual(editPhone("", "+962 79 123 4567 89", 19, JORDAN), { value: "791234567", caret: 9 }, "too long even without the code");
});

test("deleting, replacing and filling in by the browser", () => {
  assert.deepEqual(editPhone("791234567", "79123456", 8, JORDAN), { value: "79123456", caret: 8 });
  assert.deepEqual(editPhone("791234567", "79124567", 4, JORDAN), { value: "79124567", caret: 4 });
  assert.deepEqual(editPhone("0791234567", "791234567", 0, JORDAN), { value: "791234567", caret: 0 });
  assert.deepEqual(editPhone("791234567", "5", 1, JORDAN), { value: "5", caret: 1 });
  assert.deepEqual(editPhone("", "079 123 4567", 0, JORDAN), { value: "0791234567", caret: 10 }, "a filled-in value with the caret at the start");
});

test("REQ-DMO-91: a number typed for another country is cut to the new country's length", () => {
  assert.equal(fitPhone("0791234567", KUWAIT), "079123456");
  assert.equal(fitPhone("965500123", KUWAIT), "500123", "with Kuwait's dial code in front");
  assert.equal(fitPhone("791234567", GERMANY), "791234567");
});
