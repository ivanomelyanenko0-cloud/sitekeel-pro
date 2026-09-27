<?php
/**
 * CSV export of the timeline, with the filters currently applied.
 *
 * @package SitekeelPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export button under the timeline filters.
 *
 * @param array $filters Current filters.
 */
function skeelp_export_button( $filters ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="skeelp-export">
		<input type="hidden" name="action" value="skeelp_export" />
		<input type="hidden" name="source" value="<?php echo esc_attr( $filters['source'] ); ?>" />
		<input type="hidden" name="days" value="<?php echo esc_attr( (string) $filters['days'] ); ?>" />
		<input type="hidden" name="important" value="<?php echo $filters['important'] ? '1' : ''; ?>" />
		<?php wp_nonce_field( 'skeelp_export' ); ?>
		<?php submit_button( __( 'Export these changes (CSV)', 'sitekeel-pro' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}
add_action( 'skeel_timeline_tools', 'skeelp_export_button' );

/**
 * Keeps spreadsheet apps from treating a cell as a formula.
 *
 * @param string $value Cell.
 * @return string
 */
function skeelp_csv_cell( $value ) {
	$value = (string) $value;
	return ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $value : $value;
}

/**
 * Streams the CSV.
 */
function skeelp_handle_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'sitekeel-pro' ) );
	}
	check_admin_referer( 'skeelp_export' );

	$filters = skeel_timeline_filters();
	$rows    = array();
	$offset  = 0;
	do {
		$batch  = cljournal_query( skeel_timeline_query_args( $filters, 500, $offset ) );
		$rows   = array_merge( $rows, $batch );
		$offset += 500;
	} while ( 500 === count( $batch ) && $offset < 50000 );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="sitekeel-changes-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming a download.
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 BOM so spreadsheet apps detect the encoding.
	fputcsv( $out, array( 'time', 'source', 'event', 'description', 'object_type', 'object_id', 'user', 'important' ) );
	foreach ( $rows as $row ) {
		$user = (int) $row['actor_id'] ? get_userdata( (int) $row['actor_id'] ) : false;
		fputcsv(
			$out,
			array_map(
				'skeelp_csv_cell',
				array(
					get_date_from_gmt( $row['occurred_at'], 'Y-m-d H:i:s' ),
					$row['source'],
					$row['event'],
					skeel_describe_event( $row ),
					$row['object_type'],
					(int) $row['object_id'],
					$user ? $user->user_login : '',
					skeel_is_important( $row ) ? 'yes' : 'no',
				)
			)
		);
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming a download.
	exit;
}
add_action( 'admin_post_skeelp_export', 'skeelp_handle_export' );
