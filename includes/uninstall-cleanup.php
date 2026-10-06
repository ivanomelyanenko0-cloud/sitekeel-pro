<?php
/**
 * Data removed when the plugin is uninstalled. Called from Freemius'
 * after_uninstall hook (see the main plugin file), not run as uninstall.php. The change timeline belongs to the free plugin and
 * stays; only Pro's settings and pending alerts are removed.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skeelp_uninstall_site() {
	delete_option( 'skeelp_settings' );
	delete_option( 'skeelp_alert_queue' );
	wp_clear_scheduled_hook( 'skeelp_send_alerts' );
}

/**
 * Runs the per-site cleanup on every site of a network, or on the one site.
 */
function skeelp_uninstall_all_sites() {
	if ( is_multisite() ) {
		foreach ( get_sites( array( 'fields' => 'ids' ) ) as $skeelp_site_id ) {
			switch_to_blog( $skeelp_site_id );
			skeelp_uninstall_site();
			restore_current_blog();
		}
	} else {
		skeelp_uninstall_site();
	}
}
