// The checkout phone field (REQ-CHK-21, REQ-CHK-22, REQ-DMO-56, 57, 89 to 91), kept free of page code so
// it can be unit tested. The server applies the same rule (app/Support/Phone.php). A country is
// { dial: "962", digits: [9] }: its dial code and the lengths of its numbers (Appendix D.8.3).

/** Digits only (REQ-DMO-89), with Arabic-Indic and Persian digits as 0 to 9 (REQ-DMO-90). */
export function onlyDigits(text) {
  // Both digit ranges start at a multiple of 16, so the last hex digit of the code is the digit.
  return String(text)
    .replace(/[٠-٩۰-۹]/g, (digit) => String(digit.charCodeAt(0) % 16))
    .replace(/\D/g, "");
}

/** The most digits the field takes: the country's longest number, plus 1 when it starts with 0 (REQ-DMO-91). */
export function longest(digits, country) {
  return Math.max(...country.digits) + (digits.startsWith("0") ? 1 : 0);
}

/**
 * The digits the field keeps of what was typed or pasted: a number that starts with the country's
 * dial code (after +, 00 or nothing) and is too long for the country loses that code once (REQ-DMO-57).
 */
export function phoneDigits(text, country) {
  const digits = onlyDigits(text);
  const prefix = digits.startsWith(`00${country.dial}`) ? country.dial.length + 2 : digits.startsWith(country.dial) ? country.dial.length : 0;
  return prefix > 0 && digits.length > Math.max(...country.digits) ? digits.slice(prefix) : digits;
}

/** Valid with exactly as many digits as the country uses, not counting a 0 at the start (REQ-CHK-22, REQ-DMO-56). */
export function isValidPhone(text, country) {
  return country.digits.includes(phoneDigits(text, country).replace(/^0/, "").length);
}

/** The field's value cut to what the country takes, for when the country changes (REQ-DMO-91). */
export function fitPhone(text, country) {
  const digits = phoneDigits(text, country);
  return digits.slice(0, longest(digits, country));
}

/**
 * The field after one change. `before` is what it held, `value` and `caret` what the browser made of
 * the change. Junk is dropped where it was typed, the caret stays after what was typed, and when the
 * number would be too long only the new digits that fit are kept, as a length limit would (REQ-DMO-91).
 */
export function editPhone(before, value, caret, country) {
  // What follows the caret is unchanged, and so is the start the two values share up to the change.
  // Anything else, such as a value filled in by the browser, counts as typed in full.
  let tail = value.length - caret;
  if (tail > before.length || value.slice(caret) !== before.slice(before.length - tail)) {
    tail = 0;
    caret = value.length;
  }
  const limit = Math.min(caret, before.length - tail);
  let head = 0;
  while (head < limit && before[head] === value[head]) head += 1;
  const start = before.slice(0, head);
  const end = before.slice(before.length - tail);
  const typed = onlyDigits(value.slice(head, caret));

  for (let kept = typed.length; kept >= 0; kept -= 1) {
    const joined = start + typed.slice(0, kept) + end;
    const digits = phoneDigits(joined, country);
    if (kept === 0 || digits.length <= longest(digits, country)) {
      const removed = joined.length - digits.length; // a dial code taken off the front (REQ-DMO-57)
      return { value: digits, caret: Math.max(0, start.length + kept - removed) };
    }
  }
}
