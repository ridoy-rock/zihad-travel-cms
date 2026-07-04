<?php
/**
 * Inquiry meta fields.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\PostTypes\BasePostMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the inquiry's data fields. Unlike the content types these
 * are NOT exposed to the REST API — inquiries carry visitors' personal
 * data and never leave wp-admin.
 */
final class InquiryMeta extends BasePostMeta {

	public const NAME    = 'ztc_inquiry_name';
	public const EMAIL   = 'ztc_inquiry_email';
	public const PHONE   = 'ztc_inquiry_phone';
	public const MESSAGE = 'ztc_inquiry_message';
	public const TYPE    = 'ztc_inquiry_type';    // visa|tour.
	public const SUBJECT = 'ztc_inquiry_subject'; // Related visa/tour post ID.
	public const STATUS  = 'ztc_inquiry_status';  // new|contacted|closed.

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return InquiryPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fields(): array {
		$private = array( 'show_in_rest' => false );

		return array(
			self::NAME    => $this->string_field() + $private,
			self::EMAIL   => $this->email_field() + $private,
			self::PHONE   => $this->string_field() + $private,
			self::MESSAGE => $this->textarea_field() + $private,
			self::TYPE    => $this->string_field() + $private,
			self::SUBJECT => $this->int_field() + $private,
			self::STATUS  => $this->string_field() + $private,
		);
	}
}
