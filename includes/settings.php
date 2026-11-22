<?php
/**
 * Pro settings: email alerts. How long changes are kept is a setting of
 * the free plugin.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKEELP_SETTINGS_OPTION', 'skeelp_settings' );

/**
 * @return array{alerts: bool, alert_email: string, alert_events: string[]}
 */
function skeelp_get_settings() {
	$defaults = array(
		'alerts'       => false,
		'alert_email'  => (string) get_option( 'admin_email' ),
		'alert_events' => array_keys( skeel_important_events() ),
	);
	$stored = get_option( SKEELP_SETTINGS_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	return array_merge( $defaults, array_intersect_key( $stored, $defaults ) );
}

/**
 * @param array $input Raw form data.
 */
function skeelp_update_settings( array $input ) {
	$email  = isset( $input['alert_email'] ) ? sanitize_email( $input['alert_email'] ) : '';
	$known  = array_keys( skeel_important_events() );
	$events = isset( $input['alert_events'] ) && is_array( $input['alert_events'] ) ? array_values( array_intersect( array_map( 'sanitize_text_field', $input['alert_events'] ), $known ) ) : array();

	update_option(
		SKEELP_SETTINGS_OPTION,
		array(
			'alerts'       => ! empty( $input['alerts'] ),
			'alert_email'  => is_email( $email ) ? $email : (string) get_option( 'admin_email' ),
			'alert_events' => $events,
		),
		false
	);
}
