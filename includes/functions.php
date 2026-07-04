<?php
/**
 * Template helper functions.
 *
 * The only global functions the plugin defines besides ztc(). They
 * exist for template authors — templates are pure views and should
 * not touch the container directly.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ztc_view' ) ) {
	/**
	 * The current template's view-model, prepared by the
	 * TemplateLoader controller.
	 *
	 * @return array<string, mixed>
	 */
	function ztc_view(): array {
		return (array) get_query_var( 'ztc_view' );
	}
}

if ( ! function_exists( 'ztc_part' ) ) {
	/**
	 * Render a template part from /templates/frontend/parts
	 * (theme-overridable at zihad-travel-cms/frontend/parts/…).
	 *
	 * @param string               $name Part name without extension, e.g. `hero`.
	 * @param array<string, mixed> $data Variables exposed to the part as `$data`.
	 */
	function ztc_part( string $name, array $data = array() ): void {
		ztc()->get( ZihadTravelCMS\Helpers\Template::class )->render(
			'frontend/parts/' . $name . '.php',
			$data
		);
	}
}
