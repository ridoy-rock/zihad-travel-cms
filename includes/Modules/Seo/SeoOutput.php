<?php
/**
 * SEO head output.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the resolved SEO data into the document head on plugin
 * routes: title (via `document_title_parts`), meta description /
 * keywords, canonical link, OpenGraph + Twitter Card tags, robots
 * directives (via `wp_robots`) and the Schema.org JSON-LD graph.
 *
 * Every callback re-checks `SeoService::enabled()`, so the whole
 * pipeline stays inert while Yoast SEO or Rank Math is active (or the
 * seo.enabled toggle is off) — the deferral required for coexistence.
 */
final class SeoOutput implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param SeoService     $seo      Resolved SEO data.
	 * @param SchemaService  $schema   JSON-LD builders.
	 * @param Config         $config   Plugin configuration.
	 * @param GlobalSettings $settings Global settings.
	 */
	public function __construct(
		private SeoService $seo,
		private SchemaService $schema,
		private Config $config,
		private GlobalSettings $settings,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'wp', array( $this, 'take_over_canonical' ) );
		add_filter( 'document_title_parts', array( $this, 'filter_title' ), 20 );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ), 20 );
		add_action( 'wp_head', array( $this, 'render_head' ), 1 );
	}

	/**
	 * Whether this request should render (module on, plugin route,
	 * not an Elementor editor preview).
	 */
	private function active(): bool {
		if ( ! $this->seo->enabled() || ! $this->seo->applies() ) {
			return false;
		}

		// The Elementor editor renders previews through the frontend.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only feature detection.
		return ! isset( $_GET['elementor-preview'] );
	}

	/**
	 * Replace core's rel_canonical on plugin routes (ours understands
	 * per-post overrides, archives and pagination).
	 */
	public function take_over_canonical(): void {
		if ( $this->active() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	/**
	 * Swap the document title for the resolved (already complete) one.
	 *
	 * @param array<string, string> $parts Core title parts.
	 *
	 * @return array<string, string>
	 */
	public function filter_title( array $parts ): array {
		if ( ! $this->active() ) {
			return $parts;
		}

		$title = (string) ( $this->seo->data()['title'] ?? '' );

		return '' !== $title ? array( 'title' => $title ) : $parts;
	}

	/**
	 * Merge the resolved robots directive into core's robots output.
	 *
	 * @param array<string, bool|string> $robots Core robots directives.
	 *
	 * @return array<string, bool|string>
	 */
	public function filter_robots( array $robots ): array {
		if ( ! $this->active() ) {
			return $robots;
		}

		$directive = (string) ( $this->seo->data()['robots'] ?? '' );

		if ( str_contains( $directive, 'noindex' ) ) {
			$robots['noindex'] = true;
		}

		if ( str_contains( $directive, 'nofollow' ) ) {
			$robots['nofollow'] = true;
		}

		return $robots;
	}

	/**
	 * Print description/keywords, canonical, OpenGraph, Twitter Card
	 * and JSON-LD tags. The assembled block passes through
	 * `ztc_seo_head` before printing.
	 */
	public function render_head(): void {
		if ( ! $this->active() ) {
			return;
		}

		$data = $this->seo->data();
		$head = '';

		if ( '' !== (string) $data['description'] ) {
			$head .= sprintf( '<meta name="description" content="%s">' . "\n", esc_attr( (string) $data['description'] ) );
		}

		if ( '' !== (string) $data['keywords'] ) {
			$head .= sprintf( '<meta name="keywords" content="%s">' . "\n", esc_attr( (string) $data['keywords'] ) );
		}

		if ( '' !== (string) $data['canonical'] ) {
			$head .= sprintf( '<link rel="canonical" href="%s">' . "\n", esc_url( (string) $data['canonical'] ) );
		}

		$head .= $this->social_tags( $data );
		$head .= $this->schema_tag( $data );

		/**
		 * Filter the assembled SEO head block before printing.
		 *
		 * Components are individually escaped when assembled.
		 *
		 * @param string               $head Escaped head markup.
		 * @param array<string, mixed> $data Resolved SEO data.
		 */
		echo apply_filters( 'ztc_seo_head', $head, $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * OpenGraph + Twitter Card tags.
	 *
	 * @param array<string, mixed> $data Resolved SEO data.
	 */
	private function social_tags( array $data ): string {
		$markup = '';

		if ( $this->config->get( 'seo.og_enabled', true ) ) {
			$opengraph = array(
				'og:type'        => (string) $data['og_type'],
				'og:title'       => (string) $data['title'],
				'og:description' => (string) $data['description'],
				'og:url'         => (string) $data['canonical'],
				'og:site_name'   => $this->settings->company_name(),
				'og:image'       => (string) $data['image'],
				'og:locale'      => (string) get_locale(),
			);

			/**
			 * Filter the OpenGraph properties before rendering.
			 *
			 * @param array<string, string> $opengraph Property => content.
			 * @param array<string, mixed>  $data      Resolved SEO data.
			 */
			$opengraph = (array) apply_filters( 'ztc_seo_opengraph', $opengraph, $data );

			foreach ( $opengraph as $property => $content ) {
				if ( '' === (string) $content ) {
					continue;
				}

				$markup .= sprintf(
					'<meta property="%s" content="%s">' . "\n",
					esc_attr( (string) $property ),
					esc_attr( (string) $content )
				);
			}
		}

		if ( ! $this->config->get( 'seo.twitter_enabled', true ) ) {
			return $markup;
		}

		$twitter = array(
			'twitter:card'        => '' !== (string) $data['image'] ? 'summary_large_image' : 'summary',
			'twitter:title'       => (string) $data['title'],
			'twitter:description' => (string) $data['description'],
			'twitter:image'       => (string) $data['image'],
			'twitter:site'        => (string) $this->config->get( 'seo.twitter_handle', '' ),
		);

		/**
		 * Filter the Twitter Card properties before rendering.
		 *
		 * @param array<string, string> $twitter Name => content.
		 * @param array<string, mixed>  $data    Resolved SEO data.
		 */
		$twitter = (array) apply_filters( 'ztc_seo_twitter', $twitter, $data );

		foreach ( $twitter as $name => $content ) {
			if ( '' === (string) $content ) {
				continue;
			}

			$markup .= sprintf(
				'<meta name="%s" content="%s">' . "\n",
				esc_attr( (string) $name ),
				esc_attr( (string) $content )
			);
		}

		return $markup;
	}

	/**
	 * The JSON-LD script tag for the current route ('' when structured
	 * data is disabled or the graph is empty).
	 *
	 * @param array<string, mixed> $data Resolved SEO data.
	 */
	private function schema_tag( array $data ): string {
		if ( ! $this->config->get( 'seo.schema_enabled', true ) ) {
			return '';
		}

		$graph = $this->schema->graph( $this->nodes( $data ) );

		/**
		 * Filter the Schema.org graph before encoding.
		 *
		 * @param array<string, mixed> $graph Full @context/@graph array.
		 * @param array<string, mixed> $data  Resolved SEO data.
		 */
		$graph = (array) apply_filters( 'ztc_seo_schema', $graph, $data );

		if ( array() === $graph ) {
			return '';
		}

		return sprintf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * The schema nodes for the current route.
	 *
	 * @param array<string, mixed> $data Resolved SEO data.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function nodes( array $data ): array {
		$post_id = (int) $data['post_id'];
		$type    = (string) $data['type'];

		$content = array();

		if ( 'single' === (string) $data['kind'] ) {
			$content = match ( $type ) {
				SeoService::TYPE_TOUR    => $this->schema->tour( $post_id ),
				SeoService::TYPE_VISA    => $this->schema->visa( $post_id ),
				SeoService::TYPE_COUNTRY => $this->schema->country( $post_id ),
				default                  => array(),
			};
		} else {
			$content = $this->schema->collection_page(
				(string) $data['title'],
				(string) $data['description'],
				(string) $data['canonical']
			);
		}

		return array(
			$content,
			$this->schema->breadcrumbs( $this->breadcrumb_trail( $data ) ),
		);
	}

	/**
	 * The breadcrumb trail for the current route:
	 * Home → type archive → (single title | term name).
	 *
	 * @param array<string, mixed> $data Resolved SEO data.
	 *
	 * @return array<array{name: string, url: string}>
	 */
	private function breadcrumb_trail( array $data ): array {
		$type  = (string) $data['type'];
		$trail = array(
			array(
				'name' => __( 'Home', 'zihad-travel-cms' ),
				'url'  => (string) home_url( '/' ),
			),
		);

		$archive_url = (string) get_post_type_archive_link( $this->seo->post_type_for( $type ) );

		if ( 'single' === (string) $data['kind'] ) {
			$trail[] = array(
				'name' => $this->seo->archive_label( $type ),
				'url'  => $archive_url,
			);
			$trail[] = array(
				'name' => wp_strip_all_tags( get_the_title( (int) $data['post_id'] ) ),
				'url'  => '',
			);

			return $trail;
		}

		if ( is_tax() ) {
			$trail[] = array(
				'name' => $this->type_label( $type ),
				'url'  => $archive_url,
			);
		}

		$trail[] = array(
			'name' => $this->seo->archive_label( $type ),
			'url'  => '',
		);

		return $trail;
	}

	/**
	 * Plain type label (used above term names in trails).
	 *
	 * @param string $type Content type.
	 */
	private function type_label( string $type ): string {
		return match ( $type ) {
			SeoService::TYPE_TOUR    => __( 'Tours', 'zihad-travel-cms' ),
			SeoService::TYPE_VISA    => __( 'Visas', 'zihad-travel-cms' ),
			SeoService::TYPE_COUNTRY => __( 'Countries', 'zihad-travel-cms' ),
			default                  => '',
		};
	}
}
