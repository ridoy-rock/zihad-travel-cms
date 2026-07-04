<?php
/**
 * Abstract admin page.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\Pages;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Helpers\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every plugin admin page.
 *
 * A concrete page declares its identity (slug + titles) and implements
 * render(); menu registration and template access are handled here.
 * Pages attach under the plugin's top-level menu by default.
 */
abstract class AdminPage implements Registrable {

	/**
	 * The plugin's top-level menu slug.
	 */
	public const MENU_SLUG = 'zihad-travel-cms';

	/**
	 * Constructor.
	 *
	 * @param Template $template Template renderer.
	 */
	public function __construct( protected Template $template ) {}

	/**
	 * Unique page slug, e.g. `zihad-travel-cms-settings`.
	 */
	abstract public function slug(): string;

	/**
	 * Translated browser/page title.
	 */
	abstract public function page_title(): string;

	/**
	 * Translated menu label.
	 */
	abstract public function menu_title(): string;

	/**
	 * Output the page. Typically delegates to $this->template->render().
	 */
	abstract public function render(): void;

	/**
	 * Capability required to see the page.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Parent menu slug. Defaults to the plugin's top-level menu.
	 */
	public function parent_slug(): string {
		return self::MENU_SLUG;
	}

	/**
	 * Order among sibling submenu items (lower runs earlier).
	 */
	public function position(): int {
		return 10;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), $this->position() );
	}

	/**
	 * Attach the page to the admin menu.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			$this->parent_slug(),
			$this->page_title(),
			$this->menu_title(),
			$this->capability(),
			$this->slug(),
			array( $this, 'render' )
		);
	}
}
