<?php
/**
 * Uninstall handler for Block Content Protection.
 *
 * Removes plugin options and post meta when the plugin is deleted from the
 * Plugins screen. Runs only when WP_UNINSTALL_PLUGIN is defined by core.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

function bcp_uninstall_cleanup() {
    delete_option( 'bcp_options' );
    delete_option( 'bcp_db_version' );

    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_bcp_override' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_bcp_content_types' ) );
}

if ( is_multisite() ) {
    $site_ids = get_sites( [ 'fields' => 'ids' ] );
    foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );
        bcp_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    bcp_uninstall_cleanup();
}
