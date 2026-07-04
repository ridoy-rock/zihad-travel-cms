<?php
/**
 * Inquiry post type.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Admin\Pages\AdminPage;
use ZihadTravelCMS\PostTypes\BasePostType;

defined( 'ABSPATH' ) || exit;

/**
 * Visa/tour inquiries as a private post type: no frontend presence, no
 * REST exposure (inquiries are personal data), listed under the Travel
 * CMS menu. Records are created only by the inquiry pipeline — the
 * "Add New" UI is disabled.
 */
final class InquiryPostType extends BasePostType {

	/**
	 * The post type name.
	 */
	public const NAME = 'ztc_inquiry';

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
		return __( 'Inquiry', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Inquiries', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => AdminPage::MENU_SLUG,
			'show_in_rest'        => false, // Personal data stays off the API.
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				// Inquiries arrive through the form pipeline only.
				'create_posts' => 'do_not_allow',
			),
		);
	}
}
