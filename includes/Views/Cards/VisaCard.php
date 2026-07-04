<?php
/**
 * Visa card.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views\Cards;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Visa\VisaService;

defined( 'ABSPATH' ) || exit;

/**
 * Renders one visa as a Bootstrap card: country, type badges, key
 * facts (processing time, validity, fee) and the apply button.
 */
final class VisaCard extends BaseCard {

	/**
	 * Constructor.
	 *
	 * @param Template    $template Template renderer.
	 * @param VisaService $visas    Visa service.
	 */
	public function __construct( Template $template, private VisaService $visas ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function template_name(): string {
		return 'visa-card.php';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function view_data( int $post_id, array $options ): array {
		return array_merge( $this->visas->card_data( $post_id ), $options );
	}
}
