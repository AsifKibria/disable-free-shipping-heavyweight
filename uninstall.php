<?php
// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'dfsh_enabled' );
delete_option( 'dfsh_weight_limit' );
delete_option( 'dfsh_action' );
delete_option( 'dfsh_shipping_methods' );
delete_option( 'dfsh_zone_thresholds' );
delete_option( 'dfsh_class_thresholds' );
delete_option( 'dfsh_subtotal_limit' );
delete_option( 'dfsh_item_count_limit' );
delete_option( 'dfsh_dimension_limit' );
delete_option( 'dfsh_frontend_message' );
