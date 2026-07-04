<?php
/**
 * Search module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword search, filtering and sorting across tours, visas and
 * countries, served through `GET ztc/v1/search` and consumed by the
 * frontend's AJAX search/filter UI.
 */
final class SearchModule extends BaseModule {

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
	}
}
