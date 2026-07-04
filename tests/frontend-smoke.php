<?php
// Frontend engine smoke test: template routing, single + archive
// rendering, shortcodes, AJAX search service and REST controller.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/wp-content/plugins/zihad-travel-cms/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );

class WP_Post {
	public function __construct(
		public int $ID = 0,
		public string $post_type = '',
		public string $post_title = '',
	) {}
}

class WP_Query {
	public array $posts = array();
	public int $found_posts = 0;
	public int $max_num_pages = 1;
	public function __construct( public array $query_args = array() ) {
		if ( array() !== $query_args ) {
			$GLOBALS['wp_query_args'][] = $query_args;
			$this->posts       = $GLOBALS['wp_query_posts'] ?? array();
			$this->found_posts = count( $this->posts );
		}
	}
}

class WP_REST_Server { const READABLE = 'GET'; }
class WP_REST_Request {
	public function __construct( private array $params = array() ) {}
	public function get_params(): array { return $this->params; }
}
class WP_REST_Response {
	public array $headers = array();
	public function __construct( public mixed $data = null ) {}
	public function header( $key, $value ) { $this->headers[ $key ] = $value; }
}

$GLOBALS['posts'] = array(
	10 => new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' ),
	20 => new WP_Post( 20, 'ztc_country', 'Japan' ),
	30 => new WP_Post( 30, 'ztc_visa', 'Japan Tourist Visa' ),
);
$GLOBALS['postmeta'] = array(
	10 => array( 'ztc_price' => 1500.0, 'ztc_sale_price' => 0.0, 'ztc_duration' => array( 'days' => '5', 'nights' => '4' ), 'ztc_country' => 20 ),
	30 => array(
		'ztc_country'             => 20,
		'ztc_hero_image'          => 9,
		'ztc_processing_time'     => '7-10 days',
		'ztc_validity'            => '90 days',
		'ztc_stay_duration'       => '30 days',
		'ztc_entry_type'          => 'single',
		'ztc_visa_fee'            => 'USD 50',
		'ztc_requirements'        => '<p>Valid passport required.</p>',
		'ztc_required_documents'  => array( 'Passport', 'Photo' ),
		'ztc_benefits'            => array( 'Fast processing' ),
		'ztc_application_process' => array( array( 'title' => 'Submit documents', 'description' => 'Bring everything to our office.' ) ),
		'ztc_faq'                 => array( array( 'question' => 'How long does it take?', 'answer' => 'About a week.' ) ),
		'ztc_important_notes'     => '<strong>Fees are non-refundable.</strong>',
	),
);
$GLOBALS['options'] = array( 'ztc_settings' => array( 'company' => array( 'whatsapp' => '+15551234567' ) ), 'ztc_version' => '1.0.0' );
$GLOBALS['query_vars'] = array();
$GLOBALS['flags'] = array( 'singular' => null, 'archive' => null, 'tax' => null );
$GLOBALS['shortcodes'] = array();
$GLOBALS['routes'] = array();
$GLOBALS['wp_query_args'] = array();
$GLOBALS['enqueued'] = array();

// --- WP stubs.
function add_action( ...$a ) {}
function add_filter( ...$a ) {}
function do_action( ...$a ) {}
function did_action( $h ) { return 0; }
function apply_filters( $h, $v ) { return $v; }
function is_admin() { return false; }
function __( $t, $d = 'default' ) { return $t; }
function _n( $single, $plural, $number, $d = 'default' ) { return 1 === (int) $number ? $single : $plural; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_attr_e( $t, $d = 'default' ) { echo esc_attr( $t ); }
function esc_url( $t ) { return (string) $t; }
function esc_url_raw( $t ) { return (string) $t; }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_html_class( $t ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $t ) ); }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'Test Site'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { return true; }
function get_post( $id ) { return $GLOBALS['posts'][ (int) $id ] ?? null; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? ''; }
function get_the_title( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? $p->post_title : ''; }
function get_permalink( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? "https://example.test/{$p->post_type}/{$p->ID}/" : ''; }
function get_the_excerpt( $p ) { return 'An excerpt.'; }
function get_post_thumbnail_id( $p ) { return 7; }
function wp_get_attachment_image_url( $id, $size ) { return $id > 0 ? "https://img.test/{$id}-{$size}.jpg" : false; }
function get_the_terms( $id, $tax ) { $t = new stdClass(); $t->name = 'Tourist'; return array( $t ); }
function wp_list_pluck( $list, $field ) { return array_map( static fn( $i ) => is_object( $i ) ? $i->$field : $i[ $field ], $list ); }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function get_posts( $args ) { return $GLOBALS['get_posts_return'] ?? array(); }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'missing template: ' . $a[1] ); }
function set_query_var( $k, $v ) { $GLOBALS['query_vars'][ $k ] = $v; }
function get_query_var( $k, $d = '' ) { return $GLOBALS['query_vars'][ $k ] ?? $d; }
function is_singular( $pt = '' ) { return $GLOBALS['flags']['singular'] === $pt; }
function is_post_type_archive( $pt = '' ) { return $GLOBALS['flags']['archive'] === $pt; }
function is_tax( $tax = '' ) { return $GLOBALS['flags']['tax'] === $tax; }
function get_queried_object_id() { return $GLOBALS['queried_id'] ?? 0; }
function wp_enqueue_style( $h ) { $GLOBALS['enqueued'][] = $h; }
function wp_enqueue_script( $h ) { $GLOBALS['enqueued'][] = $h; }
function add_shortcode( $tag, $cb ) { $GLOBALS['shortcodes'][ $tag ] = $cb; }
function shortcode_atts( $defaults, $atts, $tag = '' ) {
	$atts = (array) $atts;
	$out  = array();
	foreach ( $defaults as $k => $v ) { $out[ $k ] = $atts[ $k ] ?? $v; }
	return $out;
}
function get_header() { echo '<!--header-->'; }
function get_footer() { echo '<!--footer-->'; }
function the_archive_title( $before = '', $after = '' ) { echo $before . 'Tours' . $after; }
function the_archive_description( $before = '', $after = '' ) {}
function the_posts_pagination( $args = array() ) { echo '<!--pagination-->'; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function get_terms( $args ) {
	$t = new stdClass();
	$t->term_id = 5; $t->slug = 'asia'; $t->name = 'Asia';
	return array( $t );
}
function is_wp_error( $x ) { return false; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['routes'][] = array( $ns, $route, $args ); }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Frontend\TemplateLoader;
use ZihadTravelCMS\Modules\Search\SearchController;
use ZihadTravelCMS\Modules\Search\SearchService;

$plugin = ztc();
$plugin->boot();

// --- 1. Template routing.
$loader = $plugin->get( TemplateLoader::class );

$GLOBALS['flags']['singular'] = 'ztc_visa';
$GLOBALS['queried_id']        = 30;
$file = $loader->resolve( '/theme/index.php' );
assert( str_ends_with( $file, 'templates/frontend/single-visa.php' ) );
assert( 'Japan Tourist Visa' === get_query_var( 'ztc_view' )['title'] );

$GLOBALS['flags']['singular'] = null;
assert( '/theme/index.php' === $loader->resolve( '/theme/index.php' ) ); // non-plugin route untouched
echo "template routing: OK\n";

// --- 2. Single visa renders end to end.
$GLOBALS['flags']['singular'] = 'ztc_visa';
$single_file                  = $loader->resolve( '/theme/index.php' );
ob_start();
include $single_file;
$html = ob_get_clean();

assert( str_contains( $html, '<!--header-->' ) && str_contains( $html, '<!--footer-->' ) );
assert( str_contains( $html, 'Japan Tourist Visa' ) );                       // hero title
assert( str_contains( $html, 'ztc-hero--image' ) );                          // hero image applied
assert( str_contains( $html, '<p>Valid passport required.</p>' ) );          // rich requirements
assert( str_contains( $html, 'Passport' ) && str_contains( $html, 'Photo' ) ); // documents checklist
assert( str_contains( $html, 'Submit documents' ) );                          // timeline step
assert( str_contains( $html, '7-10 days' ) );                                 // facts
assert( str_contains( $html, 'https://wa.me/15551234567' ) );                 // apply via global WhatsApp
assert( str_contains( $html, 'How long does it take?' ) );                    // FAQ item
assert( str_contains( $html, '"@type":"FAQPage"' ) );                         // JSON-LD schema
assert( str_contains( $html, 'Fees are non-refundable.' ) );                  // notes
echo "single visa render: OK\n";

// --- 3. Tour archive renders with cards, search form and pagination.
$GLOBALS['flags']['singular'] = null;
$GLOBALS['flags']['archive']  = 'ztc_tour';
$archive_query              = new WP_Query();
$archive_query->posts       = array( $GLOBALS['posts'][10] );
$archive_query->found_posts = 1;
$GLOBALS['wp_query']        = $archive_query;

$archive_file = $loader->resolve( '/theme/index.php' );
assert( str_ends_with( $archive_file, 'templates/frontend/archive-tour.php' ) );
ob_start();
include $archive_file;
$html = ob_get_clean();

assert( str_contains( $html, 'Tokyo Adventure' ) );                           // card rendered
assert( str_contains( $html, 'data-ztc-results' ) );                          // AJAX target
assert( str_contains( $html, 'data-ztc-search data-ztc-type="tour"' ) );      // search form wired
assert( str_contains( $html, 'name="tour_type"' ) && str_contains( $html, 'name="region"' ) );
assert( str_contains( $html, 'name="min_price"' ) );                          // price filter for tours
assert( str_contains( $html, '1 result' ) );
assert( str_contains( $html, '<!--pagination-->' ) );                         // SEO/no-JS pagination
assert( str_contains( $html, 'USD 1,500.00' ) );                              // formatted price on card
echo "tour archive render: OK\n";

// --- 4. Shortcodes.
assert( array( 'ztc_tours', 'ztc_visas', 'ztc_countries', 'ztc_search', 'ztc_search_widget', 'ztc_inquiry_form', 'ztc_cta' ) === array_keys( $GLOBALS['shortcodes'] ) );

$GLOBALS['get_posts_return'] = array( $GLOBALS['posts'][10] );
$html = call_user_func( $GLOBALS['shortcodes']['ztc_tours'], array( 'count' => '3', 'heading' => 'Top Tours' ) );
assert( str_contains( $html, 'Top Tours' ) && str_contains( $html, 'Tokyo Adventure' ) );
assert( in_array( 'ztc-frontend', $GLOBALS['enqueued'], true ) );             // assets enqueued on demand

$html = call_user_func( $GLOBALS['shortcodes']['ztc_cta'], array( 'title' => 'Ready to fly?' ) );
assert( str_contains( $html, 'Ready to fly?' ) && str_contains( $html, 'https://wa.me/15551234567' ) );

$html = call_user_func( $GLOBALS['shortcodes']['ztc_search'], array( 'type' => 'tour' ) );
assert( str_contains( $html, 'data-ztc-search' ) && str_contains( $html, 'data-ztc-results' ) );
echo "shortcodes: OK\n";

// --- 5. Search service: params → WP_Query args.
$GLOBALS['wp_query_posts'] = array( $GLOBALS['posts'][10] );
$service = $plugin->get( SearchService::class );
$result  = $service->search(
	array(
		'type'      => 'tour',
		's'         => 'tokyo',
		'region'    => 'asia',
		'tour_type' => 'adventure',
		'min_price' => 100,
		'max_price' => 2000,
		'orderby'   => 'price',
		'page'      => 2,
		'per_page'  => 6,
	)
);

$args = end( $GLOBALS['wp_query_args'] );
assert( 'ztc_tour' === $args['post_type'] && 'tokyo' === $args['s'] );
assert( 2 === $args['paged'] && 6 === $args['posts_per_page'] );
assert( 2 === count( $args['tax_query'] ) );
assert( 'ztc_region' === $args['tax_query'][0]['taxonomy'] && 'asia' === $args['tax_query'][0]['terms'] );
assert( 'ztc_tour_type' === $args['tax_query'][1]['taxonomy'] );
assert( 'BETWEEN' === $args['meta_query'][0]['compare'] && array( 100.0, 2000.0 ) === $args['meta_query'][0]['value'] );
assert( 'meta_value_num' === $args['orderby'] && 'ztc_price' === $args['meta_key'] );
assert( 1 === $result['total'] && 2 === $result['page'] );
assert( str_contains( $result['items'][0]['html'], 'Tokyo Adventure' ) );     // card HTML identical to server render
echo "search service: OK\n";

// --- 6. REST controller.
$controller = $plugin->get( SearchController::class );
$controller->register_routes();
[ $ns, $route, $route_args ] = $GLOBALS['routes'][0];
assert( 'ztc/v1' === $ns && '/search' === $route );
assert( '__return_true' === $route_args['permission_callback'] );             // public read-only
assert( array( 'tour', 'visa', 'country' ) === $route_args['args']['type']['enum'] );
assert( 24 === $route_args['args']['per_page']['maximum'] );

$response = $controller->handle( new WP_REST_Request( array( 'type' => 'tour', 's' => 'tokyo' ) ) );
assert( $response instanceof WP_REST_Response );
assert( isset( $response->data['items'], $response->data['total'], $response->data['pages'] ) );
assert( 'public, max-age=300' === $response->headers['Cache-Control'] );      // cache friendly (TTL from settings)
echo "rest controller: OK\n";

echo "ALL FRONTEND ENGINE TESTS PASSED\n";
