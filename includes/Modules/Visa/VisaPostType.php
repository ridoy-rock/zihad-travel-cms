<?php
/**
 * Visa post type.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\PostTypes\BasePostType;

defined( 'ABSPATH' ) || exit;

/**
 * The Visa service post type. Archive and singles live at /visa/.
 */
final class VisaPostType extends BasePostType {

	/**
	 * The post type name.
	 */
	public const NAME = 'ztc_visa';

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
		return __( 'Visa', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Visas', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			'menu_icon'     => 'dashicons-id-alt',
			'menu_position' => 32,
			// No 'custom-fields': the raw Custom Fields metabox would
			// duplicate the tabbed Visa editor.
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
		);
	}
}
