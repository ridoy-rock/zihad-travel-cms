<?php
/**
 * Travel CMS field dynamic tag.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use ZihadTravelCMS\Modules\Tour\TourService;

defined( 'ABSPATH' ) || exit;

/**
 * A text dynamic tag exposing plugin fields to any Elementor text
 * control — visa facts, tour pricing, country facts. Formatted values
 * (price, duration) go through the module services so they match the
 * templates.
 */
final class FieldTag extends Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-field';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Travel CMS Field', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_group() {
		return 'zihad-travel-cms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_categories() {
		return array( Module::TEXT_CATEGORY );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function register_controls() {
		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ztc_visa_fee',
				'options' => array(
					// Visa.
					'ztc_processing_time'    => __( 'Visa: Processing Time', 'zihad-travel-cms' ),
					'ztc_validity'           => __( 'Visa: Validity', 'zihad-travel-cms' ),
					'ztc_stay_duration'      => __( 'Visa: Stay Duration', 'zihad-travel-cms' ),
					'ztc_entry_type'         => __( 'Visa: Entry Type', 'zihad-travel-cms' ),
					'ztc_visa_fee'           => __( 'Visa: Fee', 'zihad-travel-cms' ),
					'ztc_apply_button_text'  => __( 'Visa: Apply Button Text', 'zihad-travel-cms' ),
					// Tour (formatted by TourService).
					'tour_price'             => __( 'Tour: Price (formatted)', 'zihad-travel-cms' ),
					'tour_duration'          => __( 'Tour: Duration (formatted)', 'zihad-travel-cms' ),
					// Country.
					'ztc_bangla_name'        => __( 'Country: Bangla Name', 'zihad-travel-cms' ),
					'ztc_hero_subtitle'      => __( 'Country: Hero Subtitle', 'zihad-travel-cms' ),
					'ztc_capital'            => __( 'Country: Capital', 'zihad-travel-cms' ),
					'ztc_currency'           => __( 'Country: Currency', 'zihad-travel-cms' ),
					'ztc_language'           => __( 'Country: Language', 'zihad-travel-cms' ),
					'ztc_timezone'           => __( 'Country: Timezone', 'zihad-travel-cms' ),
					'ztc_best_time_to_visit' => __( 'Country: Best Time to Visit', 'zihad-travel-cms' ),
					'ztc_embassy_name'       => __( 'Country: Embassy Name', 'zihad-travel-cms' ),
					'ztc_embassy_phone'      => __( 'Country: Embassy Phone', 'zihad-travel-cms' ),
					'ztc_embassy_email'      => __( 'Country: Embassy Email', 'zihad-travel-cms' ),
				),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render() {
		$post_id = (int) get_the_ID();

		if ( $post_id <= 0 ) {
			return;
		}

		$field = (string) $this->get_settings( 'field' );

		if ( 'tour_price' === $field ) {
			echo esc_html( ztc()->get( TourService::class )->formatted_price( $post_id ) );

			return;
		}

		if ( 'tour_duration' === $field ) {
			echo esc_html( ztc()->get( TourService::class )->duration_text( $post_id ) );

			return;
		}

		$value = get_post_meta( $post_id, $field, true );

		if ( is_array( $value ) ) {
			$value = implode( ', ', array_filter( $value, 'is_scalar' ) );
		}

		echo esc_html( (string) $value );
	}
}
