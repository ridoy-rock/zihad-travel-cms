<?php
/**
 * Visa module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Visa services: the Visa post type, its meta fields and the Visa Type
 * taxonomy. Data access via VisaRepository, business logic via
 * VisaService.
 */
final class VisaModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param VisaPostType     $post_type The Visa post type.
	 * @param VisaTypeTaxonomy $visa_type The Visa Type taxonomy.
	 * @param VisaMeta         $meta      The Visa meta fields.
	 * @param VisaEditor       $editor    The tabbed Visa editor.
	 */
	public function __construct(
		private VisaPostType $post_type,
		private VisaTypeTaxonomy $visa_type,
		private VisaMeta $meta,
		private VisaEditor $editor,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'visa';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		$components = array( $this->post_type, $this->visa_type, $this->meta );

		// The editor only exists in wp-admin.
		if ( is_admin() ) {
			$components[] = $this->editor;
		}

		return $components;
	}
}
