// JUDE storefront script: one bundle, loaded on every page.
import { initUi } from "./store/ui.js";
import { initBag } from "./store/bag.js";
import { initShop } from "./store/shop.js";
import { initProduct } from "./store/product.js";
import { initCheckout } from "./store/checkout.js";

initUi();
initCheckout();
initBag();
initShop();
initProduct();
