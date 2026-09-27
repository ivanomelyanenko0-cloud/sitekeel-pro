<?php
/**
 * "Pro" tab on the Sitekeel page.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array $tabs Tabs.
 * @return array
 */
function skeelp_add_tab( $tabs ) {
	$tabs['pro'] = __( 'History & alerts', 'sitekeel-pro' );
	return $tabs;
}
add_filter( 'skeel_admin_tabs', 'skeelp_add_tab' );

/**
 * Saves the form.
 */
function skeelp_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'sitekeel-pro' ) );
	}
	check_admin_referer( 'skeelp_save' );

	skeelp_update_settings(
		array(
			'retention_days' => isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : 365,
			'alerts'         => ! empty( $_POST['alerts'] ),
			'alert_email'    => isset( $_POST['alert_email'] ) ? sanitize_email( wp_unslash( $_POST['alert_email'] ) ) : '',
			'alert_events'   => isset( $_POST['alert_events'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['alert_events'] ) ) : array(),
		)
	);

	if ( ! empty( $_POST['send_test'] ) ) {
		$sent = wp_mail(
			skeelp_get_settings()['alert_email'],
			__( 'Sitekeel test alert', 'sitekeel-pro' ),
			__( 'Alerts from Sitekeel reach this address.', 'sitekeel-pro' )
		);
		$notice = $sent ? 'test_sent' : 'test_failed';
	} else {
		$notice = 'saved';
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'sitekeel', 'tab' => 'pro', 'skeelp' => $notice ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_skeelp_save', 'skeelp_handle_save' );

/**
 * Renders the tab.
 */
function skeelp_render_tab() {
	$settings = skeelp_get_settings();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only notice after a nonce-checked redirect.
	$notice   = isset( $_GET['skeelp'] ) ? sanitize_key( wp_unslash( $_GET['skeelp'] ) ) : '';
	$messages = array(
		'saved'       => array( 'success', __( 'Settings saved.', 'sitekeel-pro' ) ),
		'test_sent'   => array( 'success', __( 'Settings saved and a test email was sent.', 'sitekeel-pro' ) ),
		'test_failed' => array( 'error', __( 'Settings saved, but WordPress could not send the test email. Check your site\'s email setup.', 'sitekeel-pro' ) ),
	);
	if ( isset( $messages[ $notice ] ) ) {
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );
	}
	$labels = array(
		90  => __( '90 days', 'sitekeel-pro' ),
		180 => __( '6 months', 'sitekeel-pro' ),
		365 => __( '1 year', 'sitekeel-pro' ),
		730 => __( '2 years', 'sitekeel-pro' ),
		0   => __( 'Keep everything', 'sitekeel-pro' ),
	);
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="skeelp_save" />
		<?php wp_nonce_field( 'skeelp_save' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="skeelp-retention"><?php esc_html_e( 'Keep changes for', 'sitekeel-pro' ); ?></label></th>
				<td>
					<select id="skeelp-retention" name="retention_days">
						<?php foreach ( skeelp_retention_choices() as $days ) : ?>
							<option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( (int) $settings['retention_days'], $days ); ?>><?php echo esc_html( $labels[ $days ] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Changes older than this are removed once a day. The free plugin keeps 90 days.', 'sitekeel-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email alerts', 'sitekeel-pro' ); ?></th>
				<td>
					<label><input type="checkbox" name="alerts" value="1" <?php checked( $settings['alerts'] ); ?> /> <?php esc_html_e( 'Email me when an important change happens (at most one email every 10 minutes)', 'sitekeel-pro' ); ?></label>
					<p><label><?php esc_html_e( 'Send to', 'sitekeel-pro' ); ?> <input type="email" name="alert_email" value="<?php echo esc_attr( $settings['alert_email'] ); ?>" class="regular-text" /></label></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Alert me about', 'sitekeel-pro' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( skeel_important_events() as $key => $label ) : ?>
							<label><input type="checkbox" name="alert_events[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['alert_events'], true ) ); ?> /> <?php echo esc_html( $label ); ?></label><br />
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<p>
			<?php submit_button( __( 'Save', 'sitekeel-pro' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Save and send a test email', 'sitekeel-pro' ), 'secondary', 'send_test', false ); ?>
		</p>
	</form>
	<?php
}
add_action( 'skeel_render_tab_pro', 'skeelp_render_tab' );
