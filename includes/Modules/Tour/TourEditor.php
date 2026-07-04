<?php
/**
 * Tour editor.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Admin\UI\Editor;
use ZihadTravelCMS\Admin\UI\Fields\DurationField;
use ZihadTravelCMS\Admin\UI\Fields\FaqField;
use ZihadTravelCMS\Admin\UI\Fields\GalleryField;
use ZihadTravelCMS\Admin\UI\Fields\ListField;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\NumberField;
use ZihadTravelCMS\Admin\UI\Fields\RepeaterField;
use ZihadTravelCMS\Admin\UI\Fields\RichEditorField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\SeoField;
use ZihadTravelCMS\Admin\UI\Fields\TaxonomyField;
use ZihadTravelCMS\Admin\UI\Fields\TimelineField;
use ZihadTravelCMS\Admin\UI\Fields\UrlField;
use ZihadTravelCMS\Admin\UI\Tab;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Services\NotificationService;

defined( 'ABSPATH' ) || exit;

/**
 * The tabbed Tour editor.
 *
 * Purely declarative: every Tour meta field is mapped onto the
 * framework's tabs and reusable components — no bespoke UI. Values
 * save through the registered `ztc_*` meta (Tour Type through the
 * taxonomy), so everything is readable and writable via the REST API,
 * Gutenberg, Elementor dynamic tags and the future AI auto-fill.
 *
 * Cross-field validation runs in after_save(): an invalid sale price
 * (>= regular price) is cleared and reported through the
 * NotificationService.
 */
final class TourEditor extends Editor {

	/**
	 * Constructor.
	 *
	 * @param CountryRepository   $countries     Country repository (for the country selector).
	 * @param TourRepository      $tours         Tour repository (for validation reads/writes).
	 * @param NotificationService $notifications Admin notice queue.
	 */
	public function __construct(
		private CountryRepository $countries,
		private TourRepository $tours,
		private NotificationService $notifications,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return TourPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function meta_box_title(): string {
		return __( 'Tour Details', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tabs(): array {
		return array(
			$this->general_tab(),
			$this->hero_tab(),
			$this->gallery_tab(),
			$this->itinerary_tab(),
			$this->inclusions_tab(),
			$this->hotels_tab(),
			$this->travel_info_tab(),
			$this->faq_tab(),
			$this->seo_tab(),
		);
	}

	/**
	 * Sale price must be lower than the regular price; an invalid one
	 * is cleared and the editor is told why.
	 *
	 * {@inheritDoc}
	 */
	protected function after_save( int $post_id ): void {
		$price = (float) $this->tours->meta( $post_id, TourMeta::PRICE );
		$sale  = (float) $this->tours->meta( $post_id, TourMeta::SALE_PRICE );

		if ( $sale > 0 && $sale >= $price ) {
			$this->tours->save_meta( $post_id, TourMeta::SALE_PRICE, 0.0 );
			$this->notifications->warning(
				__( 'The sale price must be lower than the regular price, so it was cleared.', 'zihad-travel-cms' )
			);
		}
	}

	/**
	 * General: destination, type, pricing and duration.
	 */
	private function general_tab(): Tab {
		return new Tab(
			Tab::GENERAL,
			__( 'General', 'zihad-travel-cms' ),
			array(
				new SelectField(
					TourMeta::COUNTRY,
					__( 'Country', 'zihad-travel-cms' ),
					array(
						'options'     => $this->country_options(),
						'placeholder' => __( '— Select a country —', 'zihad-travel-cms' ),
						'description' => __( 'The destination country for this tour.', 'zihad-travel-cms' ),
					)
				),
				new TaxonomyField(
					TourTypeTaxonomy::NAME,
					__( 'Tour Type', 'zihad-travel-cms' ),
					array( 'description' => __( 'Hold Ctrl/Cmd to select multiple types.', 'zihad-travel-cms' ) )
				),
				new NumberField(
					TourMeta::PRICE,
					__( 'Price', 'zihad-travel-cms' ),
					array(
						'min'         => 0,
						'description' => __( 'Regular price per person, in the default currency.', 'zihad-travel-cms' ),
					)
				),
				new NumberField(
					TourMeta::SALE_PRICE,
					__( 'Sale Price', 'zihad-travel-cms' ),
					array(
						'min'         => 0,
						'description' => __( 'Optional discounted price. Must be lower than the regular price.', 'zihad-travel-cms' ),
					)
				),
				new DurationField( TourMeta::DURATION, __( 'Duration', 'zihad-travel-cms' ) ),
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
					TourMeta::HERO_IMAGE,
					__( 'Hero Image', 'zihad-travel-cms' ),
					array( 'description' => __( 'Large banner shown at the top of the tour page. Falls back to the featured image, then the first gallery image.', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-format-image'
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
				new GalleryField( TourMeta::GALLERY, __( 'Gallery Images', 'zihad-travel-cms' ) ),
			),
			'dashicons-format-gallery'
		);
	}

	/**
	 * Itinerary: day-by-day timeline plus trip highlights.
	 */
	private function itinerary_tab(): Tab {
		return new Tab(
			Tab::ITINERARY,
			__( 'Itinerary', 'zihad-travel-cms' ),
			array(
				new ListField(
					TourMeta::HIGHLIGHTS,
					__( 'Highlights', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'Highlight', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Sunset cruise on the bay', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add highlight', 'zihad-travel-cms' ),
					)
				),
				new TimelineField(
					TourMeta::ITINERARY,
					__( 'Day-by-day Itinerary', 'zihad-travel-cms' ),
					array(
						'row_label'    => __( 'Day', 'zihad-travel-cms' ),
						'button_label' => __( 'Add day', 'zihad-travel-cms' ),
						'description'  => __( 'Shown as a numbered timeline on the tour page.', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-list-view'
		);
	}

	/**
	 * Inclusions: what the package does and does not cover.
	 */
	private function inclusions_tab(): Tab {
		return new Tab(
			Tab::INCLUSIONS,
			__( 'Inclusions', 'zihad-travel-cms' ),
			array(
				new ListField(
					TourMeta::INCLUDED,
					__( 'Included', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'Included item', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Airport transfers', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add included item', 'zihad-travel-cms' ),
					)
				),
				new ListField(
					TourMeta::EXCLUDED,
					__( 'Not Included', 'zihad-travel-cms' ),
					array(
						'item_label'       => __( 'Excluded item', 'zihad-travel-cms' ),
						'item_placeholder' => __( 'e.g. Personal expenses', 'zihad-travel-cms' ),
						'button_label'     => __( 'Add excluded item', 'zihad-travel-cms' ),
					)
				),
			),
			'dashicons-yes-alt'
		);
	}

	/**
	 * Hotels.
	 */
	private function hotels_tab(): Tab {
		return new Tab(
			Tab::HOTELS,
			__( 'Hotels', 'zihad-travel-cms' ),
			array(
				new RepeaterField(
					TourMeta::HOTELS,
					__( 'Hotels', 'zihad-travel-cms' ),
					array(
						'row_label'    => __( 'Hotel', 'zihad-travel-cms' ),
						'button_label' => __( 'Add hotel', 'zihad-travel-cms' ),
						'fields'       => array(
							array(
								'key'   => 'name',
								'label' => __( 'Name', 'zihad-travel-cms' ),
								'type'  => 'text',
							),
							array(
								'key'         => 'rating',
								'label'       => __( 'Rating', 'zihad-travel-cms' ),
								'type'        => 'text',
								'placeholder' => __( 'e.g. 4-star', 'zihad-travel-cms' ),
							),
							array(
								'key'   => 'description',
								'label' => __( 'Description', 'zihad-travel-cms' ),
								'type'  => 'textarea',
								'rows'  => 3,
							),
						),
					)
				),
			),
			'dashicons-building'
		);
	}

	/**
	 * Travel info: flights, meals and the map link.
	 */
	private function travel_info_tab(): Tab {
		return new Tab(
			Tab::TRAVEL_INFO,
			__( 'Travel Info', 'zihad-travel-cms' ),
			array(
				new RichEditorField(
					TourMeta::FLIGHTS,
					__( 'Flights', 'zihad-travel-cms' ),
					array( 'rows' => 5 )
				),
				new RichEditorField(
					TourMeta::MEALS,
					__( 'Meals', 'zihad-travel-cms' ),
					array( 'rows' => 5 )
				),
				new UrlField(
					TourMeta::MAP,
					__( 'Map URL', 'zihad-travel-cms' ),
					array( 'description' => __( 'A Google Maps share or embed URL for the tour route.', 'zihad-travel-cms' ) )
				),
			),
			'dashicons-airplane'
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
				new FaqField( TourMeta::FAQ, __( 'Frequently Asked Questions', 'zihad-travel-cms' ) ),
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
				new SeoField( TourMeta::SEO, __( 'Search Engine Optimization', 'zihad-travel-cms' ) ),
			),
			'dashicons-search'
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
