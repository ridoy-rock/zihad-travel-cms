<?php
/**
 * Tour meta fields.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\PostTypes\BasePostMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every Tour data field as typed, sanitized post meta.
 * Repositories and services reference the key constants — never raw
 * strings.
 *
 * The tour's category is the Tour Type taxonomy, not meta; the linked
 * country is a post relation to the Country post type. Price is a
 * number so the future Search module can sort and filter on it.
 */
final class TourMeta extends BasePostMeta {

	public const COUNTRY       = 'ztc_country';
	public const HERO_IMAGE    = 'ztc_hero_image';
	public const PRICE         = 'ztc_price';
	public const SALE_PRICE    = 'ztc_sale_price';
	public const DURATION      = 'ztc_duration';
	public const DURATION_DAYS = 'ztc_duration_days';
	public const GALLERY       = 'ztc_gallery';
	public const HIGHLIGHTS    = 'ztc_highlights';
	public const ITINERARY     = 'ztc_itinerary';
	public const INCLUDED      = 'ztc_included';
	public const EXCLUDED      = 'ztc_excluded';
	public const HOTELS        = 'ztc_hotels';
	public const FLIGHTS       = 'ztc_flights';
	public const MEALS         = 'ztc_meals';
	public const MAP           = 'ztc_map';
	public const FAQ           = 'ztc_faq';
	public const SEO           = 'ztc_seo';

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return TourPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fields(): array {
		return array(
			self::COUNTRY       => $this->int_field(),    // Country post ID.
			self::HERO_IMAGE    => $this->int_field(),    // Attachment ID.
			self::PRICE         => $this->number_field(),
			self::SALE_PRICE    => $this->number_field(),
			self::DURATION      => $this->object_field(
				array(
					'days'   => 'text',
					'nights' => 'text',
				)
			),
			// Numeric mirror of DURATION['days'], maintained by
			// TourDurationSync so search can range-filter and sort
			// without parsing the object.
			self::DURATION_DAYS => $this->int_field(),
			self::GALLERY       => $this->int_list_field(), // Attachment IDs.
			self::HIGHLIGHTS    => $this->string_list_field(),
			self::ITINERARY     => $this->object_list_field(
				array(
					'title'       => 'text',
					'description' => 'rich',
				)
			),
			self::INCLUDED      => $this->string_list_field(),
			self::EXCLUDED      => $this->string_list_field(),
			self::HOTELS        => $this->object_list_field(
				array(
					'name'        => 'text',
					'rating'      => 'text',
					'description' => 'rich',
				)
			),
			self::FLIGHTS       => $this->rich_text_field(),
			self::MEALS         => $this->rich_text_field(),
			self::MAP           => $this->url_field(), // Google Maps share/embed URL.
			self::FAQ           => $this->object_list_field(
				array(
					'question' => 'text',
					'answer'   => 'rich',
				)
			),
			self::SEO           => $this->seo_field(),
		);
	}
}
