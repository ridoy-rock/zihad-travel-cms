<?php
/**
 * Container exception.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when the container cannot resolve an identifier.
 */
final class ContainerException extends RuntimeException {}
