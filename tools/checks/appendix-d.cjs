// REQ-DMO-01: measures the storefront in Chromium against the Build values of SRS Appendix D.5 and D.6.
// Each context opens one page at one width (optionally with a filled bag or an action),
// then each check reads computed styles of the first visible match. Needs the demo sample data.
// See docs/SETUP.md, "Checks in a browser".
//   BASE=http://127.0.0.1:8000 TOKEN=<an order's link_token> OUT=report.json node tools/checks/appendix-d.cjs
const { chromium } = require('playwright');
const fs = require('fs');
const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const TOKEN = process.env.TOKEN;
const BAG = JSON.stringify([{ id: 2, size: null, qty: 2 }, { id: 23, size: 'M', qty: 1 }]);

const C = {
  white: '#ffffff', ink: '#2e1c22', inkSoft: '#50393f', inkMuted: '#735e64', inkSubtle: '#7c656b', rose: '#a24f68',
  roseStrong: '#874058', roseSoft: '#f6d5de', plum: '#4a2633', wine: '#8c4459', wineDeep: '#6e3547', gold: '#eab2bf',
  accent: '#b84d70', onBrand: '#fff7f9', b50: '#fdf6f8', b100: '#faebef', b200: '#f6dde4', muted: '#faeff2', sunken: '#f4e0e6',
  image: '#fbf2f4', glassBadge: '#ffffffe6', glassEdge: '#ffffff99', scrim: '#4a263373', line: '#8c445924', lineFaint: '#8c445917',
  lineStrong: '#8c445933', lineBold: '#8c445942', lineDashed: '#e5cbd2', heroText: '#5e2f3f', heroLead: '#50393fe0', clear: '#00000000',
};
const L1 = '0 8px 24px #2a1c2012', L2 = '0 14px 36px #2a1c2029', L3 = '0 18px 50px #00000026', L3S = '-18px 0 50px #00000026';
const gutter = w => Math.min(Math.max(20, w * 0.04), 56) + 'px';
const MONT = 'f:Montserrat', MERRI = 'f:Merriweather', BODONI = 'f:Bodoni Moda';
const box = (t, r = t, b = t, l = r) => ({ paddingTop: t, paddingRight: r, paddingBottom: b, paddingLeft: l });
const padX = v => ({ paddingLeft: v, paddingRight: v });
const padY = v => ({ paddingTop: v, paddingBottom: v });
const border = (w, c, style = 'solid') => ({ borderTopWidth: w, borderRightWidth: w, borderBottomWidth: w, borderLeftWidth: w, borderTopColor: c, borderTopStyle: style });
const R = v => ({ borderTopLeftRadius: v });
const FULL = R('9999px');
const gap = (row, col = row) => ({ rowGap: row, columnGap: col });

// [label, selector, expected, options]
const CONTEXTS = [
  { name: 'home 1280', path: '/', width: 1280, checks: [
    ['D.5.1 all buttons (Shop now)', '.ds-hero .ds-btn', { display: 'inline-flex|flex', alignItems: 'center', justifyContent: 'center', columnGap: '8px', minHeight: '48px', ...padY('12px'), borderTopWidth: '1px', fontFamily: MONT, fontWeight: 600 }],
    ['D.5.1 pill', '.ds-hero .ds-btn--pill', { ...FULL, ...padX('32px'), fontSize: '16px', textTransform: 'none', letterSpacing: 'em:0' }],
    ['D.5.1 primary', '.ds-hero .ds-btn--primary', { backgroundColor: C.rose, color: C.onBrand, boxShadow: 'none' }],
    ['D.5.1 primary hover', '.ds-hero .ds-btn--primary', { backgroundColor: C.roseStrong, transform: 'none', boxShadow: 'none' }, { hover: true }],
    ['D.5.1 pressed', '.ds-hero .ds-btn--primary', { transform: 'scale:0.96' }, { press: true }],
    ['D.5.1 transitions', '.ds-hero .ds-btn', { transitionProperty: 'has:transform,background-color,border-color', transitionDuration: 'has:0.2s' }],
    ['D.5.1 text button (العربية)', '.ds-header__tools .ds-text-btn', { minHeight: '44px', ...padX('8px'), ...R('8px'), fontSize: '11px', backgroundColor: C.clear }],
    ['D.5.1 text button hover', '.ds-header__tools .ds-text-btn', { backgroundColor: C.b50, color: C.wine }, { hover: true }],
    ['D.5.1 icon button', '.ds-header__tools .ds-icon-btn', { w: '44px', h: '44px', ...FULL, backgroundColor: C.clear }],
    ['D.5.1 icon button icon', '.ds-header__tools .ds-icon-btn .ds-icon', { w: '19px', h: '19px' }],
    ['D.5.1 icon button hover', '.ds-header__tools .ds-icon-btn', { backgroundColor: C.b100, color: C.wine }, { hover: true }],
    ['D.5.3 header', '.ds-header', { position: 'sticky', top: '0px', h: '72px', paddingTop: '10px', paddingLeft: '10px', paddingRight: '10px', zIndex: '100' }],
    ['D.5.3 bar', '.ds-header__bar', { maxWidth: '1536px', ...FULL, ...border('1px', C.glassEdge), boxShadow: 'inset 0 1px 0 #ffffffbf, inset 0 -1px 0 #ffffff33, 0 8px 28px #2b0a122e', backdropFilter: 'blur(18px) saturate(1.8)', ...padX(gutter(1280)), display: 'grid', columnGap: '16px', gridTemplateColumns: 'cols:3' }],
    ['D.5.3 menu button hidden', '.ds-header__menu', { display: 'none' }, { any: true }],
    ['D.5.3 tools gap', '.ds-header__tools', { columnGap: '8px' }],
    ['D.5.3 logo', '.ds-header__logo', { ...box('4px'), ...R('8px') }],
    ['D.5.3 logo image', '.ds-header__logo .ds-brand__logo', { h: '32px' }],
    ['D.5.3 logo hover', '.ds-header__logo', { opacity: 0.75 }, { hover: true }],
    ['D.5.3 navigation', '.ds-header__nav', { columnGap: '24px' }],
    ['D.5.3 nav link', '.ds-header__link:not([aria-current])', { ...padY('8px'), fontSize: '14px', fontWeight: 500, letterSpacing: 'em:0.03', color: C.inkSoft }],
    ['D.5.3 nav underline', '.ds-header__link:not([aria-current])', { h: '2px', backgroundColor: C.gold, transform: 'scale:0' }, { pseudo: '::after' }],
    ['D.5.3 nav underline on current page', '.ds-header__link[aria-current="page"]', { transform: 'scale:1', backgroundColor: C.gold }, { pseudo: '::after' }],
    ['D.5.3 nav underline grows on hover', '.ds-header__link:not([aria-current])', { transform: 'scale:1' }, { pseudo: '::after', hover: true }],
    ['D.5.3 nav current text', '.ds-header__link[aria-current="page"]', { color: C.wine }],
    ['D.5.3 nav hover', '.ds-header__link:not([aria-current])', { color: C.wine }, { hover: true }],
    ['D.5.3 nav underline transition', '.ds-header__link', { transitionDuration: 'has:0.2s' }, { pseudo: '::after' }],
    ['D.5.3 currency button chevron', '.ds-currency__toggle .ds-icon', { w: '15px' }],
    ['D.6.1 hero', '.ds-hero', { backgroundImage: 'has:radial-gradient', ...gap('32px'), paddingTop: '104px', paddingBottom: '32px', alignItems: 'end', gridTemplateColumns: 'ratio:5:7' }],
    ['D.6.1 hero side padding', '.ds-hero', { paddingLeft: gutter(1280) }],
    ['D.6.1 hero text', '.ds-hero__content', { rowGap: '16px', alignSelf: 'center', paddingBottom: '32px' }],
    ['D.6.1 headline', '.ds-hero__headline', { lh: 1.1 }],
    ['D.6.1 headline text', '.ds-hero__headline-main', { fontFamily: BODONI, fontWeight: 400, letterSpacing: 'em:-0.01', color: C.heroText }],
    ['D.6.1 lead', '.ds-hero__lead', { fontSize: '16px', lh: 1.5, color: C.heroLead, maxWidth: '520px' }],
    ['D.6.1 rack stage 4:3', '.ds-hero__stage', { aspectRatio: '4 / 3' }],
    ['D.6.1 rail', '.ds-rack__rail', { h: '8px', backgroundImage: 'has:linear-gradient', boxShadow: '0 6px 14px #5e2f3f33', top: '%:8:parent-h' }],
    ['D.6.1 post', '.ds-rack__post', { w: '7px' }],
    ['D.6.1 foot', '.ds-rack__foot', { h: '6px', w: '%:13:parent-w' }],
    ['D.6.1 garment name tag', '.ds-rack__tag', { ...padY('4px'), ...padX('12px'), ...FULL, backgroundColor: C.glassBadge, fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.03', color: C.plum, whiteSpace: 'nowrap', boxShadow: L2 }],
    ['D.6.1 trust strip', '.ds-trust', { backgroundColor: C.muted }],
    ['D.6.1 trust strip height', '.ds-trust', { minHeight: '70px' }],
    ['D.6.1 trust item', '.ds-trust__item', { columnGap: '12px', justifyContent: 'center' }],
    ['D.6.1 trust divider', '.ds-trust__item + .ds-trust__item', { borderLeftWidth: '1px', borderLeftColor: C.lineStrong }],
    ['D.6.1 trust icon', '.ds-trust__item .ds-icon', { w: '24px' }],
    ['D.6.1 trust title', '.ds-trust__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '20px', lh: 1.2 }],
    ['D.6.1 trust text', '.ds-trust__text', { fontSize: '11px', color: C.inkMuted, lh: 1.3 }],
    ['D.6.1 about section', '.ds-callout', { backgroundColor: C.white, ...padY('128px'), columnGap: '64px', gridTemplateColumns: 'ratio:5:6', alignItems: 'center' }],
    ['D.6.1 looks', '.ds-callout__looks', { gridTemplateColumns: 'cols:3', columnGap: '16px' }],
    ['D.6.1 look photo', '.ds-callout__look-media', { aspectRatio: '3 / 4', ...R('12px'), backgroundColor: C.b100 }],
    ['D.6.1 look caption', '.ds-callout__look-caption', { fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: C.wine, textAlign: 'center', whiteSpace: 'nowrap' }],
    ['D.6.1 about text', '.ds-callout__copy', { rowGap: '16px' }],
    ['D.6.1 about title', '.ds-callout__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '36px', lh: 1.2 }],
    ['D.6.1 about body', '.ds-callout__body', { fontSize: '16px', lh: 1.5, color: C.inkMuted, maxWidth: '520px' }],
    ['D.5.12 eyebrow (from 769)', '.ds-callout .ds-eyebrow', { fontSize: '16px', fontWeight: 500, letterSpacing: 'em:0.09', textTransform: 'uppercase' }],
    ['D.6.1 how to order', '.ds-stores', { backgroundColor: C.b50, ...padY('96px') }],
    ['D.6.1 how to order title', '.ds-stores__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '36px' }],
    ['D.6.1 how to order note', '.ds-stores__title-note', { fontFamily: MONT, fontSize: '12px', fontWeight: 500, letterSpacing: 'em:0.03', color: C.wine }],
    ['D.6.1 steps', '.ds-order-grid', { gridTemplateColumns: 'has:', display: 'grid' }],
    ['D.6.1 step card', '.ds-order', { ...box('24px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, boxShadow: L1 }],
    ['D.6.1 step title', '.ds-order__title', { fontFamily: MERRI, fontSize: '20px', lh: 1.3, color: C.ink, marginBottom: '8px' }],
    ['D.6.1 step text', '.ds-order__detail', { fontSize: '16px', lh: 1.5, color: C.inkMuted }],
    ['D.6.1 step arrow', '.ds-order__arrow', { w: '36px', h: '36px', ...FULL, ...border('1px', C.gold), backgroundColor: C.white, boxShadow: L1 }],
    ['D.6.1 step arrow icon', '.ds-order__arrow .ds-icon', { w: '15px', color: C.rose }],
    ['D.6.1 button above', '.ds-order-cta', { marginTop: '32px' }],
    ['D.6.1 reviews', '.ds-testimonials', { backgroundColor: C.white, ...padY('128px') }, { optional: true }],
    ['D.6.1 reviews title', '.ds-testimonials__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '36px', whiteSpace: 'nowrap' }, { optional: true }],
    ['D.6.1 review grid', '.ds-testimonials__grid', { marginTop: '48px', columnGap: '16px', gridTemplateColumns: 'colw:240' }, { optional: true }],
    ['D.6.1 review card', '.ds-testimonial', { ...box('12px'), ...border('1px', C.gold), ...R('12px'), backgroundColor: C.white, boxShadow: L1 }, { optional: true }],
    ['D.6.1 review image', '.ds-testimonial__image', { aspectRatio: '4 / 5', objectFit: 'contain', ...R('12px'), marginBottom: '12px' }, { optional: true }],
    ['D.6.1 review name', '.ds-testimonial__name', { fontFamily: MERRI, fontSize: '20px', color: C.ink }, { optional: true }],
    ['D.6.1 review flag', 'img.ds-testimonial__flag, span.ds-testimonial__flag', { w: '18px', ...R('2px') }, { optional: true }],
    ['D.5.5 footer', '.ds-footer', { borderTopWidth: '1px', borderTopColor: C.line, backgroundColor: C.white }],
    ['D.5.5 grid', '.ds-footer__grid', { maxWidth: '1480px', paddingTop: '48px', paddingBottom: '32px', ...padX(gutter(1280)), gridTemplateColumns: 'ratio:1.4:1:1:1', columnGap: '48px' }],
    ['D.5.5 logo', '.ds-footer__logo .ds-brand__logo', { h: '44px' }],
    ['D.5.5 brand block', '.ds-footer__brand', { rowGap: '16px' }],
    ['D.5.5 brand icons', '.ds-footer__brand-icon', { w: '24px' }],
    ['D.5.5 column heading', '.ds-footer__heading', { fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.12', textTransform: 'uppercase', color: C.wine, marginBottom: '8px' }],
    ['D.5.5 column link', '.ds-footer__nav .ds-footer__link', { minHeight: '44px', fontSize: '14px', color: C.inkSoft }],
    ['D.5.5 column link hover', '.ds-footer__nav .ds-footer__link', { color: C.wine }, { hover: true }],
    ['D.5.5 bottom line', '.ds-footer__bottom', { borderTopWidth: '1px', borderTopColor: C.lineFaint }],
    ['D.5.5 bottom text', '.ds-footer__legal', { maxWidth: '1480px', ...padY('16px'), ...padX(gutter(1280)), fontSize: '11px', color: C.inkSubtle, flexWrap: 'wrap' }],
    ['D.5.11 back to top', '.ds-back-to-top', { position: 'fixed', w: '48px', h: '48px', ...FULL, ...border('1px', C.line), backgroundColor: C.white, color: C.plum, boxShadow: L2, zIndex: '90', right: gutter(1280), bottom: gutter(1280) }, { any: true }],
    ['D.5.11 back to top icon', '.ds-back-to-top .ds-icon', { w: '20px' }, { any: true }],
    ['D.5.11 toast', '.ds-toast', { position: 'fixed', bottom: '16px', right: '16px', minHeight: '48px', ...padY('12px'), ...padX('16px'), ...R('8px'), backgroundColor: C.ink, color: C.white, fontSize: '12px', boxShadow: L2, zIndex: '500', maxWidth: '440px' }, { any: true }],
    ['D.5.11 toast icon', '.ds-toast .ds-icon', { w: '16px', color: C.roseSoft }, { any: true }],
    ['D.5.11 skip link', '.ds-skip-link', { backgroundColor: C.ink, color: C.white, fontSize: '12px', ...padY('12px'), ...padX('16px'), zIndex: '1000' }, { any: true }],
    ['D.5.11 selection', 'p', { backgroundColor: C.roseSoft, color: C.ink }, { pseudo: '::selection' }],
  ] },
  { name: 'home 390', path: '/', width: 390, checks: [
    ['D.5.3 bar padding from 361', '.ds-header__bar', { ...padX('16px') }],
    ['D.5.3 tools below 1024: bag only', '.ds-header__tools > :not(.ds-header__desktop-only)', { count: 1 }, { visibleCount: true }],
    ['D.6.1 hero phone', '.ds-hero', { rowGap: '24px', paddingTop: '120px', paddingBottom: '32px', paddingLeft: '20px', gridTemplateColumns: 'cols:1' }],
    ['D.6.1 trust phone', '.ds-trust', { ...padY('12px') }],
    ['D.6.1 trust title phone', '.ds-trust__title', { fontSize: '16px' }],
    ['D.6.1 about phone', '.ds-callout', { ...padY('64px'), rowGap: '32px' }],
    ['D.6.1 looks phone', '.ds-callout__looks', { columnGap: '8px' }],
    ['D.6.1 about title phone', '.ds-callout__title', { fontSize: '30px' }],
    ['D.5.12 eyebrow phone', '.ds-callout .ds-eyebrow', { fontSize: '12px' }],
    ['D.6.1 how to order phone', '.ds-stores', { ...padY('64px') }],
    ['D.6.1 steps phone', '.ds-order-grid', { maxWidth: '520px', rowGap: '48px', marginTop: '32px' }],
    ['D.6.1 reviews phone', '.ds-testimonials', { ...padY('96px') }, { optional: true }],
    ['D.6.1 review grid phone', '.ds-testimonials__grid', { gridTemplateColumns: 'cols:2', columnGap: '12px' }, { optional: true }],
    ['D.5.5 grid phone', '.ds-footer__grid', { rowGap: '32px' }],
    ['D.5.5 columns phone', '.ds-footer__grid', { gridTemplateColumns: 'cols:3', columnGap: '16px' }],
    ['D.5.5 brand row phone', '.ds-footer__brand', { w: 'full-parent' }],
    ['D.5.11 toast phone', '.ds-toast', { bottom: '12px', maxWidth: '354px' }, { any: true }],
  ] },
  { name: 'home 360', path: '/', width: 360, checks: [
    ['D.5.3 bar padding below 361', '.ds-header__bar', { ...padX('12px') }],
    ['D.5.5 links below 361', '.ds-footer__nav .ds-footer__link', { fontSize: '12px' }],
  ] },
  { name: 'menu open 390', path: '/', width: 390, fill: 'bag', action: async p => { await p.click('[data-modal-open="mobile-menu"]'); await p.waitForTimeout(400); }, checks: [
    ['D.5.4 panel', '.ds-menu', { backgroundColor: C.white, w: 'vw', h: 'vh' }],
    ['D.5.4 layer', '.ds-modal--menu', { zIndex: '300' }],
    ['D.5.4 head', '.ds-menu__head', { minHeight: '72px', ...padY('8px'), ...padX('16px'), borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.4 links', '.ds-menu__link', { minHeight: '56px', ...padX('24px'), fontFamily: MERRI, fontSize: '24px' }],
    ['D.5.4 current link', '.ds-menu__link[aria-current="page"]', { color: C.wine }],
    ['D.5.4 other link', '.ds-menu__link:not([aria-current])', { color: C.ink }],
    ['D.5.4 utility cell', '.ds-menu__utility', { minHeight: '56px', ...padX('24px'), columnGap: '8px', fontSize: '14px' }],
    ['D.5.4 utilities: one cell (Cart)', '.ds-menu__utility', { count: 1, text: 'has:Cart' }, { visibleCount: true }],
    ['D.5.4 language button', '.ds-menu__language', { minHeight: '54px', fontSize: '14px' }],
    ['D.5.4 language row line', '.ds-menu__languages', { borderTopWidth: '1px' }],
    ['D.5.4 current currency', '.ds-menu__currency-option[aria-pressed="true"]', { fontWeight: 600, color: C.wine }],
    ['D.5.4 other currency', '.ds-menu__currency-option[aria-pressed="false"]', { color: C.inkMuted }],
  ] },
  { name: 'currency open 1280', path: '/', width: 1280, action: async p => { await p.click('[data-currency-toggle]'); await p.waitForTimeout(250); }, checks: [
    ['D.5.3 currency menu', '.ds-currency .ds-dropdown__menu, .ds-dropdown__menu', { w: '190px', ...border('1px', C.line), backgroundColor: C.white, boxShadow: L2, zIndex: '110', gapAbove: '6px', alignRightWithButton: true }],
    ['D.5.3 currency row', '.ds-dropdown__item', { minHeight: '48px', ...padX('12px'), fontSize: '12px', borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.3 currency name', '.ds-dropdown__item .ds-currency__name, .ds-dropdown__item span:not(:first-child)', { color: C.inkMuted }],
    ['D.5.3 currency row hover', '.ds-dropdown__item', { backgroundColor: C.b50 }, { hover: true }],
  ] },
  { name: 'shop 1280', path: '/shop', width: 1280, checks: [
    ['D.5.12 collection band', '.ds-collection-intro', { ...padY('48px'), backgroundColor: C.b50, borderBottomWidth: '1px', borderBottomColor: C.gold }],
    ['D.5.12 collection title', '.ds-collection-intro__title', { textAlign: 'center', fontFamily: MERRI, fontWeight: 300, fontSize: '36px', lh: 1.1 }],
    ['D.6.2 layout', '.ds-shop', { gridTemplateColumns: 'first:280', columnGap: '24px', w: 'vw-48', paddingTop: '24px' }],
    ['D.5.7 filter panel desktop', '.ds-filters', { w: '280px', ...box('24px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, boxShadow: L1 }],
    ['D.5.7 filter clear link', '.ds-filters__clear', { fontSize: '12px', color: C.wine, textDecorationLine: 'underline' }],
    ['D.5.7 no bottom bar', '.ds-filters__actions', { display: 'none' }, { any: true }],
    ['D.5.7 color label', '.ds-filters__label', { fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.12', textTransform: 'uppercase', color: C.inkMuted, marginBottom: '8px' }],
    ['D.5.7 checkbox option', '.ds-filters .ds-option', { minHeight: '44px', ...padY('4px'), ...padX('8px'), ...R('8px'), columnGap: '8px', fontSize: '14px' }],
    ['D.5.7 checkbox', '.ds-option__control', { w: '16px', h: '16px', accentColor: C.roseStrong }],
    ['D.5.7 checkbox option hover', '.ds-filters .ds-option', { backgroundColor: C.b50 }, { hover: true }],
    ['D.5.7 filter swatch', '.ds-filters .ds-swatch', { w: '16px', h: '16px' }],
    ['D.5.7 size section 24 px under the colors', '.ds-filters__section:has(+ .ds-filters__section)', { paddingBottom: '24px', borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.7 size label like the color label', '.ds-filters__section + .ds-filters__section .ds-filters__label', { fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.12', textTransform: 'uppercase', color: C.inkMuted, marginBottom: '8px' }],
    ['D.5.7 sizes in size order', '.ds-filters__section + .ds-filters__section .ds-option', { count: 6, text: 'All sizes,S,M,L,XL,XXL' }, { visibleCount: true }],
    ['D.6.2 toolbar', '.ds-results__toolbar', { rowGap: '12px', marginBottom: '24px' }],
    ['D.6.2 search row', '.ds-results__search', { columnGap: '8px' }],
    ['D.5.7 search bar', '.ds-searchbar__input', { ...FULL, h: '50px', ...border('1px', C.lineBold), backgroundColor: C.white, fontSize: '16px', paddingLeft: '48px' }],
    ['D.5.7 search icon', '.ds-searchbar > .ds-icon', { color: C.wine, w: '19px', left: '18px' }],
    ['D.5.7 search bar hover', '.ds-searchbar__input', { borderTopColor: C.wine }, { hover: true }],
    ['D.5.7 placeholder', '.ds-searchbar__input', { color: C.inkSubtle }, { pseudo: '::placeholder' }],
    ['D.5.7 search button', '.ds-searchbar__submit', { ...FULL, h: '50px', textTransform: 'none', ...padX('32px'), backgroundColor: C.rose }],
    ['D.5.7 tabs', '.ds-category-tabs', { columnGap: '8px' }],
    ['D.5.7 tab', '.ds-category-tabs__tab:not([aria-current])', { minHeight: '44px', ...padX('16px'), ...border('1px', C.lineBold), ...FULL, backgroundColor: C.white, fontSize: '14px', fontWeight: 600, color: C.inkSoft }],
    ['D.5.7 tab hover', '.ds-category-tabs__tab:not([aria-current])', { borderTopColor: C.wine, color: C.wine, backgroundColor: C.b50 }, { hover: true }],
    ['D.5.7 current tab', '.ds-category-tabs__tab[aria-current="page"]', { backgroundColor: C.rose, borderTopColor: C.rose, color: C.onBrand }],
    ['D.5.7 tab count', '.ds-category-tabs__tab:not([aria-current]) .ds-category-tabs__count', { minWidth: '24px', ...padY('4px'), ...padX('8px'), ...FULL, backgroundColor: C.b100, fontSize: '11px', color: C.inkMuted }], // 2 px becomes 4 px (D.4.1; decided in M1)
    ['D.5.7 current tab count', '[aria-current="page"] > .ds-category-tabs__count', { backgroundColor: C.roseStrong, color: C.onBrand }],
    ['D.5.7 no sort menu (version 1.2)', '.ds-sort, select[name="sort"]', { count: 0 }, { visibleCount: true }],
    ['D.5.7 filters button hidden from 1024', '.ds-results__filters-btn', { display: 'none' }, { any: true }],
    ['D.5.6 shop grid', '.ds-grid--catalog', { gridTemplateColumns: 'cols:3', ...gap('64px') }],
    ['D.5.6 card', '.ds-card', { display: 'flex', flexDirection: 'column', ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, overflow: 'hidden' }],
    ['D.5.6 card hover', '.ds-card', { borderTopColor: C.gold, boxShadow: L1 }, { hover: true }],
    ['D.5.6 card transition', '.ds-card', { transitionDuration: 'has:0.3s' }],
    ['D.5.6 photo area', '.ds-card__media', { aspectRatio: '3 / 4', backgroundColor: C.sunken }],
    ['D.5.6 photo', '.ds-card__image--primary', { objectFit: 'cover', transitionDuration: 'has:0.3s' }],
    ['D.5.6 photo hover', '.ds-card:has(.ds-card__image--secondary) .ds-card__image--primary', { opacity: 0, transform: 'scale:1.035' }, { hover: '.ds-card:has(.ds-card__image--secondary)' }],
    ['D.5.6 second photo hover', '.ds-card .ds-card__image--secondary', { opacity: 1, transform: 'scale:1.035' }, { hover: '.ds-card:has(.ds-card__image--secondary)' }],
    ['D.5.6 body', '.ds-card__body', { ...gap('24px'), paddingTop: '24px', paddingRight: '32px', paddingBottom: '32px', paddingLeft: '32px' }],
    ['D.5.6 name', '.ds-card__title', { fontSize: '14px', fontWeight: 600, lh: 1.3, textTransform: 'uppercase', webkitLineClamp: '2' }],
    ['D.5.6 name hover', '.ds-card__title', { textDecorationLine: 'underline' }, { hover: true }],
    ['D.5.6 price', '.ds-card__price', { maxWidth: '45%', fontSize: '16px', fontWeight: 700, fontVariantNumeric: 'tabular-nums' }],
    ['D.5.1 compact', '.ds-card .ds-btn--compact', { minHeight: '44px', ...padX('12px'), fontSize: '12px', letterSpacing: 'em:0.06', w: 'full-parent' }],
    ['D.5.1 compact primary on hijab', '.ds-card .ds-btn--compact.ds-btn--primary', { backgroundColor: C.rose }],
    ['D.5.1 all buttons radius', '.ds-card .ds-btn--compact', { ...R('8px'), textTransform: 'uppercase' }],
    ['D.5.1 button icon 18', '.ds-btn .ds-icon', { w: '18px', h: '18px' }, { optional: true }],
    ['D.5.7 pagination', '.ds-pagination', { justifyContent: 'center', columnGap: '8px', marginTop: '48px' }],
    ['D.5.7 page item', '.ds-pagination__item:not(.is-current):not(.is-disabled)', { w: '44px', h: '44px', ...border('1px', C.lineStrong), ...R('8px'), backgroundColor: C.white, fontSize: '14px', fontWeight: 600, color: C.inkSoft }],
    ['D.5.7 page item hover', 'a.ds-pagination__item:not(.is-current)', { borderTopColor: C.wine, color: C.wine, backgroundColor: C.b50 }, { hover: true }],
    ['D.5.7 current page', '.ds-pagination__item.is-current', { backgroundColor: C.rose, borderTopColor: C.rose, color: C.onBrand }],
    ['D.5.7 unavailable arrow', '.ds-pagination__item.is-disabled', { opacity: 0.4, pointerEvents: 'none' }],
  ] },
  { name: 'shop 800', path: '/shop', width: 800, checks: [
    ['D.5.6 grid from 769', '.ds-grid--catalog', { gridTemplateColumns: 'cols:3', ...gap('48px') }],
    ['D.5.6 body from 769', '.ds-card__body', { ...gap('16px'), paddingTop: '16px', paddingRight: '16px', paddingBottom: '24px', paddingLeft: '16px' }],
    ['D.5.7 search button from 769', '.ds-searchbar__submit', { ...padX('32px') }],
    ['D.5.7 filters button', '.ds-results__filters-btn', { ...FULL, minHeight: '44px', ...padX('16px'), ...border('1px', C.lineBold), backgroundColor: C.white, fontSize: '12px', fontWeight: 600 }],
    ['D.5.7 filters icon', '.ds-results__filters-btn .ds-icon', { w: '15px' }],
    ['D.6.2 layout tablet', '.ds-shop', { paddingTop: '16px', paddingBottom: '64px', w: 'vw-32' }],
  ] },
  { name: 'shop 390', path: '/shop', width: 390, checks: [
    ['D.5.6 grid from 361', '.ds-grid--catalog', { gridTemplateColumns: 'cols:2', rowGap: '32px', columnGap: '16px' }],
    ['D.5.6 body phone', '.ds-card__body', { ...gap('8px'), paddingTop: '12px', paddingRight: '12px', paddingBottom: '16px', paddingLeft: '12px' }],
    ['D.5.6 name phone', '.ds-card__title', { fontSize: '11px' }],
    ['D.5.6 price phone', '.ds-card__price', { fontSize: '12px' }],
    ['D.5.7 tabs scroll phone', '.ds-category-tabs', { overflowX: 'auto', flexWrap: 'nowrap' }],
    ['D.5.7 search button phone', '.ds-searchbar__submit', { ...padX('16px') }],
    ['D.5.7 pagination phone', '.ds-pagination', { marginTop: '32px' }],
    ['D.5.12 collection band phone', '.ds-collection-intro', { ...padY('32px'), ...padX('16px') }],
    ['D.5.12 collection title phone', '.ds-collection-intro__title', { fontSize: '30px' }],
  ] },
  { name: 'shop 360', path: '/shop', width: 360, checks: [
    ['D.5.6 grid below 361', '.ds-grid--catalog', { gridTemplateColumns: 'cols:1', rowGap: '32px' }],
  ] },
  { name: 'filters open 390', path: '/shop', width: 390, action: async p => { await p.click('[data-filters-open]'); await p.waitForTimeout(400); }, checks: [
    ['D.5.7 filter sheet', '.ds-filters', { position: 'fixed', zIndex: '300', backgroundColor: C.muted, w: 'vw', h: 'vh' }],
    ['D.5.7 sheet head', '.ds-filters__head', { backgroundColor: C.white, minHeight: '64px', ...padY('12px'), ...padX('16px') }],
    ['D.5.7 sheet title', '.ds-filters__title', { fontSize: '20px', fontWeight: 700 }],
    ['D.5.7 sheet body', '.ds-filters__scroll', { paddingTop: '4px', paddingRight: '16px', paddingBottom: '24px', paddingLeft: '16px', overflowY: 'auto' }],
    ['D.5.7 sheet bar', '.ds-filters__actions', { backgroundColor: C.white, borderTopWidth: '1px', paddingTop: '12px', paddingRight: '16px', paddingBottom: '16px', paddingLeft: '16px', columnGap: '12px', gridTemplateColumns: 'ratio:0.7:1' }],
  ] },
  { name: 'search 1280', path: '/shop?q=olive', width: 1280, checks: [
    ['D.5.7 search chip', '.ds-results__chip', { ...FULL, minHeight: '44px', ...padX('12px'), backgroundColor: C.b100, fontSize: '12px', fontWeight: 600, color: C.wine }],
    ['D.5.7 search chip hover', '.ds-results__chip', { backgroundColor: C.b200 }, { hover: true }],
  ] },
  { name: 'no results 1280', path: '/shop?q=zzzz', width: 1280, checks: [
    ['D.5.7 empty state', '.ds-empty', { minHeight: '430px', paddingTop: '32px', ...border('1px', C.lineDashed, 'dashed'), ...R('12px'), textAlign: 'center' }],
    ['D.5.7 empty icon', '.ds-empty__icon', { w: '56px', h: '56px', backgroundColor: C.b200, color: C.wineDeep, ...FULL }],
    ['D.5.7 empty title', '.ds-empty__title', { fontFamily: MERRI, fontSize: '30px', fontWeight: 300, marginTop: '16px' }],
    ['D.5.7 empty body', '.ds-empty__body', { fontSize: '16px', color: C.inkMuted, maxWidth: '520px', marginTop: '8px', marginBottom: '24px' }],
  ] },
  { name: 'no results 390', path: '/shop?q=zzzz', width: 390, checks: [
    ['D.5.7 empty state phone', '.ds-empty', { paddingTop: '24px', paddingLeft: '16px', paddingRight: '16px' }],
  ] },
  { name: 'bundles 1280', path: '/bundles', width: 1280, checks: [
    ['D.5.2 badge', '.ds-card__badge', { ...FULL, ...padY('4px'), ...padX('8px'), backgroundColor: C.glassBadge, backdropFilter: 'blur(4px)', color: C.plum, fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.06', textTransform: 'uppercase', top: '12px', left: '12px', maxWidth: 'calc(100% - 52px)' }],
  ] },
  { name: 'bundles 390', path: '/bundles', width: 390, checks: [
    ['D.5.2 badge phone', '.ds-card__badge', { top: '8px', left: '8px', fontSize: '11px' }],
  ] },
  { name: 'sets 1280', path: '/sets', width: 1280, checks: [
    ['D.5.6 fitted photo area', '.ds-card--fit .ds-card__media', { backgroundColor: C.image }],
    ['D.5.6 fitted photo', '.ds-card--fit .ds-card__image--primary', { objectFit: 'contain', w: '%:76:parent-w', h: '%:86:parent-h', left: '%:12:parent-w', top: '%:7:parent-h' }],
    ['D.5.6 sized button', '.ds-card .ds-btn--compact', { backgroundColor: C.white, borderTopColor: C.rose, color: C.ink }],
    ['D.5.6 color dots', '.ds-card__colors', { columnGap: '4px', role: 'img' }],
    ['D.5.6 color dot', '.ds-card__dot', { w: '12px', h: '12px', ...FULL, ...border('1px', C.line) }],
    ['D.5.6 more colors', '.ds-card__more', { fontSize: '11px', color: C.inkMuted }],
  ] },
  { name: 'product 1280', path: '/product/wrap-set', width: 1280, checks: [
    ['D.6.3 back row', '.ds-back', { paddingTop: '24px', w: 'vw-40' }],
    ['D.5.8 back link', '.ds-back__link', { minHeight: '44px', fontSize: '12px', fontWeight: 600, color: C.inkSoft }],
    ['D.5.8 back chevron', '.ds-back__link .ds-icon', { w: '15px' }],
    ['D.5.8 back hover', '.ds-back__link', { color: C.wine }, { hover: true }],
    ['D.6.3 layout', '.ds-pdp', { gridTemplateColumns: 'ratio:1.1:0.9', columnGap: '24px', w: 'vw-40', paddingTop: '48px', paddingBottom: '32px' }],
    ['D.6.3 photo column sticky', '.ds-pdp__media', { position: 'sticky', top: '88px' }],
    ['D.6.3 purchase column', '.ds-pdp__purchase', { position: 'sticky', top: '88px', maxWidth: '520px', overflowY: 'auto', maxHeight: 'vh-112', scrollbarWidth: 'thin' }],
    ['D.5.8 frame', '.ds-pdp__frame', { aspectRatio: '1 / 1', ...border('1px', C.line), ...R('12px'), backgroundColor: C.image, boxShadow: L1 }],
    ['D.5.8 photo contained', '.ds-pdp__image', { objectFit: 'contain' }],
    ['D.5.8 thumbs', '.ds-pdp__thumbs', { marginTop: '12px', columnGap: '8px', overflowX: 'auto' }],
    ['D.5.8 thumb', '.ds-pdp__thumb', { w: '76px', h: '76px', ...R('12px'), borderTopWidth: '2px' }],
    ['D.5.8 thumb border', '.ds-pdp__thumb:not(.is-active)', { borderTopColor: C.clear }],
    ['D.5.8 selected thumb', '.ds-pdp__thumb.is-active', { borderTopColor: C.rose }],
    ['D.5.8 title', '.ds-pdp__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '30px', lh: 1.2 }],
    ['D.5.8 price', '.ds-pdp__price', { fontSize: '20px', fontWeight: 600, color: C.wine, fontVariantNumeric: 'tabular-nums', gapAfter: ['.ds-pdp__title', '12px'] }],
    ['D.5.8 facts', '.ds-pdp__facts', { fontSize: '14px', color: C.inkMuted, rowGap: '8px', columnGap: '16px' }],
    ['D.5.8 facts value', '.ds-pdp__facts strong', { color: C.ink, fontWeight: 600 }],
    ['D.5.8 in stock', '.ds-pdp__stock', { color: C.wine, fontWeight: 600 }],
    ['D.5.8 description', '.ds-pdp__lead', { fontSize: '16px', lh: 1.5, color: C.inkMuted }],
    ['D.5.8 available colors label', '.ds-color-picker__legend', { fontSize: '14px', fontWeight: 600 }],
    ['D.5.8 color options', '.ds-color-picker__options', { columnGap: '8px' }],
    ['D.5.8 color option', '.ds-color-picker__option', { w: '44px', h: '44px', ...FULL }],
    ['D.5.8 color swatch', '.ds-color-picker__swatch', { w: '32px', h: '32px' }],
    ['D.5.8 color option hover', '.ds-color-picker__option:not(:has(:checked))', { borderTopColor: C.lineBold }, { hover: true }],
    ['D.5.8 current color ring', '.ds-color-picker__option:has(:checked)', { ring: ['2px', C.rose] }],
    ['D.5.8 size label', '.ds-size-picker__legend', { fontSize: '14px', fontWeight: 600 }],
    ['D.5.8 size guide link', '.ds-size-picker__guide', { fontSize: '12px', color: C.wine, textDecorationLine: 'underline' }],
    ['D.5.8 size options', '.ds-size-picker__options', { gridTemplateColumns: 'cols:5', columnGap: '8px' }],
    ['D.5.8 size option', '.ds-size-picker__label', { minHeight: '48px', ...border('1px', C.lineBold), ...R('8px'), backgroundColor: C.white, fontSize: '14px', fontWeight: 600, color: C.inkSoft }],
    ['D.5.8 size option hover', '.ds-size-picker__option', { borderTopColor: C.wine, color: C.wine }, { hover: true, read: '.ds-size-picker__label' }],
    ['D.5.8 quantity row', '.ds-pdp__quantity', { maxWidth: '300px' }],
    ['D.5.8 stepper', '.ds-pdp__quantity .ds-stepper', { ...border('1px', C.line), ...R('8px') }],
    ['D.5.8 stepper buttons', '.ds-pdp__quantity .ds-stepper__btn', { w: '44px', h: '44px' }],
    ['D.5.8 stepper icon', '.ds-pdp__quantity .ds-stepper__btn .ds-icon', { w: '15px' }],
    ['D.5.8 stepper number', '.ds-pdp__quantity .ds-stepper__input', { w: '36px', fontSize: '16px', textAlign: 'center' }],
    ['D.5.8 stepper at limit', '.ds-pdp__quantity .ds-stepper__btn:disabled', { opacity: 0.4 }],
    ['D.5.8 add button', '.ds-pdp__buy .ds-btn--primary', { w: 'full-parent', backgroundColor: C.rose }],
    ['D.5.1 block icon', '.ds-pdp__buy .ds-btn .ds-icon', { w: '18px', h: '18px' }],
    ['D.5.1 disabled reason row height', '.ds-pdp__buy .ds-btn--primary', { minHeight: '48px', ...padX('24px'), letterSpacing: 'em:0.03', textTransform: 'uppercase', fontSize: '14px' }],
    ['D.5.8 perks', '.ds-pdp__perk', { fontSize: '12px', fontWeight: 500, color: C.inkSoft }],
    ['D.5.8 perk icon', '.ds-pdp__perk .ds-icon', { color: C.wine }],
    ['D.5.8 size guide card', '.ds-info-card', { marginTop: '24px', ...padY('16px'), ...padX('24px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.muted }],
    ['D.5.8 size guide title', '.ds-info-card__title', { fontFamily: MERRI, fontSize: '20px', fontWeight: 300 }],
    ['D.5.8 size guide note', '.ds-info-card__note', { fontSize: '12px', color: C.inkMuted }],
    ['D.5.8 table box', '.ds-size-table__wrap', { backgroundColor: C.white, ...border('1px', C.line), ...R('8px'), overflowX: 'auto' }],
    ['D.5.8 table', '.ds-size-table', { fontSize: '12px', textAlign: 'center' }],
    ['D.5.8 table header', '.ds-size-table thead th', { backgroundColor: C.b100, fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', letterSpacing: 'em:0.03', color: C.plum }],
    ['D.5.8 size column', '.ds-size-table tbody th', { fontWeight: 700, color: C.wine }],
    ['D.5.8 cells', '.ds-size-table td', { ...box('12px'), color: C.inkSoft, borderTopWidth: '1px', borderTopColor: C.lineFaint }],
    ['D.6.3 related', '.ds-related', { paddingTop: '48px', paddingBottom: '64px' }],
    ['D.6.3 related heading', '.ds-section-heading__title', { textAlign: 'center', fontFamily: MONT, fontWeight: 600, fontSize: '30px' }],
    ['D.6.3 related rule', '.ds-section-heading__rule', { w: '48px', h: '2px', backgroundColor: C.rose, marginTop: '12px', marginBottom: '32px' }],
    ['D.5.6 related row from 1024', '.ds-grid--rail', { gridTemplateColumns: 'cols:4', columnGap: '16px' }],
  ] },
  { name: 'product 800', path: '/product/wrap-set', width: 800, checks: [
    ['D.6.3 layout from 769', '.ds-pdp', { paddingTop: '48px', paddingBottom: '32px', w: 'vw-40' }],
    ['D.5.6 related row from 769', '.ds-grid--rail', { gridTemplateColumns: 'cols:3', columnGap: '16px' }],
  ] },
  { name: 'product 390', path: '/product/wrap-set', width: 390, checks: [
    ['D.6.3 back row phone', '.ds-back', { w: 'vw-32' }],
    ['D.6.3 layout phone', '.ds-pdp', { gridTemplateColumns: 'cols:1', rowGap: '24px', paddingTop: '48px', paddingBottom: '48px' }],
    ['D.6.3 related phone', '.ds-related', { paddingTop: '48px', ...padX('16px'), paddingBottom: '64px' }],
    ['D.6.3 related heading phone', '.ds-section-heading__title', { fontSize: '20px' }],
    ['D.5.6 related row phone', '.ds-grid--rail', { overflowX: 'auto', scrollSnapType: 'has:x', columnGap: '12px' }],
    ['D.5.6 related card phone', '.ds-grid--rail > .ds-card', { w: 'min:72vw:250', scrollSnapAlign: 'has:start' }],
  ] },
  { name: 'product size error 1280', path: '/product/wrap-set', width: 1280, action: async p => { await p.click('[data-add-button]'); await p.waitForTimeout(300); }, checks: [
    ['D.5.8 size error borders', '.ds-size-picker__label', { borderTopColor: C.wine }],
    ['D.5.8 size error message', '.ds-size-picker__error', { fontSize: '12px', fontWeight: 600, color: C.wine }],
  ] },
  { name: 'product size chosen 1280', path: '/product/wrap-set', width: 1280, action: async p => { await p.click('.ds-size-picker__option >> nth=1'); await p.waitForTimeout(400); }, checks: [
    ['D.5.8 selected size', '.ds-size-picker__control:checked + .ds-size-picker__label', { backgroundColor: C.rose, borderTopColor: C.rose, color: C.onBrand }],
  ] },
  { name: 'cart 1280', path: '/cart', width: 1280, fill: 'bag', checks: [
    ['D.6.4 section', '.ds-cart', { gridTemplateColumns: 'last:380', columnGap: '64px' }],
    ['D.6.4 section width', '.ds-section', { w: 'vw-48' }],
    ['D.6.4 section padding', '.ds-section', { paddingTop: '64px', paddingBottom: '96px' }],
    ['D.5.12 list heading title', '.ds-list-head__title', { fontFamily: MERRI, fontSize: '36px', fontWeight: 300 }],
    ['D.5.12 list heading count', '.ds-list-head__count', { fontSize: '12px', color: C.inkMuted }],
    ['D.5.12 list heading link', '.ds-list-head__link', { fontSize: '12px', fontWeight: 600, color: C.wine }],
    ['D.5.12 list heading', '.ds-list-head', { marginBottom: '32px' }],
    ['D.5.9 column titles', '.ds-cart__columns', { fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.12', textTransform: 'uppercase', color: C.inkMuted, borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.9 column title padding', '.ds-cart__columns', { ...padY('12px'), columnGap: '24px' }],
    ['D.5.9 page line', '.ds-cart-line--page', { columnGap: '24px', ...padY('24px'), gridTemplateColumns: 'colsPx:104,*,136,96,44' }],
    ['D.5.9 page line photo', '.ds-cart-line--page .ds-cart-line__image', { w: '104px', h: '128px', ...R('12px') }],
    ['D.5.9 page line name', '.ds-cart-line--page .ds-cart-line__name', { fontSize: '20px', fontFamily: MERRI, lh: 1.3 }],
    ['D.5.9 size tag', '.ds-cart-line--page .ds-cart-line__meta', { ...FULL, ...padY('4px'), ...padX('8px'), backgroundColor: C.b100, fontSize: '11px', fontWeight: 600, color: C.wine }], // 2 px becomes 4 px
    ['D.5.9 line stepper', '.ds-cart-line--page .ds-stepper__btn', { w: '44px', h: '44px' }],
    ['D.5.9 line stepper number', '.ds-cart-line--page .ds-stepper__value', { fontSize: '11px', borderLeftWidth: '1px', borderRightWidth: '1px' }],
    ['D.5.9 line total', '.ds-cart-line--page .ds-cart-line__total', { fontSize: '14px', fontWeight: 600 }],
    ['D.5.9 remove', '.ds-cart-line--page .ds-cart-line__remove', { w: '44px', h: '44px' }],
    ['D.5.9 summary', '.ds-summary', { ...box('24px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.muted, position: 'sticky', top: '96px' }],
    ['D.5.9 summary title', '.ds-summary__title', { fontFamily: MERRI, fontSize: '24px', fontWeight: 300, marginBottom: '24px' }],
    ['D.5.9 summary row', '.ds-summary__row', { fontSize: '12px', ...padY('16px'), borderTopWidth: '1px', borderTopColor: C.line }],
    ['D.5.9 summary note', '.ds-summary__note', { fontSize: '11px', color: C.inkMuted }],
  ] },
  { name: 'cart 390', path: '/cart', width: 390, fill: 'bag', checks: [
    ['D.6.4 section phone', '.ds-section', { paddingTop: '48px', paddingBottom: '64px', w: 'vw-32' }],
    ['D.6.4 stacked', '.ds-cart', { rowGap: '32px', gridTemplateColumns: 'cols:1' }],
    ['D.5.12 list title phone', '.ds-list-head__title', { fontSize: '30px' }],
  ] },
  { name: 'cart empty 390', path: '/cart', width: 390, checks: [
    ['D.5.7 empty state (bag)', '.ds-empty', { minHeight: '430px', ...border('1px', C.lineDashed, 'dashed') }],
  ] },
  { name: 'drawer 390', path: '/shop', width: 390, fill: 'bag', action: async p => { await p.click('.ds-header [data-modal-open="cart-drawer"]'); await p.waitForTimeout(500); }, checks: [
    ['D.5.9 drawer phone', '.ds-drawer', { top: '72px', w: 'vw-32', maxHeight: 'vh-120', ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, boxShadow: L3 }],
    ['D.5.9 drawer head', '.ds-drawer__head', { minHeight: '86px', ...box('16px'), backgroundColor: C.b50 }],
    ['D.5.9 drawer title', '.ds-drawer__title', { fontFamily: MERRI, fontSize: '24px', fontWeight: 300 }],
    ['D.5.9 drawer count', '.ds-drawer__meta', { fontSize: '11px', color: C.inkMuted }],
    ['D.5.9 drawer body', '.ds-drawer__body', { minHeight: '170px', ...padX('16px'), overflowY: 'auto' }],
    ['D.5.9 drawer footer', '.ds-drawer__footer', { borderTopWidth: '1px', ...box('16px'), rowGap: '8px' }],
    ['D.5.9 subtotal row', '.ds-drawer__row', { fontSize: '14px' }],
    ['D.5.9 subtotal amount', '.ds-drawer__row strong, .ds-drawer__row [data-bag-subtotal]', { fontWeight: 700 }],
    ['D.5.9 drawer line', '.ds-drawer .ds-cart-line', { columnGap: '12px', rowGap: '8px', ...padY('16px'), borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.9 drawer photo', '.ds-drawer .ds-cart-line__image', { w: '88px', h: '100px', ...R('8px') }],
    ['D.5.9 drawer name', '.ds-drawer .ds-cart-line__name', { fontFamily: MERRI, fontSize: '20px', lh: 1.3 }],
    ['D.5.9 drawer stepper', '.ds-drawer .ds-stepper__btn', { w: '44px', h: '44px' }],
    ['D.5.9 drawer secondary', '.ds-drawer__footer .ds-btn--secondary', { backgroundColor: C.white, ...border('1px', C.rose), color: C.ink }],
    ['D.5.11 dialog scrim', '.ds-modal--drawer', { backgroundColor: C.scrim, backdropFilter: 'blur(2px)' }],
    ['D.5.9 drawer secondary hover', '.ds-drawer__footer .ds-btn--secondary', { backgroundColor: C.b50, borderTopColor: C.ink }, { hover: true }],
  ] },
  { name: 'drawer 1280', path: '/shop', width: 1280, fill: 'bag', action: async p => { await p.click('.ds-header [data-modal-open="cart-drawer"]'); await p.waitForTimeout(500); }, checks: [
    ['D.5.9 drawer side', '.ds-drawer', { w: '440px', h: 'vh', right: '0px', borderTopWidth: '0px', borderTopLeftRadius: '0px', boxShadow: L3S }],
    ['D.5.9 drawer head from 769', '.ds-drawer__head', { minHeight: '92px' }],
  ] },
  { name: 'drawer empty 1280', path: '/shop', width: 1280, action: async p => { await p.click('.ds-header [data-modal-open="cart-drawer"]'); await p.waitForTimeout(500); }, checks: [
    ['D.5.9 drawer empty', '.ds-drawer__empty', { minHeight: '300px', textAlign: 'center' }],
    ['D.5.9 drawer empty icon', '.ds-drawer__empty .ds-icon', { w: '30px' }],
    ['D.5.9 drawer empty title', '.ds-drawer__empty-title', { fontFamily: MERRI, fontSize: '24px', fontWeight: 300 }],
    ['D.5.9 drawer empty body', '.ds-drawer__empty-body', { fontSize: '16px', color: C.inkMuted }],
    ['D.5.1 disabled', '.ds-drawer__footer .ds-btn--primary', { opacity: 0.4, cursor: 'not-allowed' }],
    ['D.5.1 disabled no hover', '.ds-drawer__footer .ds-btn--primary', { backgroundColor: C.rose }, { hover: true }],
  ] },
  { name: 'checkout 1280', path: '/checkout', width: 1280, fill: 'bag', checks: [
    ['D.5.12 heading (left-aligned)', '.ds-page-heading--start', { paddingBottom: '24px', borderBottomWidth: '1px', borderBottomColor: C.line }],
    ['D.5.12 heading eyebrow', '.ds-page-heading--start .ds-eyebrow', { fontSize: '11px', fontWeight: 600, letterSpacing: 'em:0.12', color: C.wine }],
    ['D.5.12 heading title', '.ds-page-heading--start .ds-page-heading__title', { fontSize: '36px' }],
    ['D.6.6 layout', '.ds-checkout-layout', { gridTemplateColumns: 'ratio:1.3:0.9', columnGap: '48px' }],
    ['D.5.10 summary sticky', '.ds-checkout-layout__summary', { position: 'sticky', top: '96px' }],
    ['D.5.10 form card', '.ds-checkout', { ...box('32px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, boxShadow: L1, rowGap: '24px' }],
    ['D.5.10 group title', '.ds-checkout__legend', { fontFamily: MERRI, fontSize: '20px', fontWeight: 300, marginBottom: '16px' }],
    ['D.5.10 second group', '.ds-checkout__group + .ds-checkout__group', { borderTopWidth: '1px', borderTopColor: C.line, paddingTop: '24px' }],
    ['D.5.10 field grid', '.ds-checkout__grid', { gridTemplateColumns: 'cols:2', ...gap('16px') }],
    ['D.5.10 street spans', '.ds-checkout__wide', { gridColumnEnd: 'span 2|-1' }],
    ['D.5.10 label', '.ds-field__label', { fontSize: '12px', fontWeight: 600, marginBottom: '8px' }],
    ['D.5.10 optional', '.ds-field__optional', { fontWeight: 400, color: C.inkMuted }],
    ['D.5.10 text field', 'input.ds-field__input[type="text"], input.ds-field__input[type="email"]', { h: '48px', ...padX('12px'), ...border('1px', C.line), ...R('8px'), fontSize: '16px' }],
    ['D.5.10 hint', '.ds-field__hint', { fontSize: '11px', color: C.inkMuted }],
    ['D.5.10 phone field', '.ds-phone', { h: '48px', ...border('1px', C.line), ...R('8px') }],
    ['D.5.10 phone flag', '.ds-phone__flag', { fontSize: '20px' }],
    ['D.5.10 phone dial', '.ds-phone__dial', { fontSize: '14px', fontWeight: 600, fontVariantNumeric: 'tabular-nums' }],
    ['D.5.10 phone country', '.ds-phone__country', { borderRightWidth: '1px', borderRightColor: C.line, ...padX('12px') }],
    ['D.5.10 phone country list', '.ds-phone__select', { opacity: 0 }],
    ['D.5.10 phone number', '.ds-phone__input', { fontSize: '16px' }],
    ['D.5.10 city select', '.ds-field__select .ds-field__input', { h: '48px' }],
    ['D.5.10 city chevron', '.ds-field__select > .ds-icon', { w: '15px', right: '14px' }],
    ['D.5.10 area picker', '.ds-picker__trigger', { h: '48px', ...border('1px', C.line), ...R('8px') }],
    ['D.5.10 area disabled', '.ds-picker__trigger:disabled', { backgroundColor: C.muted, color: C.inkSubtle, cursor: 'not-allowed' }],
    ['D.5.10 aside lines photo', '.ds-summary__thumb', { w: '48px', h: '48px', ...R('8px') }],
    ['D.5.10 aside qty', '.ds-summary__qty', { color: C.inkMuted }],
    ['D.5.10 aside size', '.ds-summary__size', { fontSize: '11px', fontWeight: 600, color: C.wine }],
    ['D.5.10 total row', '.ds-summary__row--total', { fontSize: '16px' }],
    ['D.5.10 total amount', '.ds-summary__row--total strong', { fontFamily: MERRI, fontSize: '24px', fontWeight: 400 }],
    ['D.5.10 pay note', '.ds-pay-note', { marginTop: '12px', ...padY('12px'), ...padX('16px'), ...border('1px', C.line), ...R('8px'), backgroundColor: C.white }],
    ['D.5.10 pay note title', '.ds-pay-note__title', { fontSize: '12px', fontWeight: 600 }],
    ['D.5.10 pay note icon', '.ds-pay-note__title .ds-icon', { color: C.wine }],
    ['D.5.10 pay note body', '.ds-pay-note__body', { fontSize: '11px', color: C.inkMuted }],
    ['D.5.10 pay note link', '.ds-pay-note__link', { color: C.wine, textDecorationLine: 'underline' }],
  ] },
  { name: 'checkout 390', path: '/checkout', width: 390, fill: 'bag', checks: [
    ['D.6.6 summary on top', '.ds-checkout-layout__summary', { above: '.ds-checkout' }],
    ['D.6.6 gap phone', '.ds-checkout-layout', { rowGap: '24px' }],
    ['D.5.10 form card phone', '.ds-checkout', { ...box('16px') }],
    ['D.5.10 field grid phone', '.ds-checkout__grid', { gridTemplateColumns: 'cols:1' }],
  ] },
  { name: 'checkout errors 1280', path: '/checkout', width: 1280, fill: 'bag', action: async p => { await p.click('form [type=submit]'); await p.waitForTimeout(400); }, checks: [
    ['D.5.10 error summary', '.ds-checkout__alert', { ...padY('12px'), ...padX('16px'), ...border('1px', C.wine), ...R('8px'), backgroundColor: C.b50, fontSize: '12px', color: C.wine, role: 'alert' }],
    ['D.5.10 invalid field', '.ds-field__input[aria-invalid="true"]', { borderTopColor: C.wine }],
    ['D.5.10 invalid phone', '.ds-phone', { borderTopColor: C.wine }],
    ['D.5.10 error text', '.ds-field__error:not([hidden])', { fontSize: '11px', color: C.wine }],
  ] },
  { name: 'checkout area open 1280', path: '/checkout', width: 1280, fill: 'bag', action: async p => { await p.selectOption('select[name="city"], select[data-city]', { index: 1 }); await p.waitForTimeout(150); await p.click('.ds-picker__trigger'); await p.waitForTimeout(250); }, checks: [
    ['D.5.10 area open', '.ds-picker__trigger', { borderTopColor: C.roseStrong }],
    ['D.5.10 area panel', '.ds-picker__panel', { ...box('8px'), ...border('1px', C.line), ...R('12px'), backgroundColor: C.white, boxShadow: L2, zIndex: '110', gapAbove: '6px' }],
    ['D.5.10 area search', '.ds-picker__search-input', { h: '44px', fontSize: '16px' }],
    ['D.5.10 area list', '.ds-picker__list', { maxHeight: '260px', overflowY: 'auto' }],
    ['D.5.10 area option', '.ds-picker__option:not(.ds-picker__option--other)', { minHeight: '44px', ...padX('12px'), ...R('8px'), fontSize: '14px' }],
    ['D.5.10 area option hover', '.ds-picker__option:not(.ds-picker__option--other)', { backgroundColor: C.b50 }, { hover: true }],
    ['D.5.10 area other', '.ds-picker__option--other', { color: C.wine, borderTopWidth: '1px' }],
  ] },
  { name: 'contact 1280', path: '/contact', width: 1280, checks: [
    ['D.6.8 page', '.ds-contact', { paddingTop: '64px', paddingBottom: '96px', minHeight: 'vh*0.7', w: '1200px' }],
    ['D.6.8 header', '.ds-contact__header', { maxWidth: '640px', marginBottom: '64px' }],
    ['D.6.8 title', '.ds-contact__title', { fontFamily: MERRI, fontWeight: 300, lh: 1 }],
    ['D.6.8 intro', '.ds-contact__intro', { fontSize: '16px', color: C.inkMuted, marginTop: '16px' }],
    ['D.6.8 layout', '.ds-contact__layout', { gridTemplateColumns: 'first:320', columnGap: '64px' }],
    ['D.5.12 contact card', '.ds-contact__card', { maxWidth: '520px', ...box('24px'), ...R('12px'), backgroundColor: C.b200 }],
    ['D.5.12 card icon', '.ds-contact__card-icon', { w: '19px' }],
    ['D.5.12 card title', '.ds-contact__card-title', { fontFamily: MERRI, fontSize: '30px', fontWeight: 300 }],
    ['D.5.12 card subtitle', '.ds-contact__card-subtitle', { fontSize: '14px' }],
    ['D.5.12 card text', '.ds-contact__card-text', { fontSize: '12px', color: C.inkMuted }, { optional: true }],
    ['D.5.12 card buttons', '.ds-contact__actions', { columnGap: '8px', flexDirection: 'row' }],
    ['D.6.8 one primary (WhatsApp)', '.ds-contact__card .ds-btn--primary', { count: 1, text: 'WhatsApp' }, { visibleCount: true }],
    ['D.5.12 FAQ question', '.ds-faq__question', { minHeight: '64px', fontFamily: MERRI, fontWeight: 300, fontSize: '20px' }],
    ['D.5.12 FAQ icon', '.ds-faq__question .ds-icon', { w: '17px', transitionDuration: 'has:0.2s' }],
    ['D.5.12 FAQ answer', '.ds-faq__answer', { fontSize: '16px', lh: 1.5, color: C.inkMuted, maxWidth: '640px', paddingRight: '48px', paddingBottom: '24px' }, { action: p => p.click('.ds-faq__question') }],
    ['D.5.12 FAQ open icon', '.ds-faq__item[open] .ds-faq__question .ds-icon', { transform: 'rotate:45' }],
    ['D.5.12 FAQ lines', '.ds-faq__item', { borderTopWidth: '1px' }],
    ['D.5.12 FAQ last line', '.ds-faq__item:last-child', { borderBottomWidth: '1px' }],
  ] },
  { name: 'contact 390', path: '/contact', width: 390, checks: [
    ['D.6.8 page phone', '.ds-contact', { paddingTop: '64px', paddingBottom: '64px', w: 'vw-32' }],
    ['D.6.8 header phone', '.ds-contact__header', { marginBottom: '32px' }],
    ['D.6.8 stacked', '.ds-contact__layout', { rowGap: '32px' }],
    ['D.5.12 FAQ question phone', '.ds-faq__question', { fontSize: '16px' }],
  ] },
  { name: 'terms 1280', path: '/terms', width: 1280, checks: [
    ['D.5.12 prose', '.ds-prose', { paddingTop: '64px', paddingBottom: '96px' }],
    ['D.5.12 prose title', '.ds-prose__title', { fontFamily: MERRI, fontSize: '30px', fontWeight: 300 }],
    ['D.5.12 prose intro', '.ds-prose__intro', { fontSize: '16px', color: C.inkMuted, marginTop: '24px', marginBottom: '48px' }],
    ['D.5.12 prose section', '.ds-prose__section', { borderTopWidth: '1px', ...padY('24px') }],
    ['D.5.12 prose heading', '.ds-prose__heading', { fontSize: '16px' }],
    ['D.5.12 prose body', '.ds-prose__body', { fontSize: '16px', color: C.inkMuted }],
  ] },
  { name: 'terms 390', path: '/terms', width: 390, checks: [
    ['D.5.12 prose phone', '.ds-prose', { paddingTop: '48px', paddingBottom: '96px' }],
  ] },
  { name: 'confirmation 1280', path: '/order/' + TOKEN, width: 1280, checks: [
    ['D.6.7 column', '.ds-confirmation', { maxWidth: '560px' }],
    ['D.6.7 icon', '.ds-confirmation__icon', { w: '56px', h: '56px', ...FULL, backgroundColor: C.rose, color: C.onBrand }],
    ['D.6.7 eyebrow gap', '.ds-confirmation .ds-eyebrow', { gapAfter: ['.ds-confirmation__icon', '16px'] }], // 20 px is not one of the 10 steps; becomes 16 px (D.4.1)
    ['D.6.7 title', '.ds-confirmation__title', { fontFamily: MERRI, fontWeight: 300, fontSize: '36px', lh: 1.1 }],
    ['D.6.7 number pill', '.ds-confirmation__number', { fontSize: '12px', fontWeight: 600, letterSpacing: 'em:0.03', color: C.wine, backgroundColor: C.b100, ...FULL, ...padY('4px'), ...padX('12px') }],
    ['D.6.7 note', '.ds-confirmation__note', { fontSize: '12px', color: C.inkMuted }, { optional: true }],
    ['D.6.7 detail row', '.ds-confirmation__detail', { fontSize: '12px', ...padY('16px'), borderTopWidth: '1px' }],
    ['D.6.7 detail label', '.ds-confirmation__detail dt', { color: C.inkMuted }],
    ['D.6.7 detail value', '.ds-confirmation__detail dd', { fontWeight: 600, textAlign: 'right|end' }],
    ['D.6.7 receipt button', '.ds-confirmation [data-receipt]', { gapAfter: ['.ds-confirmation__details', '12px'], backgroundColor: C.white, ...border('1px', C.rose) }],
    ['D.6.7 receipt icon', '.ds-confirmation [data-receipt] .ds-icon', { w: '18px', h: '18px' }],
    ['D.6.7 continue under the receipt', '.ds-confirmation__continue', { gapAfter: ['.ds-confirmation [data-receipt]', '8px'], backgroundColor: C.rose }],
  ] },
  { name: 'not found 1280', path: '/no-such-page', width: 1280, checks: [
    ['D.6.10 empty state', '.ds-empty', { minHeight: '430px' }],
  ] },
  { name: 'toast 1280', path: '/hijabs', width: 1280, action: async p => { await p.click('.ds-card .ds-btn--primary'); await p.waitForTimeout(350); }, checks: [
    ['D.5.11 toast shown', '.ds-toast', { position: 'fixed', bottom: '16px', right: '16px', role: 'status', opacity: 1 }],
    ['D.5.2 count bubble', '.ds-header__tools .ds-count', { minWidth: '20px', h: '20px', ...padX('4px'), ...border('2px', C.white), ...FULL, backgroundColor: C.accent, color: C.white, fontSize: '11px', fontWeight: 700, top: '2px' }],
  ] },
  { name: 'back to top 1280', path: '/shop', width: 1280, action: async p => { await p.evaluate(() => window.scrollTo(0, 700)); await p.waitForTimeout(400); }, checks: [
    ['D.5.11 back to top shown', '.ds-back-to-top', { visible: true }],
    ['D.5.11 back to top hover', '.ds-back-to-top', { backgroundColor: C.b100 }, { hover: true }],
  ] },
  { name: 'skip link 1280', path: '/', width: 1280, action: async p => { await p.keyboard.press('Tab'); await p.waitForTimeout(300); }, checks: [
    ['D.5.11 skip link focused', '.ds-skip-link', { top: '10px', left: '10px', transitionDuration: 'has:0.15s' }],
  ] },
  { name: 'widths 1920', path: '/shop', width: 1920, checks: [
    ['D.6.2 layout width', '.ds-shop', { w: '1480px' }],
    ['D.5.5 footer grid width', '.ds-footer__grid', { w: '1480px' }],
    ['D.5.5 footer bottom width', '.ds-footer__legal', { w: '1480px' }],
    ['D.5.3 bar width', '.ds-header__bar', { w: '1536px' }],
  ] },
  { name: 'product 1920', path: '/product/wrap-set', width: 1920, checks: [
    ['D.6.3 back row width', '.ds-back', { w: '1400px' }],
    ['D.6.3 layout width', '.ds-pdp', { w: '1400px' }],
    ['D.5.8 frame width', '.ds-pdp__frame', { w: '600px' }],
    ['D.5.6 related row width', '.ds-grid--rail', { w: '1400px' }],
  ] },
  { name: 'cart 1920', path: '/cart', width: 1920, fill: 'bag', checks: [
    ['D.6.4 section width', '.ds-section', { w: '1400px' }],
  ] },
  { name: 'contact 1920', path: '/contact', width: 1920, checks: [
    ['D.6.8 page width', '.ds-contact', { w: '1200px' }],
  ] },
  { name: 'terms 1920', path: '/terms', width: 1920, checks: [
    ['D.5.12 prose width', '.ds-prose', { w: '896px' }],
  ] },
];

const NO_MOTION = '*,*::before,*::after{transition:none!important;animation:none!important}';

function parseColor(s) {
  if (!s) return null;
  if (s.startsWith('#')) {
    let h = s.slice(1); if (h.length === 3 || h.length === 4) h = [...h].map(c => c + c).join('');
    const n = [0, 2, 4].map(i => parseInt(h.slice(i, i + 2), 16)); n.push(h.length === 8 ? parseInt(h.slice(6, 8), 16) / 255 : 1); return n;
  }
  const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return s === 'transparent' ? [0, 0, 0, 0] : null;
  const p = m[1].split(/[ ,/]+/).filter(Boolean).map(Number); if (p.length === 3) p.push(1); return p;
}
const sameColor = (a, b) => { const x = parseColor(a), y = parseColor(b); return x && y && (x[3] === 0 && y[3] === 0 || x.slice(0, 3).every((v, i) => Math.abs(v - y[i]) <= 1) && Math.abs(x[3] - y[3]) < 0.01); };
// Shadows: compare offsets, blur and colour of each layer.
function sameShadow(actual, expected) {
  const norm = s => s.split(/,(?![^(]*\))/).map(l => { const colour = (l.match(/rgba?\([^)]+\)|#[0-9a-f]+/i) || [''])[0]; const nums = l.replace(colour, '').match(/-?[\d.]+px|\b0\b/g) || []; return { inset: /inset/.test(l), nums: nums.map(n => parseFloat(n)).concat([0, 0, 0, 0]).slice(0, 4), colour }; });
  const a = norm(actual), e = norm(expected);
  return a.length === e.length && a.every((l, i) => l.inset === e[i].inset && l.nums.every((n, j) => Math.abs(n - e[i].nums[j]) < 0.6) && sameColor(l.colour, e[i].colour));
}

function compare(key, actual, expected, ctx) {
  if (actual === undefined || actual === null) return false;
  if (typeof expected === 'number') return Math.abs(parseFloat(actual) - expected) < 0.011;
  if (typeof expected !== 'string') return JSON.stringify(actual) === JSON.stringify(expected);
  if (expected.startsWith('#')) return sameColor(actual, expected);
  if (key === 'boxShadow' && expected !== 'none') return sameShadow(actual, expected);
  if (/^-?[\d.]+px$/.test(expected)) return Math.abs(parseFloat(actual) - parseFloat(expected)) < 0.6;
  if (/^-?[\d.]+%$/.test(expected)) return actual === expected;
  if (expected.startsWith('f:')) return actual.split(',')[0].replace(/"/g, '').trim() === expected.slice(2);
  if (expected.startsWith('has:')) return expected.slice(4).split(',').every(part => actual.includes(part));
  if (expected.startsWith('em:')) return Math.abs(actual - parseFloat(expected.slice(3))) < 0.004;
  if (expected.includes('|')) return expected.split('|').includes(actual);
  return actual === expected;
}

(async () => {
  const browser = await chromium.launch({ args: ['--no-proxy-server', '--ignore-certificate-errors'] });
  const results = [];
  for (const c of CONTEXTS) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: c.width, height: 900 }, deviceScaleFactor: 1, bypassCSP: true });
    await ctx.addInitScript(([bag, fill]) => { if (fill === 'bag') localStorage.setItem('jude.bag.v2', bag); }, [BAG, c.fill]);
    const page = await ctx.newPage();
    await page.goto(BASE + c.path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(300);
    if (c.action) { try { await c.action(page); } catch (e) { results.push({ ctx: c.name, label: 'action', ok: false, fail: [['action', e.message.split('\n')[0]]] }); } }
    for (const [label, sel, expected, opts = {}] of c.checks) {
      if (opts.action) { try { await opts.action(page); await page.waitForTimeout(300); } catch {} }
      let styleHandle = null;
      if (opts.hover || opts.press) {
        styleHandle = await page.addStyleTag({ content: NO_MOTION });
        const target = typeof opts.hover === 'string' ? opts.hover : sel;
        const loc = page.locator(target).filter({ visible: true }).first();
        try { await loc.scrollIntoViewIfNeeded({ timeout: 2000 }); await loc.hover({ timeout: 2000, force: true }); if (opts.press) await page.mouse.down(); } catch (e) { results.push({ ctx: c.name, label, ok: false, fail: [['hover', 'not found']] }); await styleHandle.evaluate(n => n.remove()); continue; }
        await page.waitForTimeout(400);
      }
      const actual = await page.evaluate(([sel, expected, opts]) => {
        const visible = e => { const r = e.getBoundingClientRect(); const s = getComputedStyle(e); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
        const all = [...document.querySelectorAll(opts.read || sel)];
        if (opts.visibleCount) { const v = all.filter(visible); return { count: v.length, text: v.map(e => e.textContent.trim()).join(',') }; }
        const el = opts.any ? all[0] : all.find(visible);
        if (!el) return null;
        const s = getComputedStyle(el, opts.pseudo || null);
        const r = el.getBoundingClientRect();
        const parent = el.offsetParent || el.parentElement; const pr = parent.getBoundingClientRect();
        const out = {};
        for (const [k, v] of Object.entries(expected)) {
          if (k === 'w' || k === 'h') {
            let size = opts.pseudo ? parseFloat(s[k === 'w' ? 'width' : 'height']) : r[k === 'w' ? 'width' : 'height'];
            if (opts.pseudo && isNaN(size)) size = 0;
            const vw = document.documentElement.clientWidth, vh = window.innerHeight;
            if (v === 'full') out[k] = Math.abs(size - r.width) < 1 || Math.abs(size - el.getBoundingClientRect().width) < 1 ? 'full' : size + 'px';
            else if (v === 'full-parent') out[k] = Math.abs(r.width - el.parentElement.getBoundingClientRect().width + parseFloat(getComputedStyle(el.parentElement).paddingLeft) + parseFloat(getComputedStyle(el.parentElement).paddingRight)) < 1 ? 'full-parent' : r.width + 'px of ' + el.parentElement.getBoundingClientRect().width;
            else if (v === 'vw') out[k] = Math.abs(size - vw) < 1 ? 'vw' : size + 'px';
            else if (v === 'vh') out[k] = Math.abs(size - vh) < 1 ? 'vh' : size + 'px';
            else if (/^vw-\d+$/.test(v)) out[k] = Math.abs(size - (vw - +v.slice(3))) < 1 ? v : size + 'px';
            else if (v.startsWith('%:')) { const [, pct, of] = v.split(':'); const base = of === 'parent-w' ? pr.width : pr.height; out[k] = Math.abs(size / base * 100 - +pct) < 0.6 ? v : (size / base * 100).toFixed(1) + '%'; }
            else if (v.startsWith('min:')) { const [, a, b] = v.split(':'); const want = Math.min(parseFloat(a) / 100 * vw, +b); out[k] = Math.abs(size - want) < 1 ? v : size + 'px (want ' + want + ')'; }
            else out[k] = size + 'px';
            continue;
          }
          if (k === 'lh') { out[k] = (parseFloat(s.lineHeight) / parseFloat(s.fontSize)).toFixed(3); continue; }
          if (k === 'letterSpacing' && typeof v === 'string' && v.startsWith('em:')) { out[k] = s.letterSpacing === 'normal' ? 0 : parseFloat(s.letterSpacing) / parseFloat(s.fontSize); continue; }
          if (k === 'gridTemplateColumns' && typeof v === 'string') {
            const cols = s.gridTemplateColumns.split(' ').filter(x => x && x !== 'none').map(parseFloat);
            if (v.startsWith('cols:')) out[k] = cols.length === +v.slice(5) ? v : s.gridTemplateColumns;
            else if (v.startsWith('ratio:')) { const want = v.slice(6).split(':').map(Number); const ok = cols.length === want.length && cols.every((c, i) => Math.abs(c / cols[0] - want[i] / want[0]) < 0.03); out[k] = ok ? v : s.gridTemplateColumns; }
            else if (v.startsWith('first:')) out[k] = Math.abs(cols[0] - +v.slice(6)) < 1 ? v : s.gridTemplateColumns;
            else if (v.startsWith('last:')) out[k] = Math.abs(cols[cols.length - 1] - +v.slice(5)) < 1 ? v : s.gridTemplateColumns;
            else if (v.startsWith('colw:')) out[k] = cols.every(c => Math.abs(c - +v.slice(5)) < 1) ? v : s.gridTemplateColumns;
            else if (v.startsWith('colsPx:')) { const want = v.slice(7).split(','); const ok = cols.length === want.length && want.every((w, i) => w === '*' || Math.abs(cols[i] - +w) < 1); out[k] = ok ? v : s.gridTemplateColumns; }
            else out[k] = s.gridTemplateColumns;
            continue;
          }
          if (k === 'transform' && typeof v === 'string' && v.startsWith('scale:')) { const m = new DOMMatrix(s.transform === 'none' ? undefined : s.transform); out[k] = Math.abs(m.a - parseFloat(v.slice(6))) < 0.002 ? v : s.transform; continue; }
          if (k === 'transform' && typeof v === 'string' && v.startsWith('rotate:')) { const m = new DOMMatrix(s.transform === 'none' ? undefined : s.transform); const deg = Math.round(Math.atan2(m.b, m.a) * 180 / Math.PI); out[k] = Math.abs(Math.abs(deg) - +v.slice(7)) < 1 ? v : s.transform + ' (' + deg + '°)'; continue; }
          if ((k === 'top' || k === 'left') && typeof v === 'string' && v.startsWith('%:')) { const [, pct, of] = v.split(':'); const off = k === 'top' ? r.top - pr.top : r.left - pr.left; const base = of === 'parent-w' ? pr.width : pr.height; out[k] = Math.abs(off / base * 100 - +pct) < 0.6 ? v : (off / base * 100).toFixed(1) + '%'; continue; }
          if (k === 'top' || k === 'left' || k === 'right' || k === 'bottom') {
            if (s.position === 'fixed' || s.position === 'sticky') { out[k] = s[k]; continue; }
            const bl = parent.clientLeft, bt = parent.clientTop; const off = { top: r.top - pr.top - bt, left: r.left - pr.left - bl, right: pr.right - r.right - bl, bottom: pr.bottom - r.bottom - bt }[k]; out[k] = off + 'px'; continue;
          }
          if (k === 'maxHeight' && /^vh-\d+$/.test(v)) { out[k] = Math.abs(parseFloat(s.maxHeight) - (window.innerHeight - +v.slice(3))) < 1 ? v : s.maxHeight; continue; }
          if (k === 'minHeight' && v === 'vh*0.7') { out[k] = Math.abs(parseFloat(s.minHeight) - window.innerHeight * 0.7) < 1 ? v : s.minHeight; continue; }
          if (k === 'maxWidth' && v === 'calc:card-52') { const card = el.closest('.ds-card').getBoundingClientRect().width; out[k] = Math.abs(parseFloat(s.maxWidth) - (card - 52)) < 1 ? v : s.maxWidth + ' (card ' + card + ')'; continue; }
          if (k === 'gapAbove') { if (v === null) { out[k] = null; continue; } const prev = el.previousElementSibling || el.parentElement; const pr2 = (opts.anchor ? document.querySelector(opts.anchor) : prev).getBoundingClientRect(); out[k] = Math.round(r.top - pr2.bottom) + 'px'; continue; }
          if (k === 'gapAfter') { const a = document.querySelector(v[0]).getBoundingClientRect(); out[k] = Math.abs(r.top - a.bottom - parseFloat(v[1])) < 0.6 ? 'ok' : Math.round(r.top - a.bottom) + 'px'; continue; }
          if (k === 'alignRightWithButton') { const btn = el.parentElement.querySelector('button'); out[k] = btn && Math.abs(btn.getBoundingClientRect().right - r.right) < 1; continue; }
          if (k === 'rightEdgeOf') { const box = document.querySelector(v).getBoundingClientRect(); out[k] = Math.abs(box.right - r.right) < 2 ? v : 'ends ' + Math.round(box.right - r.right) + 'px before'; continue; }
          if (k === 'above') { const other = document.querySelector(v).getBoundingClientRect(); out[k] = r.bottom <= other.top + 1 ? v : 'below'; continue; }
          if (k === 'ring') { const shadow = s.boxShadow; const border = s.borderTopWidth + ' ' + s.borderTopColor; out[k] = { shadow, border, outline: s.outlineWidth + ' ' + s.outlineColor }; continue; }
          if (k === 'role') { out[k] = el.getAttribute('role'); continue; }
          if (k === 'visible') { out[k] = visible(el) && +s.opacity > 0; continue; }
          if (k === 'webkitLineClamp') { out[k] = s.webkitLineClamp || s.lineClamp; continue; }
          out[k] = s[k];
        }
        return out;
      }, [sel, expected, { ...opts, action: undefined }]);
      if (styleHandle) {
        if (opts.press) { await page.mouse.move(0, 0); await page.mouse.up(); }
        try { await styleHandle.evaluate(n => n.remove()); } catch {}
        await page.mouse.move(0, 0);
        if (page.url() !== BASE + c.path) { await page.goto(BASE + c.path, { waitUntil: 'networkidle' }); if (c.action) await c.action(page); }
      }
      if (!actual) { results.push({ ctx: c.name, label, ok: !!opts.optional, skipped: true, fail: opts.optional ? [] : [['selector', sel + ' not found']] }); continue; }
      const fail = [];
      for (const [k, v] of Object.entries(expected)) {
        if (k === 'gapAbove' && v === null) continue;
        let ok;
        if (k === 'ring') ok = (sameColor(actual[k].border.split(' ').slice(1).join(' '), v[1]) && parseFloat(actual[k].border) >= parseFloat(v[0])) || actual[k].shadow.includes(v[0]) && sameColor((actual[k].shadow.match(/rgba?\([^)]+\)/) || [''])[0], v[1]) || (actual[k].outline.startsWith(v[0]) && sameColor(actual[k].outline.split(' ').slice(1).join(' '), v[1]));
        else if (k === 'count') ok = actual.count === v;
        else if (k === 'text') ok = v.startsWith('has:') ? actual.text.includes(v.slice(4)) : actual.text === v;
        else if (k === 'alignRightWithButton' || k === 'visible') ok = actual[k] === true;
        else if (['w', 'h', 'gridTemplateColumns', 'transform', 'top', 'left', 'maxHeight', 'minHeight', 'maxWidth', 'gapAfter', 'rightEdgeOf', 'above'].includes(k) && typeof v === 'string' && actual[k] === v) ok = true;
        else if (k === 'gapAfter') ok = actual[k] === 'ok';
        else ok = compare(k, actual[k], v);
        if (!ok) fail.push([k, actual[k], v]);
      }
      results.push({ ctx: c.name, label, ok: !fail.length, fail });
    }
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync(process.env.OUT || 'dmo-report.json', JSON.stringify(results, null, 1));
  const bad = results.filter(r => !r.ok);
  console.log(`${results.length} checks, ${results.length - bad.length} pass, ${bad.length} fail`);
  for (const r of bad) console.log(`✗ [${r.ctx}] ${r.label}: ` + r.fail.map(([k, a, e]) => `${k}=${typeof a === 'object' ? JSON.stringify(a) : a} (want ${Array.isArray(e) ? e.join(' ') : e})`).join('; '));
})();
