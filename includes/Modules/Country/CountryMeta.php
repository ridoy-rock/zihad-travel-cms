<?php
/**
 * Country meta fields.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\PostTypes\BasePostMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every Country data field as typed, sanitized post meta.
 * Repositories, services and the Country editor reference the key
 * constants — never raw strings.
 *
 * The country's region is the Region taxonomy, not meta.
 */
final class CountryMeta extends BasePostMeta {

	// General.
	public const BANGLA_NAME       = 'ztc_bangla_name';
	public const SHORT_DESCRIPTION = 'ztc_short_description';
	public const CURRENCY          = 'ztc_currency';
	public const CAPITAL           = 'ztc_capital';
	public const LANGUAGE          = 'ztc_language';
	public const TIMEZONE          = 'ztc_timezone';

	// Hero.
	public const HERO_IMAGE    = 'ztc_hero_image';
	public const FLAG          = 'ztc_flag';
	public const HERO_SUBTITLE = 'ztc_hero_subtitle';

	// Travel info.
	public const OVERVIEW       = 'ztc_overview';
	public const TRAVEL_TIPS    = 'ztc_travel_tips';
	public const BEST_TIME      = 'ztc_best_time_to_visit';
	public const POPULAR_CITIES = 'ztc_popular_cities';

	// Embassy.
	public const EMBASSY_NAME    = 'ztc_embassy_name';
	public const EMBASSY_ADDRESS = 'ztc_embassy_address';
	public const EMBASSY_PHONE   = 'ztc_embassy_phone';
	public const EMBASSY_EMAIL   = 'ztc_embassy_email';
	public const EMBASSY_WEBSITE = 'ztc_embassy_website';

	// Content blocks.
	public const GALLERY = 'ztc_gallery';
	public const FAQ     = 'ztc_faq';
	public const SEO     = 'ztc_seo';

	// Settings.
	public const FEATURED         = 'ztc_featured';
	public const SHOW_ON_HOMEPAGE = 'ztc_show_on_homepage';

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return CountryPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fields(): array {
		return array(
			// General.
			self::BANGLA_NAME       => $this->string_field(),
			self::SHORT_DESCRIPTION => $this->textarea_field(),
			self::CURRENCY          => $this->string_field(),
			self::CAPITAL           => $this->string_field(),
			self::LANGUAGE          => $this->string_field(),
			self::TIMEZONE          => $this->string_field(),
			// Hero.
			self::HERO_IMAGE        => $this->int_field(), // Attachment ID.
			self::FLAG              => $this->int_field(), // Attachment ID.
			self::HERO_SUBTITLE     => $this->string_field(),
			// Travel info.
			self::OVERVIEW          => $this->rich_text_field(),
			self::TRAVEL_TIPS       => $this->rich_text_field(),
			self::BEST_TIME         => $this->string_field(),
			self::POPULAR_CITIES    => $this->string_list_field(),
			// Embassy.
			self::EMBASSY_NAME      => $this->string_field(),
			self::EMBASSY_ADDRESS   => $this->textarea_field(),
			self::EMBASSY_PHONE     => $this->string_field(),
			self::EMBASSY_EMAIL     => $this->email_field(),
			self::EMBASSY_WEBSITE   => $this->url_field(),
			// Content blocks.
			self::GALLERY           => $this->int_list_field(), // Attachment IDs.
			self::FAQ               => $this->object_list_field(
				array(
					'question' => 'text',
					'answer'   => 'rich',
				)
			),
			self::SEO               => $this->seo_field(),
			// Settings.
			self::FEATURED          => $this->bool_field(),
			self::SHOW_ON_HOMEPAGE  => $this->bool_field(),
		);
	}
}
