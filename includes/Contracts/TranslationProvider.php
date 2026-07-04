<?php
/**
 * Translation provider contract.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstracts multilingual lookups so the plugin is translation-ready
 * without depending on any specific multilingual plugin.
 *
 * The default SiteTranslationProvider is a null object (single-language
 * site). When multilingual support lands, WPML/Polylang adapters
 * implement this interface and are swapped in via the
 * `ztc_translation_provider` filter — repositories and services never
 * change.
 */
interface TranslationProvider {

	/**
	 * The language of the current request, e.g. `en_US`.
	 */
	public function current_language(): string;

	/**
	 * The site's default language.
	 */
	public function default_language(): string;

	/**
	 * All translations of a post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<string, int> Language code => post ID.
	 */
	public function post_translations( int $post_id ): array;

	/**
	 * The ID of a post's translation in the given language, falling
	 * back to the post itself when no translation exists.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $language Target language code.
	 */
	public function translated_post_id( int $post_id, string $language ): int;
}
