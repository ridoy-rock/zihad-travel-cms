<?php
/**
 * Visa editor.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Admin\UI\Editor;
use ZihadTravelCMS\Admin\UI\Fields\FaqField;
use ZihadTravelCMS\Admin\UI\Fields\ListField;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\RichEditorField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\SeoField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\TimelineField;
use ZihadTravelCMS\Admin\UI\Tab;
use ZihadTravelCMS\Modules\Country\CountryRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tabbed Visa editor.
 *
 * Purely declarative: every Visa meta field is mapped onto the
 * framework's tabs and field components — no bespoke UI. Values save
 * through the registered `ztc_*` meta, so they are readable and
 * writable via the REST API (Gutenberg, Elementor dynamic tags, and
 * the future AI auto-fill, which fills fields through REST and the
 * `ztc_editor_render_before` toolbar hook). Multilingual plugins
 * duplicate the post per language; each translation gets its own
 * editor with its own values (see wpml-config.xml).
 */
final class VisaEditor extends Editor {

	/**
	 * Constructor.
	 *
	 * @param CountryRepository $countries Country repository (for the country selector).
	 */
	public function __construct( private CountryRepository $countries ) {}

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return VisaPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function meta_box_title(): string {
		return __( 'Visa Details', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tabs(): array {
		return array(
			$this->general_tab(),
			$this->hero_tab(),
			$this->requirements_tab(),
			$this->documents_tab(),
			$this->benefits_tab(),
			$this->application_tab(),
			$this->faq_tab(),
			$this->seo_tab(),
			$this->settings_tab(),
		);
	}

	/**
	 * General: destination, entry type and the key facts.
	 */
	private function general_tab(): Tab {
		return new Tab(
			Tab::GENERAL,
			__( 'General', 'zihad-travel-cms' ),
			array(
				new SelectField(
					VisaMeta::COUNTRY,
					__( 'Country', 'zihad-travel-cms' ),
					array(
						'options'     => $this->country_options(),
						'placeholder' => __( '— Select a country —', 'zihad-travel-cms' ),
						'description' => __( 'The destination country this visa is for.', 'zihad-travel-cms' ),
					)
				),
				new SelectField(
					VisaMeta::ENTRY_TYPE,
					__( 'Entry Type', 'zihad-travel-cms' ),
					array(
						'options'     => array(
							'single'   => __( 'Single Entry', 'zihad-travel-cms' ),
							'double'   => __( 'Double Entry', 'zihad-travel-cms' ),
							'multiple' => __( 'Multiple Entry', 'zihad-travel-cms' ),
						),
						'placeholder' => __( '— Select —', 'zihad-travel-cms' ),
					)
				),
				new TextField(
					VisaMeta::PROCESSING_TIME,
					__( 'Processing Time', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. 7–10 business days', 'zihad-travel-cms' ) )
				),
				new TextField(
					VisaMeta::VALIDITY,
					__( 'Validity', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. 90 days from issue', 'zihad-travel-cms' ) )
				),
				new TextField(
					VisaMeta::STAY_DURATION,
					__( 'Stay Duration', 'zihad-travel-cms' ),
					array( 'placeholder' => __( 'e.g. up to 30 days', 'zihad-travel-cms' ) )
				),
				new TextField(
					VisaMeta::FEE,
					__( 'Visa Fee', 'zihad-travel-cms' ),
					array(
						'placeholder' => __( 'e.g. USD 50 + service charge', 'zihad-travel-cms' ),
						'description' => __( 'Free-form so you can include currency and surcharges.', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-info-outline'
		);
	}

	/**
	 * Hero: the banner image.
	 */
	private function hero_tab(): Tab {
		return new Tab(
			Tab::HERO,
			__( 'Hero', 'zihad-travel-cms' ),
			array(
				new MediaField(
					VisaMeta::HERO_IMAGE,
					__( 'Hero Image', 'zihad-travel-cms' ),
					array( 'description' => __( 'Large banner shown at the top of the visa page. Falls back to the featured image.', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-format-image'
		);
	}

	/**
	 * Requirements: eligibility rules and important notes.
	 */
	private function requirements_tab(): Tab {
		return new Tab(
			Tab::REQUIREMENTS,
			__( 'Requirements', 'zihad-travel-cms' ),
			array(
				new RichEditorField(
					VisaMeta::REQUIREMENTS,
					__( 'Requirements', 'zihad-travel-cms' ),
					array( 'description' => __( 'Eligibility rules and conditions applicants must meet.', 'zihad-travel-cms' ) )
				),
				new RichEditorField(
					VisaMeta::IMPORTANT_NOTES,
					__( 'Important Notes', 'zihad-travel-cms' ),
					array(
						'rows'        => 5,
						'description' => __( 'Warnings and disclaimers, shown highlighted on the visa page.', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-yes-alt'
		);
	}

	/**
	 * Documents: the required-documents checklist.
	 */
	private function documents_tab(): Tab {
		return new Tab(
			Tab::DOCUMENTS,
			__( 'Documents', 'zihad-travel-cms' ),
			array(
				new ListField(
					VisaMeta::REQUIRED_DOCUMENTS,
					__( 'Required Documents', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'Document', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Passport valid for 6 months', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add document', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-media-document'
		);
	}

	/**
	 * Benefits: selling points of applying through the agency.
	 */
	private function benefits_tab(): Tab {
		return new Tab(
			Tab::BENEFITS,
			__( 'Benefits', 'zihad-travel-cms' ),
			array(
				new ListField(
					VisaMeta::BENEFITS,
					__( 'Benefits', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'Benefit', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Free document review', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add benefit', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-awards'
		);
	}

	/**
	 * Application: the step-by-step process.
	 */
	private function application_tab(): Tab {
		return new Tab(
			Tab::APPLICATION,
			__( 'Application', 'zihad-travel-cms' ),
			array(
				new TimelineField(
					VisaMeta::APPLICATION_PROCESS,
					__( 'Application Process', 'zihad-travel-cms' ),
					array( 'description' => __( 'Shown as a numbered timeline on the visa page.', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-list-view'
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
				new FaqField( VisaMeta::FAQ, __( 'Frequently Asked Questions', 'zihad-travel-cms' ) ),
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
				new SeoField( VisaMeta::SEO, __( 'Search Engine Optimization', 'zihad-travel-cms' ) ),
			),
			'dashicons-search'
		);
	}

	/**
	 * Settings: per-visa contact overrides.
	 */
	private function settings_tab(): Tab {
		return new Tab(
			Tab::SETTINGS,
			__( 'Settings', 'zihad-travel-cms' ),
			array(
				new TextField(
					VisaMeta::WHATSAPP,
					__( 'WhatsApp Number', 'zihad-travel-cms' ),
					array(
						'placeholder' => __( 'e.g. +8801XXXXXXXXX', 'zihad-travel-cms' ),
						'description' => __( 'Overrides the agency-wide WhatsApp number for this visa only.', 'zihad-travel-cms' ),
					)
				),
				new TextField(
					VisaMeta::APPLY_BUTTON_TEXT,
					__( 'Apply Button Text', 'zihad-travel-cms' ),
					array(
						'placeholder' => __( 'Apply Now', 'zihad-travel-cms' ),
						'description' => __( 'Leave empty to use the default label.', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-admin-settings'
		);
	}

	/**
	 * Published countries as select options (ID => title).
	 *
	 * @return array<int, string>
	 */
	private function country_options(): array {
		$options = array();

		foreach ( $this->countries->all(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		) as $country ) {
			$options[ (string) $country->ID ] = $country->post_title;
		}

		return $options;
	}
}
