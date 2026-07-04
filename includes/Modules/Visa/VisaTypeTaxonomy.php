<?php
/**
 * Visa Type taxonomy.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Taxonomies\BaseTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Visa categories (Tourist, Business, Student…). Archives live at
 * /visa-type/.
 */
final class VisaTypeTaxonomy extends BaseTaxonomy {

	/**
	 * The taxonomy name.
	 */
	public const NAME = 'ztc_visa_type';

	/**
	 * {@inheritDoc}
	 */
	public function taxonomy(): string {
		return self::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function object_types(): array {
		return array( VisaPostType::NAME );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function singular_label(): string {
		return __( 'Visa Type', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Visa Types', 'zihad-travel-cms' );
	}
}
