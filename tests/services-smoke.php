<?php
// Services smoke test: repositories, services, cards (real templates),
// notifications, global settings and health checks — with WP stubs.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/wp-content/plugins/zihad-travel-cms/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );
define( 'MINUTE_IN_SECONDS', 60 );

// --- Fixtures.
class WP_Post {
	public int $ID; public string $post_type; public string $post_title;
	public function __construct( int $id, string $type, string $title ) {
		$this->ID = $id; $this->post_type = $type; $this->post_title = $title;
	}
}
$GLOBALS['posts'] = array(
	10 => new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' ),
	20 => new WP_Post( 20, 'ztc_country', 'Japan' ),
	30 => new WP_Post( 30, 'ztc_visa', 'Japan Tourist Visa' ),
);
$GLOBALS['postmeta'] = array(
	10 => array(
		'ztc_price' => 1000.0, 'ztc_sale_price' => 799.0,
		'ztc_duration' => array( 'days' => '5', 'nights' => '4' ),
		'ztc_gallery' => array( 3, 4 ), 'ztc_country' => 20,
	),
	30 => array( 'ztc_country' => 20, 'ztc_whatsapp_number' => '', 'ztc_apply_button_text' => '', 'ztc_processing_time' => '7-10 days', 'ztc_visa_fee' => 'USD 50' ),
);
$GLOBALS['options'] = array(
	'ztc_settings' => array( 'company' => array( 'whatsapp' => '+1 (555) 123-4567' ) ),
	'ztc_version' => '1.0.0',
	'permalink_structure' => '/%postname%/',
);
$GLOBALS['transients'] = array();
$GLOBALS['get_posts_calls'] = array();

// --- WP stubs.
function add_filter( ...$a ) {}
function add_shortcode( ...$a ) {}
function add_action( ...$a ) {}
function apply_filters( $h, $v ) { return $v; }
function do_action( ...$a ) {}
function did_action( $h ) { return 0; }
function is_admin() { return false; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return (string) $t; }
function esc_url_raw( $t ) { return (string) $t; }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function wp_kses_post( $t ) { return (string) $t; }
function sanitize_text_field( $t ) { return trim( (string) $t ); }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_bloginfo( $k ) { return 'name' === $k ? 'Test Travel Agency' : ( 'version' === $k ? '6.8' : 'admin@test.dev' ); }
function get_option( $name, $default_value = false ) { return $GLOBALS['options'][ $name ] ?? $default_value; }
function update_option( $n, $v ) { $GLOBALS['options'][ $n ] = $v; return true; }
function get_post( $id ) { return $GLOBALS['posts'][ (int) $id ] ?? null; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? ''; }
function get_the_title( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? $p->post_title : ''; }
function get_permalink( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? "https://example.test/{$p->post_type}/{$p->ID}/" : ''; }
function get_the_excerpt( $p ) { return 'An excerpt.'; }
function get_post_thumbnail_id( $p ) { return 7; }
function wp_get_attachment_image_url( $id, $size ) { return $id > 0 ? "https://img.test/{$id}-{$size}.jpg" : false; }
function wp_get_attachment_url( $id ) { return "https://img.test/file-{$id}.pdf"; }
function get_the_terms( $id, $tax ) {
	$term = new stdClass();
	$term->name = 'ztc_tour_type' === $tax ? 'Adventure' : 'Tourist';
	return array( $term );
}
function wp_list_pluck( $list, $field ) { return array_map( static fn( $i ) => is_object( $i ) ? $i->$field : $i[ $field ], $list ); }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function get_posts( $args ) { $GLOBALS['get_posts_calls'][] = $args; return array(); }
function wp_count_posts( $t ) { $o = new stdClass(); $o->publish = 2; return $o; }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'template missing: ' . $a[1] ); }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function get_current_user_id() { return 1; }
function wp_remote_get( $url, $args = array() ) { return array( 'response' => array( 'code' => 200 ) ); }
function is_wp_error( $x ) { return false; }
function wp_remote_retrieve_response_code( $r ) { return $r['response']['code']; }
function wp_using_ext_object_cache() { return false; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Modules\Tour\TourService;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Visa\VisaService;
use ZihadTravelCMS\Services\HealthService;
use ZihadTravelCMS\Services\NotificationService;
use ZihadTravelCMS\Settings\GlobalSettings;
use ZihadTravelCMS\Views\Cards\CtaCard;
use ZihadTravelCMS\Views\Cards\TourCard;

$plugin = ZihadTravelCMS\Plugin::instance();
$plugin->boot();

// --- GlobalSettings.
$settings = $plugin->get( GlobalSettings::class );
assert( 'Test Travel Agency' === $settings->company_name() ); // bloginfo fallback
assert( 'https://wa.me/15551234567' === $settings->whatsapp_link() );
assert( 'https://wa.me/15551234567?text=Hi%20there' === $settings->whatsapp_link( 'Hi there' ) );
assert( '#0d6efd' === $settings->brand_color() );
assert( 'USD' === $settings->default_currency() );
assert( 'en_US' === $settings->default_language() );
assert( array() === $settings->social_links() );
echo "global settings: OK\n";

// --- TourService business logic.
$tours = $plugin->get( TourService::class );
assert( true === $tours->is_on_sale( 10 ) );
assert( 'USD 799.00' === $tours->formatted_price( 10 ) );
assert( '5 Days / 4 Nights' === $tours->duration_text( 10 ) );
assert( array( 'https://img.test/3-large.jpg', 'https://img.test/4-large.jpg' ) === $tours->gallery( 10 ) );
assert( 'Japan' === $tours->country_name( 10 ) );
$card_data = $tours->card_data( 10 );
assert( 'Tokyo Adventure' === $card_data['title'] );
assert( 'USD 1,000.00' === $card_data['regular_price'] );
assert( array() === $tours->card_data( 999 ) ); // missing post
assert( array() === $tours->card_data( 20 ) );  // wrong post type
echo "tour service: OK\n";

// --- TourRepository data access.
$repo = $plugin->get( TourRepository::class );
$repo->by_country( 20, 6 );
$last = end( $GLOBALS['get_posts_calls'] );
assert( 'ztc_tour' === $last['post_type'] && 'ztc_country' === $last['meta_key'] && 20 === $last['meta_value'] && 6 === $last['posts_per_page'] );
assert( false === $last['suppress_filters'] ); // multilingual-ready queries
echo "tour repository: OK\n";

// --- VisaService fallbacks.
$visas = $plugin->get( VisaService::class );
assert( '+1 (555) 123-4567' === $visas->whatsapp_number( 30 ) ); // global fallback
assert( 'Apply Now' === $visas->apply_button_text( 30 ) );       // default label
assert( str_contains( $visas->whatsapp_link( 30 ), 'https://wa.me/15551234567?text=' ) );
assert( str_contains( $visas->whatsapp_link( 30 ), rawurlencode( 'Japan Tourist Visa' ) ) );
echo "visa service: OK\n";

// --- Cards render real templates.
$tour_card = $plugin->get( TourCard::class )->render( 10 );
assert( str_contains( $tour_card, 'Tokyo Adventure' ) );
assert( str_contains( $tour_card, 'USD 799.00' ) );
assert( str_contains( $tour_card, 'text-decoration-line-through' ) ); // sale strike-through
assert( str_contains( $tour_card, 'badge' ) );
assert( '' === $plugin->get( TourCard::class )->render( 999 ) ); // missing post → empty

$cta = $plugin->get( CtaCard::class )->render();
assert( str_contains( $cta, 'https://wa.me/15551234567' ) );
assert( str_contains( $cta, 'Need help planning your trip?' ) );
echo "cards: OK\n";

// --- Notifications queue + render-once.
$notices = $plugin->get( NotificationService::class );
$notices->success( 'Import finished: 42 tours.' );
$notices->warning( 'Two rows skipped.' );
ob_start();
$notices->render_notices();
$html = ob_get_clean();
assert( str_contains( $html, 'notice-success' ) && str_contains( $html, 'Import finished: 42 tours.' ) );
assert( str_contains( $html, 'notice-warning' ) );
ob_start();
$notices->render_notices();
assert( '' === ob_get_clean() ); // queue cleared
echo "notifications: OK\n";

// --- Health checks.
$health = $plugin->get( HealthService::class )->checks();
assert( 7 === count( $health ) );
assert( 'good' === $health['php']['status'] && PHP_VERSION === $health['php']['value'] );
assert( 'good' === $health['wordpress']['status'] );
assert( 'good' === $health['rest']['status'] );
assert( 'good' === $health['rewrites']['status'] );
assert( 'warning' === $health['elementor']['status'] ); // not active
assert( 'warning' === $health['cache']['status'] );     // no object cache
assert( 'good' === $health['plugin']['status'] && '1.0.0' === $health['plugin']['value'] );
echo "health checks: OK\n";

echo "ALL SERVICE-LAYER TESTS PASSED\n";
