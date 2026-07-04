<?php
/**
 * SEO service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Modules\Country\CountryMeta;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Country\CountryService;
use ZihadTravelCMS\Modules\Country\RegionTaxonomy;
use ZihadTravelCMS\Modules\Tour\TourMeta;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Tour\TourService;
use ZihadTravelCMS\Modules\Tour\TourTypeTaxonomy;
use ZihadTravelCMS\Modules\Visa\VisaMeta;
use ZihadTravelCMS\Modules\Visa\VisaPostType;
use ZihadTravelCMS\Modules\Visa\VisaRepository;
use ZihadTravelCMS\Modules\Visa\VisaService;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;
use ZihadTravelCMS\Services\MediaService;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the effective SEO values for the current request.
 *
 * Precedence per value: the post's `ztc_seo` meta, then the `seo.*`
 * global defaults, then a value derived from the content itself (post
 * title + site name, excerpt/short description, hero image fallback
 * chain via the content services).
 *
 * The result is computed once per request and memoized; every value
 * passes through its public filter (`ztc_seo_title`,
 * `ztc_seo_description`, `ztc_seo_canonical`, `ztc_seo_robots`) before
 * being cached, so filters run exactly once.
 */
final class SeoService {

	public const TYPE_TOUR    = 'tour';
	public const TYPE_VISA    = 'visa';
	public const TYPE_COUNTRY = 'country';

	/**
	 * Memoized per-request SEO data (null until first resolved).
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $data = null;

	/**
	 * Constructor.
	 *
	 * @param Config            $config       Plugin configuration.
	 * @param GlobalSettings    $settings     Global settings.
	 * @param MediaService      $media        Media service.
	 * @param TourService       $tours        Tour business logic.
	 * @param VisaService       $visas        Visa business logic.
	 * @param CountryService    $countries    Country business logic.
	 * @param TourRepository    $tour_repo    Tour data access.
	 * @param VisaRepository    $visa_repo    Visa data access.
	 * @param CountryRepository $country_repo Country data access.
	 */
	public function __construct(
		private Config $config,
		private GlobalSettings $settings,
		private MediaService $media,
		private TourService $tours,
		private VisaService $visas,
		private CountryService $countries,
		private TourRepository $tour_repo,
		private VisaRepository $visa_repo,
		private CountryRepository $country_repo,
	) {}

	/**
	 * Whether a third-party SEO plugin is handling head output.
	 */
	public function deferred(): bool {
		$third_party = defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' )
			|| defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );

		/**
		 * Filter whether the SEO module defers to another SEO plugin.
		 *
		 * @param bool $third_party True when Yoast SEO or Rank Math is active.
		 */
		return (bool) apply_filters( 'ztc_seo_defer', $third_party );
	}

	/**
	 * Whether the module should render at all: enabled in settings and
	 * not deferring to a third-party SEO plugin.
	 */
	public function enabled(): bool {
		return (bool) $this->config->get( 'seo.enabled', true ) && ! $this->deferred();
	}

	/**
	 * Whether the current request is a plugin route (same routes the
	 * TemplateLoader serves).
	 */
	public function applies(): bool {
		return '' !== $this->context()['type'];
	}

	/**
	 * The current route: content type and kind.
	 *
	 * @return array{type: string, kind: string} `type` is tour|visa|country
	 *                                           ('' off plugin routes),
	 *                                           `kind` is single|archive.
	 */
	public function context(): array {
		if ( is_singular( TourPostType::NAME ) ) {
			return array(
				'type' => self::TYPE_TOUR,
				'kind' => 'single',
			);
		}

		if ( is_singular( VisaPostType::NAME ) ) {
			return array(
				'type' => self::TYPE_VISA,
				'kind' => 'single',
			);
		}

		if ( is_singular( CountryPostType::NAME ) ) {
			return array(
				'type' => self::TYPE_COUNTRY,
				'kind' => 'single',
			);
		}

		if ( is_post_type_archive( TourPostType::NAME ) || is_tax( TourTypeTaxonomy::NAME ) ) {
			return array(
				'type' => self::TYPE_TOUR,
				'kind' => 'archive',
			);
		}

		if ( is_post_type_archive( VisaPostType::NAME ) || is_tax( VisaTypeTaxonomy::NAME ) ) {
			return array(
				'type' => self::TYPE_VISA,
				'kind' => 'archive',
			);
		}

		if ( is_post_type_archive( CountryPostType::NAME ) || is_tax( RegionTaxonomy::NAME ) ) {
			return array(
				'type' => self::TYPE_COUNTRY,
				'kind' => 'archive',
			);
		}

		return array(
			'type' => '',
			'kind' => '',
		);
	}

	/**
	 * The resolved, filtered SEO data for the current request.
	 *
	 * Computed once per request; subsequent calls return the memoized
	 * array. Empty array off plugin routes.
	 *
	 * @return array<string, mixed> title, description, keywords,
	 *                              canonical, robots, image, og_type,
	 *                              type, kind, post_id.
	 */
	public function data(): array {
		if ( null !== $this->data ) {
			return $this->data;
		}

		$context = $this->context();

		if ( '' === $context['type'] ) {
			$this->data = array();

			return $this->data;
		}

		$post_id = 'single' === $context['kind'] ? (int) get_queried_object_id() : 0;
		$meta    = $post_id > 0 ? $this->seo_meta( $context['type'], $post_id ) : array();

		$raw = array(
			'type'        => $context['type'],
			'kind'        => $context['kind'],
			'post_id'     => $post_id,
			'title'       => $this->resolve_title( $context, $post_id, $meta ),
			'description' => $this->resolve_description( $context, $post_id, $meta ),
			'keywords'    => $this->resolve_keywords( $meta ),
			'canonical'   => $this->resolve_canonical( $context, $post_id, $meta ),
			'robots'      => $this->resolve_robots( $context, $meta ),
			'image'       => $this->resolve_image( $context['type'], $post_id ),
			'og_type'     => 'single' === $context['kind'] ? 'article' : 'website',
		);

		/**
		 * Filter the resolved meta title.
		 *
		 * @param string               $title Resolved title.
		 * @param array<string, mixed> $raw   Full resolved SEO data.
		 */
		$raw['title'] = (string) apply_filters( 'ztc_seo_title', $raw['title'], $raw );

		/**
		 * Filter the resolved meta description.
		 *
		 * @param string               $description Resolved description.
		 * @param array<string, mixed> $raw         Full resolved SEO data.
		 */
		$raw['description'] = (string) apply_filters( 'ztc_seo_description', $raw['description'], $raw );

		/**
		 * Filter the resolved canonical URL.
		 *
		 * @param string               $canonical Resolved canonical URL.
		 * @param array<string, mixed> $raw       Full resolved SEO data.
		 */
		$raw['canonical'] = (string) apply_filters( 'ztc_seo_canonical', $raw['canonical'], $raw );

		/**
		 * Filter the resolved robots directive ('' for index,follow).
		 *
		 * @param string               $robots Resolved robots directive.
		 * @param array<string, mixed> $raw    Full resolved SEO data.
		 */
		$raw['robots'] = (string) apply_filters( 'ztc_seo_robots', $raw['robots'], $raw );

		$this->data = $raw;

		return $this->data;
	}

	/**
	 * Drop the memoized data (tests, long-running processes).
	 */
	public function refresh(): void {
		$this->data = null;
	}

	/**
	 * The post's `ztc_seo` meta object.
	 *
	 * @param string $type    Content type: tour|visa|country.
	 * @param int    $post_id Post ID.
	 *
	 * @return array<string, string>
	 */
	private function seo_meta( string $type, int $post_id ): array {
		$value = match ( $type ) {
			self::TYPE_TOUR    => $this->tour_repo->meta( $post_id, TourMeta::SEO ),
			self::TYPE_VISA    => $this->visa_repo->meta( $post_id, VisaMeta::SEO ),
			self::TYPE_COUNTRY => $this->country_repo->meta( $post_id, CountryMeta::SEO ),
			default            => array(),
		};

		return array_map( 'strval', is_array( $value ) ? $value : array() );
	}

	/**
	 * Effective title: meta title → archive setting → derived.
	 *
	 * @param array{type: string, kind: string} $context Route context.
	 * @param int                               $post_id Queried post ID.
	 * @param array<string, string>             $meta    Post SEO meta.
	 */
	private function resolve_title( array $context, int $post_id, array $meta ): string {
		if ( '' !== ( $meta['title'] ?? '' ) ) {
			return $meta['title'];
		}

		$separator = (string) $this->config->get( 'seo.title_separator', '–' );
		$site      = (string) get_bloginfo( 'name' );

		if ( 'single' === $context['kind'] ) {
			return trim( get_the_title( $post_id ) . ' ' . $separator . ' ' . $site );
		}

		$setting = (string) $this->config->get( 'seo.archive_' . $context['type'] . '_title', '' );

		if ( '' !== $setting ) {
			return $setting;
		}

		return trim( $this->archive_label( $context['type'] ) . ' ' . $separator . ' ' . $site );
	}

	/**
	 * Effective description: meta → content-derived → archive setting →
	 * global default.
	 *
	 * @param array{type: string, kind: string} $context Route context.
	 * @param int                               $post_id Queried post ID.
	 * @param array<string, string>             $meta    Post SEO meta.
	 */
	private function resolve_description( array $context, int $post_id, array $meta ): string {
		if ( '' !== ( $meta['description'] ?? '' ) ) {
			return $meta['description'];
		}

		if ( 'single' === $context['kind'] ) {
			$derived = self::TYPE_COUNTRY === $context['type']
				? (string) $this->country_repo->meta( $post_id, CountryMeta::SHORT_DESCRIPTION )
				: '';

			if ( '' === $derived ) {
				$derived = (string) get_the_excerpt( $post_id );
			}

			if ( '' !== $derived ) {
				return $this->trim_description( $derived );
			}
		} else {
			$setting = (string) $this->config->get( 'seo.archive_' . $context['type'] . '_description', '' );

			if ( '' !== $setting ) {
				return $setting;
			}
		}

		return (string) $this->config->get( 'seo.default_description', '' );
	}

	/**
	 * Effective keywords: meta → global default.
	 *
	 * @param array<string, string> $meta Post SEO meta.
	 */
	private function resolve_keywords( array $meta ): string {
		if ( '' !== ( $meta['keywords'] ?? '' ) ) {
			return $meta['keywords'];
		}

		return (string) $this->config->get( 'seo.default_keywords', '' );
	}

	/**
	 * Effective canonical URL: meta override → permalink / archive or
	 * term link, with `/page/N/` appended on paginated archives.
	 *
	 * @param array{type: string, kind: string} $context Route context.
	 * @param int                               $post_id Queried post ID.
	 * @param array<string, string>             $meta    Post SEO meta.
	 */
	private function resolve_canonical( array $context, int $post_id, array $meta ): string {
		if ( '' !== ( $meta['canonical'] ?? '' ) ) {
			return $meta['canonical'];
		}

		if ( 'single' === $context['kind'] ) {
			return (string) get_permalink( $post_id );
		}

		$base = '';

		if ( is_tax() ) {
			$link = get_term_link( get_queried_object() );
			$base = is_wp_error( $link ) ? '' : (string) $link;
		} else {
			$base = (string) get_post_type_archive_link( $this->post_type_for( $context['type'] ) );
		}

		if ( '' === $base ) {
			return '';
		}

		$paged = (int) get_query_var( 'paged' );

		return $paged > 1 ? trailingslashit( $base ) . 'page/' . $paged . '/' : $base;
	}

	/**
	 * Effective robots directive: per-post meta on singles, the
	 * `noindex_archives` setting on archives ('' = index,follow).
	 *
	 * @param array{type: string, kind: string} $context Route context.
	 * @param array<string, string>             $meta    Post SEO meta.
	 */
	private function resolve_robots( array $context, array $meta ): string {
		if ( 'single' === $context['kind'] ) {
			return (string) ( $meta['robots'] ?? '' );
		}

		return $this->config->get( 'seo.noindex_archives', false ) ? 'noindex' : '';
	}

	/**
	 * Social/share image URL: the content's own hero fallback chain
	 * (hero meta → featured image → gallery), then the global default
	 * social image, then the logo.
	 *
	 * @param string $type    Content type.
	 * @param int    $post_id Queried post ID (0 on archives).
	 */
	private function resolve_image( string $type, int $post_id ): string {
		$image = '';

		if ( $post_id > 0 ) {
			$image = match ( $type ) {
				self::TYPE_TOUR    => $this->tours->hero_url( $post_id ),
				self::TYPE_VISA    => $this->visas->hero_url( $post_id ),
				self::TYPE_COUNTRY => $this->countries->hero_url( $post_id ),
				default            => '',
			};
		}

		if ( '' === $image ) {
			$image = $this->media->image_url( (int) $this->config->get( 'seo.default_og_image', 0 ), MediaService::SIZE_HERO );
		}

		return '' !== $image ? $image : $this->settings->logo_url();
	}

	/**
	 * The registered post type name for a content type.
	 *
	 * @param string $type Content type: tour|visa|country.
	 */
	public function post_type_for( string $type ): string {
		return match ( $type ) {
			self::TYPE_TOUR    => TourPostType::NAME,
			self::TYPE_VISA    => VisaPostType::NAME,
			self::TYPE_COUNTRY => CountryPostType::NAME,
			default            => '',
		};
	}

	/**
	 * Human archive label for a content type (taxonomy term name on
	 * term archives, post type label otherwise).
	 *
	 * @param string $type Content type.
	 */
	public function archive_label( string $type ): string {
		if ( is_tax() ) {
			$term = get_queried_object();

			if ( is_object( $term ) && '' !== (string) ( $term->name ?? '' ) ) {
				return (string) $term->name;
			}
		}

		return match ( $type ) {
			self::TYPE_TOUR    => __( 'Tours', 'zihad-travel-cms' ),
			self::TYPE_VISA    => __( 'Visas', 'zihad-travel-cms' ),
			self::TYPE_COUNTRY => __( 'Countries', 'zihad-travel-cms' ),
			default            => '',
		};
	}

	/**
	 * Squash whitespace and cap a derived description at ~160 chars.
	 *
	 * @param string $text Raw text.
	 */
	private function trim_description( string $text ): string {
		$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );

		if ( mb_strlen( $text ) <= 160 ) {
			return $text;
		}

		return rtrim( mb_substr( $text, 0, 157 ) ) . '…';
	}
}
