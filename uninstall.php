<?php
/**
 * Uninstall handler. The change timeline belongs to the free plugin and
 * stays; only Pro's settings and pending alerts are removed.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function skeelp_uninstall_site() {
	delete_option( 'skeelp_settings' );
	delete_option( 'skeelp_alert_queue' );
	wp_clear_scheduled_hook( 'skeelp_send_alerts' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $skeelp_site_id ) {
		switch_to_blog( $skeelp_site_id );
		skeelp_uninstall_site();
		restore_current_blog();
	}
} else {
	skeelp_uninstall_site();
}
