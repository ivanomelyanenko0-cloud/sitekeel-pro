<?php
/**
 * Email alerts for important changes. Changes are queued as they happen and
 * sent together at most once every ten minutes, so a bulk plugin update
 * produces one email, not twenty.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKEELP_ALERT_QUEUE', 'skeelp_alert_queue' );
define( 'SKEELP_ALERT_HOOK', 'skeelp_send_alerts' );
define( 'SKEELP_ALERT_DELAY', 10 * MINUTE_IN_SECONDS );

/**
 * Rows to queue in this request.
 *
 * @param array|null $row Row to add, or null to read and clear.
 * @return array[]
 */
function skeelp_alert_buffer( $row = null ) {
	static $rows = array();
	if ( null === $row ) {
		$out  = $rows;
		$rows = array();
		return $out;
	}
	$rows[] = $row;
	return array();
}

/**
 * Picks important changes as the journal records them.
 *
 * @param array|false $row Journal row about to be written.
 * @return array|false Unchanged.
 */
function skeelp_watch_journal( $row ) {
	if ( ! is_array( $row ) ) {
		return $row;
	}
	$settings = skeelp_get_settings();
	if ( $settings['alerts'] && in_array( $row['source'] . '/' . $row['event'], $settings['alert_events'], true ) ) {
		skeelp_alert_buffer( $row );
		if ( ! has_action( 'shutdown', 'skeelp_queue_alerts' ) ) {
			add_action( 'shutdown', 'skeelp_queue_alerts', 5 );
		}
	}
	return $row;
}
add_filter( 'cljournal_record_event', 'skeelp_watch_journal', 20 );

/**
 * Stores this request's important changes and schedules the next email.
 */
function skeelp_queue_alerts() {
	$rows = skeelp_alert_buffer();
	if ( ! $rows ) {
		return;
	}
	$queue = get_option( SKEELP_ALERT_QUEUE, array() );
	$queue = array_slice( array_merge( is_array( $queue ) ? $queue : array(), $rows ), -200 );
	update_option( SKEELP_ALERT_QUEUE, $queue, false );

	if ( ! wp_next_scheduled( SKEELP_ALERT_HOOK ) ) {
		wp_schedule_single_event( time() + SKEELP_ALERT_DELAY, SKEELP_ALERT_HOOK );
	}
}

/**
 * Sends one email with everything queued.
 *
 * @return bool Whether an email was sent.
 */
function skeelp_send_alerts() {
	$queue = get_option( SKEELP_ALERT_QUEUE, array() );
	delete_option( SKEELP_ALERT_QUEUE );
	if ( empty( $queue ) || ! is_array( $queue ) ) {
		return false;
	}

	$settings = skeelp_get_settings();
	$site     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$lines    = array();
	foreach ( $queue as $row ) {
		$row['data'] = isset( $row['data'] ) && is_array( $row['data'] ) ? $row['data'] : array();
		$lines[]     = '- ' . get_date_from_gmt( $row['occurred_at'], get_option( 'time_format' ) ) . ': ' . skeel_describe_event( $row );
	}

	/* translators: 1: number of changes, 2: site name. */
	$subject = sprintf( _n( '[%2$s] %1$d important change on the site', '[%2$s] %1$d important changes on the site', count( $lines ), 'sitekeel-pro' ), count( $lines ), $site );
	$body    = implode( "\n", $lines ) . "\n\n"
		/* translators: %s: link to the timeline. */
		. sprintf( __( 'Full timeline: %s', 'sitekeel-pro' ), admin_url( 'admin.php?page=sitekeel' ) ) . "\n"
		/* translators: %s: link to the alert settings. */
		. sprintf( __( 'Change which alerts you get: %s', 'sitekeel-pro' ), admin_url( 'admin.php?page=sitekeel&tab=pro' ) ) . "\n";

	return (bool) wp_mail( $settings['alert_email'], $subject, $body );
}
add_action( SKEELP_ALERT_HOOK, 'skeelp_send_alerts' );
