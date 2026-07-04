<?php
/**
 * Country service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Services\MediaService;

defined( 'ABSPATH' ) || exit;

/**
 * Business logic for Countries: image fallbacks, labelled fact sheets
 * and the card view-model. Data access goes through the repository.
 */
final class CountryService {

	/**
	 * Constructor.
	 *
	 * @param CountryRepository $repository Country repository.
	 * @param MediaService      $media      Media service.
	 */
	public function __construct(
		private CountryRepository $repository,
		private MediaService $media,
	) {}

	/**
	 * The country's flag image URL.
	 *
	 * @param int $country_id Country post ID.
	 */
	public function flag_url( int $country_id ): string {
		return $this->media->flag_url( (int) $this->repository->meta( $country_id, CountryMeta::FLAG ) );
	}

	/**
	 * The country's hero image URL, falling back to the featured image.
	 *
	 * @param int $country_id Country post ID.
	 */
	public function hero_url( int $country_id ): string {
		$hero = $this->media->hero_url( (int) $this->repository->meta( $country_id, CountryMeta::HERO_IMAGE ) );

		if ( '' !== $hero ) {
			return $hero;
		}

		return $this->media->image_url( (int) get_post_thumbnail_id( $country_id ), MediaService::SIZE_HERO );
	}

	/**
	 * Labelled quick facts (capital, currency, language, timezone),
	 * empty values removed.
	 *
	 * @param int $country_id Country post ID.
	 *
	 * @return array<string, array{label: string, value: string}>
	 */
	public function facts( int $country_id ): array {
		$facts = array(
			'capital'  => array(
				'label' => __( 'Capital', 'zihad-travel-cms' ),
				'value' => (string) $this->repository->meta( $country_id, CountryMeta::CAPITAL ),
			),
			'currency' => array(
				'label' => __( 'Currency', 'zihad-travel-cms' ),
				'value' => (string) $this->repository->meta( $country_id, CountryMeta::CURRENCY ),
			),
			'language' => array(
				'label' => __( 'Language', 'zihad-travel-cms' ),
				'value' => (string) $this->repository->meta( $country_id, CountryMeta::LANGUAGE ),
			),
			'timezone' => array(
				'label' => __( 'Timezone', 'zihad-travel-cms' ),
				'value' => (string) $this->repository->meta( $country_id, CountryMeta::TIMEZONE ),
			),
		);

		return array_filter( $facts, static fn( array $fact ): bool => '' !== $fact['value'] );
	}

	/**
	 * The full view-model for the single country page.
	 *
	 * @param int $country_id Country post ID.
	 *
	 * @return array<string, mixed> Empty when the country does not exist.
	 */
	public function page_data( int $country_id ): array {
		$post = $this->repository->find( $country_id );

		if ( null === $post ) {
			return array();
		}

		$repository = $this->repository;

		return array(
			'id'                => $post->ID,
			'title'             => get_the_title( $post ),
			'bangla_name'       => (string) $repository->meta( $post->ID, CountryMeta::BANGLA_NAME ),
			'short_description' => (string) $repository->meta( $post->ID, CountryMeta::SHORT_DESCRIPTION ),
			'hero'              => $this->hero_url( $post->ID ),
			'hero_subtitle'     => (string) $repository->meta( $post->ID, CountryMeta::HERO_SUBTITLE ),
			'flag'              => $this->flag_url( $post->ID ),
			'facts'             => $this->facts( $post->ID ),
			'overview'          => (string) $repository->meta( $post->ID, CountryMeta::OVERVIEW ),
			'travel_tips'       => (string) $repository->meta( $post->ID, CountryMeta::TRAVEL_TIPS ),
			'best_time'         => (string) $repository->meta( $post->ID, CountryMeta::BEST_TIME ),
			'popular_cities'    => array_map( 'strval', (array) $repository->meta( $post->ID, CountryMeta::POPULAR_CITIES ) ),
			'embassy'           => array_filter(
				array(
					'name'    => (string) $repository->meta( $post->ID, CountryMeta::EMBASSY_NAME ),
					'address' => (string) $repository->meta( $post->ID, CountryMeta::EMBASSY_ADDRESS ),
					'phone'   => (string) $repository->meta( $post->ID, CountryMeta::EMBASSY_PHONE ),
					'email'   => (string) $repository->meta( $post->ID, CountryMeta::EMBASSY_EMAIL ),
					'website' => (string) $repository->meta( $post->ID, CountryMeta::EMBASSY_WEBSITE ),
				)
			),
			'gallery'           => $this->media->gallery_urls( array_map( 'intval', (array) $repository->meta( $post->ID, CountryMeta::GALLERY ) ) ),
			'faq'               => (array) $repository->meta( $post->ID, CountryMeta::FAQ ),
			'regions'           => wp_list_pluck( $repository->terms( $post->ID, RegionTaxonomy::NAME ), 'name' ),
		);
	}

	/**
	 * The view-model consumed by the Country card and, later,
	 * Elementor widgets.
	 *
	 * @param int $country_id Country post ID.
	 *
	 * @return array<string, mixed> Empty when the country does not exist.
	 */
	public function card_data( int $country_id ): array {
		$post = $this->repository->find( $country_id );

		if ( null === $post ) {
			return array();
		}

		$image = $this->media->image_url( (int) get_post_thumbnail_id( $post ), MediaService::SIZE_CARD );

		return array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => (string) get_permalink( $post ),
			'excerpt' => get_the_excerpt( $post ),
			'image'   => '' !== $image ? $image : $this->hero_url( $post->ID ),
			'flag'    => $this->flag_url( $post->ID ),
			'facts'   => $this->facts( $post->ID ),
			'regions' => wp_list_pluck( $this->repository->terms( $post->ID, RegionTaxonomy::NAME ), 'name' ),
		);
	}
}
