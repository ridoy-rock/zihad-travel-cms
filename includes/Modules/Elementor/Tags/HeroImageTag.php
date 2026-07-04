<?php
/**
 * Hero image dynamic tag.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * An image dynamic tag returning the current post's hero image
 * (`ztc_hero_image`), falling back to the featured image — usable in
 * any Elementor image control.
 */
final class HeroImageTag extends Data_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-hero-image';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Travel CMS Hero Image', 'zihad-travel-cms' );
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
		return array( Module::IMAGE_CATEGORY );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string, mixed> $options Tag options.
	 */
	public function get_value( array $options = array() ) {
		$post_id = (int) get_the_ID();

		$attachment_id = (int) get_post_meta( $post_id, 'ztc_hero_image', true );

		if ( $attachment_id <= 0 ) {
			$attachment_id = (int) get_post_thumbnail_id( $post_id );
		}

		return array(
			'id'  => $attachment_id,
			'url' => $attachment_id > 0 ? (string) wp_get_attachment_image_url( $attachment_id, 'full' ) : '',
		);
	}
}
