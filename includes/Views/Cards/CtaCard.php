<?php
/**
 * Call-to-action card.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views\Cards;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a call-to-action panel. Not tied to a post: title, text and
 * button come from render options, contact details fall back to the
 * agency's global settings (WhatsApp, phone).
 */
final class CtaCard extends BaseCard {

	/**
	 * Constructor.
	 *
	 * @param Template       $template Template renderer.
	 * @param GlobalSettings $settings Global settings.
	 */
	public function __construct( Template $template, private GlobalSettings $settings ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function template_name(): string {
		return 'cta-card.php';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function view_data( int $post_id, array $options ): array {
		$defaults = array(
			'title'        => __( 'Need help planning your trip?', 'zihad-travel-cms' ),
			'text'         => __( 'Talk to our travel experts and get a free consultation.', 'zihad-travel-cms' ),
			'button_text'  => __( 'Contact Us', 'zihad-travel-cms' ),
			'button_url'   => '',
			'whatsapp_url' => $this->settings->whatsapp_link(),
			'phone'        => $this->settings->phone(),
			'brand_color'  => $this->settings->brand_color(),
		);

		return array_merge( $defaults, $options );
	}
}
