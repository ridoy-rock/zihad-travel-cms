<?php
/**
 * Tour import mapping.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Contracts\ImportMapping;

defined( 'ABSPATH' ) || exit;

/**
 * Maps flat tour records onto the Tour post type. Duration is a JSON
 * object (`{"days":"5","nights":"4"}`); prices are plain numbers.
 */
final class TourImportMapping implements ImportMapping {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'tour';
	}

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return TourPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fields(): array {
		return array(
			'title'      => array(
				'target'   => 'post:title',
				'required' => true,
			),
			'slug'       => array( 'target' => 'post:slug' ),
			'status'     => array( 'target' => 'post:status' ),
			'content'    => array( 'target' => 'post:content' ),
			'excerpt'    => array( 'target' => 'post:excerpt' ),
			'country'    => array( 'target' => 'relation:' . TourMeta::COUNTRY ),
			'tour_types' => array( 'target' => 'terms:' . TourTypeTaxonomy::NAME ),
			'price'      => array( 'target' => 'meta:' . TourMeta::PRICE ),
			'sale_price' => array( 'target' => 'meta:' . TourMeta::SALE_PRICE ),
			'duration'   => array( 'target' => 'json:' . TourMeta::DURATION ),
			'highlights' => array( 'target' => 'list:' . TourMeta::HIGHLIGHTS ),
			'itinerary'  => array( 'target' => 'json:' . TourMeta::ITINERARY ),
			'included'   => array( 'target' => 'list:' . TourMeta::INCLUDED ),
			'excluded'   => array( 'target' => 'list:' . TourMeta::EXCLUDED ),
			'hotels'     => array( 'target' => 'json:' . TourMeta::HOTELS ),
			'flights'    => array( 'target' => 'meta:' . TourMeta::FLIGHTS ),
			'meals'      => array( 'target' => 'meta:' . TourMeta::MEALS ),
			'map'        => array( 'target' => 'meta:' . TourMeta::MAP ),
			'faq'        => array( 'target' => 'json:' . TourMeta::FAQ ),
			'seo'        => array( 'target' => 'json:' . TourMeta::SEO ),
			'hero_image' => array( 'target' => 'image:' . TourMeta::HERO_IMAGE ),
			'gallery'    => array( 'target' => 'gallery:' . TourMeta::GALLERY ),
			'thumbnail'  => array( 'target' => 'thumbnail' ),
		);
	}
}
