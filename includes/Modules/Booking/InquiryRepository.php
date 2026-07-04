<?php
/**
 * Inquiry repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Data\BaseRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Data access for inquiries — the only layer touching WordPress data
 * APIs for the inquiry post type.
 */
final class InquiryRepository extends BaseRepository {

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return InquiryPostType::NAME;
	}

	/**
	 * Persist a sanitized inquiry.
	 *
	 * @param array<string, mixed> $data Sanitized fields: name, email,
	 *                                   phone, message, type, subject
	 *                                   (post ID), subject_title.
	 *
	 * @return int The new inquiry ID (0 on failure).
	 */
	public function create( array $data ): int {
		$title = '' !== (string) ( $data['subject_title'] ?? '' )
			/* translators: 1: visitor name, 2: visa/tour title. */
			? sprintf( __( '%1$s — %2$s', 'zihad-travel-cms' ), (string) $data['name'], (string) $data['subject_title'] )
			: (string) $data['name'];

		$inquiry_id = wp_insert_post(
			array(
				'post_type'   => InquiryPostType::NAME,
				'post_status' => 'publish', // Non-public CPT: admin-only either way.
				'post_title'  => $title,
			)
		);

		if ( ! is_int( $inquiry_id ) || $inquiry_id <= 0 ) {
			return 0;
		}

		$this->save_meta( $inquiry_id, InquiryMeta::NAME, (string) $data['name'] );
		$this->save_meta( $inquiry_id, InquiryMeta::EMAIL, (string) $data['email'] );
		$this->save_meta( $inquiry_id, InquiryMeta::PHONE, (string) ( $data['phone'] ?? '' ) );
		$this->save_meta( $inquiry_id, InquiryMeta::MESSAGE, (string) $data['message'] );
		$this->save_meta( $inquiry_id, InquiryMeta::TYPE, (string) $data['type'] );
		$this->save_meta( $inquiry_id, InquiryMeta::SUBJECT, (int) ( $data['subject'] ?? 0 ) );
		$this->save_meta( $inquiry_id, InquiryMeta::STATUS, 'new' );

		return $inquiry_id;
	}
}
