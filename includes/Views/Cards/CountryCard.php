<?php
/**
 * Country card.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views\Cards;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Country\CountryService;

defined( 'ABSPATH' ) || exit;

/**
 * Renders one country as a Bootstrap card: hero image, flag, region
 * badges and quick facts.
 */
final class CountryCard extends BaseCard {

	/**
	 * Constructor.
	 *
	 * @param Template       $template  Template renderer.
	 * @param CountryService $countries Country service.
	 */
	public function __construct( Template $template, private CountryService $countries ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function template_name(): string {
		return 'country-card.php';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function view_data( int $post_id, array $options ): array {
		return array_merge( $this->countries->card_data( $post_id ), $options );
	}
}
