<?php
/**
 * Demo content generator.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the curated sources (seeds × localized templates) into
 * importer-format JSON for countries, visas and tours.
 *
 * The generator is deliberately pure: no randomness, no timestamps.
 * Pool choices are derived from the seed index and slugs are stable,
 * so output is byte-identical between runs and re-installing always
 * upserts through the importer instead of duplicating content.
 */
final class DemoContentGenerator {

	public const LOCALES = array( 'en', 'bn' );

	/**
	 * Constructor.
	 *
	 * @param SourceRepository $sources Generator sources.
	 */
	public function __construct( private SourceRepository $sources ) {}

	/**
	 * Generate all three data files.
	 *
	 * @param string      $locale Content locale (en|bn).
	 * @param string|null $dir    Target directory (defaults to /demo-data).
	 *
	 * @return array{locale: string, files: array<string, string>, counts: array<string, int>}
	 *
	 * @throws RuntimeException When the locale is unknown or a file cannot be written.
	 */
	public function generate( string $locale = 'en', ?string $dir = null ): array {
		if ( ! in_array( $locale, self::LOCALES, true ) ) {
			throw new RuntimeException( sprintf( 'Unknown demo locale "%s".', $locale ) );
		}

		$dir = null !== $dir ? untrailingslashit( $dir ) : untrailingslashit( $this->sources->output_dir() );

		if ( ! wp_mkdir_p( $dir ) ) {
			throw new RuntimeException( sprintf( 'Could not create "%s".', $dir ) );
		}

		$countries = array();
		$visas     = array();
		$tours     = array();

		foreach ( $this->sources->seeds() as $index => $seed ) {
			$countries[] = $this->country_record( $seed, $index, $locale );
			$visas       = array_merge( $visas, $this->visa_records( $seed, $index, $locale ) );
			$tours       = array_merge( $tours, $this->tour_records( $seed, $index, $locale ) );
		}

		$files = array(
			'country' => $this->write( $dir . '/countries.json', $locale, $countries ),
			'visa'    => $this->write( $dir . '/visas.json', $locale, $visas ),
			'tour'    => $this->write( $dir . '/tours.json', $locale, $tours ),
		);

		return array(
			'locale' => $locale,
			'files'  => $files,
			'counts' => array(
				'country' => count( $countries ),
				'visa'    => count( $visas ),
				'tour'    => count( $tours ),
			),
		);
	}

	/**
	 * One country record.
	 *
	 * @param array<string, mixed> $seed   Country seed.
	 * @param int                  $index  Seed index (drives pool picks).
	 * @param string               $locale Content locale.
	 *
	 * @return array<string, mixed>
	 */
	private function country_record( array $seed, int $index, string $locale ): array {
		$templates = $this->sources->templates();
		$country   = (array) ( $templates['country'] ?? array() );
		$tokens    = $this->tokens( $seed, array( '{plot}' => (string) ( 2 + ( $index % 18 ) ), '{road}' => (string) ( 50 + ( $index % 40 ) ) ) );
		$slug      = $this->slug( $seed );

		return array(
			'title'              => 'bn' === $locale ? (string) $seed['bn'] : (string) $seed['name'],
			'slug'               => $slug,
			'status'             => 'publish',
			'excerpt'            => $this->text( $country['short_description'] ?? array(), $locale, $tokens ),
			'content'            => $this->text( $country['overview'] ?? array(), $locale, $tokens ),
			'bangla_name'        => (string) $seed['bn'],
			'short_description'  => $this->text( $country['short_description'] ?? array(), $locale, $tokens ),
			'currency'           => (string) $seed['currency'],
			'capital'            => (string) $seed['capital'],
			'language'           => (string) $seed['language'],
			'timezone'           => (string) $seed['tz'],
			'hero_subtitle'      => $this->text( $country['hero_subtitle'] ?? array(), $locale, $tokens ),
			'overview'           => $this->text( $country['overview'] ?? array(), $locale, $tokens ),
			'travel_tips'        => $this->text( $country['travel_tips'] ?? array(), $locale, $tokens ),
			'best_time_to_visit' => $this->pick( (array) ( $country['best_time'] ?? array() ), $index ),
			'popular_cities'     => array_map( 'strval', (array) $seed['cities'] ),
			'embassy_name'       => $this->text( $country['embassy']['name'] ?? array(), $locale, $tokens ),
			'embassy_address'    => $this->text( $country['embassy']['address'] ?? array(), $locale, $tokens ),
			'embassy_phone'      => sprintf( '+880-2-55%04d', 1000 + $index ),
			'embassy_email'      => 'info@' . $slug . '-embassy.example',
			'embassy_website'    => 'https://www.' . $slug . '-embassy.example/',
			'featured'           => $index < 12,
			'show_on_homepage'   => $index < 8,
			'regions'            => array( 'bn' === $locale ? (string) $seed['region_bn'] : (string) $seed['region'] ),
			'hero_image'         => $this->image( 'hero', $slug, $seed ),
			'flag'               => $this->image( 'flag', $slug, $seed ),
			'gallery'            => $this->gallery( $slug, $seed ),
			'faq'                => $this->faq_rows( (array) ( $country['faq'] ?? array() ), $locale, $tokens ),
			'seo'                => $this->seo( 'bn' === $locale ? (string) $seed['bn'] : (string) $seed['name'], $seed, $locale ),
			'thumbnail'          => $this->image( 'thumbnail', $slug, $seed ),
		);
	}

	/**
	 * Four or five visa records per country.
	 *
	 * @param array<string, mixed> $seed   Country seed.
	 * @param int                  $index  Seed index.
	 * @param string               $locale Content locale.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function visa_records( array $seed, int $index, string $locale ): array {
		$templates = $this->sources->templates();
		$visa      = (array) ( $templates['visa'] ?? array() );
		$types     = array_values( (array) ( $templates['visa_types'] ?? array() ) );

		// Everyone gets the four core types; every second country also
		// gets a work visa — deterministic and ≥ 400 in total.
		$count   = 0 === $index % 2 ? 5 : 4;
		$records = array();

		foreach ( array_slice( $types, 0, $count ) as $type_index => $type ) {
			$slug   = $this->slug( $seed );
			$tokens = $this->tokens(
				$seed,
				array(
					'{type}'    => (string) $type['en'],
					'{type_bn}' => (string) $type['bn'],
				)
			);

			$pick      = $index + $type_index;
			$title     = $this->text( $visa['title'] ?? array(), $locale, $tokens );
			$visa_slug = $slug . '-' . $type['slug'] . '-visa';

			$records[] = array(
				'title'               => $title,
				'slug'                => $visa_slug,
				'status'              => 'publish',
				'excerpt'             => $this->text( $visa['excerpt'] ?? array(), $locale, $tokens ),
				'country'             => (string) $seed['name'],
				'visa_types'          => array( 'bn' === $locale ? (string) $type['bn'] : (string) $type['en'] ),
				'processing_time'     => $this->pick( (array) ( $templates['processing_times'][ $locale ] ?? array() ), $pick ),
				'validity'            => $this->pick( (array) ( $templates['validity'][ $locale ] ?? array() ), $pick ),
				'stay_duration'       => $this->pick( (array) ( $templates['stay'][ $locale ] ?? array() ), $pick ),
				'entry_type'          => $this->pick( (array) ( $templates['entry_types'] ?? array() ), $pick ),
				'fee'                 => $this->pick( (array) ( $templates['fees'] ?? array() ), $pick ),
				'requirements'        => $this->text( $visa['requirements'] ?? array(), $locale, $tokens ),
				'important_notes'     => $this->text( $visa['notes'] ?? array(), $locale, $tokens ),
				'apply_button_text'   => $this->text( $visa['apply'] ?? array(), $locale, $tokens ),
				'required_documents'  => $this->list_of( $visa['documents'] ?? array(), $locale, $tokens ),
				'benefits'            => $this->list_of( $visa['benefits'] ?? array(), $locale, $tokens ),
				'application_process' => $this->step_rows( (array) ( $visa['steps'] ?? array() ), $locale, $tokens ),
				'faq'                 => $this->faq_rows( (array) ( $visa['faq'] ?? array() ), $locale, $tokens ),
				'seo'                 => $this->seo( $title, $seed, $locale ),
				'hero_image'          => $this->image( 'hero', $visa_slug, $seed ),
				'thumbnail'           => $this->image( 'thumbnail', $visa_slug, $seed ),
			);
		}

		return $records;
	}

	/**
	 * One or two tour records per country.
	 *
	 * @param array<string, mixed> $seed   Country seed.
	 * @param int                  $index  Seed index.
	 * @param string               $locale Content locale.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function tour_records( array $seed, int $index, string $locale ): array {
		$count   = 0 === $index % 4 ? 2 : 1; // ≥ 125 tours in total.
		$records = array();

		for ( $tour_index = 0; $tour_index < $count; $tour_index++ ) {
			$records[] = $this->tour_record( $seed, $index, $tour_index, $locale );
		}

		return $records;
	}

	/**
	 * One tour record.
	 *
	 * @param array<string, mixed> $seed       Country seed.
	 * @param int                  $index      Seed index.
	 * @param int                  $tour_index Tour number within the country.
	 * @param string               $locale     Content locale.
	 *
	 * @return array<string, mixed>
	 */
	private function tour_record( array $seed, int $index, int $tour_index, string $locale ): array {
		$templates = $this->sources->templates();
		$tour      = (array) ( $templates['tour'] ?? array() );
		$pick      = $index + $tour_index;

		$duration = (array) $this->pick( (array) ( $tour['durations'] ?? array( array( 5, 4 ) ) ), $pick );
		$days     = (string) ( $duration[0] ?? 5 );
		$nights   = (string) ( $duration[1] ?? 4 );

		$titles  = array_values( (array) ( $tour['titles'] ?? array() ) );
		$pattern = (array) $this->pick( $titles, $pick );
		$tokens  = $this->tokens( $seed, array( '{days}' => $days, '{nights}' => $nights ) );

		$slug  = $this->slug( $seed ) . '-' . (string) ( $pattern['slug'] ?? 'tour' ) . '-' . $days . 'd';
		$title = $this->interpolate( (string) ( $pattern[ $locale ] ?? $pattern['en'] ?? '' ), $tokens );

		$price = (float) $this->pick( (array) ( $tour['prices'] ?? array( 99900 ) ), $pick );
		// Every third tour is on sale, at the configured ratio.
		$sale = 0 === $pick % 3 ? round( $price * (float) ( $tour['sale_ratio'] ?? 0.85 ), -2 ) : 0;

		$type_pool = array_values( (array) ( $tour['tour_types'] ?? array() ) );
		$type      = (array) $this->pick( $type_pool, $pick );

		// Itinerary: arrival + as many middle days as fit + departure.
		$patterns = array_values( (array) ( $tour['itinerary'] ?? array() ) );
		$rows     = array();
		$total    = max( 2, min( (int) $days, 6 ) );

		for ( $day = 0; $day < $total; $day++ ) {
			$is_last  = $day === $total - 1;
			$template = $is_last ? end( $patterns ) : $patterns[ min( $day, count( $patterns ) - 2 ) ];
			$rows[]   = array(
				'title'       => $this->text( $template['t'] ?? array(), $locale, $tokens ),
				'description' => $this->text( $template['d'] ?? array(), $locale, $tokens ),
			);
		}

		return array(
			'title'      => $title,
			'slug'       => $slug,
			'status'     => 'publish',
			'excerpt'    => $this->text( $tour['excerpt'] ?? array(), $locale, $tokens ),
			'country'    => (string) $seed['name'],
			'tour_types' => array( (string) ( $type[ $locale ] ?? $type['en'] ?? '' ) ),
			'price'      => $price,
			'sale_price' => $sale,
			'duration'   => array( 'days' => $days, 'nights' => $nights ),
			'highlights' => $this->list_of( $tour['highlights'] ?? array(), $locale, $tokens ),
			'itinerary'  => $rows,
			'included'   => $this->list_of( $tour['included'] ?? array(), $locale, $tokens ),
			'excluded'   => $this->list_of( $tour['excluded'] ?? array(), $locale, $tokens ),
			'hotels'     => array_map(
				fn( array $hotel ): array => array(
					'name'        => $this->text( $hotel['n'] ?? array(), $locale, $tokens ),
					'rating'      => (string) ( $hotel['r'] ?? '' ),
					'description' => $this->text( $hotel['d'] ?? array(), $locale, $tokens ),
				),
				array_values( (array) ( $tour['hotels'] ?? array() ) )
			),
			'flights'    => $this->text( $tour['flights'] ?? array(), $locale, $tokens ),
			'meals'      => $this->text( $tour['meals'] ?? array(), $locale, $tokens ),
			'map'        => $this->interpolate( (string) ( $tour['map'] ?? '' ), array( '{capital}' => rawurlencode( (string) $seed['capital'] ) ) ),
			'faq'        => $this->faq_rows( (array) ( $tour['faq'] ?? array() ), $locale, $tokens ),
			'seo'        => $this->seo( $title, $seed, $locale ),
			'hero_image' => $this->image( 'hero', $slug, $seed ),
			'gallery'    => $this->gallery( $slug, $seed ),
			'thumbnail'  => $this->image( 'thumbnail', $slug, $seed ),
		);
	}

	// -----------------------------------------------------------------
	// Template helpers.
	// -----------------------------------------------------------------

	/**
	 * The replacement tokens for a seed.
	 *
	 * @param array<string, mixed>  $seed  Country seed.
	 * @param array<string, string> $extra Extra tokens.
	 *
	 * @return array<string, string>
	 */
	private function tokens( array $seed, array $extra = array() ): array {
		$cities = array_map( 'strval', (array) $seed['cities'] );

		return $extra + array(
			'{country}'    => (string) $seed['name'],
			'{country_bn}' => (string) $seed['bn'],
			'{capital}'    => (string) $seed['capital'],
			'{city}'       => $cities[0] ?? (string) $seed['capital'],
			'{city2}'      => $cities[1] ?? ( $cities[0] ?? (string) $seed['capital'] ),
			'{currency}'   => (string) $seed['currency'],
			'{language}'   => (string) $seed['language'],
			'{region}'     => (string) $seed['region'],
			'{region_bn}'  => (string) $seed['region_bn'],
			'{iso}'        => (string) $seed['iso'],
		);
	}

	/**
	 * Resolve a localized template node and interpolate tokens.
	 *
	 * @param mixed                 $node   `{en: …, bn: …}` node.
	 * @param string                $locale Content locale.
	 * @param array<string, string> $tokens Replacements.
	 */
	private function text( mixed $node, string $locale, array $tokens ): string {
		$node = (array) $node;

		return $this->interpolate( (string) ( $node[ $locale ] ?? $node['en'] ?? '' ), $tokens );
	}

	/**
	 * Resolve a localized list template.
	 *
	 * @param mixed                 $node   `{en: [...], bn: [...]}` node.
	 * @param string                $locale Content locale.
	 * @param array<string, string> $tokens Replacements.
	 *
	 * @return array<string>
	 */
	private function list_of( mixed $node, string $locale, array $tokens ): array {
		$node  = (array) $node;
		$items = (array) ( $node[ $locale ] ?? $node['en'] ?? array() );

		return array_map( fn( mixed $item ): string => $this->interpolate( (string) $item, $tokens ), array_values( $items ) );
	}

	/**
	 * FAQ template rows → importer rows.
	 *
	 * @param array<array<string, mixed>> $rows   Template rows (q/a nodes).
	 * @param string                      $locale Content locale.
	 * @param array<string, string>       $tokens Replacements.
	 *
	 * @return array<array{question: string, answer: string}>
	 */
	private function faq_rows( array $rows, string $locale, array $tokens ): array {
		return array_map(
			fn( array $row ): array => array(
				'question' => $this->text( $row['q'] ?? array(), $locale, $tokens ),
				'answer'   => $this->text( $row['a'] ?? array(), $locale, $tokens ),
			),
			array_values( array_filter( $rows, 'is_array' ) )
		);
	}

	/**
	 * Step template rows → importer rows.
	 *
	 * @param array<array<string, mixed>> $rows   Template rows (t/d nodes).
	 * @param string                      $locale Content locale.
	 * @param array<string, string>       $tokens Replacements.
	 *
	 * @return array<array{title: string, description: string}>
	 */
	private function step_rows( array $rows, string $locale, array $tokens ): array {
		return array_map(
			fn( array $row ): array => array(
				'title'       => $this->text( $row['t'] ?? array(), $locale, $tokens ),
				'description' => $this->text( $row['d'] ?? array(), $locale, $tokens ),
			),
			array_values( array_filter( $rows, 'is_array' ) )
		);
	}

	/**
	 * The SEO object for a record.
	 *
	 * @param string               $title  Record title.
	 * @param array<string, mixed> $seed   Country seed.
	 * @param string               $locale Content locale.
	 *
	 * @return array{title: string, description: string, keywords: string}
	 */
	private function seo( string $title, array $seed, string $locale ): array {
		$seo    = (array) ( $this->sources->templates()['seo'] ?? array() );
		$tokens = $this->tokens( $seed, array( '{title}' => $title ) );

		return array(
			'title'       => $this->text( $seo['title'] ?? array(), $locale, $tokens ),
			'description' => $this->text( $seo['description'] ?? array(), $locale, $tokens ),
			'keywords'    => $this->text( $seo['keywords'] ?? array(), $locale, $tokens ),
		);
	}

	/**
	 * One placeholder image URL from the configured patterns.
	 *
	 * @param string               $kind hero|thumbnail|flag.
	 * @param string               $slug Record slug (seeds the placeholder).
	 * @param array<string, mixed> $seed Country seed (for the ISO flag code).
	 */
	private function image( string $kind, string $slug, array $seed ): string {
		$patterns = (array) ( $this->sources->templates()['images'] ?? array() );

		return $this->interpolate(
			(string) ( $patterns[ $kind ] ?? '' ),
			array(
				'{slug}' => $slug,
				'{iso}'  => (string) $seed['iso'],
			)
		);
	}

	/**
	 * The gallery image URLs for a slug.
	 *
	 * @param string               $slug Record slug.
	 * @param array<string, mixed> $seed Country seed.
	 *
	 * @return array<string>
	 */
	private function gallery( string $slug, array $seed ): array {
		$patterns = (array) ( $this->sources->templates()['images'] ?? array() );
		$count    = max( 1, (int) ( $patterns['gallery_count'] ?? 3 ) );
		$urls     = array();

		for ( $n = 1; $n <= $count; $n++ ) {
			$urls[] = $this->interpolate(
				(string) ( $patterns['gallery'] ?? '' ),
				array(
					'{slug}' => $slug,
					'{iso}'  => (string) $seed['iso'],
					'{n}'    => (string) $n,
				)
			);
		}

		return $urls;
	}

	/**
	 * Deterministic pool pick.
	 *
	 * @param array<mixed> $pool  Value pool.
	 * @param int          $index Pick index.
	 */
	private function pick( array $pool, int $index ): mixed {
		$pool = array_values( $pool );

		return array() === $pool ? '' : $pool[ $index % count( $pool ) ];
	}

	/**
	 * The stable latin slug for a seed (shared across locales, which
	 * is the linking key future translation imports rely on).
	 *
	 * @param array<string, mixed> $seed Country seed.
	 */
	private function slug( array $seed ): string {
		return sanitize_title( (string) $seed['name'] );
	}

	/**
	 * Replace tokens in a template string.
	 *
	 * @param string                $template Template with {tokens}.
	 * @param array<string, string> $tokens   Replacements.
	 */
	private function interpolate( string $template, array $tokens ): string {
		return strtr( $template, $tokens );
	}

	/**
	 * Write one output file in importer format.
	 *
	 * @param string                      $path    Target path.
	 * @param string                      $locale  Content locale.
	 * @param array<array<string, mixed>> $records The records.
	 *
	 * @return string The written path.
	 *
	 * @throws RuntimeException When the file cannot be written.
	 */
	private function write( string $path, string $locale, array $records ): string {
		$body = wp_json_encode(
			array(
				'schema'  => 'ztc-demo/1',
				'locale'  => $locale,
				'records' => $records,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, (string) $body ) ) {
			throw new RuntimeException( sprintf( 'Could not write "%s".', $path ) );
		}

		return $path;
	}
}
