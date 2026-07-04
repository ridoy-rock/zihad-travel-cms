<?php
/**
 * Card grid renderer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views;

use WP_Post;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Visa\VisaPostType;
use ZihadTravelCMS\Modules\Visa\VisaRepository;
use ZihadTravelCMS\Views\Cards\CountryCard;
use ZihadTravelCMS\Views\Cards\TourCard;
use ZihadTravelCMS\Views\Cards\VisaCard;

defined( 'ABSPATH' ) || exit;

/**
 * Renders posts as responsive card grids.
 *
 * The single render path shared by shortcodes, Elementor widgets, the
 * AJAX search endpoint and the archive templates — a card looks the
 * same everywhere it appears.
 */
final class GridRenderer {

	/**
	 * Public content type => post type.
	 */
	public const TYPES = array(
		'tour'    => TourPostType::NAME,
		'visa'    => VisaPostType::NAME,
		'country' => CountryPostType::NAME,
	);

	/**
	 * Constructor.
	 *
	 * @param TourRepository    $tours        Tour repository.
	 * @param VisaRepository    $visas        Visa repository.
	 * @param CountryRepository $countries    Country repository.
	 * @param TourCard          $tour_card    Tour card.
	 * @param VisaCard          $visa_card    Visa card.
	 * @param CountryCard       $country_card Country card.
	 * @param Template          $template     Template renderer.
	 */
	public function __construct(
		private TourRepository $tours,
		private VisaRepository $visas,
		private CountryRepository $countries,
		private TourCard $tour_card,
		private VisaCard $visa_card,
		private CountryCard $country_card,
		private Template $template,
	) {}

	/**
	 * Render a grid of cards.
	 *
	 * @param string               $type Content type: tour|visa|country.
	 * @param array<string, mixed> $args Options: `count` (default 6),
	 *                                   `columns` (default 3), `heading`,
	 *                                   `term` (type-taxonomy slug),
	 *                                   `region` (region slug),
	 *                                   `country` (country post ID).
	 */
	public function render( string $type, array $args = array() ): string {
		if ( ! isset( self::TYPES[ $type ] ) ) {
			return '';
		}

		$cards = array_map(
			fn( WP_Post $post ): string => $this->card_for( $type, $post->ID ),
			$this->posts( $type, $args )
		);

		return $this->template->get(
			'frontend/parts/grid.php',
			array(
				'heading' => (string) ( $args['heading'] ?? '' ),
				'columns' => (int) ( $args['columns'] ?? 3 ),
				'cards'   => array_filter( $cards ),
				'type'    => $type,
			)
		);
	}

	/**
	 * Render one card by content type.
	 *
	 * @param string $type    Content type: tour|visa|country.
	 * @param int    $post_id Post ID.
	 */
	public function card_for( string $type, int $post_id ): string {
		return match ( $type ) {
			'tour'    => $this->tour_card->render( $post_id ),
			'visa'    => $this->visa_card->render( $post_id ),
			'country' => $this->country_card->render( $post_id ),
			default   => '',
		};
	}

	/**
	 * Fetch the posts for a grid through the module repositories.
	 *
	 * @param string               $type Content type.
	 * @param array<string, mixed> $args Grid options.
	 *
	 * @return array<WP_Post>
	 */
	private function posts( string $type, array $args ): array {
		$count   = max( 1, min( 24, (int) ( $args['count'] ?? 6 ) ) );
		$term    = (string) ( $args['term'] ?? '' );
		$region  = (string) ( $args['region'] ?? '' );
		$country = (int) ( $args['country'] ?? 0 );

		if ( 'tour' === $type ) {
			if ( '' !== $term ) {
				return $this->tours->by_type( $term, $count );
			}
			if ( '' !== $region ) {
				return $this->tours->by_region( $region, $count );
			}
			if ( $country > 0 ) {
				return $this->tours->by_country( $country, $count );
			}

			return $this->tours->all( array( 'posts_per_page' => $count ) );
		}

		if ( 'visa' === $type ) {
			if ( '' !== $term ) {
				return $this->visas->by_type( $term, $count );
			}
			if ( $country > 0 ) {
				return $this->visas->by_country( $country, $count );
			}

			return $this->visas->all( array( 'posts_per_page' => $count ) );
		}

		if ( '' !== $region ) {
			return $this->countries->by_region( $region, $count );
		}

		return $this->countries->all( array( 'posts_per_page' => $count ) );
	}
}
