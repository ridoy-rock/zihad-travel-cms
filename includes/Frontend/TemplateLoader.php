<?php
/**
 * Frontend template loader.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Frontend;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Assets;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Modules\Country\CountryService;
use ZihadTravelCMS\Modules\Country\RegionTaxonomy;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Tour\TourService;
use ZihadTravelCMS\Modules\Tour\TourTypeTaxonomy;
use ZihadTravelCMS\Modules\Visa\VisaPostType;
use ZihadTravelCMS\Modules\Visa\VisaService;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;
use ZihadTravelCMS\Views\GridRenderer;
use ZihadTravelCMS\Views\SearchFormData;

defined( 'ABSPATH' ) || exit;

/**
 * The frontend controller: routes plugin URLs to templates and
 * prepares each template's view-model.
 *
 * MVC split — this class (and the services it calls) is the
 * controller/model side; templates under /templates/frontend are pure
 * views reading the model via ztc_view(). Themes override any template
 * by copying it to `{theme}/zihad-travel-cms/frontend/…`; when a theme
 * supplies its own single-*.php / archive-*.php through the normal
 * hierarchy, the loader steps aside for anything it cannot resolve.
 */
final class TemplateLoader implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param Template       $template  Template renderer/locator.
	 * @param VisaService    $visas     Visa view-models.
	 * @param CountryService $countries Country view-models.
	 * @param TourService    $tours     Tour view-models.
	 * @param GridRenderer   $grids     Card grid renderer.
	 * @param SearchFormData $search    Search form view-models.
	 */
	public function __construct(
		private Template $template,
		private VisaService $visas,
		private CountryService $countries,
		private TourService $tours,
		private GridRenderer $grids,
		private SearchFormData $search,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'template_include', array( $this, 'resolve' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Swap in the plugin template for plugin routes.
	 *
	 * @param string $default_template Template chosen by WordPress.
	 */
	public function resolve( string $default_template ): string {
		$name = $this->route_template();

		if ( '' === $name ) {
			return $default_template;
		}

		$file = $this->template->locate( 'frontend/' . $name );

		if ( '' === $file ) {
			return $default_template;
		}

		set_query_var( 'ztc_view', $this->view_for( $name ) );

		return $file;
	}

	/**
	 * Enqueue frontend assets on plugin routes.
	 */
	public function enqueue_assets(): void {
		if ( '' === $this->route_template() ) {
			return;
		}

		wp_enqueue_style( Assets::STYLE_FRONTEND );
		wp_enqueue_script( Assets::SCRIPT_FRONTEND );
	}

	/**
	 * The template name for the current request ('' when the request
	 * is not a plugin route).
	 */
	private function route_template(): string {
		if ( is_singular( VisaPostType::NAME ) ) {
			return 'single-visa.php';
		}

		if ( is_singular( CountryPostType::NAME ) ) {
			return 'single-country.php';
		}

		if ( is_singular( TourPostType::NAME ) ) {
			return 'single-tour.php';
		}

		if ( is_post_type_archive( VisaPostType::NAME ) || is_tax( VisaTypeTaxonomy::NAME ) ) {
			return 'archive-visa.php';
		}

		if ( is_post_type_archive( TourPostType::NAME ) || is_tax( TourTypeTaxonomy::NAME ) ) {
			return 'archive-tour.php';
		}

		if ( is_post_type_archive( CountryPostType::NAME ) || is_tax( RegionTaxonomy::NAME ) ) {
			return 'archive-country.php';
		}

		return '';
	}

	/**
	 * Build the view-model for a template.
	 *
	 * @param string $name Template name.
	 *
	 * @return array<string, mixed>
	 */
	private function view_for( string $name ): array {
		$post_id = (int) get_queried_object_id();

		$view = match ( $name ) {
			'single-visa.php'    => $this->visas->page_data( $post_id ),
			'single-country.php' => $this->countries->page_data( $post_id ),
			'single-tour.php'    => $this->tours->page_data( $post_id ),
			'archive-visa.php'   => $this->archive_view( 'visa' ),
			'archive-tour.php'   => $this->archive_view( 'tour' ),
			default              => $this->archive_view( 'country' ),
		};

		/**
		 * Filter a frontend template's view-model.
		 *
		 * @param array<string, mixed> $view View-model.
		 * @param string               $name Template name.
		 */
		return (array) apply_filters( 'ztc_template_view', $view, $name );
	}

	/**
	 * View-model for an archive: pre-rendered cards for the main
	 * query plus the search form data.
	 *
	 * @param string $type Content type: tour|visa|country.
	 *
	 * @return array<string, mixed>
	 */
	private function archive_view( string $type ): array {
		global $wp_query;

		$posts = is_object( $wp_query ) && is_array( $wp_query->posts ?? null ) ? $wp_query->posts : array();

		return array(
			'type'   => $type,
			'cards'  => array_filter(
				array_map(
					fn( object $post ): string => $this->grids->card_for( $type, (int) $post->ID ),
					$posts
				)
			),
			'total'  => (int) ( $wp_query->found_posts ?? count( $posts ) ),
			'search' => $this->search->for_type( $type ),
		);
	}
}
