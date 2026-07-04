<?php
/**
 * Tour post type.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\PostTypes\BasePostType;

defined( 'ABSPATH' ) || exit;

/**
 * The Tour package post type. Archive and singles live at /tour/.
 */
final class TourPostType extends BasePostType {

	/**
	 * The post type name.
	 */
	public const NAME = 'ztc_tour';

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return self::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function singular_label(): string {
		return __( 'Tour', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Tours', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			'menu_icon'     => 'dashicons-palmtree',
			'menu_position' => 30,
			// No 'custom-fields': the raw Custom Fields metabox would
			// duplicate the tabbed Tour editor.
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
		);
	}
}
