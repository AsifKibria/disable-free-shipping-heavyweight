<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DFSH_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        // Add product-level override meta box
        add_action( 'add_meta_boxes', [ $this, 'add_product_override_meta_box' ] );
        add_action( 'save_post_product', [ $this, 'save_product_override_meta' ] );
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            'Disable Free Shipping Settings',
            'Disable Free Shipping',
            'manage_woocommerce',
            'dfsh-settings',
            [ $this, 'settings_page_html' ]
        );
    }

    public function register_settings() {
        register_setting( 'dfsh_settings_group', 'dfsh_enabled', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_weight_limit', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_action', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_shipping_methods', array( 'sanitize_callback' => array( $this, 'sanitize_array_field' ) ) );
        register_setting( 'dfsh_settings_group', 'dfsh_zone_thresholds', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_class_thresholds', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_subtotal_limit', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_item_count_limit', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_dimension_limit', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_frontend_message', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'dfsh_settings_group', 'dfsh_admin_notification', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        // Product-level override will be handled in product edit screen

        add_settings_section( 'dfsh_main_section', '', null, 'dfsh-settings' );

        add_settings_field(
            'dfsh_enabled',
            'Enable Plugin',
            function() {
                $val = get_option( 'dfsh_enabled', 'yes' );
                echo '<select name="dfsh_enabled">'
                    . '<option value="yes" ' . esc_attr( selected( $val, 'yes', false ) ) . '>Yes</option>'
                    . '<option value="no" ' . esc_attr( selected( $val, 'no', false ) ) . '>No</option>'
                    . '</select>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_weight_limit',
            'Weight Limit (kg)',
            function() {
                $val = get_option( 'dfsh_weight_limit', 20 );
                echo '<input type="number" name="dfsh_weight_limit" value="' . esc_attr( $val ) . '" min="0" step="0.1" />';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_action',
            'Action on Free Shipping',
            function() {
                $val = get_option( 'dfsh_action', 'hide' );
                echo '<select name="dfsh_action">'
                    . '<option value="hide" ' . esc_attr( selected( $val, 'hide', false ) ) . '>Hide</option>'
                    . '<option value="disable" ' . esc_attr( selected( $val, 'disable', false ) ) . '>Disable/Label Only</option>'
                    . '</select>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_shipping_methods',
            'Shipping Methods to Restrict',
            function() {
                $val = get_option( 'dfsh_shipping_methods', [ 'free_shipping' ] );
                $methods = [
                    'free_shipping' => 'Free Shipping',
                    'flat_rate' => 'Flat Rate',
                    'local_pickup' => 'Local Pickup',
                ];
                foreach ($methods as $key => $label) {
                    $checked = is_array($val) && in_array($key, $val) ? 'checked' : '';
                    echo '<label><input type="checkbox" name="dfsh_shipping_methods[]" value="' . esc_attr($key) . '" ' . esc_attr($checked) . '> ' . esc_html($label) . '</label><br />';
                }
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_zone_thresholds',
            'Zone Thresholds (JSON)',
            function() {
                $val = get_option( 'dfsh_zone_thresholds', '' );
                echo '<textarea name="dfsh_zone_thresholds" rows="3" cols="50">' . esc_textarea($val) . '</textarea><br /><small>Format: {"zone_id": weight_limit, ...}</small>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_class_thresholds',
            'Shipping Class Thresholds (JSON)',
            function() {
                $val = get_option( 'dfsh_class_thresholds', '' );
                echo '<textarea name="dfsh_class_thresholds" rows="3" cols="50">' . esc_textarea($val) . '</textarea><br /><small>Format: {"class_id": weight_limit, ...}</small>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_subtotal_limit',
            'Cart Subtotal Limit',
            function() {
                $val = get_option( 'dfsh_subtotal_limit', '' );
                echo '<input type="number" name="dfsh_subtotal_limit" value="' . esc_attr($val) . '" min="0" step="0.01" />';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_item_count_limit',
            'Item Count Limit',
            function() {
                $val = get_option( 'dfsh_item_count_limit', '' );
                echo '<input type="number" name="dfsh_item_count_limit" value="' . esc_attr($val) . '" min="0" step="1" />';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_dimension_limit',
            'Dimension Limit (LxWxH, cm)',
            function() {
                $val = get_option( 'dfsh_dimension_limit', '' );
                echo '<input type="text" name="dfsh_dimension_limit" value="' . esc_attr($val) . '" placeholder="e.g. 100x50x30" />';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_frontend_message',
            'Frontend Restriction Message',
            function() {
                $val = get_option( 'dfsh_frontend_message', '' );
                echo '<textarea name="dfsh_frontend_message" rows="2" cols="50">' . esc_textarea($val) . '</textarea>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );

        add_settings_field(
            'dfsh_admin_notification',
            'Enable Admin Email Notification',
            function() {
                $val = get_option( 'dfsh_admin_notification', 'yes' );
                echo '<select name="dfsh_admin_notification">'
                    . '<option value="yes" ' . esc_attr( selected( $val, 'yes', false ) ) . '>Yes</option>'
                    . '<option value="no" ' . esc_attr( selected( $val, 'no', false ) ) . '>No</option>'
                    . '</select>';
            },
            'dfsh-settings',
            'dfsh_main_section'
        );
    }

    public function settings_page_html() {
        echo '<div class="wrap">
            <h1>Disable Free Shipping Settings</h1>
            <form method="post" action="options.php">';
        settings_fields( 'dfsh_settings_group' );
        do_settings_sections( 'dfsh-settings' );
        submit_button();
        echo '</form></div>';
    }

    // Add meta box to product edit screen
    public function add_product_override_meta_box() {
        add_meta_box(
            'dfsh_product_override',
            'Disable Shipping Restriction Override',
            [ $this, 'product_override_meta_box_html' ],
            'product',
            'side',
            'default'
        );
    }

    public function product_override_meta_box_html( $post ) {
        $value = get_post_meta( $post->ID, '_dfsh_override_shipping_restriction', true );
        wp_nonce_field( 'dfsh_save_product_override', 'dfsh_product_override_nonce' );
        echo '<label><input type="checkbox" name="dfsh_override_shipping_restriction" value="yes"' . esc_attr( checked( $value, 'yes', false ) ) . '> ' . esc_html__('Exclude this product from shipping restrictions', 'disable-free-shipping-for-heavyweight-orders') . '</label>';
    }

    public function save_product_override_meta( $post_id ) {
        if ( ! isset( $_POST['dfsh_product_override_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dfsh_product_override_nonce'] ) ), 'dfsh_save_product_override' ) ) {
            return;
        }
        if ( isset( $_POST['dfsh_override_shipping_restriction'] ) ) {
            update_post_meta( $post_id, '_dfsh_override_shipping_restriction', 'yes' );
        } else {
            delete_post_meta( $post_id, '_dfsh_override_shipping_restriction' );
        }
    }

    // Sanitization callback for array fields
    public function sanitize_array_field( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'sanitize_text_field', $value );
        }
        return array();
    }
}
