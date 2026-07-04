<?php
// Booking/inquiry smoke test: the shared submission pipeline
// (sanitization, server-side validation, honeypot, IP rate limit),
// inquiry persistence, Mailer abstraction + notification content,
// the REST endpoint (validated args, status codes), the nonce-guarded
// no-JS form handler, the shared renderer across shortcode/Elementor/
// template surfaces, and admin list columns.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );
define( 'MINUTE_IN_SECONDS', 60 );

$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

// Minimal Elementor runtime so the real widget class can render.
eval(
	'namespace Elementor;
	class Widget_Base {
		public function __construct( $data = array(), $args = null ) {}
		public function get_settings_for_display() { return $GLOBALS["el_settings"] ?? array(); }
		public function start_controls_section( ...$a ) {}
		public function add_control( ...$a ) {}
		public function end_controls_section() {}
	}
	class Controls_Manager { const TEXT = "text"; const SELECT = "select"; const NUMBER = "number"; }'
);

class WP_Post {
	public function __construct(
		public int $ID = 0,
		public string $post_type = '',
		public string $post_title = '',
		public string $post_status = 'publish',
	) {}
}
class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; }
class WP_REST_Request {
	public function __construct( private array $params = array() ) {}
	public function get_params(): array { return $this->params; }
	public function get_param( $k ) { return $this->params[ $k ] ?? null; }
}
class WP_REST_Response {
	public int $status = 200;
	public function __construct( public mixed $data = null ) {}
	public function set_status( $code ) { $this->status = (int) $code; }
	public function header( ...$a ) {}
}
class ZtcRedirect extends Exception {}

$GLOBALS['posts']      = array(
	30 => new WP_Post( 30, 'ztc_visa', 'Japan Tourist Visa' ),
	10 => new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' ),
);
$GLOBALS['postmeta']   = array();
$GLOBALS['next_id']    = 100;
$GLOBALS['options']    = array( 'ztc_version' => '1.0.0' );
$GLOBALS['transients'] = array();
$GLOBALS['routes']     = array();
$GLOBALS['hooks']      = array();
$GLOBALS['shortcodes'] = array();
$GLOBALS['mail']       = array();
$GLOBALS['wp_mail']    = array();

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
function did_action( $h ) { return 0; }

// --- WP stubs.
function is_admin() { return false; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_url( $t ) { return (string) $t; }
function esc_url_raw( $t ) { return (string) $t; }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_json_encode( $v, $f = 0 ) { return json_encode( $v, $f ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_email( $t ) { return (string) preg_replace( '/[^a-zA-Z0-9._@+-]/', '', (string) $t ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_hex_color( $c ) { return (string) $c; }
function is_email( $t ) { return false !== filter_var( (string) $t, FILTER_VALIDATE_EMAIL ); }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'admin_email' === $k ? 'admin@example.test' : 'Test Agency'; }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function get_post( $id ) { return $GLOBALS['posts'][ (int) $id ] ?? null; }
function wp_insert_post( $arr, $wp_error = false ) {
	$id = $GLOBALS['next_id']++;
	$GLOBALS['posts'][ $id ] = new WP_Post( $id, (string) $arr['post_type'], (string) $arr['post_title'], (string) ( $arr['post_status'] ?? 'publish' ) );
	return $id;
}
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['postmeta'][ (int) $id ][ $key ] = $value; return true; }
function get_the_title( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? $p->post_title : ''; }
function get_permalink( $p ) { return 'https://example.test/x/'; }
function get_edit_post_link( $id ) { return 'https://example.test/wp-admin/post.php?post=' . (int) $id . '&action=edit'; }
function get_posts( $args ) { return array(); }
function get_terms( $args ) { return array(); }
function get_the_terms( $id, $tax ) { return array(); }
function wp_list_pluck( $list, $field ) { return array(); }
function wp_mail( $to, $subject, $message, $headers = array() ) { $GLOBALS['wp_mail'][] = compact( 'to', 'subject', 'message', 'headers' ); return true; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function add_query_arg( $key, $value, $url ) { return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . $key . '=' . $value; }
function wp_get_referer() { return 'https://example.test/visa/japan-tourist-visa/'; }
function wp_safe_redirect( $url ) { throw new ZtcRedirect( (string) $url ); }
function wp_nonce_field( $action, $name = '_wpnonce' ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce-ok">'; }
function check_admin_referer( $action ) { return 'nonce-ok' === ( $_REQUEST['_wpnonce'] ?? '' ); }
function current_user_can( ...$a ) { return true; }
function wp_unslash( $v ) { return $v; }
function wp_enqueue_style( $h ) {}
function wp_enqueue_script( $h ) {}
function add_shortcode( $tag, $cb ) { $GLOBALS['shortcodes'][ $tag ] = $cb; }
function shortcode_atts( $defaults, $atts, $tag = '' ) {
	$out = array();
	foreach ( $defaults as $k => $v ) { $out[ $k ] = $atts[ $k ] ?? $v; }
	return $out;
}
function register_rest_route( $ns, $route, $args ) { $GLOBALS['routes'][ $route ][] = array( $ns, $args ); }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'missing template: ' . $a[1] ); }
function set_query_var( $k, $v ) {}
function get_query_var( $k, $d = '' ) { return $d; }
function is_singular( $pt = '' ) { return false; }
function is_post_type_archive( $pt = '' ) { return false; }
function is_tax( $tax = '' ) { return false; }
function get_queried_object_id() { return 0; }
function get_queried_object() { return null; }
function get_post_type_archive_link( $pt ) { return 'https://example.test/archive/' . $pt . '/'; }
function checked( $a, $b ) { if ( (string) $a === (string) $b ) { echo ' checked'; } }
function is_front_page() { return false; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function get_the_ID() { return 0; }
function get_post_thumbnail_id( $p ) { return 0; }
function wp_get_attachment_image_url( $id, $s ) { return false; }
function wp_doing_ajax() { return false; }
function is_network_admin() { return false; }
function is_wp_error( $x ) { return false; }
function get_post_type( $id ) { return $GLOBALS['posts'][ (int) $id ]->post_type ?? ''; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Contracts\Mailer;
use ZihadTravelCMS\Modules\Booking\InquiryColumns;
use ZihadTravelCMS\Modules\Booking\InquiryController;
use ZihadTravelCMS\Modules\Booking\InquiryFormHandler;
use ZihadTravelCMS\Modules\Booking\InquiryMeta;
use ZihadTravelCMS\Modules\Booking\InquiryPostType;
use ZihadTravelCMS\Modules\Booking\InquiryService;
use ZihadTravelCMS\Services\WpMailer;
use ZihadTravelCMS\Views\InquiryFormRenderer;

// A capturing mailer, swapped in through the ztc_mailer contract filter.
final class TestMailer implements Mailer {
	public function send( string $to, string $subject, string $message, array $headers = array() ): bool {
		$GLOBALS['mail'][] = compact( 'to', 'subject', 'message', 'headers' );
		return true;
	}
}
add_filter( 'ztc_mailer', static fn() => TestMailer::class );

$plugin = ztc();
$plugin->boot();

$service  = $plugin->get( InquiryService::class );
$renderer = $plugin->get( InquiryFormRenderer::class );

$created = array();
add_action( 'ztc_inquiry_created', static function ( $id ) use ( &$created ) { $created[] = $id; return $id; } );
$spam_blocked = 0;
add_action( 'ztc_inquiry_spam_blocked', static function ( $d ) use ( &$spam_blocked ) { ++$spam_blocked; return $d; } );

$valid = array(
	'name'    => 'Rahim <b>Uddin</b>',
	'email'   => 'rahim@example.test',
	'phone'   => '+880 1711-000000',
	'message' => 'We are four people planning for December.',
	'type'    => 'visa',
	'post_id' => 30,
	'website' => '',
);

// --- 1. Mailer abstraction: default proxies wp_mail; filter swaps it.
( new WpMailer() )->send( 'a@b.test', 'Hi', 'Body', array( 'X: 1' ) );
assert( 1 === count( $GLOBALS['wp_mail'] ) && 'a@b.test' === $GLOBALS['wp_mail'][0]['to'] );
assert( $plugin->get( Mailer::class ) instanceof TestMailer );          // ztc_mailer filter respected
echo "mailer abstraction: OK\n";

// --- 2. Happy path: sanitize → persist → notify → action.
$GLOBALS['options']['ztc_settings'] = array( 'booking' => array( 'notification_email' => 'sales@agency.test' ) );
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();

$result = $service->submit( $valid );
assert( InquiryService::SENT === $result['status'] && $result['id'] > 0 );
assert( array( $result['id'] ) === $created );                          // ztc_inquiry_created fired

$inquiry = get_post( $result['id'] );
assert( InquiryPostType::NAME === $inquiry->post_type );
assert( str_contains( $inquiry->post_title, 'Rahim Uddin' ) && str_contains( $inquiry->post_title, 'Japan Tourist Visa' ) );
assert( 'Rahim Uddin' === get_post_meta( $result['id'], InquiryMeta::NAME ) );  // tags stripped
assert( 'rahim@example.test' === get_post_meta( $result['id'], InquiryMeta::EMAIL ) );
assert( 'visa' === get_post_meta( $result['id'], InquiryMeta::TYPE ) );
assert( 30 === get_post_meta( $result['id'], InquiryMeta::SUBJECT ) );
assert( 'new' === get_post_meta( $result['id'], InquiryMeta::STATUS ) );

assert( 1 === count( $GLOBALS['mail'] ) );
$mail = $GLOBALS['mail'][0];
assert( 'sales@agency.test' === $mail['to'] );                          // Booking setting
assert( 'New visa inquiry: Japan Tourist Visa' === $mail['subject'] );
assert( str_contains( $mail['message'], 'rahim@example.test' ) );
assert( str_contains( $mail['message'], 'We are four people' ) );
assert( str_contains( $mail['message'], 'post.php?post=' . $result['id'] ) );
assert( str_contains( $mail['headers'][0], 'Reply-To: Rahim Uddin <rahim@example.test>' ) );
echo "happy path: OK\n";

// --- 3. Recipient fallback chain (setting → company email → admin email).
$GLOBALS['options']['ztc_settings'] = array( 'company' => array( 'email' => 'office@agency.test' ) );
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();
$service->submit( $valid );
assert( 'office@agency.test' === end( $GLOBALS['mail'] )['to'] );

$GLOBALS['options']['ztc_settings'] = array();
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();
$service->submit( $valid );
assert( 'admin@example.test' === end( $GLOBALS['mail'] )['to'] );       // site admin fallback
echo "recipient fallbacks: OK\n";

// --- 4. Server-side validation (nothing stored, nothing sent).
$GLOBALS['transients'] = array();                                       // reset rate counter
$posts_before          = count( $GLOBALS['posts'] );
$mail_before           = count( $GLOBALS['mail'] );

$result = $service->submit( array( 'name' => '', 'email' => 'not-an-email', 'message' => '', 'type' => 'junk' ) );
assert( InquiryService::INVALID === $result['status'] );
assert( isset( $result['errors']['name'], $result['errors']['email'], $result['errors']['message'], $result['errors']['type'] ) );

$result = $service->submit( array_merge( $valid, array( 'message' => str_repeat( 'x', 5001 ) ) ) );
assert( isset( $result['errors']['message'] ) );                        // length cap

$result = $service->submit( array_merge( $valid, array( 'post_id' => 10 ) ) );
assert( isset( $result['errors']['post_id'] ) );                        // tour post on a visa inquiry

$GLOBALS['options']['ztc_settings'] = array( 'booking' => array( 'enable_visa_inquiry' => false ) );
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();
$result = $service->submit( $valid );
assert( isset( $result['errors']['type'] ) );                           // toggle respected
$GLOBALS['options']['ztc_settings'] = array();
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();

$custom_rule = static function ( array $errors ): array { $errors['custom'] = 'nope'; return $errors; };
add_filter( 'ztc_inquiry_validate', $custom_rule );
assert( isset( $service->submit( $valid )['errors']['custom'] ) );      // extensible validation
remove_filter( 'ztc_inquiry_validate', $custom_rule );

assert( count( $GLOBALS['posts'] ) === $posts_before );                 // nothing persisted
assert( count( $GLOBALS['mail'] ) === $mail_before );                   // nothing sent
echo "server-side validation: OK\n";

// --- 5. Honeypot: pretend success, store nothing, tell no one.
$result = $service->submit( array_merge( $valid, array( 'website' => 'http://spam.example' ) ) );
assert( InquiryService::SENT === $result['status'] && 0 === $result['id'] );
assert( 1 === $spam_blocked );
assert( count( $GLOBALS['posts'] ) === $posts_before );
assert( count( $GLOBALS['mail'] ) === $mail_before );
echo "honeypot: OK\n";

// --- 6. Rate limit: capped per IP, window filterable.
$GLOBALS['transients'] = array();
$tight                 = static fn() => array( 2, 60 );
add_filter( 'ztc_inquiry_rate_limit', $tight );
assert( InquiryService::SENT === $service->submit( $valid )['status'] );
assert( InquiryService::SENT === $service->submit( $valid )['status'] );
$result = $service->submit( $valid );
assert( InquiryService::LIMITED === $result['status'] && isset( $result['errors']['rate'] ) );
remove_filter( 'ztc_inquiry_rate_limit', $tight );
$GLOBALS['transients'] = array();
echo "rate limit: OK\n";

// --- 7. REST endpoint: documented args, status codes, shared pipeline.
$controller = $plugin->get( InquiryController::class );
$controller->register_routes();
[ , $route_args ] = $GLOBALS['routes']['/inquiry'][0];
assert( '__return_true' === $route_args['permission_callback'] );
assert( true === $route_args['args']['name']['required'] );
assert( 'email' === $route_args['args']['email']['format'] );
assert( array( 'visa', 'tour' ) === $route_args['args']['type']['enum'] );
foreach ( $route_args['args'] as $arg ) {
	assert( '' !== (string) ( $arg['description'] ?? '' ), 'undocumented REST arg' );
}

$response = $controller->handle( new WP_REST_Request( $valid ) );
assert( 200 === $response->status && true === $response->data['success'] );

$response = $controller->handle( new WP_REST_Request( array( 'name' => '', 'email' => 'x', 'message' => '', 'type' => 'visa' ) ) );
assert( 400 === $response->status && false === $response->data['success'] );
assert( isset( $response->data['errors']['name'] ) );

add_filter( 'ztc_inquiry_rate_limit', $tight );
$controller->handle( new WP_REST_Request( $valid ) );
$controller->handle( new WP_REST_Request( $valid ) );
$response = $controller->handle( new WP_REST_Request( $valid ) );
assert( 429 === $response->status );
remove_filter( 'ztc_inquiry_rate_limit', $tight );
$GLOBALS['transients'] = array();
echo "rest endpoint: OK\n";

// --- 8. No-JS form handler: nonce-guarded, redirects with a flag.
$handler      = $plugin->get( InquiryFormHandler::class );
$posts_before = count( $GLOBALS['posts'] );

$_POST = $_REQUEST = array_merge( $valid, array( '_wpnonce' => 'forged' ) );
$handler->handle();
assert( count( $GLOBALS['posts'] ) === $posts_before );                 // forged nonce writes nothing

$_POST = $_REQUEST = array_merge( $valid, array( '_wpnonce' => 'nonce-ok' ) );
try {
	$handler->handle();
	assert( false, 'handler must redirect' );
} catch ( ZtcRedirect $r ) {
	assert( str_contains( $r->getMessage(), 'ztc_inquiry=sent' ) );
	assert( str_contains( $r->getMessage(), '#ztc-inquiry-visa-30' ) ); // stable anchor
	assert( str_starts_with( $r->getMessage(), 'https://example.test/visa/' ) );
}
assert( count( $GLOBALS['posts'] ) === $posts_before + 1 );
$_POST = $_REQUEST = array();
echo "form handler: OK\n";

// --- 9. Renderer: gated by settings, escaped, no-JS complete.
$GLOBALS['options']['ztc_settings'] = array( 'booking' => array( 'enable_tour_inquiry' => false, 'success_message' => 'Cool "quotes" & <script>alert(1)</script>' ) );
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();

assert( array() === $renderer->data( array( 'type' => 'tour' ) ) );     // disabled type → no form anywhere
assert( '' === $renderer->render( array( 'type' => 'tour' ) ) );

$html = $renderer->render( array( 'type' => 'visa', 'post_id' => 30 ) );
assert( str_contains( $html, 'id="ztc-inquiry-visa-30"' ) );            // stable anchor target
assert( str_contains( $html, 'action="https://example.test/wp-admin/admin-post.php"' ) );
assert( str_contains( $html, 'value="ztc_inquiry"' ) );                 // admin-post action
assert( str_contains( $html, 'value="nonce-ok"' ) );                    // nonce field
assert( str_contains( $html, 'name="website"' ) && str_contains( $html, 'tabindex="-1"' ) ); // honeypot
assert( str_contains( $html, 'name="type" value="visa"' ) && str_contains( $html, 'name="post_id" value="30"' ) );
assert( str_contains( $html, 'data-ztc-inquiry' ) );                    // JS enhancement hook
assert( 3 === substr_count( $html, ' required' ) );                     // name, email, message
assert( str_contains( $html, 'Ask about this visa' ) );                 // default heading
assert( ! str_contains( $html, '<script>alert(1)</script>' ) );         // hostile setting escaped
assert( str_contains( $html, '&lt;script&gt;' ) );

$_GET = array( 'ztc_inquiry' => 'sent' );
$html = $renderer->render( array( 'type' => 'visa', 'post_id' => 30 ) );
assert( ! str_contains( $html, '<form' ) );                             // form replaced by confirmation
assert( str_contains( $html, 'Cool &quot;quotes&quot;' ) );
$_GET = array( 'ztc_inquiry' => 'limited' );
$html = $renderer->render( array( 'type' => 'visa', 'post_id' => 30 ) );
assert( str_contains( $html, 'Too many inquiries' ) && str_contains( $html, '<form' ) );
$_GET = array();
echo "renderer: OK\n";

// --- 10. One render path: shortcode, Elementor and template data agree.
$shortcode = call_user_func( $GLOBALS['shortcodes']['ztc_inquiry_form'], array( 'type' => 'visa', 'post_id' => '30' ) );

$GLOBALS['el_settings'] = array( 'inquiry_type' => 'visa', 'post_id' => 30, 'heading' => '' );
$widget = new ZihadTravelCMS\Modules\Elementor\Widgets\InquiryFormWidget();
$render = new ReflectionMethod( $widget, 'render' );
$render->setAccessible( true );
ob_start();
$render->invoke( $widget );
$elementor = ob_get_clean();
assert( $shortcode === $elementor, 'shortcode and Elementor markup diverged' ); // stable uid → byte-equal

$view = apply_filters( 'ztc_template_view', array( 'id' => 30, 'title' => 'Japan Tourist Visa' ), 'single-visa.php' );
assert( 'visa' === $view['inquiry']['type'] && 30 === $view['inquiry']['post_id'] );
$view = apply_filters( 'ztc_template_view', array( 'id' => 10 ), 'single-tour.php' );
assert( array() === $view['inquiry'] );                                 // tour inquiries disabled above
$view = apply_filters( 'ztc_template_view', array( 'id' => 20 ), 'single-country.php' );
assert( ! isset( $view['inquiry'] ) );                                  // countries have no form
echo "single render path + template data: OK\n";

// --- 11. Admin columns render escaped, useful cells.
$columns = $plugin->get( InquiryColumns::class );
$cols    = $columns->columns( array( 'cb' => '<input>', 'title' => 'Title', 'date' => 'Date' ) );
assert( array( 'cb', 'title', 'ztc_contact', 'ztc_type', 'ztc_subject', 'ztc_status', 'date' ) === array_keys( $cols ) );

$id = $created[0];
ob_start();
$columns->render_column( 'ztc_contact', $id );
$cell = ob_get_clean();
assert( str_contains( $cell, 'mailto:rahim@example.test' ) );
ob_start();
$columns->render_column( 'ztc_subject', $id );
$cell = ob_get_clean();
assert( str_contains( $cell, 'Japan Tourist Visa' ) && str_contains( $cell, 'post=30' ) );
ob_start();
$columns->render_column( 'ztc_status', $id );
assert( 'New' === trim( ob_get_clean() ) );
echo "admin columns: OK\n";

echo "ALL BOOKING/INQUIRY TESTS PASSED\n";
