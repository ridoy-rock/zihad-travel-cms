<?php
/**
 * SEO module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ztc_seo` meta (registered per post type) and the `seo.*`
 * global defaults as document-head output on plugin routes: title,
 * meta description/keywords, canonical URLs, robots directives,
 * OpenGraph, Twitter Cards and Schema.org JSON-LD (TouristTrip,
 * GovernmentService, Country, BreadcrumbList — FAQPage ships with the
 * frontend FAQ part, next to the visible questions).
 *
 * The module always loads so its settings schema stays registered (the
 * structural SettingsSanitizer would otherwise drop saved `seo.*`
 * values on the next settings write); the output pipeline defers at
 * render time while Yoast SEO or Rank Math is active — see
 * SeoService::deferred() and the `ztc_seo_defer` filter.
 */
final class SeoModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param SeoSettings $settings SEO settings schema + tab.
	 * @param SeoOutput   $output   Head output pipeline.
	 */
	public function __construct(
		private SeoSettings $settings,
		private SeoOutput $output,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		$components = array( $this->settings );

		// Head output only exists on the frontend.
		if ( ! is_admin() ) {
			$components[] = $this->output;
		}

		return $components;
	}
}
