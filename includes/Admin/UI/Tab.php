<?php
/**
 * Editor tab.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI;

use ZihadTravelCMS\Admin\UI\Fields\BaseField;

defined( 'ABSPATH' ) || exit;

/**
 * One tab in a tabbed editor: an id, a label, an optional dashicon and
 * an ordered list of fields.
 *
 * The constants are the standard tab ids shared across modules so
 * extensions can target them reliably (e.g. "add a field to the Visa
 * editor's FAQ tab").
 */
final class Tab {

	public const GENERAL      = 'general';
	public const HERO         = 'hero';
	public const TRAVEL_INFO  = 'travel-info';
	public const EMBASSY      = 'embassy';
	public const ITINERARY    = 'itinerary';
	public const INCLUSIONS   = 'inclusions';
	public const HOTELS       = 'hotels';
	public const REQUIREMENTS = 'requirements';
	public const DOCUMENTS    = 'documents';
	public const BENEFITS     = 'benefits';
	public const APPLICATION  = 'application';
	public const GALLERY      = 'gallery';
	public const FAQ          = 'faq';
	public const SEO          = 'seo';
	public const SETTINGS     = 'settings';

	/**
	 * Constructor.
	 *
	 * @param string           $id     Tab id (use the class constants where possible).
	 * @param string           $label  Translated tab label.
	 * @param array<BaseField> $fields Fields shown on the tab, in order.
	 * @param string           $icon   Optional dashicon class, e.g. `dashicons-format-gallery`.
	 */
	public function __construct(
		private string $id,
		private string $label,
		private array $fields = array(),
		private string $icon = '',
	) {}

	/**
	 * Tab id.
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Translated label.
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Fields on this tab.
	 *
	 * @return array<BaseField>
	 */
	public function fields(): array {
		return $this->fields;
	}

	/**
	 * Dashicon class ('' for none).
	 */
	public function icon(): string {
		return $this->icon;
	}

	/**
	 * Append a field (used by extensions via the editor tabs filter).
	 *
	 * @param BaseField $field Field to add.
	 */
	public function add( BaseField $field ): self {
		$this->fields[] = $field;

		return $this;
	}
}
