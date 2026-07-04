<?php
/**
 * Setup wizard module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Wizard;

use ZihadTravelCMS\Admin\Pages\WizardPage;
use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * The first-run setup wizard. Pure orchestration: it renders the
 * shared field components pre-filled from Config, saves each step
 * through the existing settings pipeline, and installs demo data
 * through the existing installer/import engine — no persistence, UI
 * framework or sanitization of its own. Extensible via the
 * `ztc_wizard_steps` filter and the `ztc_wizard_*` actions.
 */
final class WizardModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param WizardPrompt $prompt Activation redirect + setup notice.
	 */
	public function __construct( private WizardPrompt $prompt ) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'wizard';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter(
			'ztc_admin_pages',
			static function ( array $pages ): array {
				$pages[] = WizardPage::class;

				return $pages;
			}
		);

		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = WizardController::class;

				return $controllers;
			}
		);

		parent::register();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		// The prompt (redirect + notice) only exists in wp-admin.
		return is_admin() ? array( $this->prompt ) : array();
	}
}
