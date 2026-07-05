<?php
// Settings & dashboard smoke test: schema, sanitizer, settings page
// (render + save pipeline), REST controller, integrations output and
// dashboard data.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ZTC_PLUGIN_URL', 'https://example.test/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );

class WP_Post {
	public function __construct(
		public int $ID = 0,
		public string $post_type = '',
		public string $post_title = '',
		public string $post_name = '',
	) {}
}
class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; }
class WP_REST_Request {
	public function __construct( private array $params = array() ) {}
	public function get_param( $k ) { return $this->params[ $k ] ?? null; }
	public function get_params(): array { return $this->params; }
}
class WP_REST_Response {
	public array $headers = array();
	public function __construct( public mixed $data = null ) {}
	public function header( $k, $v ) { $this->headers[ $k ] = $v; }
}

$GLOBALS['options'] = array( 'ztc_version' => '1.0.0' );
$GLOBALS['routes'] = array();
$GLOBALS['transients'] = array();

// --- WP stubs.
function add_action( ...$a ) {}
function add_filter( ...$a ) {}
function add_shortcode( ...$a ) {}
function do_action( ...$a ) {}
function did_action( $h ) { return 0; }
function apply_filters( $h, $v ) { return $v; }
function is_admin() { return false; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_attr_e( $t, $d = 'default' ) { echo esc_attr( $t ); }
function esc_url( $t ) { return (string) $t; }
function esc_url_raw( $t ) { return preg_match( '#^https?://#i', (string) $t ) ? (string) $t : ''; }
function esc_js( $t ) { return addslashes( (string) $t ); }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function wp_kses_post( $t ) { return (string) $t; }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $c ) ? (string) $c : ''; }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'Test Agency'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }
function get_posts( $args ) { return array( new WP_Post( 20, 'ztc_country', 'Japan', 'japan' ) ); }
function wp_count_posts( $t ) { $o = new stdClass(); $map = array( 'ztc_country' => 105, 'ztc_visa' => 473, 'ztc_tour' => 132 ); $o->publish = $map[ $t ] ?? 0; return $o; }
function get_post_meta( $id, $k, $s = true ) { return ''; }
function update_post_meta( ...$a ) { return true; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function wp_nonce_field( $action, $name = '_wpnonce' ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce-ok">'; }
function check_admin_referer( $action ) { return 'nonce-ok' === ( $_POST['_wpnonce'] ?? '' ); }
function current_user_can( ...$a ) { return true; }
function wp_unslash( $v ) { return $v; }
function wp_enqueue_media() {}
function submit_button( $label ) { echo '<button type="submit">' . esc_html( $label ) . '</button>'; }
function wp_attachment_is_image( $id ) { return true; }
function wp_get_attachment_image( $id, $s, $i = false, $a = array() ) { return '<img>'; }
function wp_get_attachment_image_url( $id, $s ) { return $id > 0 ? "https://img.test/{$id}.jpg" : false; }
function get_the_title( $p ) { return 'File'; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['routes'][ $route ][] = array( $ns, $args ); }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'missing template: ' . $a[1] ); }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function get_current_user_id() { return 1; }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function untrailingslashit( $p ) { return rtrim( (string) $p, '/' ); }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Admin\DashboardData;
use ZihadTravelCMS\Admin\Pages\SettingsPage;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Frontend\Integrations;
use ZihadTravelCMS\Settings\GlobalSettings;
use ZihadTravelCMS\Settings\SettingsController;
use ZihadTravelCMS\Settings\SettingsSanitizer;

$plugin = ztc();
$plugin->boot();

$config    = $plugin->get( Config::class );
$sanitizer = $plugin->get( SettingsSanitizer::class );

// --- 1. Schema: new sections exist with sane defaults.
foreach ( array( 'general', 'homepage', 'company', 'social', 'whatsapp', 'integrations', 'booking', 'performance', 'custom_code' ) as $section ) {
	assert( is_array( $config->defaults()[ $section ] ), "missing section $section" );
}
assert( 300 === $config->get( 'performance.cache_ttl' ) );
assert( true === $config->get( 'performance.load_bootstrap' ) );
echo "schema: OK\n";

// --- 2. Sanitizer: unknown keys dropped, types coerced, nesting kept.
$dirty = array(
	'general'     => array( 'currency' => 'BDT', 'evil' => 'x', 'default_country' => '20' ),
	'performance' => array( 'cache_ttl' => '120', 'lazy_load' => '1' ),
	'hacked'      => array( 'a' => 1 ),
	'custom_code' => array( 'css' => 'a > b { color: red; }' ),
);
$clean = $sanitizer->sanitize( $dirty );
assert( ! isset( $clean['hacked'], $clean['general']['evil'] ) );
assert( 'BDT' === $clean['general']['currency'] );
assert( 20 === $clean['general']['default_country'] );   // int coercion
assert( 120 === $clean['performance']['cache_ttl'] );
assert( true === $clean['performance']['lazy_load'] );   // bool coercion
assert( 'a > b { color: red; }' === $clean['custom_code']['css'] ); // CSS untouched
echo "sanitizer: OK\n";

// --- 3. Settings page: 11 tabs render with prefilled values.
$page = $plugin->get( SettingsPage::class );
$GLOBALS['options']['ztc_settings'] = array( 'company' => array( 'name' => 'Zihad Travels' ) );
$config->refresh();

ob_start();
$page->render();
$html = ob_get_clean();

assert( 11 === substr_count( $html, 'role="tab"' ) );
$order = array( 'General', 'Homepage', 'Branding', 'Contact', 'Social Media', 'WhatsApp', 'Maps', 'Analytics', 'Booking', 'Performance', 'Custom CSS/JS' );
$pos   = -1;
foreach ( $order as $label ) {
	$next = strpos( $html, '<span class="ztc-editor__tab-text">' . $label . '</span>' );
	assert( false !== $next, "missing tab: $label" );
	assert( $next > $pos, "tab out of order: $label" );
	$pos = $next;
}
assert( str_contains( $html, 'value="Zihad Travels"' ) );                       // prefilled
assert( str_contains( $html, 'name="ztc_fields[company.brand_color]"' ) );
assert( str_contains( $html, 'name="ztc_fields[custom_code.js]"' ) );
assert( str_contains( $html, 'name="ztc_fields[integrations.maps_api_key]"' ) );
assert( str_contains( $html, '<option value="20">Japan</option>' ) );           // default-country select
assert( str_contains( $html, 'action="ztc_save_settings"' ) || str_contains( $html, 'value="ztc_save_settings"' ) );
assert( 2 === substr_count( $html, 'ztc-code' ) );                              // CodeField used twice
echo "settings page render: OK (11 tabs)\n";

// --- 4. Save pipeline: per-field sanitize + one batched write.
$page->persist(
	array(
		'general.currency'         => ' BDT ',
		'general.default_country'  => '20',
		'company.name'             => '<b>Zihad</b> Travels',
		'company.whatsapp'         => '+8801711000000',
		'whatsapp.floating_button' => '1',
		'social.facebook'          => 'javascript:alert(1)',
		'integrations.ga_id'       => 'G-ABC123',
		'performance.cache_ttl'    => '60',
		'performance.load_bootstrap' => '1',
		'performance.lazy_load'    => '1',
		'homepage.show_search'     => '1',
		'booking.enable_visa_inquiry' => '1',
		'booking.enable_tour_inquiry' => '1',
		'custom_code.css'          => '.x { color: red; }</style><script>evil()</script>',
		'custom_code.js'           => 'console.log("hi");</script>',
	)
);

$saved = $GLOBALS['options']['ztc_settings'];
assert( 'BDT' === $saved['general']['currency'] );
assert( 20 === $saved['general']['default_country'] );
assert( 'Zihad Travels' === $saved['company']['name'] );            // tags stripped
assert( '' === $saved['social']['facebook'] );                      // bad URL rejected
assert( true === $saved['whatsapp']['floating_button'] );
assert( 60 === $saved['performance']['cache_ttl'] );
assert( ! str_contains( $saved['custom_code']['css'], '</style' ) ); // injection guard
assert( str_contains( $saved['custom_code']['js'], 'console.log' ) );
assert( ! str_contains( $saved['custom_code']['js'], '</script' ) );

// Nonce guard: forged request never persists.
$GLOBALS['options']['ztc_settings'] = array();
$_POST = array( '_wpnonce' => 'forged', 'ztc_fields' => array( 'general.currency' => 'EUR' ) );
$page->save();
assert( array() === $GLOBALS['options']['ztc_settings'] );
echo "save pipeline + nonce guard: OK\n";

// --- 5. REST controller: get + partial update.
$GLOBALS['options']['ztc_settings'] = $saved;
$config->refresh();
$controller = $plugin->get( SettingsController::class );
$controller->register_routes();
assert( isset( $GLOBALS['routes']['/settings'] ) );

$response = $controller->get_settings();
assert( 'BDT' === $response->data['general']['currency'] );
assert( true === $response->data['performance']['load_bootstrap'] ); // defaults merged

$response = $controller->update_settings( new WP_REST_Request( array( 'settings' => array( 'company' => array( 'phone' => '+880123', 'evil' => 'x' ) ) ) ) );
assert( '+880123' === $response->data['company']['phone'] );
assert( 'BDT' === $response->data['general']['currency'] );          // partial update kept other values
assert( ! isset( $response->data['company']['evil'] ) );
echo "rest settings: OK\n";

// --- 6. GlobalSettings getters + Elementor-ready values.
$settings = $plugin->get( GlobalSettings::class );
assert( 'Zihad Travels' === $settings->company_name() );
assert( 'G-ABC123' === $settings->analytics_id() );
assert( 20 === $settings->default_country() );
assert( true === $settings->floating_whatsapp_enabled() );
assert( str_starts_with( $settings->whatsapp_link( 'Hi' ), 'https://wa.me/8801711000000?text=' ) );
echo "global settings getters: OK\n";

// --- 7. Integrations frontend output.
$integrations = $plugin->get( Integrations::class );
ob_start();
$integrations->head_output();
$head = ob_get_clean();
assert( str_contains( $head, '--ztc-brand:#0d6efd' ) );              // brand vars
assert( str_contains( $head, 'gtag/js?id=G-ABC123' ) );              // GA snippet
assert( str_contains( $head, 'ztc-custom-css' ) && str_contains( $head, 'color: red' ) );
// Breakout guard: only our own two style blocks close; the injected
// </style> was stripped at save, so anything after it stays inert
// CDATA inside the style element.
assert( 2 === substr_count( $head, '</style>' ) );
assert( ! preg_match( '#</style>\s*<script>evil#', $head ) );

ob_start();
$integrations->footer_output();
$footer = ob_get_clean();
assert( str_contains( $footer, 'console.log' ) );                    // custom JS
assert( str_contains( $footer, 'ztc-whatsapp-fab' ) );               // floating button
assert( str_contains( $footer, 'https://wa.me/8801711000000' ) );
echo "integrations output: OK\n";

// --- 8. Dashboard data.
$GLOBALS['options']['ztc_demo_installed'] = 1;
$stats = $plugin->get( DashboardData::class )->stats();
assert( 105 === $stats['counts']['country']['count'] );
assert( 473 === $stats['counts']['visa']['count'] );
assert( true === $stats['demo']['installed'] );
assert( true === $stats['demo']['files_ready'] );                    // committed demo files exist
assert( is_array( $stats['imports'] ) );

ob_start();
ztc()->get( ZihadTravelCMS\Helpers\Template::class )->render( 'admin/dashboard.php', $stats );
$dash = ob_get_clean();
assert( str_contains( $dash, '105' ) && str_contains( $dash, '473' ) );
assert( str_contains( $dash, 'Installed' ) );
assert( str_contains( $dash, 'Add Tour' ) );
echo "dashboard: OK\n";

echo "ALL SETTINGS & DASHBOARD TESTS PASSED\n";
