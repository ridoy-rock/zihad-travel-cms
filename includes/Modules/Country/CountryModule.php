<?php
/**
 * Country module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Destination guides: the Country post type, its meta fields and the
 * shared Region taxonomy. Data access via CountryRepository, business
 * logic via CountryService.
 */
final class CountryModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param CountryPostType $post_type The Country post type.
	 * @param RegionTaxonomy  $region    The Region taxonomy.
	 * @param CountryMeta     $meta      The Country meta fields.
	 * @param CountryEditor   $editor    The tabbed Country editor.
	 */
	public function __construct(
		private CountryPostType $post_type,
		private RegionTaxonomy $region,
		private CountryMeta $meta,
		private CountryEditor $editor,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'country';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		$components = array( $this->post_type, $this->region, $this->meta );

		// The editor only exists in wp-admin.
		if ( is_admin() ) {
			$components[] = $this->editor;
		}

		return $components;
	}
}
