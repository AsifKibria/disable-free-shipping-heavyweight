=== Disable Free Shipping for Heavyweight Orders ===
Contributors: asifkibria
Tags: woocommerce, shipping, weight, cart rules, admin notification
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce plugin that disables or hides selected shipping methods when the cart meets certain conditions (weight, subtotal, etc.).

== Description ==
This plugin allows WooCommerce shop owners to restrict any shipping method (Free Shipping, Flat Rate, Local Pickup, etc.) when the cart meets certain conditions. It includes an admin settings panel under WooCommerce where you can customize:

* Whether the plugin is enabled
* Which shipping methods to restrict
* The cart weight limit
* Different thresholds per shipping zone or class (JSON format)
* Cart subtotal, item count, and dimension limits
* Customizable frontend message for customers
* Product-level override to exclude specific products from restrictions
* Admin notification when a restricted shipping method is attempted

Useful for shop owners who want to avoid offering free or discounted shipping for bulky, heavy, or high-value orders.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/disable-free-shipping-heavyweight` directory, or install the plugin via the WordPress plugin repository.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce > Disable Free Shipping** to configure the plugin.

== Frequently Asked Questions ==
= Can I change the weight limit or other thresholds? =
Yes. You can update all thresholds and conditions via the settings page in the WordPress admin area. Developers can also override the weight limit using a filter:

```php
add_filter( 'dfsh_weight_limit', function( $limit ) {
    return 25; // kg
});
```

= Does it delete all data on uninstall? =
Yes. When you delete the plugin, all settings are removed automatically.

= Can I exclude certain products from restrictions? =
Yes. Edit the product and check the box labeled "Exclude this product from shipping restrictions."

== Screenshots ==
1. Admin settings panel
2. Cart with shipping method disabled due to restriction
3. Product edit screen with override option

== Changelog ==
= 1.2.0 =
* Major update: Multiple shipping methods, zone/class/subtotal/item count/dimension thresholds, admin notification, frontend messaging, product-level override
= 1.1.0 =
* Added admin settings panel
* Added uninstall cleanup
* Option to hide or label free shipping
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.2.0 =
Major update: Multiple shipping methods, zone/class/subtotal/item count/dimension thresholds, admin notification, frontend messaging, product-level override.