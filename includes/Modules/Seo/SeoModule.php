<?php
/**
 * SEO module (placeholder).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Future scope: renders the `ztc_seo` meta (already registered per
 * post type) as title/description/OpenGraph tags, plus schema.org
 * structured data (TouristTrip, GovernmentService, Country) and
 * sitemap integration. Steps aside when Yoast/Rank Math is active.
 */
final class SeoModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'seo';
	}
}
