<?php
// Boot smoke test: boot the whole kernel with WP function stubs.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/wp-content/plugins/zihad-travel-cms/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );

$GLOBALS['hooks']   = array();
$GLOBALS['actions'] = array();

function add_filter( ...$a ) {}
function add_shortcode( ...$a ) {}
function add_action( $hook, $cb, $prio = 10 ) { $GLOBALS['hooks'][] = $hook; }
function apply_filters( $hook, $value ) { return $value; }
function do_action( $hook, ...$args ) { $GLOBALS['actions'][] = $hook; }
function is_admin() { return false; }
function did_action( $h ) { return 0; }
function esc_html( $s ) { return $s; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

$plugin = ZihadTravelCMS\Plugin::instance();
$plugin->boot();
$plugin->boot(); // idempotency check

assert( in_array( 'ztc_booted', $GLOBALS['actions'], true ) );
assert( in_array( 'ztc_modules_loaded', $GLOBALS['actions'], true ) );
assert( 1 === count( array_keys( $GLOBALS['actions'], 'ztc_booted', true ) ) ); // booted once

// Core services resolved as shared singletons.
$assets = $plugin->get( ZihadTravelCMS\Core\Assets::class );
assert( $assets === $plugin->get( ZihadTravelCMS\Core\Assets::class ) );

// Hooks attached by core services (I18n, Assets, Upgrade, Settings, REST).
foreach ( array( 'init', 'admin_enqueue_scripts', 'wp_enqueue_scripts', 'rest_api_init' ) as $expected ) {
	assert( in_array( $expected, $GLOBALS['hooks'], true ), "missing hook: $expected" );
}

// Template renderer resolvable (autowires Config).
assert( $plugin->get( ZihadTravelCMS\Helpers\Template::class ) instanceof ZihadTravelCMS\Helpers\Template );

echo "BOOT SMOKE TEST PASSED — hooks: " . count( $GLOBALS['hooks'] ) . ", actions fired: " . implode( ', ', $GLOBALS['actions'] ) . "\n";
