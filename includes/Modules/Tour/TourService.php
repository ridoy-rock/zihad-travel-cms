<?php
/**
 * Tour service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Services\MediaService;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Business logic for Tours: pricing, sale detection, duration
 * formatting, gallery resolution and the card view-model. Data access
 * goes through the repositories.
 */
final class TourService {

	/**
	 * Constructor.
	 *
	 * @param TourRepository    $repository Tour repository.
	 * @param CountryRepository $countries  Country repository.
	 * @param MediaService      $media      Media service.
	 * @param GlobalSettings    $settings   Global settings.
	 */
	public function __construct(
		private TourRepository $repository,
		private CountryRepository $countries,
		private MediaService $media,
		private GlobalSettings $settings,
	) {}

	/**
	 * Regular price.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function price( int $tour_id ): float {
		return (float) $this->repository->meta( $tour_id, TourMeta::PRICE );
	}

	/**
	 * Sale price (0 when there is none).
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function sale_price( int $tour_id ): float {
		return (float) $this->repository->meta( $tour_id, TourMeta::SALE_PRICE );
	}

	/**
	 * Whether the tour is currently discounted.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function is_on_sale( int $tour_id ): bool {
		$sale = $this->sale_price( $tour_id );

		return $sale > 0 && $sale < $this->price( $tour_id );
	}

	/**
	 * The price a customer actually pays.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function effective_price( int $tour_id ): float {
		return $this->is_on_sale( $tour_id ) ? $this->sale_price( $tour_id ) : $this->price( $tour_id );
	}

	/**
	 * The effective price with currency, e.g. "USD 1,299.00", or ''
	 * when no price is set.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function formatted_price( int $tour_id ): string {
		$amount = $this->effective_price( $tour_id );

		return $amount > 0 ? $this->format_amount( $amount ) : '';
	}

	/**
	 * Format any amount using the global currency settings.
	 *
	 * @param float $amount Amount to format.
	 */
	public function format_amount( float $amount ): string {
		$currency = $this->settings->default_currency();
		$number   = number_format_i18n( $amount, 2 );

		return 'after' === $this->settings->currency_position()
			? $number . ' ' . $currency
			: $currency . ' ' . $number;
	}

	/**
	 * Human-readable duration, e.g. "5 Days / 4 Nights", or ''.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function duration_text( int $tour_id ): string {
		$duration = (array) $this->repository->meta( $tour_id, TourMeta::DURATION );
		$days     = (string) ( $duration['days'] ?? '' );
		$nights   = (string) ( $duration['nights'] ?? '' );

		if ( '' === $days ) {
			return '';
		}

		if ( '' === $nights ) {
			/* translators: %s: number of days. */
			return sprintf( __( '%s Days', 'zihad-travel-cms' ), $days );
		}

		/* translators: 1: number of days, 2: number of nights. */
		return sprintf( __( '%1$s Days / %2$s Nights', 'zihad-travel-cms' ), $days, $nights );
	}

	/**
	 * Gallery image URLs.
	 *
	 * @param int    $tour_id Tour post ID.
	 * @param string $size    Registered image size.
	 *
	 * @return array<string>
	 */
	public function gallery( int $tour_id, string $size = MediaService::SIZE_GALLERY ): array {
		$ids = array_map( 'intval', (array) $this->repository->meta( $tour_id, TourMeta::GALLERY ) );

		return $this->media->gallery_urls( $ids, $size );
	}

	/**
	 * The linked destination country's name, or ''.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function country_name( int $tour_id ): string {
		$country = $this->countries->find( (int) $this->repository->meta( $tour_id, TourMeta::COUNTRY ) );

		return null !== $country ? get_the_title( $country ) : '';
	}

	/**
	 * The tour's hero image URL: the hero meta, falling back to the
	 * featured image, then the first gallery image.
	 *
	 * @param int $tour_id Tour post ID.
	 */
	public function hero_url( int $tour_id ): string {
		$hero = $this->media->hero_url( (int) $this->repository->meta( $tour_id, TourMeta::HERO_IMAGE ) );

		if ( '' !== $hero ) {
			return $hero;
		}

		$hero = $this->media->image_url( (int) get_post_thumbnail_id( $tour_id ), MediaService::SIZE_HERO );

		if ( '' !== $hero ) {
			return $hero;
		}

		$gallery = $this->gallery( $tour_id, MediaService::SIZE_HERO );

		return $gallery[0] ?? '';
	}

	/**
	 * The full view-model for the single tour page.
	 *
	 * @param int $tour_id Tour post ID.
	 *
	 * @return array<string, mixed> Empty when the tour does not exist.
	 */
	public function page_data( int $tour_id ): array {
		$post = $this->repository->find( $tour_id );

		if ( null === $post ) {
			return array();
		}

		$country    = $this->countries->find( (int) $this->repository->meta( $post->ID, TourMeta::COUNTRY ) );
		$repository = $this->repository;

		return array(
			'id'            => $post->ID,
			'title'         => get_the_title( $post ),
			'hero'          => $this->hero_url( $post->ID ),
			'country'       => null !== $country ? get_the_title( $country ) : '',
			'country_url'   => null !== $country ? (string) get_permalink( $country ) : '',
			'types'         => wp_list_pluck( $repository->terms( $post->ID, TourTypeTaxonomy::NAME ), 'name' ),
			'duration'      => $this->duration_text( $post->ID ),
			'price'         => $this->formatted_price( $post->ID ),
			'regular_price' => $this->price( $post->ID ) > 0 ? $this->format_amount( $this->price( $post->ID ) ) : '',
			'on_sale'       => $this->is_on_sale( $post->ID ),
			'highlights'    => array_map( 'strval', (array) $repository->meta( $post->ID, TourMeta::HIGHLIGHTS ) ),
			'itinerary'     => (array) $repository->meta( $post->ID, TourMeta::ITINERARY ),
			'included'      => array_map( 'strval', (array) $repository->meta( $post->ID, TourMeta::INCLUDED ) ),
			'excluded'      => array_map( 'strval', (array) $repository->meta( $post->ID, TourMeta::EXCLUDED ) ),
			'hotels'        => (array) $repository->meta( $post->ID, TourMeta::HOTELS ),
			'flights'       => (string) $repository->meta( $post->ID, TourMeta::FLIGHTS ),
			'meals'         => (string) $repository->meta( $post->ID, TourMeta::MEALS ),
			'map'           => (string) $repository->meta( $post->ID, TourMeta::MAP ),
			'gallery'       => $this->gallery( $post->ID ),
			'faq'           => (array) $repository->meta( $post->ID, TourMeta::FAQ ),
		);
	}

	/**
	 * The view-model consumed by the Tour card and, later, Elementor
	 * widgets.
	 *
	 * @param int $tour_id Tour post ID.
	 *
	 * @return array<string, mixed> Empty when the tour does not exist.
	 */
	public function card_data( int $tour_id ): array {
		$post = $this->repository->find( $tour_id );

		if ( null === $post ) {
			return array();
		}

		return array(
			'id'            => $post->ID,
			'title'         => get_the_title( $post ),
			'url'           => (string) get_permalink( $post ),
			'excerpt'       => get_the_excerpt( $post ),
			'image'         => $this->media->image_url( (int) get_post_thumbnail_id( $post ), MediaService::SIZE_CARD ),
			'country'       => $this->country_name( $post->ID ),
			'types'         => wp_list_pluck( $this->repository->terms( $post->ID, TourTypeTaxonomy::NAME ), 'name' ),
			'duration'      => $this->duration_text( $post->ID ),
			'price'         => $this->formatted_price( $post->ID ),
			'regular_price' => $this->price( $post->ID ) > 0 ? $this->format_amount( $this->price( $post->ID ) ) : '',
			'on_sale'       => $this->is_on_sale( $post->ID ),
		);
	}
}
