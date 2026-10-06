<?php
/**
 * Plugin Name:       Sitekeel Pro
 * Plugin URI:        https://cognitolab.net/products/sitekeel
 * Description:       Longer change history, CSV export and email alerts for important changes, for Sitekeel.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            CognitoLab
 * Author URI:        https://cognitolab.net
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sitekeel-pro
 * Domain Path:       /languages
 * Requires Plugins:  sitekeel
 *
 * Internal identifiers use the SKEELP_/skeelp_ prefix.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKEELP_VERSION', '1.0.0' );
define( 'SKEELP_PLUGIN_FILE', __FILE__ );
define( 'SKEELP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Freemius product credentials. Create the product in the Freemius dashboard,
 * then paste its id and public key here; until both are set the SDK is not
 * started and the plugin runs unlicensed (development builds only).
 */
define( 'SKEELP_FS_ID', '' );
define( 'SKEELP_FS_PUBLIC_KEY', '' );

// --- START FREEMIUS INTEGRATION ---
if ( ! function_exists( 'skeelp_fs' ) ) {
	/**
	 * @return Freemius|null Null while the product credentials are not configured.
	 */
	function skeelp_fs() {
		global $skeelp_fs;

		if ( '' === SKEELP_FS_ID || '' === SKEELP_FS_PUBLIC_KEY ) {
			return null;
		}

		if ( ! isset( $skeelp_fs ) ) {
			require_once __DIR__ . '/vendor/freemius/start.php';

			$skeelp_fs = fs_dynamic_init(
				array(
					'id'                  => SKEELP_FS_ID,
					'slug'                => 'sitekeel',
					'premium_slug'        => 'sitekeel-pro',
					'type'                => 'plugin',
					'public_key'          => SKEELP_FS_PUBLIC_KEY,
					'is_premium'          => true,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					'menu'                => array(
						'slug'    => 'sitekeel',
						'contact' => false,
						'support' => false,
					),
				)
			);
		}

		return $skeelp_fs;
	}

	skeelp_fs();
	do_action( 'skeelp_fs_loaded' );
}
// --- END FREEMIUS INTEGRATION ---

/**
 * Pro features run only with a valid license or an active trial. Without
 * one, the free Sitekeel plugin keeps working and Pro's saved data stays in
 * place for when the license is active again. Development builds with the
 * Freemius credentials blanked out run unlicensed.
 *
 * @return bool
 */
function skeelp_is_licensed() {
	$fs = skeelp_fs();
	return null === $fs || $fs->can_use_premium_code();
}

/**
 * Pro is distributed through Freemius, not WordPress.org, so core's automatic
 * translation loading for wp.org-hosted plugins does not apply and the text
 * domain has to be loaded by hand.
 */
function skeelp_load_textdomain() {
	load_plugin_textdomain( 'sitekeel-pro', false, dirname( plugin_basename( SKEELP_PLUGIN_FILE ) ) . '/languages' ); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- not hosted on wordpress.org.
}
add_action( 'init', 'skeelp_load_textdomain' );

/**
 * Loads after the free plugin, whose functions everything here builds on.
 */
function skeelp_boot() {
	if ( ! defined( 'SKEEL_VERSION' ) || ! skeelp_is_licensed() ) {
		return;
	}
	require_once SKEELP_PLUGIN_DIR . 'includes/settings.php';
	require_once SKEELP_PLUGIN_DIR . 'includes/export.php';
	require_once SKEELP_PLUGIN_DIR . 'includes/alerts.php';
	require_once SKEELP_PLUGIN_DIR . 'includes/settings-tab.php';
}
add_action( 'plugins_loaded', 'skeelp_boot', 20 );
