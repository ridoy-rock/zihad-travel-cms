<?php
/**
 * Import mapping contract.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Describes how flat import records (CSV rows / JSON objects) map onto
 * a post type. Implemented once per content type and registered
 * through the `ztc_import_mappings` filter, so any module — current or
 * future — can plug into the same import/export engine.
 *
 * Field targets understood by the engine:
 *
 *  - `post:title|slug|status|content|excerpt` — core post fields.
 *  - `meta:{key}`       — scalar post meta (registered sanitizers run).
 *  - `list:{key}`       — array<string> meta; accepts a JSON array or a
 *                         pipe-separated string ("Tokyo|Osaka").
 *  - `json:{key}`       — structured meta (FAQ, itinerary, SEO);
 *                         accepts an array (JSON import) or JSON string
 *                         (CSV cell).
 *  - `terms:{taxonomy}` — term names (pipe-separated or array),
 *                         created when missing.
 *  - `relation:{key}`   — a related post's title or slug, resolved to
 *                         its ID (e.g. visa → country).
 *  - `image:{key}`      — an image URL, sideloaded to an attachment ID.
 *  - `gallery:{key}`    — image URLs → array of attachment IDs.
 *  - `thumbnail`        — an image URL set as the featured image.
 */
interface ImportMapping {

	/**
	 * Unique import type, e.g. `country`.
	 */
	public function type(): string;

	/**
	 * The post type records import into, e.g. `ztc_country`.
	 */
	public function post_type(): string;

	/**
	 * Field definitions: record key => array{target: string, required?: bool}.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function fields(): array;
}
