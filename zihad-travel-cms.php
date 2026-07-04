<?php
/**
 * Plugin Name:       Zihad Travel CMS
 * Plugin URI:        https://zihadtravelcms.com
 * Description:       A complete travel content management system — tours, visas, countries, bookings and more. A modern, professional replacement for WP Travel Engine.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            Zihad
 * Author URI:        https://zihadtravelcms.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zihad-travel-cms
 * Domain Path:       /languages
 * Update URI:        https://zihadtravelcms.com/updates
 * Elementor tested up to: 3.25
 *
 * @package ZihadTravelCMS
 */

// NOTE: This bootstrap file intentionally uses conservative PHP syntax so the
// requirements check below can run (and fail gracefully) on outdated PHP
// versions instead of triggering a parse error.

defined( 'ABSPATH' ) || exit;

define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', __FILE__ );
define( 'ZTC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZTC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZTC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check that the environment meets the plugin requirements.
 *
 * @return bool True when PHP and WordPress versions are sufficient.
 */
function ztc_requirements_met() {
	if ( version_compare( PHP_VERSION, ZTC_MIN_PHP, '<' ) ) {
		return false;
	}

	if ( version_compare( get_bloginfo( 'version' ), ZTC_MIN_WP, '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Render an admin notice explaining why the plugin did not load.
 *
 * @return void
 */
function ztc_requirements_notice() {
	$message = sprintf(
		/* translators: 1: required PHP version, 2: required WordPress version, 3: current PHP version. */
		esc_html__( 'Zihad Travel CMS requires PHP %1$s+ and WordPress %2$s+. Your server is running PHP %3$s. The plugin has not been loaded.', 'zihad-travel-cms' ),
		ZTC_MIN_PHP,
		ZTC_MIN_WP,
		PHP_VERSION
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

if ( ! ztc_requirements_met() ) {
	add_action( 'admin_notices', 'ztc_requirements_notice' );
	return;
}

// Composer autoloader (preferred), with a PSR-4 fallback for builds
// shipped without the vendor directory.
if ( file_exists( ZTC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once ZTC_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	require_once ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
	ZihadTravelCMS\Autoloader::register();
}

require_once ZTC_PLUGIN_DIR . 'includes/functions.php';

register_activation_hook( __FILE__, array( 'ZihadTravelCMS\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ZihadTravelCMS\Plugin', 'deactivate' ) );

/**
 * Retrieve the main plugin instance.
 *
 * @return ZihadTravelCMS\Plugin
 */
function ztc() {
	return ZihadTravelCMS\Plugin::instance();
}

add_action( 'plugins_loaded', array( ztc(), 'boot' ) );
