<?php
/**
 * Abstract card component.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views\Cards;

use ZihadTravelCMS\Helpers\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for reusable card components.
 *
 * A card pairs a view-model (built by a module service) with a
 * Bootstrap 5 template in /templates/cards. The same cards will back
 * shortcodes, archive templates and Elementor widgets, so presentation
 * stays identical everywhere.
 */
abstract class BaseCard {

	/**
	 * Constructor.
	 *
	 * @param Template $template Template renderer.
	 */
	public function __construct( protected Template $template ) {}

	/**
	 * Template file inside /templates/cards, e.g. `tour-card.php`.
	 */
	abstract protected function template_name(): string;

	/**
	 * Build the view-model for the template.
	 *
	 * @param int                  $post_id Source post ID (0 for post-less cards).
	 * @param array<string, mixed> $options Render options / overrides.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function view_data( int $post_id, array $options ): array;

	/**
	 * Render the card to HTML.
	 *
	 * @param int                  $post_id Source post ID (0 for post-less cards).
	 * @param array<string, mixed> $options Render options / overrides.
	 */
	public function render( int $post_id = 0, array $options = array() ): string {
		return $this->template->get( 'cards/' . $this->template_name(), $this->view_data( $post_id, $options ) );
	}
}
