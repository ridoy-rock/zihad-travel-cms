<?php
/**
 * Default translation provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Translations;

use ZihadTravelCMS\Contracts\TranslationProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Null-object provider for single-language sites: every post is its
 * own only "translation" in the site locale.
 */
final class SiteTranslationProvider implements TranslationProvider {

	/**
	 * {@inheritDoc}
	 */
	public function current_language(): string {
		return get_locale();
	}

	/**
	 * {@inheritDoc}
	 */
	public function default_language(): string {
		return get_locale();
	}

	/**
	 * {@inheritDoc}
	 */
	public function post_translations( int $post_id ): array {
		return array( $this->current_language() => $post_id );
	}

	/**
	 * {@inheritDoc}
	 */
	public function translated_post_id( int $post_id, string $language ): int {
		return $post_id;
	}
}
