<?php
/**
 * Country import mapping.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Contracts\ImportMapping;

defined( 'ABSPATH' ) || exit;

/**
 * Maps flat country records onto the Country post type. Meta keys come
 * from CountryMeta constants, so the mapping can never drift from the
 * registered schema.
 */
final class CountryImportMapping implements ImportMapping {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'country';
	}

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return CountryPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fields(): array {
		return array(
			'title'              => array(
				'target'   => 'post:title',
				'required' => true,
			),
			'slug'               => array( 'target' => 'post:slug' ),
			'status'             => array( 'target' => 'post:status' ),
			'content'            => array( 'target' => 'post:content' ),
			'excerpt'            => array( 'target' => 'post:excerpt' ),
			'bangla_name'        => array( 'target' => 'meta:' . CountryMeta::BANGLA_NAME ),
			'short_description'  => array( 'target' => 'meta:' . CountryMeta::SHORT_DESCRIPTION ),
			'currency'           => array( 'target' => 'meta:' . CountryMeta::CURRENCY ),
			'capital'            => array( 'target' => 'meta:' . CountryMeta::CAPITAL ),
			'language'           => array( 'target' => 'meta:' . CountryMeta::LANGUAGE ),
			'timezone'           => array( 'target' => 'meta:' . CountryMeta::TIMEZONE ),
			'hero_subtitle'      => array( 'target' => 'meta:' . CountryMeta::HERO_SUBTITLE ),
			'overview'           => array( 'target' => 'meta:' . CountryMeta::OVERVIEW ),
			'travel_tips'        => array( 'target' => 'meta:' . CountryMeta::TRAVEL_TIPS ),
			'best_time_to_visit' => array( 'target' => 'meta:' . CountryMeta::BEST_TIME ),
			'popular_cities'     => array( 'target' => 'list:' . CountryMeta::POPULAR_CITIES ),
			'embassy_name'       => array( 'target' => 'meta:' . CountryMeta::EMBASSY_NAME ),
			'embassy_address'    => array( 'target' => 'meta:' . CountryMeta::EMBASSY_ADDRESS ),
			'embassy_phone'      => array( 'target' => 'meta:' . CountryMeta::EMBASSY_PHONE ),
			'embassy_email'      => array( 'target' => 'meta:' . CountryMeta::EMBASSY_EMAIL ),
			'embassy_website'    => array( 'target' => 'meta:' . CountryMeta::EMBASSY_WEBSITE ),
			'featured'           => array( 'target' => 'meta:' . CountryMeta::FEATURED ),
			'show_on_homepage'   => array( 'target' => 'meta:' . CountryMeta::SHOW_ON_HOMEPAGE ),
			'regions'            => array( 'target' => 'terms:' . RegionTaxonomy::NAME ),
			'hero_image'         => array( 'target' => 'image:' . CountryMeta::HERO_IMAGE ),
			'flag'               => array( 'target' => 'image:' . CountryMeta::FLAG ),
			'gallery'            => array( 'target' => 'gallery:' . CountryMeta::GALLERY ),
			'faq'                => array( 'target' => 'json:' . CountryMeta::FAQ ),
			'seo'                => array( 'target' => 'json:' . CountryMeta::SEO ),
			'thumbnail'          => array( 'target' => 'thumbnail' ),
		);
	}
}
