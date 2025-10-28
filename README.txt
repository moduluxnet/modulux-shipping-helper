=== Modulux Shipping Helper for WooCommerce ===
Contributors: modulux  
Tags: woocommerce, shipping, weight unit, flat rate, shipping calculator  
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Enhances WooCommerce Flat Rate shipping by allowing per-product custom weight units, rule-based pricing, VAT, and smart calculation logic.

== Description ==

**Modulux Shipping Helper for WooCommerce** is a lightweight and modular WooCommerce plugin that allows merchants to define custom weight units and flexible shipping rules for each of your products.

Use it to handle shipping calculations when your products vary in volume, type, or measurement (e.g. Kg, Litre, Desi, Lbs). Choose between total or heaviest weight-based logic per unit and automatically apply fallback pricing, VAT, and free shipping thresholds.

A built-in "Suggest Weight" tool can recommend weight values using SKU/category/tag similarity, making it even easier to manage large product catalogs.

=== Key Features ===

* Define custom weight units (Kg, Deci, Liter, Pound, etc.)
* Configure flexible shipping rules for each unit
* Choose between **Total** or **Heaviest Item** calculation modes
* Add fallback pricing when no rules match
* Built-in VAT and free shipping threshold management
* Works only with WooCommerce's Flat Rate shipping system
* AJAX-powered "Suggest Weight" button to automatically fill in missing weights

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`
2. Activate through the **Plugins** menu in WordPress
3. Go to **WooCommerce → Shipping Helper** to define units, rules, and VAT settings

== Screenshots ==

1. Weight unit management
2. Per-unit shipping rules and VAT
3. "What is this?" Information tab
4. Product Edit Screen with Weight Suggestion
5. Cart preview showing calculated custom shipping rate

== Frequently Asked Questions ==

= Does it work with Local Pickup or Free Shipping? =
No — the plugin only affects WooCommerce Flat Rate shipping methods.

= Can I define my own units? =
Yes! You can freely create and manage units in the admin panel.

= What if I don’t configure rules for a unit? =
The plugin reverts to a default calculation using weight × fallback rate.

= Can I suggest weights for existing products? =
Yes. Use the Suggest Weight button — it checks for similar SKUs, categories (including parent categories), and tags.

== Shipping Logic ==

- Products are grouped by unit (kg, liter, etc.)
- Weight is calculated per unit:
  - **Total** mode: sum of all product weights
  - **Heaviest** mode: highest weight in the group
- Rules are matched per unit (`maximum → price`)
- If no rule matches, a fallback is used
- The final cost is the sum of all matching units
- VAT is applied (if set)
- Free shipping is applied if the cart total exceeds the threshold
- Fallback is specified in the order management panel

== Changelog ==

= 1.0.0 =
* Initial release
* Custom unit-based rules with Total/Heaviest modes
* VAT and free shipping threshold
* Smart weight suggestion button

== Upgrade Notice ==

= 1.0.0 =
First stable release with full feature set.

== Credits ==

Crafted by [Modulux](https://modulux.net) — WordPress plugin and theme creators.
