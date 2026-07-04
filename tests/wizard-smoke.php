<?php
// Setup wizard smoke test: step registry integrity, independent
// per-step saves through the settings pipeline (no-overwrite
// invariant), nonce guards, resume/skip/finish/reset semantics, the
// admin page render (no-JS forms), REST controller, the activation
// prompt, and a full no-JS demo install driven through the real import
// engine. Runs a REAL hook system (module wiring is filter-driven).
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );
define( 'MINUTE_IN_SECONDS', 60 );

// --- In-memory WordPress store (same bank as the importer suite).
class WP_Post {
	public int $ID = 0;
	public string $post_type = '';
	public string $post_title = '';
	public string $post_name = '';
	public string $post_status = 'publish';
	public string $post_content = '';
	public string $post_excerpt = '';
	public function __construct( array $data = array() ) {
		foreach ( $data as $k => $v ) { $this->$k = $v; }
	}
}
class WP_Error {
	public function __construct( public string $code = '', public string $message = '', public mixed $data = null ) {}
	public function get_error_message(): string { return $this->message; }
	public function get_error_code(): string { return $this->code; }
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
class ZtcRedirect extends Exception {}

$GLOBALS['store']        = array( 'posts' => array(), 'meta' => array(), 'terms' => array(), 'thumbs' => array(), 'next_id' => 100 );
$GLOBALS['options']      = array( 'ztc_version' => '1.0.0', 'permalink_structure' => '/%postname%/' );
$GLOBALS['source_index'] = array();
$GLOBALS['transients']   = array();
$GLOBALS['routes']       = array();
$GLOBALS['hooks']        = array();
$GLOBALS['can']          = true;

// --- Real (minimal) hook system.
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
	$GLOBALS['hooks'][ $hook ][ $priority ][] = $cb;
	return true;
}
function add_action( ...$a ) { return add_filter( ...$a ); }
function apply_filters( $hook, $value, ...$args ) {
	if ( empty( $GLOBALS['hooks'][ $hook ] ) ) { return $value; }
	ksort( $GLOBALS['hooks'][ $hook ] );
	foreach ( $GLOBALS['hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $cb ) { $value = $cb( $value, ...$args ); }
	}
	return $value;
}
function do_action( $hook, ...$args ) {
	if ( empty( $GLOBALS['hooks'][ $hook ] ) ) { return; }
	ksort( $GLOBALS['hooks'][ $hook ] );
	foreach ( $GLOBALS['hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $cb ) { $cb( ...$args ); }
	}
}
function remove_filter( $hook, $cb, $priority = 10 ) {
	foreach ( $GLOBALS['hooks'][ $hook ][ $priority ] ?? array() as $i => $registered ) {
		if ( $registered === $cb ) { unset( $GLOBALS['hooks'][ $hook ][ $priority ][ $i ] ); }
	}
	return true;
}
function remove_action( ...$a ) { return remove_filter( ...$a ); }
function did_action( $h ) { return 0; }

// --- WP stubs.
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
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_js( $t ) { return addslashes( (string) $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_json_encode( $v, $f = 0 ) { return json_encode( $v, $f ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $c ) ? (string) $c : ''; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_title( $t ) { return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $t ) ), '-' ); }
function sanitize_file_name( $t ) { return (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $t ); }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'version' === $k ? '6.5' : 'Test Agency'; }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function untrailingslashit( $p ) { return rtrim( (string) $p, '/' ); }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function current_user_can( ...$a ) { return $GLOBALS['can']; }
function get_current_user_id() { return 1; }
function wp_unslash( $v ) { return $v; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function wp_nonce_field( $action, $name = '_wpnonce' ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce-ok">'; }
function wp_nonce_url( $url, $action ) { return $url . '&_wpnonce=nonce-ok'; }
function check_admin_referer( $action ) { return 'nonce-ok' === ( $_REQUEST['_wpnonce'] ?? '' ); }
function wp_safe_redirect( $url ) { throw new ZtcRedirect( (string) $url ); }
function wp_get_referer() { return 'https://example.test/wp-admin/'; }
function wp_doing_ajax() { return false; }
function is_network_admin() { return false; }
function wp_enqueue_media() {}
function wp_enqueue_style( $h ) {}
function wp_enqueue_script( $h ) {}
function submit_button( $label ) {}
function wp_attachment_is_image( $id ) { return true; }
function wp_get_attachment_image( $id, $s, $i = false, $a = array() ) { return '<img>'; }
function wp_get_attachment_image_url( $id, $s ) { return $id > 0 ? "https://img.test/{$id}.jpg" : false; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['routes'][ $route ][] = array( $ns, $args ); }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'missing template: ' . $a[1] ); }
function wp_remote_get( $url, $args = array() ) { return array( 'code' => 200 ); }
function wp_remote_retrieve_response_code( $r ) { return 200; }
function wp_using_ext_object_cache() { return false; }
function wp_list_pluck( $list, $field ) { return array_map( static fn( $i ) => is_object( $i ) ? $i->$field : $i[ $field ], $list ); }
function shortcode_atts( $defaults, $atts, $tag = '' ) { return array_merge( $defaults, (array) $atts ); }
function add_shortcode( ...$a ) {}

// --- Content store (posts/meta/terms — the import engine writes here).
function wp_insert_post( $arr, $wp_error = false ) {
	$id = $GLOBALS['store']['next_id']++;
	$arr['ID'] = $id;
	$GLOBALS['store']['posts'][ $id ] = new WP_Post( $arr );
	return $id;
}
function wp_update_post( $arr, $wp_error = false ) {
	$id = (int) $arr['ID'];
	foreach ( $arr as $k => $v ) { $GLOBALS['store']['posts'][ $id ]->$k = $v; }
	return $id;
}
function wp_delete_post( $id, $force = false ) { unset( $GLOBALS['store']['posts'][ (int) $id ] ); return true; }
function get_post( $id ) { return $GLOBALS['store']['posts'][ (int) $id ] ?? null; }
function get_posts( $args ) {
	if ( 'attachment' === ( $args['post_type'] ?? '' ) && ! empty( $args['meta_key'] ) ) {
		$id = $GLOBALS['source_index'][ (string) ( $args['meta_value'] ?? '' ) ] ?? 0;
		return $id > 0 ? array( $id ) : array();
	}
	$out = array();
	foreach ( $GLOBALS['store']['posts'] as $p ) {
		if ( ! empty( $args['post_type'] ) && $p->post_type !== $args['post_type'] ) { continue; }
		if ( ! empty( $args['name'] ) && $p->post_name !== $args['name'] ) { continue; }
		if ( ! empty( $args['meta_key'] ) ) {
			$mv = $GLOBALS['store']['meta'][ $p->ID ][ $args['meta_key'] ] ?? null;
			if ( (string) $mv !== (string) ( $args['meta_value'] ?? '' ) ) { continue; }
		}
		$out[] = $p;
	}
	$limit = (int) ( $args['posts_per_page'] ?? -1 );
	if ( $limit > 0 ) { $out = array_slice( $out, 0, $limit ); }
	if ( 'ids' === ( $args['fields'] ?? '' ) ) { return array_map( static fn( $p ) => $p->ID, $out ); }
	return $out;
}
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['store']['meta'][ (int) $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['store']['meta'][ (int) $id ][ $key ] = $value;
	if ( '_ztc_source_url' === $key ) { $GLOBALS['source_index'][ (string) $value ] = (int) $id; }
	return true;
}
function wp_set_object_terms( $id, $terms, $tax, $append ) { $GLOBALS['store']['terms'][ (int) $id ][ $tax ] = (array) $terms; return $terms; }
function get_the_terms( $id, $tax ) {
	return array_map( static function ( $n ) { $t = new stdClass(); $t->name = $n; return $t; }, $GLOBALS['store']['terms'][ (int) $id ][ $tax ] ?? array() );
}
function set_post_thumbnail( $id, $tid ) { $GLOBALS['store']['thumbs'][ (int) $id ] = (int) $tid; return true; }
function get_post_thumbnail_id( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return $GLOBALS['store']['thumbs'][ $id ] ?? 0; }
function get_the_title( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return isset( $GLOBALS['store']['posts'][ $id ] ) ? $GLOBALS['store']['posts'][ $id ]->post_title : ''; }
function get_permalink( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? "https://example.test/{$p->post_type}/{$p->ID}/" : ''; }
function wp_get_attachment_url( $id ) { return 'https://site.test/uploads/' . (int) $id . '.jpg'; }
function wp_count_posts( $t ) {
	$o = new stdClass();
	$o->publish = count( array_filter( $GLOBALS['store']['posts'], static fn( $p ) => $p->post_type === $t ) );
	return $o;
}
function wp_basename( $p ) { return basename( (string) $p ); }
function wp_parse_url( $u, $c ) { return parse_url( (string) $u, $c ); }
function wp_mkdir_p( $dir ) { return is_dir( $dir ) || mkdir( $dir, 0777, true ); }
function wp_delete_file( $f ) {}
function download_url( $url, $timeout = 30 ) { return '/tmp/fake-download'; }
function media_handle_sideload( $file_array, $parent_id ) {
	return wp_insert_post( array( 'post_type' => 'attachment', 'post_title' => $file_array['name'], 'post_status' => 'inherit' ) );
}

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Admin\Pages\WizardPage;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Arr;
use ZihadTravelCMS\Modules\ModuleManager;
use ZihadTravelCMS\Modules\Wizard\WizardController;
use ZihadTravelCMS\Modules\Wizard\WizardPrompt;
use ZihadTravelCMS\Modules\Wizard\WizardService;

$plugin = ztc();
$plugin->boot();

$config = $plugin->get( Config::class );
$wizard = $plugin->get( WizardService::class );
$page   = $plugin->get( WizardPage::class );

// --- 1. Module wiring through the existing filters.
assert( null !== $plugin->get( ModuleManager::class )->get( 'wizard' ) );
assert( in_array( WizardPage::class, apply_filters( 'ztc_admin_pages', array() ), true ) );
assert( in_array( WizardController::class, apply_filters( 'ztc_rest_controllers', array() ), true ) );
echo "module wiring: OK\n";

// --- 2. Step registry: order, and every field maps to a real settings key.
$expected = array( 'welcome', 'company', 'branding', 'contact', 'social', 'whatsapp', 'maps', 'analytics', 'homepage', 'demo', 'finish' );
assert( $expected === $wizard->step_ids() );
assert( 11 === count( $wizard->steps() ) );

$field_count = 0;
foreach ( $wizard->steps() as $step ) {
	assert( '' !== $step['title'] && '' !== $step['intro'] );
	foreach ( $step['fields'] as $field ) {
		++$field_count;
		assert( '__missing__' !== Arr::get( $config->defaults(), $field->name(), '__missing__' ), "unknown settings key {$field->name()}" );
	}
}
assert( $field_count >= 25 );
assert( array() === $wizard->step( 'welcome' )['fields'] );
assert( null === $wizard->step( 'nope' ) );
echo "step registry: OK ({$field_count} fields, all schema-backed)\n";

// --- 3. ztc_wizard_steps filter extends the registry.
$extra_step = static function ( array $steps ): array {
	$steps[] = array( 'id' => 'booking-intro', 'title' => 'Booking', 'intro' => 'x', 'fields' => array() );
	return $steps;
};
add_filter( 'ztc_wizard_steps', $extra_step );
assert( in_array( 'booking-intro', $wizard->step_ids(), true ) );
remove_filter( 'ztc_wizard_steps', $extra_step );
assert( ! in_array( 'booking-intro', $wizard->step_ids(), true ) );
echo "steps filter: OK\n";

// --- 4. Independent per-step saves: only the step's keys change.
$GLOBALS['options']['ztc_settings'] = array(
	'company'     => array( 'name' => 'Existing Agency' ),
	'custom_code' => array( 'js' => 'console.log(1);' ),
);
$config->refresh();

assert( true === $wizard->save_step(
	'company',
	array(
		'company.name'              => '<b>Zihad</b> Travels',
		'general.currency'          => ' BDT ',
		'general.currency_position' => 'junk-value',
		'hacked.key'                => 'x',
	)
) );

$saved = $GLOBALS['options']['ztc_settings'];
assert( 'Zihad Travels' === $saved['company']['name'] );                 // sanitized (tags stripped)
assert( 'BDT' === $saved['general']['currency'] );
assert( 'before' === $saved['general']['currency_position'] );          // select fell back to default
assert( 'console.log(1);' === $saved['custom_code']['js'] );            // untouched key preserved
assert( ! isset( $saved['hacked'] ) );                                   // structural gate
assert( in_array( 'company', $wizard->state()['completed'], true ) );

// Saving a fieldless step only marks progress.
$before = $GLOBALS['options']['ztc_settings'];
assert( true === $wizard->save_step( 'welcome' ) );
assert( $before === $GLOBALS['options']['ztc_settings'] );
assert( false === $wizard->save_step( 'nope' ) );
echo "independent saves + no-overwrite: OK\n";

// --- 5. Resume, skip, finish, reset (settings survive reset).
assert( 'branding' === $wizard->next_step() );                           // welcome+company done

$ended = array();
add_filter( 'ztc_wizard_completed', static function ( $how ) use ( &$ended ) { $ended[] = $how; return $how; } );

$wizard->skip();
assert( $wizard->is_completed() && true === $wizard->state()['skipped'] );
$wizard->reset();
assert( ! $wizard->is_completed() && array() === $wizard->state()['completed'] );
assert( 'Zihad Travels' === $config->get( 'company.name' ) );            // reset never touches settings
assert( 'welcome' === $wizard->next_step() );
$wizard->finish();
assert( true === $wizard->state()['finished'] );
assert( array( 'skipped', 'finished' ) === $ended );                     // ztc_wizard_completed fired
$wizard->reset();
echo "resume/skip/finish/reset: OK\n";

// --- 6. Admin page: nonce guards write nothing; happy path saves + redirects.
$_POST = $_REQUEST = array(
	'_wpnonce'   => 'forged',
	'ztc_step'   => 'contact',
	'ztc_fields' => array( 'company.email' => 'evil@x.test' ),
);
$page->save();
assert( '' === (string) ( $GLOBALS['options']['ztc_settings']['company']['email'] ?? '' ) );
assert( array() === $wizard->state()['completed'] );

$_POST = $_REQUEST = array(
	'_wpnonce'   => 'nonce-ok',
	'ztc_step'   => 'contact',
	'ztc_fields' => array( 'company.email' => 'hello@agency.test', 'company.phone' => ' +880123 ' ),
);
try {
	$page->save();
	assert( false, 'save() must redirect' );
} catch ( ZtcRedirect $r ) {
	assert( str_contains( $r->getMessage(), 'page=zihad-travel-cms-setup&step=social' ) ); // next step
}
assert( 'hello@agency.test' === $config->get( 'company.email' ) );
assert( '+880123' === $config->get( 'company.phone' ) );

// Finish via the form completes the wizard and lands on the dashboard.
$_POST = $_REQUEST = array( '_wpnonce' => 'nonce-ok', 'ztc_step' => 'finish' );
try {
	$page->save();
} catch ( ZtcRedirect $r ) {
	assert( str_contains( $r->getMessage(), 'page=zihad-travel-cms' ) );
}
assert( $wizard->is_completed() );

// Skip-all and restart handlers.
$wizard->reset();
$_POST = $_REQUEST = array( '_wpnonce' => 'nonce-ok' );
try { $page->skip_wizard(); } catch ( ZtcRedirect $r ) {}
assert( true === $wizard->state()['skipped'] );
try { $page->restart(); } catch ( ZtcRedirect $r ) {
	assert( str_contains( $r->getMessage(), 'page=zihad-travel-cms-setup' ) );
}
assert( ! $wizard->is_completed() );
$_POST = $_REQUEST = array();
echo "page handlers + nonce guards: OK\n";

// --- 7. Render: pre-filled fields, no-JS forms, step indicator.
$_GET = array( 'step' => 'company' );
ob_start();
$page->render();
$html = ob_get_clean();
assert( str_contains( $html, 'Step 2 of 11' ) );
assert( str_contains( $html, 'name="ztc_fields[company.name]"' ) );
assert( str_contains( $html, 'value="Zihad Travels"' ) );                // pre-filled = visible before overwrite
assert( str_contains( $html, 'value="nonce-ok"' ) );                     // nonce field
assert( str_contains( $html, 'Save &amp; Continue' ) || str_contains( $html, 'Save & Continue' ) );
assert( str_contains( $html, 'step=welcome' ) );                         // back link
assert( 11 === substr_count( $html, 'ztc-wizard__step' ) - substr_count( $html, 'ztc-wizard__steps' ) );

$_GET = array( 'step' => 'welcome' );
ob_start();
$page->render();
$html = ob_get_clean();
assert( str_contains( $html, 'Start setup' ) && str_contains( $html, 'Skip setup' ) );
assert( str_contains( $html, 'action=ztc_wizard_skip&_wpnonce=nonce-ok' ) );

$_GET = array( 'step' => 'demo' );
ob_start();
$page->render();
$html = ob_get_clean();
assert( str_contains( $html, 'data-ztc-demo' ) );                        // JS path (existing admin.js)
assert( str_contains( $html, '<noscript>' ) );                           // no-JS fallback form
assert( str_contains( $html, 'value="ztc_wizard_demo"' ) );
assert( str_contains( $html, 'Skip this step' ) );

$_GET = array( 'step' => 'finish' );
ob_start();
$page->render();
$html = ob_get_clean();
assert( str_contains( $html, 'Rewrite Rules' ) );                        // permalink check surfaced
assert( str_contains( $html, 'Pretty permalinks active' ) );
assert( str_contains( $html, 'Restart wizard' ) );
$_GET = array();
echo "render: OK\n";

// --- 8. Resume point drives the default view.
$wizard->reset();
$wizard->mark_complete( 'welcome' );
$wizard->mark_complete( 'company' );
ob_start();
$page->render();                                                          // no ?step → resume at branding
$html = ob_get_clean();
assert( str_contains( $html, 'Step 3 of 11' ) && str_contains( $html, 'Branding' ) );
echo "resume rendering: OK\n";

// --- 9. REST controller.
$controller = $plugin->get( WizardController::class );
$controller->register_routes();
foreach ( array( '/wizard', '/wizard/step', '/wizard/skip', '/wizard/complete', '/wizard/reset' ) as $route ) {
	assert( isset( $GLOBALS['routes'][ $route ] ) );
}

$state = $controller->get_state()->data;
assert( false === $state['completed'] && 'branding' === $state['next'] );
assert( 11 === count( $state['steps'] ) );
assert( 'company.name' === $state['steps'][1]['fields'][0]['name'] );
assert( 'Zihad Travels' === $state['steps'][1]['fields'][0]['value'] );

$error = $controller->save_step( new WP_REST_Request( array( 'id' => 'nope', 'values' => array() ) ) );
assert( $error instanceof WP_Error && 'ztc_wizard_unknown_step' === $error->get_error_code() );

$response = $controller->save_step( new WP_REST_Request( array( 'id' => 'analytics', 'values' => array( 'integrations.ga_id' => ' G-TEST1 ' ) ) ) );
assert( 'G-TEST1' === $config->get( 'integrations.ga_id' ) );
assert( in_array( 'analytics', $response->data['state']['completed'], true ) );

$controller->complete();
assert( true === $controller->get_state()->data['completed'] );
$controller->reset();
assert( false === $controller->get_state()->data['completed'] );
echo "rest controller: OK\n";

// --- 10. Activation prompt: one-shot redirect + notice.
$prompt = $plugin->get( WizardPrompt::class );
$prompt->register();                                                      // module only registers it in wp-admin

do_action( 'ztc_activated' );                                            // Activator fires this
assert( 1 === $GLOBALS['options'][ WizardService::REDIRECT_OPTION ] );
try {
	$prompt->maybe_redirect();
	assert( false, 'must redirect on fresh install' );
} catch ( ZtcRedirect $r ) {
	assert( str_contains( $r->getMessage(), 'page=zihad-travel-cms-setup' ) );
}
assert( ! isset( $GLOBALS['options'][ WizardService::REDIRECT_OPTION ] ) ); // one-shot
$prompt->maybe_redirect();                                                // no flag → no redirect

$wizard->skip();
do_action( 'ztc_activated' );
$prompt->maybe_redirect();                                                // completed → flag consumed, no redirect
assert( ! isset( $GLOBALS['options'][ WizardService::REDIRECT_OPTION ] ) );

$_GET = array( 'page' => 'zihad-travel-cms' );
ob_start();
$prompt->render_notice();
assert( '' === ob_get_clean() );                                          // completed → no nag
$wizard->reset();
ob_start();
$prompt->render_notice();
$notice = ob_get_clean();
assert( str_contains( $notice, 'Run the setup wizard' ) );
$_GET = array( 'page' => 'zihad-travel-cms-setup' );
ob_start();
$prompt->render_notice();
assert( '' === ob_get_clean() );                                          // never on the wizard itself
$_GET = array();
echo "activation prompt: OK\n";

// --- 11. No-JS demo install: bounded batches through the real importer.
$wizard->reset();
$first = $wizard->advance_demo( 1, 50 );                                  // one bounded slice
assert( false === $first['finished'] );
assert( 'country' === $first['type'] && 50 === $first['processed'] );
$progress = $wizard->demo()['progress'];
assert( 'country' === $progress['type'] && '' !== (string) $progress['job'] ); // resumable between requests

$rounds = 0;
do {
	$result = $wizard->advance_demo( 10, 50 );
	assert( ++$rounds < 30, 'demo install did not converge' );
} while ( ! $result['finished'] );

assert( in_array( 'demo', $wizard->state()['completed'], true ) );
assert( array() === $wizard->demo()['progress'] );
assert( wp_count_posts( 'ztc_country' )->publish >= 100 );
assert( wp_count_posts( 'ztc_visa' )->publish >= 400 );
assert( wp_count_posts( 'ztc_tour' )->publish >= 100 );

$japan = get_posts( array( 'post_type' => 'ztc_country', 'name' => 'japan' ) )[0];
assert( 'জাপান' === get_post_meta( $japan->ID, 'ztc_bangla_name' ) );    // Bangla intact end to end
echo "no-js demo install: OK ({$rounds} continue-clicks)\n";

echo "ALL SETUP WIZARD TESTS PASSED\n";
