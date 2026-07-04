<?php
/**
 * Internationalisation loader.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin text domain so every string is translatable.
 */
final class I18n implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translations from the /languages directory.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'zihad-travel-cms',
			false,
			dirname( $this->config->basename() ) . '/languages'
		);
	}
}
