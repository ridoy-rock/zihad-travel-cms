<?php
/**
 * Frontend shortcodes.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Frontend;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Assets;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Views\Cards\CtaCard;
use ZihadTravelCMS\Views\GridRenderer;
use ZihadTravelCMS\Views\SearchFormData;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes wrapping the shared view components:
 *
 *  [ztc_tours count="6" columns="3" type="adventure" region="asia" country="0" heading=""]
 *  [ztc_visas count="6" columns="3" type="tourist" country="0" heading=""]
 *  [ztc_countries count="6" columns="3" region="asia" heading=""]
 *  [ztc_search type="tour"]
 *  [ztc_cta title="" text="" button_text="" button_url=""]
 */
final class Shortcodes implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param GridRenderer   $grids    Card grid renderer.
	 * @param SearchFormData $search   Search form view-models.
	 * @param CtaCard        $cta      CTA card.
	 * @param Template       $template Template renderer.
	 */
	public function __construct(
		private GridRenderer $grids,
		private SearchFormData $search,
		private CtaCard $cta,
		private Template $template,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_shortcode( 'ztc_tours', array( $this, 'tours' ) );
		add_shortcode( 'ztc_visas', array( $this, 'visas' ) );
		add_shortcode( 'ztc_countries', array( $this, 'countries' ) );
		add_shortcode( 'ztc_search', array( $this, 'search_form' ) );
		add_shortcode( 'ztc_cta', array( $this, 'cta' ) );
	}

	/**
	 * [ztc_tours] — a grid of tour cards.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function tours( array|string $atts ): string {
		return $this->grid( 'tour', $atts, 'ztc_tours' );
	}

	/**
	 * [ztc_visas] — a grid of visa cards.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function visas( array|string $atts ): string {
		return $this->grid( 'visa', $atts, 'ztc_visas' );
	}

	/**
	 * [ztc_countries] — a grid of country cards.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function countries( array|string $atts ): string {
		return $this->grid( 'country', $atts, 'ztc_countries' );
	}

	/**
	 * [ztc_search] — the AJAX search/filter form plus a results grid.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function search_form( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'type'    => 'tour',
				'count'   => 9,
				'columns' => 3,
			),
			is_array( $atts ) ? $atts : array(),
			'ztc_search'
		);

		$this->enqueue();

		$type = isset( GridRenderer::TYPES[ $atts['type'] ] ) ? $atts['type'] : 'tour';

		return $this->template->get( 'frontend/parts/search-form.php', $this->search->for_type( $type ) )
			. $this->grids->render(
				$type,
				array(
					'count'   => (int) $atts['count'],
					'columns' => (int) $atts['columns'],
				)
			);
	}

	/**
	 * [ztc_cta] — a call-to-action panel with global-settings fallbacks.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function cta( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'title'       => '',
				'text'        => '',
				'button_text' => '',
				'button_url'  => '',
			),
			is_array( $atts ) ? $atts : array(),
			'ztc_cta'
		);

		$this->enqueue();

		// Empty attributes fall back to the card's global-settings defaults.
		return $this->cta->render( 0, array_filter( $atts, static fn( string $value ): bool => '' !== $value ) );
	}

	/**
	 * Shared grid shortcode handler.
	 *
	 * @param string                       $type Content type.
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @param string                       $tag  Shortcode tag (for the atts filter).
	 */
	private function grid( string $type, array|string $atts, string $tag ): string {
		$atts = shortcode_atts(
			array(
				'count'   => 6,
				'columns' => 3,
				'type'    => '',
				'region'  => '',
				'country' => 0,
				'heading' => '',
			),
			is_array( $atts ) ? $atts : array(),
			$tag
		);

		$this->enqueue();

		return $this->grids->render(
			$type,
			array(
				'count'   => (int) $atts['count'],
				'columns' => (int) $atts['columns'],
				'term'    => (string) $atts['type'],
				'region'  => (string) $atts['region'],
				'country' => (int) $atts['country'],
				'heading' => (string) $atts['heading'],
			)
		);
	}

	/**
	 * Enqueue frontend assets when a shortcode renders.
	 */
	private function enqueue(): void {
		wp_enqueue_style( Assets::STYLE_FRONTEND );
		wp_enqueue_script( Assets::SCRIPT_FRONTEND );
	}
}
