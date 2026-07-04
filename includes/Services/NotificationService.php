<?php
/**
 * Notification service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Services;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * One channel for all plugin messaging: admin notices, importer
 * results, system warnings and success confirmations.
 *
 * Notices queue in a short-lived per-user transient, so they survive
 * the redirect after a form submit or import and render exactly once.
 */
final class NotificationService implements Registrable {

	public const SUCCESS = 'success';
	public const ERROR   = 'error';
	public const WARNING = 'warning';
	public const INFO    = 'info';

	private const TRANSIENT_LIFETIME = 300;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Queue a success message.
	 *
	 * @param string $message Message text (post-safe HTML allowed).
	 */
	public function success( string $message ): void {
		$this->add( $message, self::SUCCESS );
	}

	/**
	 * Queue an error message.
	 *
	 * @param string $message Message text.
	 */
	public function error( string $message ): void {
		$this->add( $message, self::ERROR );
	}

	/**
	 * Queue a system warning.
	 *
	 * @param string $message Message text.
	 */
	public function warning( string $message ): void {
		$this->add( $message, self::WARNING );
	}

	/**
	 * Queue an informational message.
	 *
	 * @param string $message Message text.
	 */
	public function info( string $message ): void {
		$this->add( $message, self::INFO );
	}

	/**
	 * Queue a notice for the current user.
	 *
	 * @param string $message     Message text (post-safe HTML allowed).
	 * @param string $type        One of the type constants.
	 * @param bool   $dismissible Whether the notice can be dismissed.
	 */
	public function add( string $message, string $type = self::INFO, bool $dismissible = true ): void {
		$notices = $this->stored();

		$notices[] = array(
			'message'     => $message,
			'type'        => in_array( $type, array( self::SUCCESS, self::ERROR, self::WARNING, self::INFO ), true ) ? $type : self::INFO,
			'dismissible' => $dismissible,
		);

		set_transient( $this->transient_key(), $notices, self::TRANSIENT_LIFETIME );
	}

	/**
	 * Output queued notices once, then clear the queue.
	 */
	public function render_notices(): void {
		$notices = $this->stored();

		if ( array() === $notices ) {
			return;
		}

		delete_transient( $this->transient_key() );

		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s%2$s"><p>%3$s</p></div>',
				esc_attr( $notice['type'] ),
				$notice['dismissible'] ? ' is-dismissible' : '',
				wp_kses_post( $notice['message'] )
			);
		}
	}

	/**
	 * Notices queued for the current user.
	 *
	 * @return array<array{message: string, type: string, dismissible: bool}>
	 */
	private function stored(): array {
		$notices = get_transient( $this->transient_key() );

		return is_array( $notices ) ? $notices : array();
	}

	/**
	 * Per-user transient key.
	 */
	private function transient_key(): string {
		return 'ztc_notices_' . get_current_user_id();
	}
}
