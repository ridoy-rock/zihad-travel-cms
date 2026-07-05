<?php
/**
 * Demo data admin actions.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use ZihadTravelCMS\Modules\Importer\ImportService;
use ZihadTravelCMS\Modules\Importer\JobRepository;
use ZihadTravelCMS\Services\NotificationService;

defined( 'ABSPATH' ) || exit;

/**
 * The no-JS recovery actions for demo imports, both nonce + capability
 * guarded:
 *
 *  - Resume: continue an incomplete demo install in bounded batch
 *    slices through the existing import engine (repeat until done).
 *  - Reset: delete stale unfinished demo *job records* so the UI stops
 *    reporting a phantom run. Content is never touched — deleting
 *    posts remains the importer's explicit rollback feature.
 */
final class DemoDataActions {

	public const ACTION_RESUME = 'ztc_demo_resume';
	public const ACTION_RESET  = 'ztc_demo_reset';

	/**
	 * Constructor.
	 *
	 * @param DemoDataStatus      $status        Truthful demo state.
	 * @param DemoDataInstaller   $installer     Job starter (upsert).
	 * @param ImportService       $import        Batch processor.
	 * @param JobRepository       $jobs          Job records.
	 * @param NotificationService $notifications Admin notices.
	 */
	public function __construct(
		private DemoDataStatus $status,
		private DemoDataInstaller $installer,
		private ImportService $import,
		private JobRepository $jobs,
		private NotificationService $notifications,
	) {}

	/**
	 * Attach the admin-post handlers.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_RESUME, array( $this, 'resume' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( $this, 'reset' ) );
	}

	/**
	 * Resume an incomplete demo install (admin-post.php).
	 */
	public function resume(): void {
		if ( ! $this->guard( self::ACTION_RESUME ) ) {
			return;
		}

		$result = $this->advance();

		if ( $result['installed'] ) {
			$this->notifications->success( __( 'Demo data install completed.', 'zihad-travel-cms' ) );
		} else {
			$this->notifications->info(
				sprintf(
					/* translators: 1: content type, 2: processed count, 3: total count. */
					__( 'Demo import resumed: %1$s at %2$d of %3$d. Click "Resume demo import" again to continue.', 'zihad-travel-cms' ),
					(string) ( $result['type'] ?? '' ),
					(int) ( $result['processed'] ?? 0 ),
					(int) ( $result['total'] ?? 0 )
				)
			);
		}

		$this->redirect_back();
	}

	/**
	 * Clear stale unfinished demo job records (admin-post.php).
	 * Deliberately never deletes content — only the job bookkeeping.
	 */
	public function reset(): void {
		if ( ! $this->guard( self::ACTION_RESET ) ) {
			return;
		}

		$cleared = $this->clear_unfinished_jobs();

		$this->notifications->success(
			sprintf(
				/* translators: %d: number of job records removed. */
				__( 'Cleared %d incomplete demo import record(s). Your content was not modified.', 'zihad-travel-cms' ),
				$cleared
			)
		);

		$this->redirect_back();
	}

	/**
	 * Continue the demo install by a bounded amount of work: finish the
	 * active job first, then start the next type whose records are
	 * still short. Safe to call repeatedly; everything runs in upsert
	 * mode, so nothing ever duplicates.
	 *
	 * @param int $batch_calls Max process() calls this request.
	 * @param int $batch_size  Records per process() call.
	 *
	 * @return array{installed: bool, type: string, processed: int, total: int}
	 */
	public function advance( int $batch_calls = 4, int $batch_size = 25 ): array {
		$last = null;

		while ( $batch_calls > 0 ) {
			$job = $this->status->active_job();

			if ( null === $job ) {
				$missing = $this->status->next_missing_type();

				if ( '' === $missing ) {
					break; // Everything the files provide exists.
				}

				$job = $this->installer->start( $missing );
			}

			$last = $this->import->process( $job->id, $batch_size );
			--$batch_calls;
		}

		return array(
			'installed' => $this->status->installed(),
			'type'      => null !== $last ? $last->type : '',
			'processed' => null !== $last ? $last->processed : 0,
			'total'     => null !== $last ? $last->total : 0,
		);
	}

	/**
	 * Delete every unfinished demo job record (content untouched).
	 *
	 * @return int Records removed.
	 */
	public function clear_unfinished_jobs(): int {
		$cleared = 0;

		foreach ( $this->jobs->all() as $job ) {
			if ( ! $job->is_finished() && $this->status->is_demo_job( $job ) ) {
				$this->jobs->delete( $job->id );
				++$cleared;
			}
		}

		return $cleared;
	}

	/**
	 * Shared nonce + capability guard.
	 *
	 * @param string $action The nonce action.
	 */
	private function guard( string $action ): bool {
		return false !== check_admin_referer( $action ) && current_user_can( 'manage_options' );
	}

	/**
	 * Back to the Import/Export screen (or wherever the form lived).
	 */
	private function redirect_back(): void {
		$back = wp_get_referer();
		$back = is_string( $back ) && '' !== $back ? $back : admin_url( 'admin.php?page=zihad-travel-cms-import' );

		wp_safe_redirect( $back );
		exit;
	}
}
