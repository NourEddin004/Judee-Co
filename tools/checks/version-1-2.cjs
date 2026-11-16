// SRS 1.2 behaviour that only a browser can see, in English and Arabic, on a computer and a phone:
// choosing a colour changes the page without a reload (REQ-DMO-99), and "Continue shopping" closes
// the bag drawer (REQ-DMO-101). See docs/SETUP.md, "Checks in a browser".
//   BASE=http://127.0.0.1:8000 node tools/checks/version-1-2.cjs
const { chromium } = require('playwright');
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const BAG = JSON.stringify([{ id: 2, size: null, qty: 2 }, { id: 23, size: 'M', qty: 1 }]);
// Both languages share one address; Arabic is the jude_lang cookie the language button sets.
const RUNS = [['en', 1440], ['en', 390], ['ar', 1440], ['ar', 390]];

(async () => {
  const browser = await chromium.launch({ args: ['--no-proxy-server'] });
  let failed = 0;
  const report = (ok, ...line) => { if (!ok) failed++; console.log(ok ? 'ok  ' : 'FAIL', ...line); };

  for (const [lang, width] of RUNS) {
    const context = await browser.newContext({ viewport: { width, height: 844 }, hasTouch: width < 500 });
    await context.addInitScript((bag) => localStorage.setItem('jude.bag.v2', bag), BAG);
    await context.addCookies([{ name: 'jude_lang', value: lang, url: BASE }]);
    const page = await context.newPage();
    const where = `${lang} ${width}`;

    // REQ-DMO-99, with the SRS step: open on Navy, choose Black; the address ends in ?color=black.
    const read = () => page.evaluate(() => ({
      url: location.pathname + location.search,
      photo: document.querySelector('[data-gallery-main]')?.getAttribute('src') ?? '',
      name: document.querySelector('[data-color-name]')?.textContent.trim(),
      language: document.querySelector('[data-language-link]')?.getAttribute('href') ?? '',
    }));
    for (const [start, pick, slug] of [['/product/wrap-set', 1, 'navy'], ['/product/wrap-set?color=navy', 0, 'black']]) {
      await page.goto(BASE + start, { waitUntil: 'networkidle' });
      await page.evaluate(() => { window.__samePage = true; });
      const before = await read();
      await page.locator('.ds-color-picker__option').nth(pick).click();
      await page.waitForTimeout(300);
      const after = await read();
      const noReload = await page.evaluate(() => window.__samePage === true);
      report(noReload && after.url.endsWith(`?color=${slug}`) && after.photo.includes(slug) && after.name !== before.name && after.language.endsWith(`?color=${slug}&lang=${lang === 'ar' ? 'en' : 'ar'}`),
        where, `REQ-DMO-99 choose ${slug}:`, JSON.stringify({ ...after, noReload }));
    }

    // REQ-DMO-101: the drawer offers checkout and "Continue shopping", which closes it and gives focus back.
    await page.goto(BASE + '/shop', { waitUntil: 'networkidle' });
    const opener = page.locator('.ds-header [data-modal-open="cart-drawer"]').filter({ visible: true }).first();
    await opener.focus();
    await page.keyboard.press('Enter');
    await page.waitForTimeout(500);
    const footer = page.locator('#cart-drawer .ds-drawer__footer');
    const open = await page.locator('[data-modal="cart-drawer"]').isVisible();
    const buttons = (await footer.locator('a, button').filter({ visible: true }).allTextContents()).map((text) => text.trim());
    await footer.locator('[data-modal-close]').click();
    await page.waitForTimeout(400);
    const closed = !(await page.locator('[data-modal="cart-drawer"]').isVisible());
    const focusBack = await page.evaluate(() => document.activeElement?.matches('.ds-header [data-modal-open="cart-drawer"]') ?? false);
    report(open && buttons.length === 2 && closed && focusBack, where, 'REQ-DMO-101 drawer:', JSON.stringify({ buttons, closed, focusBack }));

    await context.close();
  }

  await browser.close();
  console.log(failed ? `${failed} failed` : 'all passed');
  process.exit(failed ? 1 : 0);
})();
