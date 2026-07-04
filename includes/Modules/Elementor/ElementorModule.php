<?php
/**
 * Elementor module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor;

use ZihadTravelCMS\Modules\BaseModule;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Visa\VisaPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration: a "Travel CMS" widget category with card-grid
 * and CTA widgets, dynamic tags for plugin fields, and Elementor
 * editing support for the plugin post types.
 *
 * All Elementor classes are referenced only after `elementor/loaded`,
 * so the module is safe when Elementor is absent.
 */
final class ElementorModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'elementor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Elementor may load before or after this plugin; handle both.
		if ( did_action( 'elementor/loaded' ) ) {
			$this->boot_integration();

			return;
		}

		add_action( 'elementor/loaded', array( $this, 'boot_integration' ) );
	}

	/**
	 * Attach the Elementor hooks. Runs only when Elementor is active.
	 */
	public function boot_integration(): void {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tags' ) );
		add_filter( 'option_elementor_cpt_support', array( $this, 'add_cpt_support' ) );
	}

	/**
	 * Add the "Travel CMS" widget category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_category( object $elements_manager ): void {
		$elements_manager->add_category(
			'zihad-travel-cms',
			array(
				'title' => __( 'Travel CMS', 'zihad-travel-cms' ),
				'icon'  => 'eicon-globe',
			)
		);
	}

	/**
	 * Register the plugin widgets.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( object $widgets_manager ): void {
		$widgets = array(
			Widgets\ToursWidget::class,
			Widgets\VisasWidget::class,
			Widgets\CountriesWidget::class,
			Widgets\CtaWidget::class,
			Widgets\SearchWidget::class,
		);

		/**
		 * Filter the Elementor widgets registered by the plugin.
		 *
		 * @param array<class-string> $widgets Widget class names.
		 */
		$widgets = (array) apply_filters( 'ztc_elementor_widgets', $widgets );

		foreach ( $widgets as $widget_class ) {
			$widgets_manager->register( new $widget_class() );
		}
	}

	/**
	 * Register the dynamic tags group and tags.
	 *
	 * @param object $dynamic_tags Elementor dynamic tags manager.
	 */
	public function register_tags( object $dynamic_tags ): void {
		$dynamic_tags->register_group(
			'zihad-travel-cms',
			array( 'title' => __( 'Travel CMS', 'zihad-travel-cms' ) )
		);

		$dynamic_tags->register( new Tags\FieldTag() );
		$dynamic_tags->register( new Tags\HeroImageTag() );
		$dynamic_tags->register( new Tags\SettingTag() );
	}

	/**
	 * Let Elementor edit the plugin post types.
	 *
	 * @param mixed $post_types Option value (false when never saved).
	 *
	 * @return array<string>
	 */
	public function add_cpt_support( mixed $post_types ): array {
		$post_types = is_array( $post_types ) ? $post_types : array( 'page', 'post' );

		return array_values(
			array_unique(
				array_merge( $post_types, array( TourPostType::NAME, VisaPostType::NAME, CountryPostType::NAME ) )
			)
		);
	}
}
