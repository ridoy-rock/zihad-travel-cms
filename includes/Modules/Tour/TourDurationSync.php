<?php
/**
 * Tour duration mirror sync.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the numeric `ztc_duration_days` mirror in sync with the
 * `ztc_duration` object ({days, nights} strings) whenever it is
 * written — by the editor, the REST API, the importer or the demo
 * installer. The mirror lets search range-filter and sort on duration
 * with a plain NUMERIC meta query instead of parsing objects per row.
 */
final class TourDurationSync implements Registrable {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'added_post_meta', array( $this, 'sync' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'sync' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'clear' ), 10, 3 );
	}

	/**
	 * Mirror the duration's day count after any duration write.
	 *
	 * @param int    $meta_id    Meta row ID (unused).
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key that was written.
	 * @param mixed  $meta_value New value.
	 */
	public function sync( int $meta_id, int $object_id, string $meta_key, mixed $meta_value ): void {
		if ( ! $this->is_tour_duration( $object_id, $meta_key ) ) {
			return;
		}

		$days = is_array( $meta_value ) ? (int) ( $meta_value['days'] ?? 0 ) : 0;

		update_post_meta( $object_id, TourMeta::DURATION_DAYS, max( 0, $days ) );
	}

	/**
	 * Drop the mirror when the duration itself is deleted.
	 *
	 * @param array<int>|int $meta_ids  Deleted meta row IDs (unused).
	 * @param int            $object_id Post ID.
	 * @param string         $meta_key  Meta key that was deleted.
	 */
	public function clear( array|int $meta_ids, int $object_id, string $meta_key ): void {
		if ( ! $this->is_tour_duration( $object_id, $meta_key ) ) {
			return;
		}

		delete_post_meta( $object_id, TourMeta::DURATION_DAYS );
	}

	/**
	 * Whether this meta write is a tour's duration object.
	 *
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 */
	private function is_tour_duration( int $object_id, string $meta_key ): bool {
		return TourMeta::DURATION === $meta_key && TourPostType::NAME === get_post_type( $object_id );
	}
}
