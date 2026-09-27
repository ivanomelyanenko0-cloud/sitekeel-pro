<?php
/**
 * Pro settings: how long changes are kept, and email alerts.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKEELP_SETTINGS_OPTION', 'skeelp_settings' );

/**
 * @return array{retention_days: int, alerts: bool, alert_email: string, alert_events: string[]}
 */
function skeelp_get_settings() {
	$defaults = array(
		'retention_days' => 365,
		'alerts'         => false,
		'alert_email'    => (string) get_option( 'admin_email' ),
		'alert_events'   => array_keys( skeel_important_events() ),
	);
	$stored = get_option( SKEELP_SETTINGS_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	return array_merge( $defaults, array_intersect_key( $stored, $defaults ) );
}

/**
 * @return int[] Retention choices in days; 0 means keep everything.
 */
function skeelp_retention_choices() {
	return array( 90, 180, 365, 730, 0 );
}

/**
 * @param array $input Raw form data.
 */
function skeelp_update_settings( array $input ) {
	$retention = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : 365;
	$email     = isset( $input['alert_email'] ) ? sanitize_email( $input['alert_email'] ) : '';
	$known     = array_keys( skeel_important_events() );
	$events    = isset( $input['alert_events'] ) && is_array( $input['alert_events'] ) ? array_values( array_intersect( array_map( 'sanitize_text_field', $input['alert_events'] ), $known ) ) : array();

	update_option(
		SKEELP_SETTINGS_OPTION,
		array(
			'retention_days' => in_array( $retention, skeelp_retention_choices(), true ) ? $retention : 365,
			'alerts'         => ! empty( $input['alerts'] ),
			'alert_email'    => is_email( $email ) ? $email : (string) get_option( 'admin_email' ),
			'alert_events'   => $events,
		),
		false
	);
}

/**
 * Longer history: the shared journal reads its retention from this filter.
 *
 * @return int Days.
 */
function skeelp_retention_days() {
	$days = (int) skeelp_get_settings()['retention_days'];
	return 0 === $days ? 36500 : $days;
}
add_filter( 'cljournal_retention_days', 'skeelp_retention_days' );
