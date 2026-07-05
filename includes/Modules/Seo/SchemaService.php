<?php
/**
 * Schema.org structured data service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Modules\Country\CountryMeta;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Tour\TourMeta;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Tour\TourService;
use ZihadTravelCMS\Modules\Visa\VisaMeta;
use ZihadTravelCMS\Modules\Visa\VisaRepository;
use ZihadTravelCMS\Modules\Visa\VisaService;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The single place JSON-LD graphs are built. Every node goes through
 * node(): typed, recursively cleaned of empty values (valid Schema.org
 * output never carries empty properties), so future schema types are
 * one small builder method away — no duplication.
 *
 * FAQPage stays with the frontend FAQ template part, next to the
 * visible questions it describes (Google requires the schema to match
 * on-page content); this service intentionally never emits it.
 */
final class SchemaService {

	public const CONTEXT = 'https://schema.org';

	/**
	 * Constructor.
	 *
	 * @param GlobalSettings    $settings     Global settings.
	 * @param TourService       $tours        Tour business logic.
	 * @param VisaService       $visas        Visa business logic.
	 * @param TourRepository    $tour_repo    Tour data access.
	 * @param VisaRepository    $visa_repo    Visa data access.
	 * @param CountryRepository $country_repo Country data access.
	 */
	public function __construct(
		private GlobalSettings $settings,
		private TourService $tours,
		private VisaService $visas,
		private TourRepository $tour_repo,
		private VisaRepository $visa_repo,
		private CountryRepository $country_repo,
	) {}

	/**
	 * A typed node with empty properties removed.
	 *
	 * @param string               $type       Schema.org type, e.g. `TouristTrip`.
	 * @param array<string, mixed> $properties Node properties.
	 *
	 * @return array<string, mixed>
	 */
	public function node( string $type, array $properties ): array {
		return array( '@type' => $type ) + $this->clean( $properties );
	}

	/**
	 * Wrap nodes in a `@context`/`@graph` envelope.
	 *
	 * @param array<array<string, mixed>> $nodes Schema nodes.
	 *
	 * @return array<string, mixed> Empty when there are no nodes.
	 */
	public function graph( array $nodes ): array {
		$nodes = array_values( array_filter( $nodes ) );

		if ( array() === $nodes ) {
			return array();
		}

		return array(
			'@context' => self::CONTEXT,
			'@graph'   => $nodes,
		);
	}

	/**
	 * The agency as a TravelAgency organization node.
	 *
	 * @return array<string, mixed>
	 */
	public function organization(): array {
		return $this->node(
			'TravelAgency',
			array(
				'name'      => $this->settings->company_name(),
				'url'       => (string) home_url( '/' ),
				'logo'      => $this->settings->logo_url(),
				'email'     => $this->settings->email(),
				'telephone' => $this->settings->phone(),
				'sameAs'    => array_values( $this->settings->social_links() ),
			)
		);
	}

	/**
	 * A tour as a TouristTrip node (name, image, itinerary, offer).
	 *
	 * @param int $tour_id Tour post ID.
	 *
	 * @return array<string, mixed> Empty when the tour does not exist.
	 */
	public function tour( int $tour_id ): array {
		$post = $this->tour_repo->find( $tour_id );

		if ( null === $post ) {
			return array();
		}

		$itinerary = array_values(
			array_filter(
				array_map(
					fn( mixed $day ): array => is_array( $day ) && '' !== (string) ( $day['title'] ?? '' )
						? $this->node( 'TouristAttraction', array( 'name' => wp_strip_all_tags( (string) $day['title'] ) ) )
						: array(),
					(array) $this->tour_repo->meta( $tour_id, TourMeta::ITINERARY )
				)
			)
		);

		$price = $this->tours->effective_price( $tour_id );

		return $this->node(
			'TouristTrip',
			array(
				'name'        => wp_strip_all_tags( get_the_title( $post ) ),
				'description' => $this->excerpt_text( $tour_id ),
				'url'         => (string) get_permalink( $post ),
				'image'       => $this->tours->hero_url( $tour_id ),
				'itinerary'   => array() !== $itinerary
					? $this->node(
						'ItemList',
						array(
							'numberOfItems'   => count( $itinerary ),
							'itemListElement' => $itinerary,
						)
					)
					: array(),
				'offers'      => $price > 0
					? $this->node(
						'Offer',
						array(
							'price'         => number_format( $price, 2, '.', '' ),
							'priceCurrency' => $this->settings->default_currency(),
							'url'           => (string) get_permalink( $post ),
							'availability'  => self::CONTEXT . '/InStock',
						)
					)
					: array(),
				'provider'    => $this->organization(),
			)
		);
	}

	/**
	 * A visa service as a GovernmentService node.
	 *
	 * @param int $visa_id Visa post ID.
	 *
	 * @return array<string, mixed> Empty when the visa does not exist.
	 */
	public function visa( int $visa_id ): array {
		$post = $this->visa_repo->find( $visa_id );

		if ( null === $post ) {
			return array();
		}

		$types = wp_list_pluck( $this->visa_repo->terms( $visa_id, VisaTypeTaxonomy::NAME ), 'name' );

		return $this->node(
			'GovernmentService',
			array(
				'name'            => wp_strip_all_tags( get_the_title( $post ) ),
				'description'     => $this->excerpt_text( $visa_id ),
				'url'             => (string) get_permalink( $post ),
				'serviceType'     => '' !== (string) ( $types[0] ?? '' )
					? (string) $types[0]
					: __( 'Visa Service', 'zihad-travel-cms' ),
				'areaServed'      => '' !== $this->visas->country_name( $visa_id )
					? $this->node( 'Country', array( 'name' => $this->visas->country_name( $visa_id ) ) )
					: array(),
				'offers'          => '' !== (string) $this->visa_repo->meta( $visa_id, VisaMeta::FEE )
					? $this->node(
						'Offer',
						array( 'description' => (string) $this->visa_repo->meta( $visa_id, VisaMeta::FEE ) )
					)
					: array(),
				'provider'        => $this->organization(),
				'serviceOperator' => $this->organization(),
			)
		);
	}

	/**
	 * A destination as a Country node.
	 *
	 * @param int $country_id Country post ID.
	 *
	 * @return array<string, mixed> Empty when the country does not exist.
	 */
	public function country( int $country_id ): array {
		$post = $this->country_repo->find( $country_id );

		if ( null === $post ) {
			return array();
		}

		return $this->node(
			'Country',
			array(
				'name'          => wp_strip_all_tags( get_the_title( $post ) ),
				'alternateName' => (string) $this->country_repo->meta( $country_id, CountryMeta::BANGLA_NAME ),
				'description'   => '' !== (string) $this->country_repo->meta( $country_id, CountryMeta::SHORT_DESCRIPTION )
					? wp_strip_all_tags( (string) $this->country_repo->meta( $country_id, CountryMeta::SHORT_DESCRIPTION ) )
					: $this->excerpt_text( $country_id ),
				'url'           => (string) get_permalink( $post ),
			)
		);
	}

	/**
	 * A BreadcrumbList node from an ordered trail.
	 *
	 * Per Google's guidelines the last item (the current page) may omit
	 * its URL.
	 *
	 * @param array<array{name: string, url?: string}> $trail Ordered crumbs.
	 *
	 * @return array<string, mixed> Empty for trails shorter than two crumbs.
	 */
	public function breadcrumbs( array $trail ): array {
		$trail = array_values(
			array_filter( $trail, static fn( array $crumb ): bool => '' !== (string) ( $crumb['name'] ?? '' ) )
		);

		if ( count( $trail ) < 2 ) {
			return array();
		}

		$last  = count( $trail ) - 1;
		$items = array();

		foreach ( $trail as $position => $crumb ) {
			$items[] = $this->node(
				'ListItem',
				array(
					'position' => $position + 1,
					'name'     => (string) $crumb['name'],
					'item'     => $position < $last ? (string) ( $crumb['url'] ?? '' ) : '',
				)
			);
		}

		return $this->node( 'BreadcrumbList', array( 'itemListElement' => $items ) );
	}

	/**
	 * An archive as a CollectionPage node.
	 *
	 * @param string $name        Archive title.
	 * @param string $description Archive description.
	 * @param string $url         Archive URL.
	 *
	 * @return array<string, mixed>
	 */
	public function collection_page( string $name, string $description, string $url ): array {
		return $this->node(
			'CollectionPage',
			array(
				'name'        => $name,
				'description' => $description,
				'url'         => $url,
			)
		);
	}

	/**
	 * Plain-text excerpt for schema descriptions.
	 *
	 * @param int $post_id Post ID.
	 */
	private function excerpt_text( int $post_id ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ) ) );
	}

	/**
	 * Recursively drop empty strings, empty arrays and nulls so no node
	 * ships hollow properties.
	 *
	 * @param array<string|int, mixed> $properties Node properties.
	 *
	 * @return array<string|int, mixed>
	 */
	private function clean( array $properties ): array {
		$clean = array();

		foreach ( $properties as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = $this->clean( $value );
			}

			if ( null === $value || '' === $value || array() === $value ) {
				continue;
			}

			$clean[ $key ] = $value;
		}

		// array_values( $x ) === $x ≡ array_is_list( $x ) (WP core only polyfills the latter since 6.5).
		return array_values( $properties ) === $properties && array() !== $clean ? array_values( $clean ) : $clean;
	}
}
