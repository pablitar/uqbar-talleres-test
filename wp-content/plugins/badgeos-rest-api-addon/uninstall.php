<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$badgeos_settings = get_option( 'badgeos_settings' );
$remove_data_on_uninstall = ( isset( $badgeos_settings['remove_data_on_uninstall'] ) ) ? $badgeos_settings['remove_data_on_uninstall'] : '';

/**
 * Return - if delete option is not enabled.
 */
if( "on" != $remove_data_on_uninstall ) {
    return;
}

global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name ='badgeos_embed_url';");
delete_transient('non_ob_conversion_progress');