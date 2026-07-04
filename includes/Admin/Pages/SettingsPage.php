<?php
/**
 * Settings admin page.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\Pages;

use ZihadTravelCMS\Admin\UI\Fields\CodeField;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\NumberField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\TextareaField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\ToggleField;
use ZihadTravelCMS\Admin\UI\Fields\UrlField;
use ZihadTravelCMS\Admin\UI\Tab;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Arr;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Services\NotificationService;
use ZihadTravelCMS\Settings\SettingsSanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * The global settings screen: eleven built-in tabs (modules append
 * more via `ztc_settings_tabs`, e.g. the SEO tab) built from the same
 * Tab + field components the content editors use. Field names are the
 * Config dot keys, values load from Config, and saves run each
 * field's sanitize(), then the structural SettingsSanitizer, then one
 * batched option write — the same pipeline REST writes use.
 */
final class SettingsPage extends AdminPage {

	private const SAVE_ACTION = 'ztc_save_settings';

	/**
	 * Constructor.
	 *
	 * @param Template            $template      Template renderer.
	 * @param Config              $config        Plugin configuration.
	 * @param SettingsSanitizer   $sanitizer     Structural sanitizer.
	 * @param NotificationService $notifications Admin notices.
	 * @param CountryRepository   $countries     Country repository (default-country select).
	 */
	public function __construct(
		Template $template,
		private Config $config,
		private SettingsSanitizer $sanitizer,
		private NotificationService $notifications,
		private CountryRepository $countries,
	) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'zihad-travel-cms-settings';
	}

	/**
	 * {@inheritDoc}
	 */
	public function page_title(): string {
		return __( 'Travel CMS Settings', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function menu_title(): string {
		return __( 'Settings', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function position(): int {
		return 20;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		parent::register();
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'save' ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {
		wp_enqueue_media();

		printf( '<div class="wrap ztc-settings"><h1>%s</h1>', esc_html( $this->page_title() ) );
		printf( '<form method="post" action="%s">', esc_url( admin_url( 'admin-post.php' ) ) );
		printf( '<input type="hidden" name="action" value="%s">', esc_attr( self::SAVE_ACTION ) );
		wp_nonce_field( self::SAVE_ACTION );

		echo '<div class="ztc-editor" data-ztc-editor="settings">';
		echo '<div class="ztc-editor__tabs" role="tablist" aria-label="' . esc_attr( $this->page_title() ) . '">';

		$tabs = $this->tabs();

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<button type="button" class="ztc-editor__tab" role="tab" id="ztc-tab-settings-%1$s" aria-controls="ztc-panel-settings-%1$s" aria-selected="%2$s" tabindex="%3$s"><span class="ztc-editor__tab-text">%4$s</span></button>',
				esc_attr( $tab->id() ),
				0 === $index ? 'true' : 'false',
				0 === $index ? '0' : '-1',
				esc_html( $tab->label() )
			);
		}

		echo '</div><div class="ztc-editor__panels">';

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<div class="ztc-editor__panel" role="tabpanel" id="ztc-panel-settings-%1$s" aria-labelledby="ztc-tab-settings-%1$s" tabindex="0"%2$s>',
				esc_attr( $tab->id() ),
				0 === $index ? '' : ' hidden'
			);

			foreach ( $tab->fields() as $field ) {
				$field->render_row( $this->config->get( $field->name() ) );
			}

			echo '</div>';
		}

		echo '</div></div>';

		submit_button( __( 'Save Settings', 'zihad-travel-cms' ) );
		echo '</form></div>';
	}

	/**
	 * Handle the form submission (admin-post.php).
	 */
	public function save(): void {
		if ( ! check_admin_referer( self::SAVE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field sanitizes its own value in persist().
		$raw = isset( $_POST['ztc_fields'] ) && is_array( $_POST['ztc_fields'] ) ? wp_unslash( $_POST['ztc_fields'] ) : array();

		$this->persist( $raw );
		$this->notifications->success( __( 'Settings saved.', 'zihad-travel-cms' ) );

		wp_safe_redirect( (string) wp_get_referer() );
		exit;
	}

	/**
	 * Sanitize every field and write the settings in one batch.
	 *
	 * @param array<string, mixed> $raw Submitted ztc_fields values.
	 */
	public function persist( array $raw ): void {
		$settings = $this->config->all();

		foreach ( $this->tabs() as $tab ) {
			foreach ( $tab->fields() as $field ) {
				Arr::set( $settings, $field->name(), $field->sanitize( $raw[ $field->name() ] ?? null ) );
			}
		}

		update_option( Config::OPTION_NAME, $this->sanitizer->sanitize( $settings ) );
		$this->config->refresh();
	}

	/**
	 * The settings tabs: eleven built-in plus any appended through the
	 * `ztc_settings_tabs` filter.
	 *
	 * @return array<Tab>
	 */
	public function tabs(): array {
		$tabs = array(
			new Tab(
				'general',
				__( 'General', 'zihad-travel-cms' ),
				array(
					new TextField( 'general.currency', __( 'Default Currency', 'zihad-travel-cms' ), array( 'placeholder' => 'USD' ) ),
					new SelectField(
						'general.currency_position',
						__( 'Currency Position', 'zihad-travel-cms' ),
						array(
							'options' => array(
								'before' => __( 'Before amount (USD 100)', 'zihad-travel-cms' ),
								'after'  => __( 'After amount (100 USD)', 'zihad-travel-cms' ),
							),
							'default' => 'before',
						)
					),
					new TextField( 'general.date_format', __( 'Date Format', 'zihad-travel-cms' ), array( 'placeholder' => 'Y-m-d' ) ),
					new TextField(
						'general.language',
						__( 'Default Language', 'zihad-travel-cms' ),
						array(
							'placeholder' => 'en_US',
							'description' => __( 'Leave empty to use the site locale.', 'zihad-travel-cms' ),
						)
					),
					new SelectField(
						'general.default_country',
						__( 'Default Country', 'zihad-travel-cms' ),
						array(
							'options'     => $this->country_options(),
							'placeholder' => __( '— None —', 'zihad-travel-cms' ),
							'description' => __( 'Pre-selected in search forms and inquiry forms.', 'zihad-travel-cms' ),
						)
					),
				)
			),
			new Tab(
				'homepage',
				__( 'Homepage', 'zihad-travel-cms' ),
				array(
					new TextField( 'homepage.hero_title', __( 'Hero Title', 'zihad-travel-cms' ) ),
					new TextField( 'homepage.hero_subtitle', __( 'Hero Subtitle', 'zihad-travel-cms' ) ),
					new ToggleField(
						'homepage.show_search',
						__( 'Search Widget', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Show the search widget on the homepage', 'zihad-travel-cms' ) )
					),
					new NumberField( 'homepage.featured_countries_count', __( 'Featured Countries', 'zihad-travel-cms' ), array( 'min' => 0, 'max' => 24, 'step' => 1 ) ),
					new NumberField( 'homepage.popular_tours_count', __( 'Popular Tours', 'zihad-travel-cms' ), array( 'min' => 0, 'max' => 24, 'step' => 1 ) ),
				)
			),
			new Tab(
				'branding',
				__( 'Branding', 'zihad-travel-cms' ),
				array(
					new TextField( 'company.name', __( 'Company Name', 'zihad-travel-cms' ) ),
					new MediaField( 'company.logo', __( 'Logo', 'zihad-travel-cms' ) ),
					new TextField(
						'company.brand_color',
						__( 'Brand Color', 'zihad-travel-cms' ),
						array(
							'placeholder' => '#0d6efd',
							'description' => __( 'Hex value; exposed to the frontend as the --ztc-brand CSS variable.', 'zihad-travel-cms' ),
						)
					),
					new TextField( 'company.secondary_color', __( 'Secondary Color', 'zihad-travel-cms' ), array( 'placeholder' => '#198754' ) ),
				)
			),
			new Tab(
				'contact',
				__( 'Contact', 'zihad-travel-cms' ),
				array(
					new TextField( 'company.email', __( 'Contact Email', 'zihad-travel-cms' ) ),
					new TextField( 'company.phone', __( 'Phone', 'zihad-travel-cms' ) ),
					new TextField( 'company.hotline', __( 'Hotline', 'zihad-travel-cms' ) ),
					new TextareaField( 'company.address', __( 'Office Address', 'zihad-travel-cms' ), array( 'rows' => 3 ) ),
				)
			),
			new Tab(
				'social',
				__( 'Social Media', 'zihad-travel-cms' ),
				array(
					new UrlField( 'social.facebook', __( 'Facebook', 'zihad-travel-cms' ) ),
					new UrlField( 'social.instagram', __( 'Instagram', 'zihad-travel-cms' ) ),
					new UrlField( 'social.twitter', __( 'X / Twitter', 'zihad-travel-cms' ) ),
					new UrlField( 'social.youtube', __( 'YouTube', 'zihad-travel-cms' ) ),
					new UrlField( 'social.linkedin', __( 'LinkedIn', 'zihad-travel-cms' ) ),
					new UrlField( 'social.tiktok', __( 'TikTok', 'zihad-travel-cms' ) ),
				)
			),
			new Tab(
				'whatsapp',
				__( 'WhatsApp', 'zihad-travel-cms' ),
				array(
					new TextField(
						'company.whatsapp',
						__( 'WhatsApp Number', 'zihad-travel-cms' ),
						array( 'placeholder' => __( 'e.g. +8801XXXXXXXXX', 'zihad-travel-cms' ) )
					),
					new TextareaField(
						'whatsapp.default_message',
						__( 'Default Message', 'zihad-travel-cms' ),
						array(
							'rows'        => 3,
							'description' => __( 'Pre-filled when a visitor opens a WhatsApp chat.', 'zihad-travel-cms' ),
						)
					),
					new ToggleField(
						'whatsapp.floating_button',
						__( 'Floating Button', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Show a floating WhatsApp button on the frontend', 'zihad-travel-cms' ) )
					),
				)
			),
			new Tab(
				'maps',
				__( 'Maps', 'zihad-travel-cms' ),
				array(
					new TextField( 'integrations.maps_api_key', __( 'Google Maps API Key', 'zihad-travel-cms' ) ),
					new NumberField( 'integrations.maps_default_zoom', __( 'Default Zoom', 'zihad-travel-cms' ), array( 'min' => 1, 'max' => 20, 'step' => 1 ) ),
				)
			),
			new Tab(
				'analytics',
				__( 'Analytics', 'zihad-travel-cms' ),
				array(
					new TextField(
						'integrations.ga_id',
						__( 'Google Analytics ID', 'zihad-travel-cms' ),
						array( 'placeholder' => 'G-XXXXXXXXXX' )
					),
					new TextField( 'integrations.fb_pixel_id', __( 'Facebook Pixel ID', 'zihad-travel-cms' ) ),
				)
			),
			new Tab(
				'booking',
				__( 'Booking', 'zihad-travel-cms' ),
				array(
					new TextField(
						'booking.notification_email',
						__( 'Notification Email', 'zihad-travel-cms' ),
						array( 'description' => __( 'Where inquiry notifications are sent. Defaults to the contact email.', 'zihad-travel-cms' ) )
					),
					new ToggleField(
						'booking.enable_visa_inquiry',
						__( 'Visa Inquiries', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Enable visa inquiry forms', 'zihad-travel-cms' ) )
					),
					new ToggleField(
						'booking.enable_tour_inquiry',
						__( 'Tour Inquiries', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Enable tour inquiry forms', 'zihad-travel-cms' ) )
					),
					new TextareaField( 'booking.success_message', __( 'Success Message', 'zihad-travel-cms' ), array( 'rows' => 3 ) ),
				)
			),
			new Tab(
				'performance',
				__( 'Performance', 'zihad-travel-cms' ),
				array(
					new NumberField(
						'performance.cache_ttl',
						__( 'API Cache TTL (seconds)', 'zihad-travel-cms' ),
						array(
							'min'         => 0,
							'max'         => 86400,
							'step'        => 1,
							'description' => __( 'Cache-Control max-age for public REST responses (search results).', 'zihad-travel-cms' ),
						)
					),
					new ToggleField(
						'performance.lazy_load',
						__( 'Lazy Loading', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Lazy-load frontend images', 'zihad-travel-cms' ) )
					),
					new ToggleField(
						'performance.load_bootstrap',
						__( 'Bootstrap Assets', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Load the bundled Bootstrap 5 (disable if your theme ships Bootstrap)', 'zihad-travel-cms' ) )
					),
				)
			),
			new Tab(
				'custom-code',
				__( 'Custom CSS/JS', 'zihad-travel-cms' ),
				array(
					new CodeField(
						'custom_code.css',
						__( 'Custom CSS', 'zihad-travel-cms' ),
						array( 'description' => __( 'Printed in the site head on every page.', 'zihad-travel-cms' ) )
					),
					new CodeField(
						'custom_code.js',
						__( 'Custom JavaScript', 'zihad-travel-cms' ),
						array( 'description' => __( 'Printed before the closing body tag. Do not include <script> tags.', 'zihad-travel-cms' ) )
					),
				)
			),
		);

		/**
		 * Filter the settings tabs.
		 *
		 * @param array<Tab> $tabs Settings tabs.
		 */
		$tabs = apply_filters( 'ztc_settings_tabs', $tabs );

		return array_values( array_filter( (array) $tabs, static fn( $tab ): bool => $tab instanceof Tab ) );
	}

	/**
	 * Published countries as select options (ID => title).
	 *
	 * @return array<string, string>
	 */
	private function country_options(): array {
		$options = array();

		foreach ( $this->countries->all( array( 'orderby' => 'title', 'order' => 'ASC' ) ) as $country ) {
			$options[ (string) $country->ID ] = $country->post_title;
		}

		return $options;
	}
}
