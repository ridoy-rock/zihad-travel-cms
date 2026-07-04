<?php
/**
 * Shared media service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Services;

defined( 'ABSPATH' ) || exit;

/**
 * One place for attachment handling: hero images, galleries, flags,
 * icons and documents. Modules and cards never call attachment
 * functions directly, so sizes and markup stay consistent and can be
 * filtered centrally later.
 */
final class MediaService {

	public const SIZE_HERO    = 'full';
	public const SIZE_CARD    = 'large';
	public const SIZE_GALLERY = 'large';
	public const SIZE_FLAG    = 'thumbnail';
	public const SIZE_ICON    = 'thumbnail';

	/**
	 * URL of an image attachment, or '' when unavailable.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size          Registered image size.
	 */
	public function image_url( int $attachment_id, string $size = self::SIZE_CARD ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		return (string) wp_get_attachment_image_url( $attachment_id, $size );
	}

	/**
	 * Full <img> markup (srcset, lazy loading) for an attachment.
	 *
	 * @param int                   $attachment_id Attachment ID.
	 * @param string                $size          Registered image size.
	 * @param array<string, string> $attributes    Extra HTML attributes.
	 */
	public function image( int $attachment_id, string $size = self::SIZE_CARD, array $attributes = array() ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		return wp_get_attachment_image( $attachment_id, $size, false, $attributes );
	}

	/**
	 * Alt text of an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function alt( int $attachment_id ): string {
		return (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	}

	/**
	 * Hero image URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function hero_url( int $attachment_id ): string {
		return $this->image_url( $attachment_id, self::SIZE_HERO );
	}

	/**
	 * Flag image URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function flag_url( int $attachment_id ): string {
		return $this->image_url( $attachment_id, self::SIZE_FLAG );
	}

	/**
	 * Icon image URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function icon_url( int $attachment_id ): string {
		return $this->image_url( $attachment_id, self::SIZE_ICON );
	}

	/**
	 * URLs for a gallery of attachments, skipping broken IDs.
	 *
	 * @param array<int> $attachment_ids Attachment IDs.
	 * @param string     $size           Registered image size.
	 *
	 * @return array<string>
	 */
	public function gallery_urls( array $attachment_ids, string $size = self::SIZE_GALLERY ): array {
		$urls = array_map(
			fn( int $id ): string => $this->image_url( $id, $size ),
			$attachment_ids
		);

		return array_values( array_filter( $urls ) );
	}

	/**
	 * Download URL for a document attachment (PDF forms, checklists…).
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function document_url( int $attachment_id ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		return (string) wp_get_attachment_url( $attachment_id );
	}
}
