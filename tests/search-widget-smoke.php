<?php
// Homepage search widget smoke test: SearchService duration/budget
// ranges, the duration-days mirror sync, REST arg validation, the
// shared SearchWidgetRenderer (cached country options, escaping,
// CSS-only tabs), identical markup across shortcode and Elementor
// surfaces, no-JS archive filtering, and homepage injection.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );
define( 'MINUTE_IN_SECONDS', 60 );

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
		public string $post_name = '',
	) {}
}

class WP_Query {
	public array $posts = array();
	public int $found_posts = 0;
	public int $max_num_pages = 1;
	public array $vars = array();
	public bool $main = false;
	public string $pt_archive = '';
	public string $taxonomy = '';
	public function __construct( public array $query_args = array() ) {
		if ( array() !== $query_args ) {
			$GLOBALS['wp_query_args'][] = $query_args;
			$this->posts = $GLOBALS['wp_query_posts'] ?? array();
			$this->found_posts = count( $this->posts );
		}
	}
	public function is_main_query() { return $this->main; }
	public function is_post_type_archive( $pt = '' ) { return $this->pt_archive === $pt; }
	public function is_tax( $tax = '' ) { return '' === $tax ? '' !== $this->taxonomy : $this->taxonomy === $tax; }
	public function get( $k, $d = '' ) { return $this->vars[ $k ] ?? $d; }
	public function set( $k, $v ) { $this->vars[ $k ] = $v; }
}

class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; }
class WP_REST_Request {
	public function __construct( private array $params = array() ) {}
	public function get_params(): array { return $this->params; }
	public function get_param( $k ) { return $this->params[ $k ] ?? null; }
}
class WP_REST_Response {
	public array $headers = array();
	public function __construct( public mixed $data = null ) {}
	public function header( $k, $v ) { $this->headers[ $k ] = $v; }
}

$GLOBALS['options']       = array( 'ztc_version' => '1.0.0' );
$GLOBALS['postmeta']      = array();
$GLOBALS['posts']         = array();
$GLOBALS['transients']    = array();
$GLOBALS['routes']        = array();
$GLOBALS['hooks']         = array();
$GLOBALS['shortcodes']    = array();
$GLOBALS['wp_query_args'] = array();
$GLOBALS['get_posts_log'] = 0;
$GLOBALS['front']         = array( 'front_page' => false, 'main_query' => true, 'in_loop' => true, 'the_id' => 50 );

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
function is_admin() { return $GLOBALS['is_admin'] ?? false; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_attr_e( $t, $d = 'default' ) { echo esc_attr( $t ); }
function esc_url( $t ) { return (string) $t; }
function esc_url_raw( $t ) { return (string) $t; }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_json_encode( $v, $f = 0 ) { return json_encode( $v, $f ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_hex_color( $c ) { return (string) $c; }
function sanitize_html_class( $t ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $t ) ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function absint( $v ) { return abs( (int) $v ); }
function checked( $a, $b ) { if ( (string) $a === (string) $b ) { echo ' checked'; } }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'Test Site'; }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function get_post( $id ) { return $GLOBALS['posts'][ (int) $id ] ?? null; }
function get_post_type( $id ) { return $GLOBALS['posts'][ (int) $id ]->post_type ?? ''; }
function get_posts( $args ) {
	++$GLOBALS['get_posts_log'];
	$out = array();
	foreach ( $GLOBALS['posts'] as $p ) {
		if ( ! empty( $args['post_type'] ) && $p->post_type !== $args['post_type'] ) { continue; }
		$out[] = $p;
	}
	return $out;
}
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['postmeta'][ (int) $id ][ $key ] = $value;
	do_action( 'updated_post_meta', 1, (int) $id, $key, $value ); // real WP fires this
	return true;
}
function delete_post_meta( $id, $key ) {
	unset( $GLOBALS['postmeta'][ (int) $id ][ $key ] );
	do_action( 'deleted_post_meta', array( 1 ), (int) $id, $key, '' );
	return true;
}
function get_the_title( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? $p->post_title : ''; }
function get_permalink( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? "https://example.test/{$p->post_type}/{$p->ID}/" : ''; }
function get_the_excerpt( $p ) { return ''; }
function get_post_thumbnail_id( $p ) { return 0; }
function wp_get_attachment_image_url( $id, $size ) { return false; }
function get_the_terms( $id, $tax ) { return array(); }
function wp_list_pluck( $list, $field ) { return array_map( static fn( $i ) => is_object( $i ) ? $i->$field : $i[ $field ], $list ); }
function get_terms( $args ) {
	$bank = array(
		'ztc_visa_type' => array( array( 'tourist', 'Tourist' ), array( 'student', 'Student' ) ),
		'ztc_tour_type' => array( array( 'adventure', 'Adventure' ), array( 'family', 'Family' ) ),
		'ztc_region'    => array( array( 'asia', 'Asia' ) ),
	);
	return array_map(
		static function ( $row ) { $t = new stdClass(); $t->slug = $row[0]; $t->name = $row[1]; return $t; },
		$bank[ $args['taxonomy'] ?? '' ] ?? array()
	);
}
function get_post_type_archive_link( $pt ) { return 'https://example.test/archive/' . $pt . '/'; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function is_front_page() { return $GLOBALS['front']['front_page']; }
function is_main_query() { return $GLOBALS['front']['main_query']; }
function in_the_loop() { return $GLOBALS['front']['in_loop']; }
function get_the_ID() { return $GLOBALS['front']['the_id']; }
function is_wp_error( $x ) { return false; }
function current_user_can( ...$a ) { return true; }
function wp_unslash( $v ) { return $v; }
function wp_enqueue_style( $h ) { $GLOBALS['enqueued'][] = $h; }
function wp_enqueue_script( $h ) { $GLOBALS['enqueued'][] = $h; }
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
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function wp_doing_ajax() { return false; }
function is_network_admin() { return false; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Modules\Search\ArchiveFilters;
use ZihadTravelCMS\Modules\Search\HomepageSearch;
use ZihadTravelCMS\Modules\Search\SearchController;
use ZihadTravelCMS\Modules\Search\SearchService;
use ZihadTravelCMS\Modules\Tour\TourDurationSync;
use ZihadTravelCMS\Modules\Tour\TourMeta;
use ZihadTravelCMS\Views\SearchWidgetRenderer;

$plugin = ztc();
$plugin->boot();

$service  = $plugin->get( SearchService::class );
$renderer = $plugin->get( SearchWidgetRenderer::class );

// Content: two countries (one hostile title) and a tour.
$GLOBALS['posts'] = array(
	20 => new WP_Post( 20, 'ztc_country', 'Japan' ),
	21 => new WP_Post( 21, 'ztc_country', '<script>alert(1)</script>land' ),
	10 => new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' ),
);

// --- 1. SearchService: duration + budget ranges → NUMERIC clauses.
$service->search(
	array(
		'type'      => 'tour',
		'duration'  => '4-7',
		'budget'    => '500-1000',
		'country'   => 20,
		'tour_type' => 'adventure',
	)
);
$args = end( $GLOBALS['wp_query_args'] );
$meta = $args['meta_query'];
assert( 3 === count( $meta ) );
assert( TourMeta::COUNTRY === $meta[0]['key'] && 20 === $meta[0]['value'] );
assert( TourMeta::PRICE === $meta[1]['key'] && array( 500.0, 1000.0 ) === $meta[1]['value'] && 'BETWEEN' === $meta[1]['compare'] );
assert( TourMeta::DURATION_DAYS === $meta[2]['key'] && array( 4.0, 7.0 ) === $meta[2]['value'] && 'NUMERIC' === $meta[2]['type'] );
assert( 'ztc_tour_type' === $args['tax_query'][0]['taxonomy'] );

// Open-ended range, junk rejected, regression on min/max price.
$service->search( array( 'type' => 'tour', 'duration' => '15-0' ) );
$meta = end( $GLOBALS['wp_query_args'] )['meta_query'];
assert( 15.0 === $meta[0]['value'] && '>=' === $meta[0]['compare'] );

$service->search( array( 'type' => 'tour', 'duration' => 'abc', 'budget' => '0-0' ) );
assert( ! isset( end( $GLOBALS['wp_query_args'] )['meta_query'] ) );

$service->search( array( 'type' => 'tour', 'min_price' => 100, 'max_price' => 2000 ) );
$meta = end( $GLOBALS['wp_query_args'] )['meta_query'];
assert( array( 100.0, 2000.0 ) === $meta[0]['value'] );

// filter_clauses is the public, shared translation (visa ignores duration).
$clauses = $service->filter_clauses( 'visa', array( 'visa_type' => 'tourist', 'country' => 20, 'duration' => '4-7' ) );
assert( 'ztc_visa_type' === $clauses['tax_query'][0]['taxonomy'] );
assert( 1 === count( $clauses['meta_query'] ) && 'ztc_country' === $clauses['meta_query'][0]['key'] );
echo "search service ranges: OK\n";

// --- 2. REST args: documented, validated, sanitized, public read-only.
$plugin->get( SearchController::class )->register_routes();
[ , $route_args ] = $GLOBALS['routes']['/search'][0];
assert( '__return_true' === $route_args['permission_callback'] );
foreach ( array( 'budget', 'duration' ) as $param ) {
	$schema = $route_args['args'][ $param ];
	assert( 'string' === $schema['type'] );
	assert( '' !== $schema['description'] );
	assert( str_contains( $schema['pattern'], '^\d+' ) );
	assert( 'sanitize_text_field' === $schema['sanitize_callback'] );
	assert( 1 === preg_match( '#' . $schema['pattern'] . '#', '4-7' ) );
	assert( 1 === preg_match( '#' . $schema['pattern'] . '#', '' ) );        // empty allowed
	assert( 0 === preg_match( '#' . $schema['pattern'] . '#', '4-7;DROP' ) );
}
echo "rest args: OK\n";

// --- 3. Duration mirror sync (fires from every meta write path).
$plugin->get( TourDurationSync::class )->register();
update_post_meta( 10, TourMeta::DURATION, array( 'days' => '5', 'nights' => '4' ) );
assert( 5 === get_post_meta( 10, TourMeta::DURATION_DAYS ) );
update_post_meta( 10, TourMeta::DURATION, array( 'days' => '12' ) );
assert( 12 === get_post_meta( 10, TourMeta::DURATION_DAYS ) );
update_post_meta( 20, TourMeta::DURATION, array( 'days' => '9' ) );          // not a tour
assert( '' === get_post_meta( 20, TourMeta::DURATION_DAYS ) );
delete_post_meta( 10, TourMeta::DURATION );
assert( '' === get_post_meta( 10, TourMeta::DURATION_DAYS ) );
echo "duration mirror sync: OK\n";

// --- 4. Renderer: cached country options + invalidation.
$before = $GLOBALS['get_posts_log'];
$first  = $renderer->country_options();
assert( array( 20 => 'Japan', 21 => '<script>alert(1)</script>land' ) === $first );
assert( $GLOBALS['get_posts_log'] === $before + 1 );
$renderer->country_options();
assert( $GLOBALS['get_posts_log'] === $before + 1 );                         // served from transient
do_action( 'save_post_ztc_country', 20 );                                    // SearchModule flush hook
assert( false === get_transient( SearchWidgetRenderer::COUNTRIES_TRANSIENT ) );
$renderer->country_options();
assert( $GLOBALS['get_posts_log'] === $before + 2 );
echo "country options cache: OK\n";

// --- 5. Renderer markup: CSS tabs, both forms, all filters, escaping.
$html = $renderer->render( array( 'heading' => 'Find your trip', 'default' => 'tour' ) );
assert( str_contains( $html, 'Find your trip' ) );
assert( 2 === substr_count( $html, 'type="radio"' ) );                        // CSS-only tabs
assert( 1 === substr_count( $html, ' checked' ) );
assert( str_contains( $html, 'ztc-search-widget__radio--tour' ) );
assert( str_contains( $html, 'data-ztc-type="visa"' ) && str_contains( $html, 'data-ztc-type="tour"' ) );
assert( str_contains( $html, 'action="https://example.test/archive/ztc_visa/"' ) );  // no-JS target
assert( str_contains( $html, 'action="https://example.test/archive/ztc_tour/"' ) );
assert( str_contains( $html, 'name="country"' ) && str_contains( $html, 'name="visa_type"' ) );
assert( str_contains( $html, 'name="tour_type"' ) && str_contains( $html, 'name="duration"' ) && str_contains( $html, 'name="budget"' ) );
assert( str_contains( $html, 'value="4-7"' ) && str_contains( $html, '8–14 days' ) );
assert( str_contains( $html, 'Up to USD 500' ) && str_contains( $html, 'USD 2,000 and up' ) );
assert( str_contains( $html, 'value="2000-0"' ) );
assert( str_contains( $html, 'data-ztc-results' ) );                          // AJAX container
assert( ! str_contains( $html, '<script>alert(1)</script>' ) );               // hostile title escaped
assert( str_contains( $html, '&lt;script&gt;alert(1)&lt;/script&gt;land' ) );
assert( in_array( 'ztc-frontend', $GLOBALS['enqueued'] ?? array(), true ) );

// Tab args validated; visa-only widget has no duration/budget.
$visa_only = $renderer->render( array( 'tabs' => 'visa', 'default' => 'junk' ) );
assert( 1 === substr_count( $visa_only, 'type="radio"' ) );
assert( ! str_contains( $visa_only, 'name="duration"' ) );
assert( str_contains( $renderer->render( array( 'tabs' => 'junk,also-junk' ) ), 'data-ztc-type="visa"' ) ); // falls back to defaults
echo "widget markup: OK\n";

// --- 6. One render path: shortcode and Elementor output match.
$shortcode = call_user_func( $GLOBALS['shortcodes']['ztc_search_widget'], array( 'heading' => 'Same', 'default' => 'visa' ) );

$GLOBALS['el_settings'] = array( 'heading' => 'Same', 'tabs' => 'visa,tour', 'default_tab' => 'visa' );
require_once ZTC_PLUGIN_DIR . 'includes/Modules/Elementor/Widgets/SearchWidget.php';
$widget = new ZihadTravelCMS\Modules\Elementor\Widgets\SearchWidget();
$render = new ReflectionMethod( $widget, 'render' );
$render->setAccessible( true );
ob_start();
$render->invoke( $widget );
$elementor = ob_get_clean();

$normalize = static fn( string $html ): string => (string) preg_replace( '/ztc-search-widget-\d+/', 'UID', $html );
assert( $normalize( $shortcode ) === $normalize( $elementor ), 'shortcode and Elementor markup diverged' );
echo "single render path: OK\n";

// --- 7. No-JS archive filters (same clauses as REST, main query only).
$filters = $plugin->get( ArchiveFilters::class );

$_GET = array( 'duration' => '4-7', 'budget' => '500-1000', 'country' => '20', 'tour_type' => 'adventure', 'evil' => 'x' );
$query = new WP_Query();
$query->main = true;
$query->pt_archive = 'ztc_tour';
$filters->apply( $query );
$meta = $query->get( 'meta_query' );
assert( 3 === count( $meta ) );
assert( TourMeta::DURATION_DAYS === $meta[2]['key'] );
assert( 'ztc_tour_type' === $query->get( 'tax_query' )[0]['taxonomy'] );

$untouched = new WP_Query();                                                   // not main query
$untouched->pt_archive = 'ztc_tour';
$filters->apply( $untouched );
assert( array() === $untouched->vars );

$blog = new WP_Query();                                                        // not a plugin archive
$blog->main = true;
$filters->apply( $blog );
assert( array() === $blog->vars );

$GLOBALS['is_admin'] = true;                                                   // admin queries untouched
$admin_query = new WP_Query();
$admin_query->main = true;
$admin_query->pt_archive = 'ztc_tour';
$filters->apply( $admin_query );
assert( array() === $admin_query->vars );
$GLOBALS['is_admin'] = false;
$_GET = array();
echo "archive filters: OK\n";

// --- 8. Homepage injection: setting-gated, once, Elementor-safe.
$homepage = $plugin->get( HomepageSearch::class );

$GLOBALS['front']['front_page'] = false;
assert( 'body' === $homepage->inject( 'body' ) );                              // not the front page

$GLOBALS['front']['front_page'] = true;
$injected = $homepage->inject( 'body' );
assert( str_contains( $injected, 'ztc-search-widget' ) && str_ends_with( $injected, 'body' ) );
assert( 'body' === $homepage->inject( 'body' ) );                              // only once per request

$fresh = new HomepageSearch( $plugin->get( ZihadTravelCMS\Core\Config::class ), $renderer );
$GLOBALS['postmeta'][50]['_elementor_edit_mode'] = 'builder';
assert( 'body' === $fresh->inject( 'body' ) );                                 // Elementor page → widget instead
unset( $GLOBALS['postmeta'][50]['_elementor_edit_mode'] );

$veto = static fn() => false;
add_filter( 'ztc_show_homepage_search', $veto );
assert( 'body' === $fresh->inject( 'body' ) );                                 // theme veto
remove_filter( 'ztc_show_homepage_search', $veto );

$GLOBALS['options']['ztc_settings'] = array( 'homepage' => array( 'show_search' => false ) );
$plugin->get( ZihadTravelCMS\Core\Config::class )->refresh();
assert( 'body' === $fresh->inject( 'body' ) );                                 // setting off
echo "homepage injection: OK\n";

echo "ALL SEARCH WIDGET TESTS PASSED\n";
