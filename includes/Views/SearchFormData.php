<?php
/**
 * Search form view-model builder.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views;

use ZihadTravelCMS\Modules\Country\RegionTaxonomy;
use ZihadTravelCMS\Modules\Tour\TourTypeTaxonomy;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the data for the AJAX search/filter form (used by archive
 * templates and the [ztc_search] shortcode), so terms are queried in
 * one place and never inside a template.
 */
final class SearchFormData {

	/**
	 * View-model for a content type's search form.
	 *
	 * @param string $type Content type: tour|visa|country.
	 *
	 * @return array<string, mixed>
	 */
	public function for_type( string $type ): array {
		$type = isset( GridRenderer::TYPES[ $type ] ) ? $type : 'tour';

		$data = array(
			'type'        => $type,
			'show_price'  => 'tour' === $type,
			'type_param'  => '',
			'type_label'  => '',
			'type_terms'  => array(),
			'regions'     => array(),
			'placeholder' => __( 'Search…', 'zihad-travel-cms' ),
		);

		if ( 'tour' === $type ) {
			$data['type_param']  = 'tour_type';
			$data['type_label']  = __( 'Tour type', 'zihad-travel-cms' );
			$data['type_terms']  = $this->terms( TourTypeTaxonomy::NAME );
			$data['regions']     = $this->terms( RegionTaxonomy::NAME );
			$data['placeholder'] = __( 'Search tours…', 'zihad-travel-cms' );
		} elseif ( 'visa' === $type ) {
			$data['type_param']  = 'visa_type';
			$data['type_label']  = __( 'Visa type', 'zihad-travel-cms' );
			$data['type_terms']  = $this->terms( VisaTypeTaxonomy::NAME );
			$data['placeholder'] = __( 'Search visas…', 'zihad-travel-cms' );
		} else {
			$data['regions']     = $this->terms( RegionTaxonomy::NAME );
			$data['placeholder'] = __( 'Search countries…', 'zihad-travel-cms' );
		}

		return $data;
	}

	/**
	 * Non-empty terms as slug => name.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array<string, string>
	 */
	private function terms( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$options = array();

		foreach ( $terms as $term ) {
			$options[ (string) $term->slug ] = (string) $term->name;
		}

		return $options;
	}
}
