<?php
/**
 * Setup wizard prompt.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Wizard;

use ZihadTravelCMS\Admin\Pages\WizardPage;
use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Gets new installs into the wizard: a one-shot redirect right after
 * activation (via the existing `ztc_activated` action — Activator is
 * untouched) and a gentle "finish setting up" notice on plugin screens
 * while the wizard is neither finished nor skipped.
 */
final class WizardPrompt implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param WizardService $wizard Wizard orchestration.
	 * @param WizardPage    $page   The wizard page (URL source).
	 */
	public function __construct(
		private WizardService $wizard,
		private WizardPage $page,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'ztc_activated', array( $this, 'flag_redirect' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	/**
	 * Remember to open the wizard on the next admin request.
	 */
	public function flag_redirect(): void {
		// One-shot admin flag: never autoloaded on the frontend.
		update_option( WizardService::REDIRECT_OPTION, 1, false );
	}

	/**
	 * One-shot redirect to the wizard after activation. Skipped for
	 * bulk/network activations, AJAX requests, users who cannot run the
	 * wizard, and installs that already finished or skipped it.
	 */
	public function maybe_redirect(): void {
		if ( ! get_option( WizardService::REDIRECT_OPTION ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		delete_option( WizardService::REDIRECT_OPTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only feature detection.
		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) || is_network_admin() || $this->wizard->is_completed() ) {
			return;
		}

		wp_safe_redirect( $this->page->url() );
		exit;
	}

	/**
	 * A "finish setting up" notice on plugin screens (never on the
	 * wizard itself) while the wizard is incomplete.
	 */
	public function render_notice(): void {
		if ( $this->wizard->is_completed() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );

		if ( ! str_starts_with( $page, 'zihad-travel-cms' ) || WizardPage::SLUG === $page ) {
			return;
		}

		printf(
			'<div class="notice notice-info ztc-wizard-notice"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Zihad Travel CMS is not fully set up yet.', 'zihad-travel-cms' ),
			esc_url( $this->page->url() ),
			esc_html__( 'Run the setup wizard', 'zihad-travel-cms' )
		);
	}
}
