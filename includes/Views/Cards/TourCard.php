<?php
/**
 * Tour card.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views\Cards;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Tour\TourService;

defined( 'ABSPATH' ) || exit;

/**
 * Renders one tour as a Bootstrap card: image, type badges, duration
 * and price (with sale strike-through).
 */
final class TourCard extends BaseCard {

	/**
	 * Constructor.
	 *
	 * @param Template    $template Template renderer.
	 * @param TourService $tours    Tour service.
	 */
	public function __construct( Template $template, private TourService $tours ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function template_name(): string {
		return 'tour-card.php';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function view_data( int $post_id, array $options ): array {
		return array_merge( $this->tours->card_data( $post_id ), $options );
	}
}
