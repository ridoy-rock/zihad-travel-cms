<?php
/**
 * Homepage search widget renderer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views;

use ZihadTravelCMS\Core\Assets;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Visa\VisaPostType;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The single render path for the tabbed Visa/Tour search widget —
 * shared verbatim by the [ztc_search_widget] shortcode, the Elementor
 * widget and the homepage auto-injection, so no markup is ever
 * duplicated. The HTML itself lives in the theme-overridable
 * `frontend/parts/search-widget.php` part.
 *
 * Tabs work without JavaScript (CSS-only radio tabs) and each form
 * submits to its type's archive, where ArchiveFilters applies the same
 * clauses server-side; with JavaScript the existing frontend.js drives
 * the forms through `GET ztc/v1/search` into the widget's results
 * container.
 */
final class SearchWidgetRenderer {

	/**
	 * Cached country select options (invalidated by SearchModule when
	 * a country is saved or deleted).
	 */
	public const COUNTRIES_TRANSIENT = 'ztc_country_options';

	/**
	 * Tab types the widget can render, in default order.
	 */
	public const TABS = array( 'visa', 'tour' );

	/**
	 * Per-request widget counter (unique ids for the radio tabs).
	 *
	 * @var int
	 */
	private int $instances = 0;

	/**
	 * Constructor.
	 *
	 * @param SearchFormData    $forms     Term lists per content type.
	 * @param CountryRepository $countries Country data access.
	 * @param GlobalSettings    $settings  Currency for budget labels.
	 * @param Template          $template  Template renderer.
	 */
	public function __construct(
		private SearchFormData $forms,
		private CountryRepository $countries,
		private GlobalSettings $settings,
		private Template $template,
	) {}

	/**
	 * Render the widget.
	 *
	 * @param array<string, mixed> $args `tabs` (array/CSV of visa|tour),
	 *                                   `default` (initially open tab),
	 *                                   `heading` (optional heading text).
	 */
	public function render( array $args = array() ): string {
		wp_enqueue_style( Assets::STYLE_FRONTEND );
		wp_enqueue_script( Assets::SCRIPT_FRONTEND );

		return $this->template->get( 'frontend/parts/search-widget.php', $this->data( $args ) );
	}

	/**
	 * Build the widget view-model (also used by the REST-less preview
	 * contexts — Elementor editor renders exactly this).
	 *
	 * @param array<string, mixed> $args See render().
	 *
	 * @return array<string, mixed>
	 */
	public function data( array $args = array() ): array {
		$requested = $args['tabs'] ?? self::TABS;
		$requested = is_string( $requested ) ? array_map( 'trim', explode( ',', $requested ) ) : (array) $requested;
		$tabs      = array_values( array_intersect( $requested, self::TABS ) );
		$tabs      = array() !== $tabs ? $tabs : self::TABS;

		$default = (string) ( $args['default'] ?? '' );
		$default = in_array( $default, $tabs, true ) ? $default : $tabs[0];

		// One country query for both tabs (and cached across requests).
		$countries = $this->country_options();

		$panels = array();

		foreach ( $tabs as $type ) {
			$panels[] = 'visa' === $type
				? $this->visa_tab( $countries )
				: $this->tour_tab( $countries );
		}

		++$this->instances;

		$data = array(
			'uid'     => 'ztc-search-widget-' . $this->instances,
			'heading' => (string) ( $args['heading'] ?? '' ),
			'default' => $default,
			'tabs'    => $panels,
		);

		/**
		 * Filter the search widget view-model before rendering.
		 *
		 * @param array<string, mixed> $data View-model.
		 * @param array<string, mixed> $args Render arguments.
		 */
		return (array) apply_filters( 'ztc_search_widget_data', $data, $args );
	}

	/**
	 * The visa tab: country + visa type.
	 *
	 * @param array<int, string> $countries Country options (ID => title).
	 *
	 * @return array<string, mixed>
	 */
	private function visa_tab( array $countries ): array {
		$form = $this->forms->for_type( 'visa' );

		return array(
			'type'        => 'visa',
			'label'       => __( 'Visas', 'zihad-travel-cms' ),
			'action'      => (string) get_post_type_archive_link( VisaPostType::NAME ),
			'placeholder' => (string) $form['placeholder'],
			'countries'   => $countries,
			'type_param'  => 'visa_type',
			'type_label'  => __( 'Visa type', 'zihad-travel-cms' ),
			'type_terms'  => (array) $form['type_terms'],
			'durations'   => array(),
			'budgets'     => array(),
		);
	}

	/**
	 * The tour tab: country + tour type + duration + budget.
	 *
	 * @param array<int, string> $countries Country options (ID => title).
	 *
	 * @return array<string, mixed>
	 */
	private function tour_tab( array $countries ): array {
		$form = $this->forms->for_type( 'tour' );

		return array(
			'type'        => 'tour',
			'label'       => __( 'Tours', 'zihad-travel-cms' ),
			'action'      => (string) get_post_type_archive_link( TourPostType::NAME ),
			'placeholder' => (string) $form['placeholder'],
			'countries'   => $countries,
			'type_param'  => 'tour_type',
			'type_label'  => __( 'Tour type', 'zihad-travel-cms' ),
			'type_terms'  => (array) $form['type_terms'],
			'durations'   => $this->durations(),
			'budgets'     => $this->budgets(),
		);
	}

	/**
	 * Published countries as select options (ID => title), cached in a
	 * transient so the widget never re-queries per render or per tab.
	 *
	 * @return array<int, string>
	 */
	public function country_options(): array {
		$cached = get_transient( self::COUNTRIES_TRANSIENT );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$options = array();

		foreach ( $this->countries->all(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		) as $country ) {
			$options[ (int) $country->ID ] = (string) $country->post_title;
		}

		set_transient( self::COUNTRIES_TRANSIENT, $options, 30 * MINUTE_IN_SECONDS );

		return $options;
	}

	/**
	 * Duration options: "min-max" day ranges (0 max = open-ended) as
	 * value => label.
	 *
	 * @return array<string, string>
	 */
	private function durations(): array {
		$durations = array(
			'1-3'  => __( '1–3 days', 'zihad-travel-cms' ),
			'4-7'  => __( '4–7 days', 'zihad-travel-cms' ),
			'8-14' => __( '8–14 days', 'zihad-travel-cms' ),
			'15-0' => __( '15+ days', 'zihad-travel-cms' ),
		);

		/**
		 * Filter the search widget duration options.
		 *
		 * @param array<string, string> $durations "min-max" => label.
		 */
		return (array) apply_filters( 'ztc_search_widget_durations', $durations );
	}

	/**
	 * Budget options: "min-max" price ranges in the site currency
	 * (0 max = open-ended) as value => label.
	 *
	 * @return array<string, string>
	 */
	private function budgets(): array {
		/**
		 * Filter the budget thresholds (ascending numbers in the site
		 * currency) the widget builds its ranges from.
		 *
		 * @param array<float> $steps Thresholds.
		 */
		$steps = (array) apply_filters( 'ztc_search_widget_budget_steps', array( 500.0, 1000.0, 2000.0 ) );
		$steps = array_values( array_filter( array_map( 'floatval', $steps ) ) );
		sort( $steps );

		$currency = $this->settings->default_currency();
		$budgets  = array();
		$floor    = 0.0;

		foreach ( $steps as $step ) {
			$budgets[ $this->range_value( $floor, $step ) ] = sprintf(
				/* translators: 1: currency code, 2: amount. */
				__( 'Up to %1$s %2$s', 'zihad-travel-cms' ),
				$currency,
				number_format_i18n( $step )
			);
			$floor = $step;
		}

		if ( $floor > 0 ) {
			$budgets[ $this->range_value( $floor, 0.0 ) ] = sprintf(
				/* translators: 1: currency code, 2: amount. */
				__( '%1$s %2$s and up', 'zihad-travel-cms' ),
				$currency,
				number_format_i18n( $floor )
			);
		}

		/**
		 * Filter the search widget budget options.
		 *
		 * @param array<string, string> $budgets "min-max" => label.
		 */
		return (array) apply_filters( 'ztc_search_widget_budgets', $budgets );
	}

	/**
	 * Format a range as the "min-max" value the search API expects.
	 *
	 * @param float $min Lower bound.
	 * @param float $max Upper bound (0 = open).
	 */
	private function range_value( float $min, float $max ): string {
		$format = static fn( float $number ): string => rtrim( rtrim( sprintf( '%.2F', $number ), '0' ), '.' );

		return $format( $min ) . '-' . $format( $max );
	}
}
