<?php
/**
 * Setup wizard admin page.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\Pages;

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Wizard\WizardService;
use ZihadTravelCMS\Services\NotificationService;

defined( 'ABSPATH' ) || exit;

/**
 * The Setup screen: one wizard step per request, rendered with the
 * shared field components pre-filled from Config, saved step-by-step
 * through WizardService (the settings pipeline). Plain forms and
 * redirects — fully functional without JavaScript; the demo step adds
 * the existing admin.js progress loop on top. Registered by the Wizard
 * module via `ztc_admin_pages`.
 */
final class WizardPage extends AdminPage {

	/**
	 * Page slug (also used by the activation redirect and notices).
	 */
	public const SLUG = 'zihad-travel-cms-setup';

	private const ACTION_SAVE      = 'ztc_wizard_save';
	private const ACTION_SKIP_STEP = 'ztc_wizard_skip_step';
	private const ACTION_SKIP_ALL  = 'ztc_wizard_skip';
	private const ACTION_RESTART   = 'ztc_wizard_restart';
	private const ACTION_DEMO      = 'ztc_wizard_demo';

	/**
	 * Constructor.
	 *
	 * @param Template            $template      Template renderer.
	 * @param Config              $config        Plugin configuration (field values).
	 * @param WizardService       $wizard        Wizard orchestration.
	 * @param NotificationService $notifications Admin notices.
	 */
	public function __construct(
		Template $template,
		private Config $config,
		private WizardService $wizard,
		private NotificationService $notifications,
	) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * {@inheritDoc}
	 */
	public function page_title(): string {
		return __( 'Setup Wizard', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function menu_title(): string {
		return __( 'Setup', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function position(): int {
		return 85;
	}

	/**
	 * The wizard URL, optionally for a specific step.
	 *
	 * @param string $step Step id ('' for the resume point).
	 */
	public function url( string $step = '' ): string {
		$url = admin_url( 'admin.php?page=' . self::SLUG );

		return '' !== $step ? $url . '&step=' . rawurlencode( $step ) : $url;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		parent::register();

		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'save' ) );
		add_action( 'admin_post_' . self::ACTION_SKIP_STEP, array( $this, 'skip_step' ) );
		add_action( 'admin_post_' . self::ACTION_SKIP_ALL, array( $this, 'skip_wizard' ) );
		add_action( 'admin_post_' . self::ACTION_RESTART, array( $this, 'restart' ) );
		add_action( 'admin_post_' . self::ACTION_DEMO, array( $this, 'process_demo' ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {
		wp_enqueue_media();

		$steps   = $this->wizard->steps();
		$ids     = array_column( $steps, 'id' );
		$current = $this->requested_step( $ids );
		$state   = $this->wizard->state();
		$index   = (int) array_search( $current, $ids, true );

		$items = array();

		foreach ( $steps as $i => $step ) {
			$items[] = array(
				'id'        => $step['id'],
				'title'     => $step['title'],
				'completed' => in_array( $step['id'], $state['completed'], true ),
				'current'   => $step['id'] === $current,
				'url'       => $this->url( $step['id'] ),
			);
		}

		$step   = $steps[ $index ];
		$values = array();

		foreach ( $step['fields'] as $field ) {
			$values[ $field->name() ] = $this->config->get( $field->name() );
		}

		$this->template->render(
			'admin/wizard.php',
			array(
				'title'    => $this->page_title(),
				'steps'    => $items,
				'step'     => $step,
				'values'   => $values,
				'position' => array(
					'index' => $index + 1,
					'count' => count( $steps ),
				),
				'demo'     => WizardService::STEP_DEMO === $current ? $this->wizard->demo() : array(),
				'summary'  => WizardService::STEP_FINISH === $current ? $this->wizard->summary() : array(),
				'actions'  => $this->actions( $ids, $index ),
			)
		);
	}

	/**
	 * Handle a step save (admin-post.php).
	 */
	public function save(): void {
		if ( ! $this->guard( self::ACTION_SAVE ) ) {
			return;
		}

		$id = $this->posted_step();

		if ( ! $this->wizard->save_step( $id, $this->posted_fields() ) ) {
			return;
		}

		if ( WizardService::STEP_FINISH === $id ) {
			$this->wizard->finish();
			$this->notifications->success( __( 'Setup complete — happy travels!', 'zihad-travel-cms' ) );
			$this->redirect( admin_url( 'admin.php?page=' . AdminPage::MENU_SLUG ) );

			return;
		}

		$this->notifications->success( __( 'Step saved.', 'zihad-travel-cms' ) );
		$this->redirect( $this->url( $this->step_after( $id ) ) );
	}

	/**
	 * Mark a step done without saving anything (admin-post.php).
	 */
	public function skip_step(): void {
		if ( ! $this->guard( self::ACTION_SKIP_STEP ) ) {
			return;
		}

		$id = $this->posted_step();
		$this->wizard->mark_complete( $id );
		$this->redirect( $this->url( $this->step_after( $id ) ) );
	}

	/**
	 * Skip the whole wizard (admin-post.php).
	 */
	public function skip_wizard(): void {
		if ( ! $this->guard( self::ACTION_SKIP_ALL ) ) {
			return;
		}

		$this->wizard->skip();
		$this->notifications->info( __( 'Setup skipped. You can re-run it anytime from Travel CMS → Setup.', 'zihad-travel-cms' ) );
		$this->redirect( admin_url( 'admin.php?page=' . AdminPage::MENU_SLUG ) );
	}

	/**
	 * Reset progress and start over (admin-post.php). Settings are
	 * never touched.
	 */
	public function restart(): void {
		if ( ! $this->guard( self::ACTION_RESTART ) ) {
			return;
		}

		$this->wizard->reset();
		$this->redirect( $this->url() );
	}

	/**
	 * No-JS demo installation: process a bounded slice of import
	 * batches, then bounce back to the demo step (admin-post.php).
	 */
	public function process_demo(): void {
		if ( ! $this->guard( self::ACTION_DEMO ) ) {
			return;
		}

		$result = $this->wizard->advance_demo();

		if ( $result['finished'] ) {
			$this->notifications->success( __( 'Demo data installed.', 'zihad-travel-cms' ) );
			$this->redirect( $this->url( $this->step_after( WizardService::STEP_DEMO ) ) );

			return;
		}

		$this->redirect( $this->url( WizardService::STEP_DEMO ) );
	}

	/**
	 * Shared nonce + capability guard for the admin-post handlers.
	 *
	 * @param string $action The nonce action.
	 */
	private function guard( string $action ): bool {
		return false !== check_admin_referer( $action ) && current_user_can( 'manage_options' );
	}

	/**
	 * The validated step id from the request ('' falls back to welcome).
	 */
	private function posted_step(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		$id = sanitize_key( wp_unslash( $_POST['ztc_step'] ?? '' ) );

		return in_array( $id, $this->wizard->step_ids(), true ) ? $id : WizardService::STEP_WELCOME;
	}

	/**
	 * The submitted field values.
	 *
	 * @return array<string, mixed>
	 */
	private function posted_fields(): array {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- each field sanitizes its own value; nonce verified in guard().
		return isset( $_POST['ztc_fields'] ) && is_array( $_POST['ztc_fields'] ) ? wp_unslash( $_POST['ztc_fields'] ) : array();
	}

	/**
	 * The step shown for this request: an explicit `step` query arg when
	 * valid, otherwise the resume point.
	 *
	 * @param array<string> $ids Valid step ids.
	 */
	private function requested_step( array $ids ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$requested = sanitize_key( wp_unslash( $_GET['step'] ?? '' ) );

		return in_array( $requested, $ids, true ) ? $requested : $this->wizard->next_step();
	}

	/**
	 * The id of the step after the given one (finish when at the end).
	 *
	 * @param string $id Step id.
	 */
	private function step_after( string $id ): string {
		$ids   = $this->wizard->step_ids();
		$index = (int) array_search( $id, $ids, true );

		return $ids[ $index + 1 ] ?? (string) end( $ids );
	}

	/**
	 * Everything the template needs to build forms and links — no URL
	 * or nonce logic in the view.
	 *
	 * @param array<string> $ids   Step ids.
	 * @param int           $index Current step index.
	 *
	 * @return array<string, string>
	 */
	private function actions( array $ids, int $index ): array {
		return array(
			'form'        => admin_url( 'admin-post.php' ),
			'save'        => self::ACTION_SAVE,
			'demo'        => self::ACTION_DEMO,
			'skip_step'   => self::ACTION_SKIP_STEP,
			'back'        => $index > 0 ? $this->url( $ids[ $index - 1 ] ) : '',
			'skip_all'    => wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_SKIP_ALL ), self::ACTION_SKIP_ALL ),
			'restart'     => wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_RESTART ), self::ACTION_RESTART ),
			'dashboard'   => admin_url( 'admin.php?page=' . AdminPage::MENU_SLUG ),
			'settings'    => admin_url( 'admin.php?page=zihad-travel-cms-settings' ),
			'permalinks'  => admin_url( 'options-permalink.php' ),
		);
	}

	/**
	 * Redirect and end the request.
	 *
	 * @param string $url Target URL.
	 */
	private function redirect( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}
}
