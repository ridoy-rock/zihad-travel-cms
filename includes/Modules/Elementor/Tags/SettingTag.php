<?php
/**
 * Global setting dynamic tag.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * A text dynamic tag exposing the agency's global settings (company
 * identity, contact channels, branding) to any Elementor text control
 * — all values read through GlobalSettings.
 */
final class SettingTag extends Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-setting';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Travel CMS Setting', 'zihad-travel-cms' );
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
		return array( Module::TEXT_CATEGORY, Module::URL_CATEGORY );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function register_controls() {
		$this->add_control(
			'setting',
			array(
				'label'   => __( 'Setting', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'company_name',
				'options' => array(
					'company_name'  => __( 'Company Name', 'zihad-travel-cms' ),
					'email'         => __( 'Contact Email', 'zihad-travel-cms' ),
					'phone'         => __( 'Phone', 'zihad-travel-cms' ),
					'hotline'       => __( 'Hotline', 'zihad-travel-cms' ),
					'address'       => __( 'Office Address', 'zihad-travel-cms' ),
					'whatsapp'      => __( 'WhatsApp Number', 'zihad-travel-cms' ),
					'whatsapp_link' => __( 'WhatsApp Link', 'zihad-travel-cms' ),
					'currency'      => __( 'Default Currency', 'zihad-travel-cms' ),
					'brand_color'   => __( 'Brand Color', 'zihad-travel-cms' ),
				),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render() {
		$settings = ztc()->get( GlobalSettings::class );

		$value = match ( (string) $this->get_settings( 'setting' ) ) {
			'email'         => $settings->email(),
			'phone'         => $settings->phone(),
			'hotline'       => $settings->hotline(),
			'address'       => $settings->address(),
			'whatsapp'      => $settings->whatsapp(),
			'whatsapp_link' => $settings->whatsapp_link( $settings->whatsapp_default_message() ),
			'currency'      => $settings->default_currency(),
			'brand_color'   => $settings->brand_color(),
			default         => $settings->company_name(),
		};

		echo esc_html( $value );
	}
}
