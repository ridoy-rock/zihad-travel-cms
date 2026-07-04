<?php
/**
 * Template renderer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Helpers;

use ZihadTravelCMS\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Renders PHP templates from /templates with theme override support.
 *
 * Themes can override any frontend template by copying it into a
 * `zihad-travel-cms/` directory inside the theme.
 *
 * Inside a template, the passed variables are available as `$data`
 * (never extract()ed into scope), plus `$template` for partials.
 */
final class Template {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Render a template.
	 *
	 * @param string               $name Template path relative to /templates,
	 *                                   e.g. `admin/dashboard.php`.
	 * @param array<string, mixed> $data Variables exposed to the template as `$data`.
	 */
	public function render( string $name, array $data = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $data is read by the included template through function scope.
		$file = $this->locate( $name );

		if ( '' === $file ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'Template "%s" not found.', $name ) ),
				'1.0.0'
			);

			return;
		}

		$template = $this;

		include $file;
	}

	/**
	 * Render a template and return its output.
	 *
	 * @param string               $name Template path relative to /templates.
	 * @param array<string, mixed> $data Variables exposed to the template as `$data`.
	 */
	public function get( string $name, array $data = array() ): string {
		ob_start();
		$this->render( $name, $data );

		return (string) ob_get_clean();
	}

	/**
	 * Locate a template, preferring a theme override.
	 *
	 * @param string $name Template path relative to /templates.
	 *
	 * @return string Absolute path, or '' when not found.
	 */
	public function locate( string $name ): string {
		$name = ltrim( $name, '/' );

		$theme_file = locate_template( 'zihad-travel-cms/' . $name );

		if ( '' !== $theme_file ) {
			return $theme_file;
		}

		$plugin_file = $this->config->path( 'templates/' . $name );

		return file_exists( $plugin_file ) ? $plugin_file : '';
	}
}
