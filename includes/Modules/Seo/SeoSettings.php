<?php
/**
 * SEO settings schema and tab.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Seo;

use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\TextareaField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\ToggleField;
use ZihadTravelCMS\Admin\UI\Tab;
use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `seo.*` settings section through the existing
 * extension seams: `ztc_default_settings` adds the schema (so the
 * structural SettingsSanitizer and the REST settings API accept the
 * keys on every write path) and `ztc_settings_tabs` appends the SEO
 * tab, built from the shared field components.
 *
 * This component registers even while a third-party SEO plugin is
 * active — dropping the schema would make SettingsSanitizer silently
 * discard saved SEO settings on the next settings write.
 */
final class SeoSettings implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'ztc_default_settings', array( $this, 'defaults' ) );
		add_filter( 'ztc_settings_tabs', array( $this, 'add_tab' ) );

		// Anything that read settings before this filter attached cached
		// a defaults merge without the seo section — recompute lazily.
		$this->config->refresh();
	}

	/**
	 * Add the `seo` section to the settings schema.
	 *
	 * @param array<string, mixed> $defaults Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults( array $defaults ): array {
		$defaults['seo'] = array(
			'enabled'                     => true,
			'title_separator'             => '–',
			'default_description'         => '',
			'default_keywords'            => '',
			'default_og_image'            => 0,
			'twitter_handle'              => '',
			'og_enabled'                  => true,
			'twitter_enabled'             => true,
			'schema_enabled'              => true,
			'noindex_archives'            => false,
			'archive_tour_title'          => '',
			'archive_tour_description'    => '',
			'archive_visa_title'          => '',
			'archive_visa_description'    => '',
			'archive_country_title'       => '',
			'archive_country_description' => '',
		);

		return $defaults;
	}

	/**
	 * Append the SEO tab to the settings screen.
	 *
	 * @param array<Tab> $tabs Settings tabs.
	 *
	 * @return array<Tab>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[] = new Tab( Tab::SEO, __( 'SEO', 'zihad-travel-cms' ), $this->fields() );

		return $tabs;
	}

	/**
	 * The SEO tab's fields (shared components, dot-key names).
	 *
	 * @return array<\ZihadTravelCMS\Admin\UI\Fields\BaseField>
	 */
	private function fields(): array {
		return array(
			new ToggleField(
				'seo.enabled',
				__( 'SEO Output', 'zihad-travel-cms' ),
				array(
					'checkbox_label' => __( 'Render meta tags, social tags and structured data on Travel CMS pages', 'zihad-travel-cms' ),
					'description'    => __( 'Automatically disabled while Yoast SEO or Rank Math is active.', 'zihad-travel-cms' ),
				)
			),
			new SelectField(
				'seo.title_separator',
				__( 'Title Separator', 'zihad-travel-cms' ),
				array(
					'options' => array(
						'–' => '–',
						'—' => '—',
						'|' => '|',
						'·' => '·',
						'»' => '»',
					),
					'default' => '–',
				)
			),
			new TextareaField(
				'seo.default_description',
				__( 'Default Meta Description', 'zihad-travel-cms' ),
				array(
					'rows'        => 3,
					'description' => __( 'Used when a page has no description of its own. Aim for 150–160 characters.', 'zihad-travel-cms' ),
				)
			),
			new TextField(
				'seo.default_keywords',
				__( 'Default Keywords', 'zihad-travel-cms' ),
				array( 'description' => __( 'Comma-separated. Used when a page has no keywords of its own.', 'zihad-travel-cms' ) )
			),
			new MediaField(
				'seo.default_og_image',
				__( 'Default Social Image', 'zihad-travel-cms' ),
				array( 'description' => __( 'Shown when a page has no image of its own (recommended 1200×630).', 'zihad-travel-cms' ) )
			),
			new TextField(
				'seo.twitter_handle',
				__( 'X / Twitter Handle', 'zihad-travel-cms' ),
				array( 'placeholder' => '@youragency' )
			),
			new ToggleField(
				'seo.og_enabled',
				__( 'OpenGraph', 'zihad-travel-cms' ),
				array( 'checkbox_label' => __( 'Output OpenGraph tags (Facebook, WhatsApp, LinkedIn previews)', 'zihad-travel-cms' ) )
			),
			new ToggleField(
				'seo.twitter_enabled',
				__( 'Twitter Cards', 'zihad-travel-cms' ),
				array( 'checkbox_label' => __( 'Output Twitter Card tags', 'zihad-travel-cms' ) )
			),
			new ToggleField(
				'seo.schema_enabled',
				__( 'Structured Data', 'zihad-travel-cms' ),
				array( 'checkbox_label' => __( 'Output Schema.org JSON-LD (rich results)', 'zihad-travel-cms' ) )
			),
			new ToggleField(
				'seo.noindex_archives',
				__( 'Archive Indexing', 'zihad-travel-cms' ),
				array( 'checkbox_label' => __( 'Ask search engines not to index tour/visa/country archives', 'zihad-travel-cms' ) )
			),
			new TextField( 'seo.archive_tour_title', __( 'Tours Archive Title', 'zihad-travel-cms' ) ),
			new TextareaField( 'seo.archive_tour_description', __( 'Tours Archive Description', 'zihad-travel-cms' ), array( 'rows' => 2 ) ),
			new TextField( 'seo.archive_visa_title', __( 'Visas Archive Title', 'zihad-travel-cms' ) ),
			new TextareaField( 'seo.archive_visa_description', __( 'Visas Archive Description', 'zihad-travel-cms' ), array( 'rows' => 2 ) ),
			new TextField( 'seo.archive_country_title', __( 'Countries Archive Title', 'zihad-travel-cms' ) ),
			new TextareaField( 'seo.archive_country_description', __( 'Countries Archive Description', 'zihad-travel-cms' ), array( 'rows' => 2 ) ),
		);
	}
}
