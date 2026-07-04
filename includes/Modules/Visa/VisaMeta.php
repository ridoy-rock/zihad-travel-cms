<?php
/**
 * Visa meta fields.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\PostTypes\BasePostMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every Visa data field as typed, sanitized post meta.
 * Repositories and services reference the key constants — never raw
 * strings.
 *
 * The visa's category is the Visa Type taxonomy, not meta; the linked
 * country is a post relation to the Country post type.
 */
final class VisaMeta extends BasePostMeta {

	public const COUNTRY             = 'ztc_country';
	public const HERO_IMAGE          = 'ztc_hero_image';
	public const PROCESSING_TIME     = 'ztc_processing_time';
	public const VALIDITY            = 'ztc_validity';
	public const STAY_DURATION       = 'ztc_stay_duration';
	public const ENTRY_TYPE          = 'ztc_entry_type';
	public const FEE                 = 'ztc_visa_fee';
	public const REQUIREMENTS        = 'ztc_requirements';
	public const REQUIRED_DOCUMENTS  = 'ztc_required_documents';
	public const BENEFITS            = 'ztc_benefits';
	public const APPLICATION_PROCESS = 'ztc_application_process';
	public const FAQ                 = 'ztc_faq';
	public const IMPORTANT_NOTES     = 'ztc_important_notes';
	public const WHATSAPP            = 'ztc_whatsapp_number';
	public const APPLY_BUTTON_TEXT   = 'ztc_apply_button_text';
	public const SEO                 = 'ztc_seo';

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return VisaPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fields(): array {
		return array(
			self::COUNTRY             => $this->int_field(), // Country post ID.
			self::HERO_IMAGE          => $this->int_field(), // Attachment ID.
			self::PROCESSING_TIME     => $this->string_field(),
			self::VALIDITY            => $this->string_field(),
			self::STAY_DURATION       => $this->string_field(),
			self::ENTRY_TYPE          => $this->string_field(), // e.g. single / multiple.
			self::FEE                 => $this->string_field(), // Free-form: "USD 50 + service".
			self::REQUIREMENTS        => $this->rich_text_field(),
			self::REQUIRED_DOCUMENTS  => $this->string_list_field(),
			self::BENEFITS            => $this->string_list_field(),
			self::APPLICATION_PROCESS => $this->object_list_field(
				array(
					'title'       => 'text',
					'description' => 'rich',
				)
			),
			self::FAQ                 => $this->object_list_field(
				array(
					'question' => 'text',
					'answer'   => 'rich',
				)
			),
			self::IMPORTANT_NOTES     => $this->rich_text_field(),
			self::WHATSAPP            => $this->string_field(),
			self::APPLY_BUTTON_TEXT   => $this->string_field(),
			self::SEO                 => $this->seo_field(),
		);
	}
}
