<?php
/**
 * Country editor.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Admin\UI\Editor;
use ZihadTravelCMS\Admin\UI\Fields\FaqField;
use ZihadTravelCMS\Admin\UI\Fields\GalleryField;
use ZihadTravelCMS\Admin\UI\Fields\ListField;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\RichEditorField;
use ZihadTravelCMS\Admin\UI\Fields\SeoField;
use ZihadTravelCMS\Admin\UI\Fields\TaxonomyField;
use ZihadTravelCMS\Admin\UI\Fields\TextareaField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\ToggleField;
use ZihadTravelCMS\Admin\UI\Fields\UrlField;
use ZihadTravelCMS\Admin\UI\Tab;

defined( 'ABSPATH' ) || exit;

/**
 * The tabbed Country editor.
 *
 * Purely declarative: every Country field is mapped onto the
 * framework's tabs and reusable components — no bespoke UI. Values
 * save through the registered `ztc_*` meta (Region through the
 * taxonomy), so everything is readable and writable via the REST API,
 * Gutenberg, Elementor dynamic tags and the future AI auto-fill.
 * Multilingual plugins duplicate the post per language (see
 * wpml-config.xml).
 */
final class CountryEditor extends Editor {

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return CountryPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function meta_box_title(): string {
		return __( 'Country Details', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tabs(): array {
		return array(
			$this->general_tab(),
			$this->hero_tab(),
			$this->travel_info_tab(),
			$this->embassy_tab(),
			$this->gallery_tab(),
			$this->faq_tab(),
			$this->seo_tab(),
			$this->settings_tab(),
		);
	}

	/**
	 * General: names, region and quick facts.
	 */
	private function general_tab(): Tab {
		return new Tab(
			Tab::GENERAL,
			__( 'General', 'zihad-travel-cms' ),
			array(
				new TextField(
					CountryMeta::BANGLA_NAME,
					__( 'Bangla Name', 'zihad-travel-cms' ),
					array( 'description' => __( 'The country name in Bangla, shown alongside the English title.', 'zihad-travel-cms' ) )
				),
				new TextareaField(
					CountryMeta::SHORT_DESCRIPTION,
					__( 'Short Description', 'zihad-travel-cms' ),
					array(
						'rows'        => 3,
						'description' => __( 'One or two sentences used on cards and archive listings.', 'zihad-travel-cms' ),
					)
				),
				new TaxonomyField(
					RegionTaxonomy::NAME,
					__( 'Region', 'zihad-travel-cms' ),
					array( 'description' => __( 'Hold Ctrl/Cmd to select multiple regions.', 'zihad-travel-cms' ) )
				),
				new TextField(
					CountryMeta::CURRENCY,
					__( 'Currency', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. Japanese Yen (JPY)', 'zihad-travel-cms' ) )
				),
				new TextField( CountryMeta::CAPITAL, __( 'Capital', 'zihad-travel-cms' ) ),
				new TextField( CountryMeta::LANGUAGE, __( 'Language', 'zihad-travel-cms' ) ),
				new TextField(
					CountryMeta::TIMEZONE,
					__( 'Timezone', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. GMT+9', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-info-outline'
		);
	}

	/**
	 * Hero: banner image, flag and subtitle.
	 */
	private function hero_tab(): Tab {
		return new Tab(
			Tab::HERO,
			__( 'Hero', 'zihad-travel-cms' ),
			array(
				new MediaField(
					CountryMeta::HERO_IMAGE,
					__( 'Hero Image', 'zihad-travel-cms' ),
					array( 'description' => __( 'Large banner shown at the top of the country page. Falls back to the featured image.', 'zihad-travel-cms' ) )
				),
				new MediaField(
					CountryMeta::FLAG,
					__( 'Flag', 'zihad-travel-cms' ),
					array( 'description' => __( 'Shown on country cards and next to the title.', 'zihad-travel-cms' ) )
				),
				new TextField(
					CountryMeta::HERO_SUBTITLE,
					__( 'Hero Subtitle', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. The land of the rising sun', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-format-image'
		);
	}

	/**
	 * Travel info: overview, tips and highlights for visitors.
	 */
	private function travel_info_tab(): Tab {
		return new Tab(
			Tab::TRAVEL_INFO,
			__( 'Travel Info', 'zihad-travel-cms' ),
			array(
				new RichEditorField(
					CountryMeta::OVERVIEW,
					__( 'Country Overview', 'zihad-travel-cms' )
				),
				new RichEditorField(
					CountryMeta::TRAVEL_TIPS,
					__( 'Travel Tips', 'zihad-travel-cms' ),
					array( 'rows' => 6 )
				),
				new TextField(
					CountryMeta::BEST_TIME,
					__( 'Best Time to Visit', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. November – February', 'zihad-travel-cms' ) )
				),
				new ListField(
					CountryMeta::POPULAR_CITIES,
					__( 'Popular Cities', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'City', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Tokyo', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add city', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-palmtree'
		);
	}

	/**
	 * Embassy: contact details for the local embassy.
	 */
	private function embassy_tab(): Tab {
		return new Tab(
			Tab::EMBASSY,
			__( 'Embassy', 'zihad-travel-cms' ),
			array(
				new TextField( CountryMeta::EMBASSY_NAME, __( 'Embassy Name', 'zihad-travel-cms' ) ),
				new TextareaField(
					CountryMeta::EMBASSY_ADDRESS,
					__( 'Embassy Address', 'zihad-travel-cms' ),
					array( 'rows' => 3 )
				),
				new TextField( CountryMeta::EMBASSY_PHONE, __( 'Embassy Phone', 'zihad-travel-cms' ) ),
				new TextField(
					CountryMeta::EMBASSY_EMAIL,
					__( 'Embassy Email', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. info@embassy.example', 'zihad-travel-cms' ) )
				),
				new UrlField( CountryMeta::EMBASSY_WEBSITE, __( 'Embassy Website', 'zihad-travel-cms' ) ),
			),
			'dashicons-building'
		);
	}

	/**
	 * Gallery.
	 */
	private function gallery_tab(): Tab {
		return new Tab(
			Tab::GALLERY,
			__( 'Gallery', 'zihad-travel-cms' ),
			array(
				new GalleryField( CountryMeta::GALLERY, __( 'Gallery Images', 'zihad-travel-cms' ) ),
			),
			'dashicons-format-gallery'
		);
	}

	/**
	 * FAQ.
	 */
	private function faq_tab(): Tab {
		return new Tab(
			Tab::FAQ,
			__( 'FAQ', 'zihad-travel-cms' ),
			array(
				new FaqField( CountryMeta::FAQ, __( 'Frequently Asked Questions', 'zihad-travel-cms' ) ),
			),
			'dashicons-editor-help'
		);
	}

	/**
	 * SEO.
	 */
	private function seo_tab(): Tab {
		return new Tab(
			Tab::SEO,
			__( 'SEO', 'zihad-travel-cms' ),
			array(
				new SeoField( CountryMeta::SEO, __( 'Search Engine Optimization', 'zihad-travel-cms' ) ),
			),
			'dashicons-search'
		);
	}

	/**
	 * Settings: visibility toggles.
	 */
	private function settings_tab(): Tab {
		return new Tab(
			Tab::SETTINGS,
			__( 'Settings', 'zihad-travel-cms' ),
			array(
				new ToggleField(
					CountryMeta::FEATURED,
					__( 'Featured Country', 'zihad-travel-cms' ),
					array(
						'checkbox_label' => __( 'Highlight this country in featured sections', 'zihad-travel-cms' ),
					)
				),
				new ToggleField(
					CountryMeta::SHOW_ON_HOMEPAGE,
					__( 'Show on Homepage', 'zihad-travel-cms' ),
					array(
						'checkbox_label' => __( 'Include this country in homepage listings', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-admin-settings'
		);
	}
}
