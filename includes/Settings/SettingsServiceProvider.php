<?php
/**
 * Settings service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Settings;

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the settings schema: registration with the WordPress Settings
 * API and sanitization of everything saved into the plugin option.
 *
 * The settings *screen* lives in Admin\Pages; this provider owns the
 * data layer so REST and WP-CLI writes go through the same
 * sanitization as the admin form.
 */
final class SettingsServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the plugin option with the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'ztc_settings_group',
			Config::OPTION_NAME,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize the settings array before it is saved.
	 *
	 * Field-level sanitization is added alongside the settings screen;
	 * until then, unknown top-level sections are stripped.
	 *
	 * @param mixed $value Raw submitted value.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $value ): array {
		$config = $this->container->get( Config::class );
		$value  = is_array( $value ) ? $value : array();

		// Only keep sections that exist in the defaults schema.
		return array_intersect_key( $value, $config->defaults() );
	}
}
