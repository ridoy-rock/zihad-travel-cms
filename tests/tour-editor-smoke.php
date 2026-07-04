<?php
// Tour editor smoke test: render all nine tabs, full save round-trip,
// sale-price validation and taxonomy persistence.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

class WP_Post {
	public function __construct(
		public int $ID = 0,
		public string $post_type = '',
		public string $post_title = '',
	) {}
}

$GLOBALS['postmeta'] = array(
	10 => array(
		'ztc_country'  => 20,
		'ztc_price'    => 1500.0,
		'ztc_duration' => array( 'days' => '5', 'nights' => '4' ),
		'ztc_included' => array( 'Hotel', 'Breakfast' ),
	),
);
$GLOBALS['saved']       = array();
$GLOBALS['saved_terms'] = null;
$GLOBALS['transients']  = array();

// --- WP stubs.
function add_action( ...$a ) {}
function do_action( ...$a ) {}
function apply_filters( $h, $v ) { return $v; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url_raw( $t ) { return preg_match( '#^https?://#', (string) $t ) ? (string) $t : ''; }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function wp_kses_post( $t ) { return str_replace( array( '<script>', '</script>' ), '', (string) $t ); }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'en_US'; }
function get_post_meta( $id, $key, $single ) {
	// Reads reflect prior writes so after_save() sees saved values.
	return $GLOBALS['saved'][ $key ] ?? ( $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? '' );
}
function update_post_meta( $id, $key, $value ) { $GLOBALS['saved'][ $key ] = $value; return true; }
function wp_nonce_field( $action, $name ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce-ok">'; }
function wp_verify_nonce( $nonce, $action ) { return 'nonce-ok' === $nonce; }
function wp_is_post_revision( $id ) { return false; }
function current_user_can( ...$a ) { return true; }
function wp_unslash( $v ) { return $v; }
function wp_editor( $content, $id, $settings = array() ) {
	printf( '<textarea id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $settings['textarea_name'] ), esc_textarea( $content ) );
}
function wp_attachment_is_image( $id ) { return true; }
function wp_get_attachment_image( $id, $size, $icon = false, $attr = array() ) { return '<img src="https://img.test/' . absint( $id ) . '.jpg" alt="">'; }
function get_the_title( $id ) { return 'File ' . $id; }
function is_wp_error( $x ) { return false; }
function get_terms( $args ) {
	$a = new stdClass(); $a->term_id = 8; $a->name = 'Adventure';
	$b = new stdClass(); $b->term_id = 9; $b->name = 'Honeymoon';
	return array( $a, $b );
}
function wp_get_object_terms( $post_id, $taxonomy, $args = array() ) { return array( 8 ); }
function wp_set_object_terms( $post_id, $terms, $taxonomy, $append ) {
	$GLOBALS['saved_terms'] = array( $post_id, $terms, $taxonomy, $append );
	return $terms;
}
function get_posts( $args ) { return array( new WP_Post( 20, 'ztc_country', 'Japan' ) ); }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function get_current_user_id() { return 1; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Tour\TourEditor;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Services\NotificationService;
use ZihadTravelCMS\Translations\SiteTranslationProvider;

$provider      = new SiteTranslationProvider();
$notifications = new NotificationService();
$editor        = new TourEditor( new CountryRepository( $provider ), new TourRepository( $provider ), $notifications );
$post          = new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' );

assert( 'ztc_tour' === $editor->post_type() );

// --- Render.
ob_start();
$editor->render( $post );
$html = ob_get_clean();

assert( 9 === substr_count( $html, 'role="tab"' ) );
assert( 9 === substr_count( $html, 'role="tabpanel"' ) );
$order = array( 'General', 'Hero', 'Gallery', 'Itinerary', 'Inclusions', 'Hotels', 'Travel Info', 'FAQ', 'SEO' );
$pos   = -1;
foreach ( $order as $tab_label ) {
	$next = strpos( $html, '<span class="ztc-editor__tab-text">' . $tab_label . '</span>' );
	assert( false !== $next, "missing tab: $tab_label" );
	assert( $next > $pos, "tab out of order: $tab_label" );
	$pos = $next;
}

$expected_fields = array(
	'ztc_fields[ztc_country]', 'ztc_fields[ztc_tour_type][]',
	'ztc_fields[ztc_price]', 'ztc_fields[ztc_sale_price]',
	'ztc_fields[ztc_duration][days]', 'ztc_fields[ztc_duration][nights]',
	'ztc_fields[ztc_hero_image]', 'ztc_fields[ztc_gallery]',
	'ztc_fields[ztc_highlights]', 'ztc_fields[ztc_itinerary]',
	'ztc_fields[ztc_included]', 'ztc_fields[ztc_excluded]',
	'ztc_fields[ztc_hotels]', 'ztc_fields[ztc_flights]', 'ztc_fields[ztc_meals]',
	'ztc_fields[ztc_map]', 'ztc_fields[ztc_faq]',
	'ztc_fields[ztc_seo][title]', 'ztc_fields[ztc_seo][description]', 'ztc_fields[ztc_seo][keywords]',
);
foreach ( $expected_fields as $field_name ) {
	assert( str_contains( $html, 'name="' . esc_attr( $field_name ) ), "missing field: $field_name" );
}

assert( str_contains( $html, '<option value="20" selected>Japan</option>' ) );  // country prefilled
assert( str_contains( $html, '<option value="8" selected>Adventure</option>' ) ); // tour type terms
assert( str_contains( $html, 'value="5"' ) && str_contains( $html, 'value="4"' ) ); // duration prefilled
assert( str_contains( $html, 'value="Hotel"' ) );                                // included list rows
echo "render: OK (9 tabs, 20 inputs, all mapped)\n";

// --- Save round-trip (valid data).
$_POST = array(
	'ztc_editor_nonce' => 'nonce-ok',
	'ztc_fields'       => array(
		'ztc_country'    => '20',
		'ztc_tour_type'  => array( '8', '9' ),
		'ztc_price'      => '1500',
		'ztc_sale_price' => '1199',
		'ztc_duration'   => array( 'days' => ' 7 ', 'nights' => '6' ),
		'ztc_hero_image' => '41',
		'ztc_gallery'    => '31,junk,33',
		'ztc_highlights' => array( array( 'value' => 'Sunset cruise' ), array( 'value' => '' ) ),
		'ztc_itinerary'  => array( array( 'title' => 'Day 1', 'description' => 'Arrival<script>x</script>' ) ),
		'ztc_included'   => array( array( 'value' => ' Hotel ' ) ),
		'ztc_excluded'   => array( array( 'value' => 'Visa fees' ) ),
		'ztc_hotels'     => array( array( 'name' => 'Grand Tokyo', 'rating' => '5-star', 'description' => 'Nice.', 'evil' => 'x' ) ),
		'ztc_flights'    => '<p>Round trip included</p>',
		'ztc_meals'      => 'Breakfast daily',
		'ztc_map'        => 'https://maps.google.com/?q=tokyo',
		'ztc_faq'        => array( array( 'question' => 'Group size?', 'answer' => 'Max 12.' ) ),
		'ztc_seo'        => array( 'title' => 'Tokyo Tour', 'description' => 'Five days in Japan.', 'keywords' => 'tokyo,tour' ),
	),
);
$editor->save( 10, $post );

assert( 1500.0 === $GLOBALS['saved']['ztc_price'] );
assert( 1199.0 === $GLOBALS['saved']['ztc_sale_price'] );                       // valid sale kept
assert( array( 'days' => '7', 'nights' => '6' ) === $GLOBALS['saved']['ztc_duration'] );
assert( array( 10, array( 8, 9 ), 'ztc_tour_type', false ) === $GLOBALS['saved_terms'] );
assert( ! array_key_exists( 'ztc_tour_type', $GLOBALS['saved'] ) );             // taxonomy ≠ meta
assert( array( 31, 33 ) === $GLOBALS['saved']['ztc_gallery'] );
assert( array( 'Sunset cruise' ) === $GLOBALS['saved']['ztc_highlights'] );
assert( 'Arrivalx' === $GLOBALS['saved']['ztc_itinerary'][0]['description'] ); // script tags stripped
assert( array( 'Hotel' ) === $GLOBALS['saved']['ztc_included'] );
assert( array( array( 'name' => 'Grand Tokyo', 'rating' => '5-star', 'description' => 'Nice.' ) ) === $GLOBALS['saved']['ztc_hotels'] ); // injected key stripped
assert( 'https://maps.google.com/?q=tokyo' === $GLOBALS['saved']['ztc_map'] );
assert( 16 === count( $GLOBALS['saved'] ) );                                     // all 16 meta keys written
echo "save round-trip: OK (16 meta keys + tour type terms)\n";

// --- Validation: sale price >= regular price is cleared + warning queued.
$GLOBALS['saved'] = array();
$_POST['ztc_fields']['ztc_price']      = '1000';
$_POST['ztc_fields']['ztc_sale_price'] = '1000';
$editor->save( 10, $post );
assert( 0.0 === $GLOBALS['saved']['ztc_sale_price'] );                           // cleared by after_save()
$queued = array_values( $GLOBALS['transients'] )[0] ?? array();
assert( str_contains( $queued[0]['message'] ?? '', 'sale price must be lower' ) );
assert( 'warning' === ( $queued[0]['type'] ?? '' ) );
echo "sale-price validation: OK\n";

// --- Guard: forged nonce writes nothing.
$GLOBALS['saved']       = array();
$GLOBALS['saved_terms'] = null;
$_POST['ztc_editor_nonce'] = 'forged';
$editor->save( 10, $post );
assert( array() === $GLOBALS['saved'] && null === $GLOBALS['saved_terms'] );
echo "nonce guard: OK\n";

echo "ALL TOUR EDITOR TESTS PASSED\n";
