<?php
/**
 * Frontend integrations.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Frontend;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the settings-driven frontend output: brand-color CSS
 * variables, custom CSS (head) and JS (footer), Google Analytics and
 * Facebook Pixel snippets, and the floating WhatsApp button. Reads
 * everything through GlobalSettings/Config — no duplicated logic.
 */
final class Integrations implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param GlobalSettings $settings Global settings.
	 * @param Config         $config   Plugin configuration.
	 * @param Template       $template Template renderer.
	 */
	public function __construct(
		private GlobalSettings $settings,
		private Config $config,
		private Template $template,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'head_output' ), 20 );
		add_action( 'wp_footer', array( $this, 'footer_output' ), 20 );
	}

	/**
	 * Brand variables, custom CSS and tracking snippets.
	 */
	public function head_output(): void {
		$brand     = sanitize_hex_color( $this->settings->brand_color() );
		$secondary = sanitize_hex_color( $this->settings->secondary_color() );

		printf(
			'<style id="ztc-brand-vars">:root{--ztc-brand:%1$s;--ztc-secondary:%2$s;}</style>' . "\n",
			esc_html( $brand ? $brand : '#0d6efd' ),
			esc_html( $secondary ? $secondary : '#198754' )
		);

		$css = (string) $this->config->get( 'custom_code.css', '' );

		if ( '' !== $css ) {
			// The value is guarded against </style> injection on save.
			printf( "<style id=\"ztc-custom-css\">%s</style>\n", $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$ga_id = $this->settings->analytics_id();

		if ( '' !== $ga_id ) {
			// The gtag loader is Google's canonical inline snippet; it is
			// intentionally not enqueued (must sit in the head, unaltered).
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
			printf(
				'<script async src="https://www.googletagmanager.com/gtag/js?id=%1$s"></script>' . "\n" .
				'<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","%2$s");</script>' . "\n",
				esc_attr( $ga_id ),
				esc_js( $ga_id )
			);
			// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		}

		$pixel_id = $this->settings->pixel_id();

		if ( '' !== $pixel_id ) {
			printf(
				'<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","%1$s");fbq("track","PageView");</script>' . "\n" .
				'<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=%2$s&ev=PageView&noscript=1" alt=""></noscript>' . "\n",
				esc_js( $pixel_id ),
				esc_attr( rawurlencode( $pixel_id ) )
			);
		}
	}

	/**
	 * Custom JS and the floating WhatsApp button.
	 */
	public function footer_output(): void {
		$js = (string) $this->config->get( 'custom_code.js', '' );

		if ( '' !== $js ) {
			// The value is guarded against </script> injection on save.
			printf( "<script id=\"ztc-custom-js\">%s</script>\n", $js ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! $this->settings->floating_whatsapp_enabled() ) {
			return;
		}

		$link = $this->settings->whatsapp_link( $this->settings->whatsapp_default_message() );

		if ( '' === $link ) {
			return;
		}

		$this->template->render( 'frontend/parts/whatsapp-button.php', array( 'url' => $link ) );
	}
}
