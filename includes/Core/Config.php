<?php
/**
 * Plugin configuration.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use ZihadTravelCMS\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for plugin constants and options.
 *
 * Services depend on this class instead of touching ZTC_* constants or
 * get_option() directly, which keeps them testable and gives options a
 * consistent defaults + dot-notation API.
 */
final class Config {

	/**
	 * The wp_options row that stores all plugin settings.
	 */
	public const OPTION_NAME = 'ztc_settings';

	/**
	 * The wp_options row that stores the installed plugin version.
	 */
	public const VERSION_OPTION = 'ztc_version';

	/**
	 * Cached, defaults-merged settings for this request.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $settings = null;

	/**
	 * Plugin version.
	 */
	public function version(): string {
		return ZTC_VERSION;
	}

	/**
	 * Minimum supported PHP version.
	 */
	public function min_php(): string {
		return ZTC_MIN_PHP;
	}

	/**
	 * Minimum supported WordPress version.
	 */
	public function min_wp(): string {
		return ZTC_MIN_WP;
	}

	/**
	 * Absolute path to the main plugin file.
	 */
	public function file(): string {
		return ZTC_PLUGIN_FILE;
	}

	/**
	 * Plugin basename, e.g. `zihad-travel-cms/zihad-travel-cms.php`.
	 */
	public function basename(): string {
		return ZTC_PLUGIN_BASENAME;
	}

	/**
	 * Absolute filesystem path inside the plugin directory.
	 *
	 * @param string $relative Optional path relative to the plugin root.
	 */
	public function path( string $relative = '' ): string {
		return ZTC_PLUGIN_DIR . ltrim( $relative, '/' );
	}

	/**
	 * URL inside the plugin directory.
	 *
	 * @param string $relative Optional path relative to the plugin root.
	 */
	public function url( string $relative = '' ): string {
		return ZTC_PLUGIN_URL . ltrim( $relative, '/' );
	}

	/**
	 * Default settings, used to backfill anything the user has not saved.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		$defaults = array(
			'general'      => array(
				'currency'          => 'USD',
				'currency_position' => 'before',
				'date_format'       => 'Y-m-d',
				'language'          => '',
				'default_country'   => 0,
			),
			'homepage'     => array(
				'hero_title'              => '',
				'hero_subtitle'           => '',
				'show_search'             => true,
				'featured_countries_count' => 8,
				'popular_tours_count'     => 6,
			),
			'company'      => array(
				'name'            => '',
				'whatsapp'        => '',
				'phone'           => '',
				'hotline'         => '',
				'email'           => '',
				'address'         => '',
				'brand_color'     => '#0d6efd',
				'secondary_color' => '#198754',
				'logo'            => 0,
			),
			'social'       => array(
				'facebook'  => '',
				'instagram' => '',
				'twitter'   => '',
				'youtube'   => '',
				'linkedin'  => '',
				'tiktok'    => '',
			),
			'whatsapp'     => array(
				'default_message' => '',
				'floating_button' => false,
			),
			'integrations' => array(
				'maps_api_key'      => '',
				'maps_default_zoom' => 10,
				'ga_id'             => '',
				'fb_pixel_id'       => '',
			),
			'booking'      => array(
				'notification_email'  => '',
				'enable_visa_inquiry' => true,
				'enable_tour_inquiry' => true,
				'success_message'     => '',
			),
			'performance'  => array(
				'cache_ttl'      => 300,
				'lazy_load'      => true,
				'load_bootstrap' => true,
			),
			'custom_code'  => array(
				'css' => '',
				'js'  => '',
			),
			'display'      => array(
				'tours_per_page' => 12,
				'enable_reviews' => true,
			),
			'advanced'     => array(
				'delete_data_on_uninstall' => false,
			),
		);

		/**
		 * Filter the default plugin settings.
		 *
		 * @param array<string, mixed> $defaults Default settings.
		 */
		return (array) apply_filters( 'ztc_default_settings', $defaults );
	}

	/**
	 * All settings, merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null === $this->settings ) {
			$saved          = (array) get_option( self::OPTION_NAME, array() );
			$this->settings = array_replace_recursive( $this->defaults(), $saved );
		}

		return $this->settings;
	}

	/**
	 * Get a setting using dot notation, e.g. `general.currency`.
	 *
	 * @param string $key           Dot-notation key.
	 * @param mixed  $default_value Returned when the key is missing.
	 */
	public function get( string $key, mixed $default_value = null ): mixed {
		return Arr::get( $this->all(), $key, $default_value );
	}

	/**
	 * Persist a setting using dot notation.
	 *
	 * @param string $key   Dot-notation key.
	 * @param mixed  $value Value to store.
	 */
	public function set( string $key, mixed $value ): bool {
		$settings = $this->all();
		Arr::set( $settings, $key, $value );
		$this->settings = $settings;

		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Drop the request-level settings cache (e.g. after an import).
	 */
	public function refresh(): void {
		$this->settings = null;
	}
}
