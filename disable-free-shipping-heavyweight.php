<?php
/**
 * Plugin Name: Disable Free Shipping for Heavyweight Orders
 * Plugin URI: https://github.com/asifkibria/disable-free-shipping-heavyweight
 * Description: Automatically disables or hides free shipping in WooCommerce when the cart weight exceeds a set threshold.
 * Version: 1.2.0
 * Author: Asif Kibria
 * Author URI: https://asifkibria.com
 * License: GPL2
 * Tested up to: 6.8
 * Text Domain: disable-free-shipping-for-heavyweight-orders
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';

class DFSH_Disable_Free_Shipping {

    private $is_restricted_cache = null;

    public function __construct() {
        add_filter( 'woocommerce_package_rates', [ $this, 'maybe_disable_free_shipping' ], 10, 2 );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'maybe_show_frontend_message' ] );
        add_action( 'woocommerce_before_cart', [ $this, 'maybe_show_frontend_message' ] );
        add_action( 'woocommerce_before_cart', [ $this, 'maybe_remove_free_shipping_notice' ], 1 );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'maybe_remove_free_shipping_notice' ], 1 );
        add_action( 'dfsh_send_admin_notification_async', [ $this, 'send_admin_notification' ] );
    }

    private function get_cart_cache_key() {
        if ( ! WC()->cart || WC()->cart->is_empty() ) {
            return false;
        }
        // Use cart hash which represents the state of the cart.
        return 'dfsh_restriction_' . WC()->cart->get_cart_hash();
    }

    private function is_shipping_restricted( $package = [] ) {
        if ( null !== $this->is_restricted_cache ) {
            return $this->is_restricted_cache;
        }

        $cache_key = $this->get_cart_cache_key();
        $is_persistent_cache = wp_using_ext_object_cache();

        if ( $is_persistent_cache && $cache_key ) {
            $cached_result = wp_cache_get( $cache_key, 'dfsh' );
            if ( false !== $cached_result ) {
                $this->is_restricted_cache = (bool) $cached_result;
                return $this->is_restricted_cache;
            }
        }

        $restrict = false; // Default to not restricted
        $enabled = get_option( 'dfsh_enabled', 'yes' );
        $cart = WC()->cart;

        if ( 'yes' === $enabled && $cart && ! $cart->is_empty() ) {
            // Check for product-level override first to exit early
            $has_override = false;
            foreach ( $cart->get_cart() as $cart_item ) {
                if ( get_post_meta( $cart_item['product_id'], '_dfsh_override_shipping_restriction', true ) === 'yes' ) {
                    $has_override = true;
                    break;
                }
            }

            if ( ! $has_override ) {
                // Calculation logic starts here
                $weight_limit = (float) get_option( 'dfsh_weight_limit', 20 );
                $subtotal_limit = get_option( 'dfsh_subtotal_limit', '' );
                $item_count_limit = get_option( 'dfsh_item_count_limit', '' );
                $dimension_limit = get_option( 'dfsh_dimension_limit', '' );
                $zone_thresholds = json_decode( get_option( 'dfsh_zone_thresholds', '' ), true );
                $class_thresholds = json_decode( get_option( 'dfsh_class_thresholds', '' ), true );

                $cart_weight = $cart->get_cart_contents_weight();
                $cart_subtotal = $cart->subtotal;
                $cart_item_count = $cart->get_cart_contents_count();
                $cart_dimensions = $this->get_cart_max_dimensions();

                // Get shipping zone for the package
                if ( ! empty( $package ) ) {
                    $zone = function_exists( 'wc_get_zone_for_package' ) ? wc_get_zone_for_package( $package ) : ( new WC_Shipping_Zones() )->get_zone_matching_package( $package );
                    $zone_id = $zone ? $zone->get_id() : '';

                    if ( is_array( $zone_thresholds ) && isset( $zone_thresholds[ $zone_id ] ) ) {
                        $weight_limit = (float) $zone_thresholds[ $zone_id ];
                    }
                }

                // Check for class-specific threshold
                if ( is_array( $class_thresholds ) ) {
                    foreach ( $cart->get_cart() as $cart_item ) {
                        $class_id = $cart_item['data']->get_shipping_class_id();
                        if ( $class_id && isset( $class_thresholds[ $class_id ] ) ) {
                            $weight_limit = (float) $class_thresholds[ $class_id ];
                            break;
                        }
                    }
                }

                if ( $cart_weight >= $weight_limit ) {
                    $restrict = true;
                }
                if ( ! $restrict && $subtotal_limit !== '' && $cart_subtotal >= (float) $subtotal_limit ) {
                    $restrict = true;
                }
                if ( ! $restrict && $item_count_limit !== '' && $cart_item_count >= (int) $item_count_limit ) {
                    $restrict = true;
                }
                if ( ! $restrict && $dimension_limit !== '' && $this->cart_exceeds_dimensions( $cart_dimensions, $dimension_limit ) ) {
                    $restrict = true;
                }
            }
        }
        
        $this->is_restricted_cache = $restrict;

        if ( $is_persistent_cache && $cache_key ) {
            // Cache for 12 hours, a standard session time.
            wp_cache_set( $cache_key, $restrict, 'dfsh', 12 * HOUR_IN_SECONDS );
        }
        
        return $restrict;
    }

    public function maybe_disable_free_shipping( $rates, $package ) {
        if ( $this->is_shipping_restricted( $package ) ) {
            $action = get_option( 'dfsh_action', 'hide' ); // hide or disable
            $methods = get_option( 'dfsh_shipping_methods', [ 'free_shipping' ] );

            foreach ( $rates as $rate_id => $rate ) {
                if ( in_array( $rate->method_id, $methods ) ) {
                    if ( $action === 'hide' ) {
                        unset( $rates[ $rate_id ] );
                    } elseif ( $action === 'disable' ) {
                        $rate->cost = 0;
                        $rate->label .= ' (Not available due to restriction)';
                    }
                }
            }
            $this->schedule_admin_notification();
        }

        return $rates;
    }

    public function maybe_show_frontend_message() {
        $frontend_message = get_option( 'dfsh_frontend_message', '' );
        if ( ! empty( $frontend_message ) && $this->is_shipping_restricted() ) {
            wc_print_notice( esc_html( $frontend_message ), 'notice' );
        }
    }

    public function maybe_remove_free_shipping_notice() {
        if ( $this->is_shipping_restricted() ) {
            if ( isset( WC()->session ) && method_exists( 'WC', 'get_notices' ) ) {
                $notices = WC()->session->get( 'wc_notices', [] );
                if ( ! empty( $notices ) ) {
                    foreach ( $notices as $type => $type_notices ) {
                        foreach ( $type_notices as $key => $notice ) {
                            if ( is_string($notice) && ( stripos( $notice, 'free shipping' ) !== false || stripos( $notice, 'get free shipping' ) !== false ) ) {
                                unset( $notices[ $type ][ $key ] );
                            } elseif (is_array($notice) && isset($notice['notice']) && ( stripos( $notice['notice'], 'free shipping' ) !== false || stripos( $notice['notice'], 'get free shipping' ) !== false ) ) {
                                unset( $notices[ $type ][ $key ] );
                            }
                        }
                    }
                    WC()->session->set( 'wc_notices', $notices );
                }
            }
        }
    }

    private function get_cart_max_dimensions() {
        $max_length = $max_width = $max_height = 0;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $max_length = max( $max_length, (float) $product->get_length() );
            $max_width  = max( $max_width, (float) $product->get_width() );
            $max_height = max( $max_height, (float) $product->get_height() );
        }
        return [ $max_length, $max_width, $max_height ];
    }

    private function cart_exceeds_dimensions( $cart_dimensions, $limit_str ) {
        $parts = explode( 'x', strtolower( $limit_str ) );
        if ( count($parts) !== 3 ) return false;
        list( $limit_l, $limit_w, $limit_h ) = array_map( 'floatval', $parts );
        list( $cart_l, $cart_w, $cart_h ) = $cart_dimensions;
        return $cart_l >= $limit_l || $cart_w >= $limit_w || $cart_h >= $limit_h;
    }

    // Schedule admin notification to run in the background
    private function schedule_admin_notification() {
        if ( ! wp_next_scheduled( 'dfsh_send_admin_notification_async' ) ) {
            wp_schedule_single_event( time() + 10, 'dfsh_send_admin_notification_async' );
        }
    }

    // Send the actual email
    public function send_admin_notification() {
        $notify_admin = get_option( 'dfsh_admin_notification', 'yes' );
        if ( 'no' === $notify_admin ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        $subject = 'WooCommerce Shipping Method Restricted';
        
        $customer_name = 'Guest';
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $customer_name = $user->display_name;
        }

        $cart = WC()->cart;
        $product_list = [];
        if ( $cart ) {
            foreach ( $cart->get_cart() as $cart_item ) {
                $product_list[] = $cart_item['data']->get_name() . ' (Qty: ' . $cart_item['quantity'] . ')';
            }
        }

        $message = 'A customer attempted to use a restricted shipping method due to cart conditions.' . "\r\n\r\n";
        $message .= 'Customer: ' . $customer_name . "\r\n";
        $message .= 'Products in cart: ' . "\r\n" . implode( "\r\n", $product_list );
        
        wp_mail( $admin_email, $subject, $message );
    }
}

new DFSH_Disable_Free_Shipping();
new DFSH_Admin_Settings();
