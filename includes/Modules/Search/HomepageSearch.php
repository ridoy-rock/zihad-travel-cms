<?php
/**
 * Homepage search widget injection.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Views\SearchWidgetRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Honors the "Show the search widget on the homepage" setting by
 * prepending the shared widget to the front page's content — once, on
 * the main query only, and never on Elementor-built pages (place the
 * Elementor widget there instead). Themes and site owners can veto via
 * the `ztc_show_homepage_search` filter or the setting itself.
 */
final class HomepageSearch implements Registrable {

	/**
	 * Rendered-once guard for this request.
	 */
	private bool $rendered = false;

	/**
	 * Constructor.
	 *
	 * @param Config               $config Plugin configuration.
	 * @param SearchWidgetRenderer $widget The shared widget renderer.
	 */
	public function __construct(
		private Config $config,
		private SearchWidgetRenderer $widget,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'inject' ), 5 );
	}

	/**
	 * Prepend the widget to the front page content when enabled.
	 *
	 * @param string $content Post content.
	 */
	public function inject( string $content ): string {
		if ( $this->rendered || ! $this->config->get( 'homepage.show_search', true ) ) {
			return $content;
		}

		if ( ! is_front_page() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Elementor-built front pages compose their own layout — the
		// Travel Search widget belongs inside it, not above it.
		if ( 'builder' === (string) get_post_meta( (int) get_the_ID(), '_elementor_edit_mode', true ) ) {
			return $content;
		}

		/**
		 * Filter whether the search widget is auto-rendered on the
		 * homepage (the homepage.show_search setting already gates it).
		 *
		 * @param bool $show Whether to render.
		 */
		if ( ! apply_filters( 'ztc_show_homepage_search', true ) ) {
			return $content;
		}

		$this->rendered = true;

		return $this->widget->render() . $content;
	}
}
