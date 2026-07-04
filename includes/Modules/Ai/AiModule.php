<?php
/**
 * AI module (placeholder).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Ai;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Future scope: AI-assisted content generation (tour descriptions,
 * visa requirement summaries, itineraries and SEO text) behind a
 * provider abstraction so the API vendor is swappable. Disabled until
 * an API key is configured in settings.
 */
final class AiModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'ai';
	}
}
