<?php
/**
 * Country post type.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\PostTypes\BasePostType;

defined( 'ABSPATH' ) || exit;

/**
 * The Country destination post type. Archive and singles live at
 * /country/.
 */
final class CountryPostType extends BasePostType {

	/**
	 * The post type name.
	 */
	public const NAME = 'ztc_country';

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
		return __( 'Country', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Countries', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			'menu_icon'     => 'dashicons-admin-site-alt3',
			'menu_position' => 31,
			// No 'custom-fields': the raw Custom Fields metabox would
			// duplicate the tabbed Country editor.
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
		);
	}
}
