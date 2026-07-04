<?php
/**
 * Search module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use WP_Post;
use ZihadTravelCMS\Modules\BaseModule;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Views\SearchWidgetRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword search, filtering and sorting across tours, visas and
 * countries: `GET ztc/v1/search` behind the AJAX UI, server-side
 * filter parity on the plugin archives (no-JS), and the tabbed
 * Visa/Tour search widget (shortcode + Elementor + optional homepage
 * injection) rendered by the shared SearchWidgetRenderer.
 */
final class SearchModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param ArchiveFilters $archive_filters No-JS archive filtering.
	 * @param HomepageSearch $homepage        Homepage widget injection.
	 */
	public function __construct(
		private ArchiveFilters $archive_filters,
		private HomepageSearch $homepage,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'search';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = SearchController::class;

				return $controllers;
			}
		);

		// The widget's cached country options stay fresh.
		$flush = static fn() => delete_transient( SearchWidgetRenderer::COUNTRIES_TRANSIENT );
		add_action( 'save_post_' . CountryPostType::NAME, $flush );
		add_action(
			'after_delete_post',
			static function ( int $post_id, ?WP_Post $post ) use ( $flush ): void {
				if ( null !== $post && CountryPostType::NAME === $post->post_type ) {
					$flush();
				}
			},
			10,
			2
		);

		parent::register();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		// Both components are frontend concerns; their callbacks guard
		// against admin/main-query contexts themselves.
		return is_admin() ? array() : array( $this->archive_filters, $this->homepage );
	}
}
