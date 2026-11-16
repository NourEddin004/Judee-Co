// Storefront audit: WCAG 2.1 AA (axe-core), sideways scrolling, and the SRS design rules that a
// browser can measure (REQ-QLT-02, REQ-UX-*). See docs/SETUP.md, "Checks in a browser".
//   BASE=http://127.0.0.1:8000 AXE=node_modules/axe-core/axe.min.js TOKEN=<an order's link_token> OUT=report.json node tools/checks/accessibility.cjs
const { chromium } = require('playwright');
const fs = require('fs');
const AXE = fs.readFileSync(process.env.AXE, 'utf8');
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const TOKEN = process.env.TOKEN;
const BAG = JSON.stringify([{ id: 2, size: null, qty: 2 }, { id: 23, size: 'M', qty: 1 }]);
const PAGES = [
  ['home', '/'], ['shop', '/shop'], ['sets', '/sets'], ['hijabs', '/hijabs'], ['bundles', '/bundles'],
  ['search-none', '/shop?q=zzzz'], ['product', '/product/rosemary-hijab'], ['product-sized', '/product/wrap-set'],
  ['cart-empty', '/cart'], ['cart', '/cart', 'bag'],
  ['checkout-empty', '/checkout'], ['checkout', '/checkout', 'bag'], ['contact', '/contact'], ['terms', '/terms'],
  ['confirmation', '/order/' + TOKEN], ['not-found', '/no-such-page'],
];
const STATES = { // extra states checked with axe at phone width
  'home': [['menu open', p => p.click('[data-modal-open="mobile-menu"]')]],
  'product': [['bag drawer open', async p => { await p.click('[data-add-button]'); await p.waitForTimeout(600); }]],
  'product-sized': [['size guide link', p => p.click('a.ds-size-picker__guide')], ['add without size', async p => { await p.click('[data-add-button]'); }]],
  'checkout': [['errors shown', async p => { await p.click('form [type=submit]'); await p.waitForTimeout(400); }]],
};
const WIDTHS = [360, 375, 390, 414, 768, 1024, 1280, 1440, 1920];

async function measure(page, width) {
  return page.evaluate((width) => {
    const out = { overflow: null, small: [], headings: [], margin: [], noRatio: [], blush: [] };
    const de = document.documentElement;
    if (de.scrollWidth > de.clientWidth + 0) out.overflow = { scrollWidth: de.scrollWidth, clientWidth: de.clientWidth,
      culprits: [...document.querySelectorAll('body *')].filter(e => { const r = e.getBoundingClientRect(); return r.width && (r.right > de.clientWidth + 1 || r.left < -1) && getComputedStyle(e).position !== 'fixed'; }).slice(0, 5).map(e => e.className || e.tagName) };
    const visible = e => { const r = e.getBoundingClientRect(); const s = getComputedStyle(e); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity !== 0 && !e.closest('[hidden],[inert],dialog:not([open])'); };
    // Rows that scroll sideways on their own (category tabs, related products, the size table) may run past the margin.
    const inScroller = e => { for (let n = e.parentElement; n && n !== de; n = n.parentElement) { const o = getComputedStyle(n).overflowX; if (o === 'auto' || o === 'scroll' || o === 'hidden' || o === 'clip') return true; } return false; };
    const DISPLAY = ['ds-hero__headline', 'ds-page-heading__title', 'ds-collection-intro__title', 'ds-confirmation__title', 'ds-contact__title']; // Appendix D.3, D.5.12, D.6
    const label = e => (e.className && typeof e.className === 'string' ? '.' + e.className.trim().split(/\s+/)[0] : e.tagName.toLowerCase());
    const textEls = [...document.querySelectorAll('body *')].filter(e => [...e.childNodes].some(n => n.nodeType === 3 && n.textContent.trim()) && visible(e));
    for (const e of textEls) {
      const s = getComputedStyle(e); const fs = parseFloat(s.fontSize);
      if (fs < 11) out.small.push(label(e) + ' ' + fs + 'px "' + e.textContent.trim().slice(0, 20) + '"');
      const r = e.getBoundingClientRect();
      if (width <= 414 && (r.left < 15.5 || r.right > de.clientWidth - 15.5) && !inScroller(e) && !e.closest('.ds-checkout__trap') && s.position !== 'fixed') out.margin.push(label(e) + ' ' + Math.round(r.left) + '..' + Math.round(de.clientWidth - r.right));
      // Text on blush-200: walk up to the first solid background.
      let n = e, bg = null;
      while (n && n !== de) { const b = getComputedStyle(n).backgroundColor; if (b && b !== 'rgba(0, 0, 0, 0)' && b !== 'transparent') { bg = b; break; } n = n.parentElement; }
      if (bg === 'rgb(246, 221, 228)' && !['rgb(46, 28, 34)', 'rgb(80, 57, 63)', 'rgb(115, 94, 100)'].includes(s.color)) out.blush.push(label(e) + ' ' + s.color);
    }
    for (const h of document.querySelectorAll('h1,h2,h3,h4,h5,h6')) {
      if (!visible(h) || DISPLAY.some(c => h.classList.contains(c))) continue; const s = getComputedStyle(h); const ratio = parseFloat(s.lineHeight) / parseFloat(s.fontSize);
      if (s.lineHeight !== 'normal' && Math.abs(ratio - 1.2) > 0.02) out.headings.push(label(h) + ' ' + ratio.toFixed(2));
      if (s.lineHeight === 'normal') out.headings.push(label(h) + ' normal');
    }
    out.small = [...new Set(out.small)].slice(0, 8); out.margin = [...new Set(out.margin)].slice(0, 8); out.blush = [...new Set(out.blush)].slice(0, 8); out.headings = [...new Set(out.headings)];
    return out;
  }, width);
}

(async () => {
  const browser = await chromium.launch({ args: ['--no-proxy-server', '--ignore-certificate-errors'] });
  const report = [];
  for (const locale of ['en', 'ar']) {
    for (const [name, path, fill] of PAGES) {
      const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 390, height: 844 }, deviceScaleFactor: 1, hasTouch: true });
      await ctx.addInitScript(([bag, fill]) => { if (fill === 'bag') localStorage.setItem('jude.bag.v2', bag); }, [BAG, fill]);
      // Both languages share one address; Arabic is the jude_lang cookie the language button sets.
      await ctx.addCookies([{ name: 'jude_lang', value: locale, url: BASE }]);
      const page = await ctx.newPage();
      const url = BASE + path;
      const entry = { page: locale + ' ' + name, axe: [], widths: {} };
      // Images: is the space for each one reserved before it loads (REQ-UX-35)? Block them and look.
      await page.route('**/*.{webp,png,jpg,jpeg}', r => r.abort());
      await page.goto(url, { waitUntil: 'networkidle' });
      entry.noRatio = await page.evaluate(() => [...document.images].filter(i => { const r = i.getBoundingClientRect(); const s = getComputedStyle(i); return s.display !== 'none' && !i.closest('[hidden],dialog:not([open]),template') && (r.width === 0 || r.height === 0) && !i.closest('[data-line-image]'); }).map(i => i.className || i.src.split('/').pop()).slice(0, 6));
      await page.unroute('**/*.{webp,png,jpg,jpeg}');
      await page.goto(url, { waitUntil: 'networkidle' });
      for (const w of WIDTHS) {
        await page.setViewportSize({ width: w, height: 900 }); await page.waitForTimeout(150);
        const m = await measure(page, w);
        const issues = Object.fromEntries(Object.entries(m).filter(([k, v]) => v && (!Array.isArray(v) || v.length)));
        if (Object.keys(issues).length) entry.widths[w] = issues;
      }
      const axeRun = async (label) => {
        await page.evaluate(AXE);
        const res = await page.evaluate(async () => await axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] } }));
        for (const v of res.violations) entry.axe.push({ state: label, id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 3).map(n => n.target.join(' ') + ' :: ' + (n.failureSummary || '').split('\n').slice(1, 2).join('')), count: v.nodes.length });
        entry.incomplete = (entry.incomplete || 0) + res.incomplete.length; entry.review = [...new Set([...(entry.review || []), ...res.incomplete.flatMap(i => i.nodes.map(n => i.id + " " + n.target.join(" ") + " :: " + (n.any[0]?.message || "").slice(0, 120)))])];
      };
      for (const w of [390, 1440]) { await page.setViewportSize({ width: w, height: 900 }); await page.waitForTimeout(200); await axeRun('width ' + w); }
      await page.setViewportSize({ width: 390, height: 844 });
      for (const [label, act] of STATES[name] || []) {
        await page.goto(url, { waitUntil: 'networkidle' });
        try { await act(page); await page.waitForTimeout(500); await axeRun(label); } catch (e) { entry.axe.push({ state: label, id: 'SCRIPT', impact: 'n/a', nodes: [String(e).slice(0, 160)] }); }
      }
      report.push(entry);
      await ctx.close();
      const n = entry.axe.length + Object.keys(entry.widths).length + entry.noRatio.length;
      console.log(entry.page.padEnd(22), n ? 'ISSUES' : 'ok', JSON.stringify({ axe: entry.axe.map(a => a.state + ':' + a.id + 'x' + a.count), widths: Object.keys(entry.widths), noRatio: entry.noRatio }));
    }
  }
  fs.writeFileSync(process.env.OUT, JSON.stringify(report, null, 1));
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
