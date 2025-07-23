# Disable Free Shipping for Heavyweight Orders

A powerful WooCommerce plugin that disables or hides selected shipping methods (not just Free Shipping) when the cart meets certain conditions, such as weight, subtotal, item count, or dimensions.

## Features

- Restrict any shipping method (Free Shipping, Flat Rate, Local Pickup, etc.)
- Set custom weight thresholds (default: 20kg)
- Set different thresholds per shipping zone or shipping class
- Restrict based on cart subtotal, item count, or product dimensions
- Product-level override: exclude specific products from restrictions
- Customizable frontend message for customers
- Admin notification when a restricted shipping method is attempted
- Admin settings panel under WooCommerce
- Clean uninstall (removes all plugin options)
- Lightweight and open-source

## Installation

1. Download the plugin ZIP or clone this repo:
   ```bash
   git clone https://github.com/asifkibria/disable-free-shipping-heavyweight.git
   ```
2. Upload it to `/wp-content/plugins/`
3. Activate the plugin from the WordPress dashboard.
4. Go to **WooCommerce → Disable Free Shipping** to configure.

## Settings

You’ll find the following options in the admin panel:

- **Enable plugin**: Turn on/off the plugin behavior.
- **Shipping methods to restrict**: Select which shipping methods to restrict.
- **Weight limit**: Set the weight limit in kilograms.
- **Zone/Class thresholds**: Set different weight limits for shipping zones or classes (JSON format).
- **Cart subtotal limit**: Restrict based on cart subtotal.
- **Item count limit**: Restrict based on number of items in cart.
- **Dimension limit**: Restrict based on product dimensions (LxWxH).
- **Frontend message**: Custom message shown to customers when a method is restricted.
- **Product-level override**: Exclude specific products from restrictions via a checkbox in the product edit screen.

## Uninstall

When the plugin is deleted from the dashboard, all settings will be automatically removed.

## Filters

Developers can override the weight limit using a filter:

```php
add_filter( 'dfsh_weight_limit', function( $limit ) {
    return 25; // new limit in kg
});
```

## Author

Made with ❤️ by [Asif Kibria](https://asifkibria.com)

## License

GPLv2
