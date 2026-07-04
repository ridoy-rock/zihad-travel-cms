<?php
/**
 * Global (company-wide) settings service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Settings;

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Str;
use ZihadTravelCMS\Services\MediaService;

defined( 'ABSPATH' ) || exit;

/**
 * Typed access to the agency's global identity: company details,
 * contact channels, branding and site-wide defaults. Everything the
 * frontend, cards and future Elementor widgets fall back to when a
 * post has no specific value.
 */
final class GlobalSettings {

	/**
	 * Constructor.
	 *
	 * @param Config       $config Plugin configuration.
	 * @param MediaService $media  Media service.
	 */
	public function __construct(
		private Config $config,
		private MediaService $media,
	) {}

	/**
	 * Company name, falling back to the site title.
	 */
	public function company_name(): string {
		$name = (string) $this->config->get( 'company.name', '' );

		return '' !== $name ? $name : (string) get_bloginfo( 'name' );
	}

	/**
	 * WhatsApp number in international format.
	 */
	public function whatsapp(): string {
		return (string) $this->config->get( 'company.whatsapp', '' );
	}

	/**
	 * A wa.me chat link, optionally pre-filled with a message.
	 *
	 * @param string $message Optional pre-filled message.
	 */
	public function whatsapp_link( string $message = '' ): string {
		return Str::wa_me( $this->whatsapp(), $message );
	}

	/**
	 * Phone number.
	 */
	public function phone(): string {
		return (string) $this->config->get( 'company.phone', '' );
	}

	/**
	 * Contact email, falling back to the site admin email.
	 */
	public function email(): string {
		$email = (string) $this->config->get( 'company.email', '' );

		return '' !== $email ? $email : (string) get_bloginfo( 'admin_email' );
	}

	/**
	 * Postal address.
	 */
	public function address(): string {
		return (string) $this->config->get( 'company.address', '' );
	}

	/**
	 * Brand color as a hex value, e.g. `#0d6efd`.
	 */
	public function brand_color(): string {
		return (string) $this->config->get( 'company.brand_color', '#0d6efd' );
	}

	/**
	 * Logo attachment ID (0 when unset).
	 */
	public function logo_id(): int {
		return (int) $this->config->get( 'company.logo', 0 );
	}

	/**
	 * Logo image URL, or '' when unset.
	 *
	 * @param string $size Registered image size.
	 */
	public function logo_url( string $size = 'full' ): string {
		return $this->media->image_url( $this->logo_id(), $size );
	}

	/**
	 * Default currency code, e.g. `USD`.
	 */
	public function default_currency(): string {
		return (string) $this->config->get( 'general.currency', 'USD' );
	}

	/**
	 * Whether the currency renders before or after the amount.
	 */
	public function currency_position(): string {
		return 'after' === $this->config->get( 'general.currency_position' ) ? 'after' : 'before';
	}

	/**
	 * Default content language, falling back to the site locale.
	 */
	public function default_language(): string {
		$language = (string) $this->config->get( 'general.language', '' );

		return '' !== $language ? $language : get_locale();
	}

	/**
	 * Social profile URLs, keyed by network, empty entries removed.
	 *
	 * @return array<string, string>
	 */
	public function social_links(): array {
		$links = (array) $this->config->get( 'social', array() );

		return array_filter(
			array_map( 'strval', $links ),
			static fn( string $url ): bool => '' !== $url
		);
	}
}
