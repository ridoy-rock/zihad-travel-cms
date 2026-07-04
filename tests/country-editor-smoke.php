<?php
// Country editor smoke test: render all eight tabs with every field
// mapped (meta + Region taxonomy), then run a full save round-trip.
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
	20 => array(
		'ztc_bangla_name'    => 'জাপান',
		'ztc_capital'        => 'Tokyo',
		'ztc_popular_cities' => array( 'Tokyo', 'Osaka' ),
		'ztc_featured'       => true,
	),
);
$GLOBALS['saved']       = array();
$GLOBALS['saved_terms'] = null;

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
function get_post_meta( $id, $key, $single ) { return $GLOBALS['postmeta'][ $id ][ $key ] ?? ''; }
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
	$asia = new stdClass();
	$asia->term_id = 5;
	$asia->name    = 'Asia';
	$europe = new stdClass();
	$europe->term_id = 6;
	$europe->name    = 'Europe & Nordics'; // ampersand: escaping check
	return array( $asia, $europe );
}
function wp_get_object_terms( $post_id, $taxonomy, $args = array() ) { return array( 5 ); }
function wp_set_object_terms( $post_id, $terms, $taxonomy, $append ) {
	$GLOBALS['saved_terms'] = array( $post_id, $terms, $taxonomy, $append );
	return $terms;
}

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Modules\Country\CountryEditor;

$editor = new CountryEditor();
$post   = new WP_Post( 20, 'ztc_country', 'Japan' );

assert( 'ztc_country' === $editor->post_type() );

// --- Render.
ob_start();
$editor->render( $post );
$html = ob_get_clean();

// Eight tabs, in the required order.
assert( 8 === substr_count( $html, 'role="tab"' ) );
assert( 8 === substr_count( $html, 'role="tabpanel"' ) );
$order = array( 'General', 'Hero', 'Travel Info', 'Embassy', 'Gallery', 'FAQ', 'SEO', 'Settings' );
$pos   = -1;
foreach ( $order as $tab_label ) {
	$next = strpos( $html, '<span class="ztc-editor__tab-text">' . $tab_label . '</span>' );
	assert( false !== $next, "missing tab: $tab_label" );
	assert( $next > $pos, "tab out of order: $tab_label" );
	$pos = $next;
}

// Every field mapped exactly once.
$expected_fields = array(
	'ztc_fields[ztc_bangla_name]', 'ztc_fields[ztc_short_description]', 'ztc_fields[ztc_region][]',
	'ztc_fields[ztc_currency]', 'ztc_fields[ztc_capital]', 'ztc_fields[ztc_language]', 'ztc_fields[ztc_timezone]',
	'ztc_fields[ztc_hero_image]', 'ztc_fields[ztc_flag]', 'ztc_fields[ztc_hero_subtitle]',
	'ztc_fields[ztc_overview]', 'ztc_fields[ztc_travel_tips]', 'ztc_fields[ztc_best_time_to_visit]',
	'ztc_fields[ztc_popular_cities]', 'ztc_fields[ztc_embassy_name]', 'ztc_fields[ztc_embassy_address]',
	'ztc_fields[ztc_embassy_phone]', 'ztc_fields[ztc_embassy_email]', 'ztc_fields[ztc_embassy_website]',
	'ztc_fields[ztc_gallery]', 'ztc_fields[ztc_faq]',
	'ztc_fields[ztc_seo][title]', 'ztc_fields[ztc_seo][description]', 'ztc_fields[ztc_seo][keywords]',
	'ztc_fields[ztc_featured]', 'ztc_fields[ztc_show_on_homepage]',
);
foreach ( $expected_fields as $field_name ) {
	assert( str_contains( $html, 'name="' . esc_attr( $field_name ) ), "missing field: $field_name" );
}

// Region selector: terms listed, current term selected, escaped.
assert( str_contains( $html, '<option value="5" selected>Asia</option>' ) );
assert( str_contains( $html, 'Europe &amp; Nordics' ) );
// Two toggles, checked state from meta.
assert( 2 === substr_count( $html, 'role="switch"' ) );
assert( str_contains( $html, 'value="জাপান"' ) );           // Bangla prefill
assert( str_contains( $html, 'value="Tokyo"' ) );           // list rows prefilled
echo "render: OK (8 tabs, 26 inputs, all mapped)\n";

// --- Save round-trip.
$_POST = array(
	'ztc_editor_nonce' => 'nonce-ok',
	'ztc_fields'       => array(
		'ztc_bangla_name'        => ' জাপান ',
		'ztc_short_description'  => 'Island nation in East Asia.',
		'ztc_region'             => array( '5', '6', 'junk' ),
		'ztc_currency'           => 'JPY',
		'ztc_capital'            => 'Tokyo',
		'ztc_language'           => 'Japanese',
		'ztc_timezone'           => 'GMT+9',
		'ztc_hero_image'         => '31',
		'ztc_flag'               => '32',
		'ztc_hero_subtitle'      => 'Land of the rising sun',
		'ztc_overview'           => '<p>Great country</p><script>x()</script>',
		'ztc_travel_tips'        => '<ul><li>Get a JR Pass</li></ul>',
		'ztc_best_time_to_visit' => 'March – May',
		'ztc_popular_cities'     => array(
			array( 'value' => ' Tokyo ' ),
			array( 'value' => '' ),          // dropped
			array( 'value' => 'Kyoto' ),
		),
		'ztc_embassy_name'       => 'Embassy of Japan in Dhaka',
		'ztc_embassy_address'    => "Plot 5 & 7\nBaridhara, Dhaka",
		'ztc_embassy_phone'      => '+880-2-2222',
		'ztc_embassy_email'      => 'info@japan-embassy.example',
		'ztc_embassy_website'    => 'https://www.bd.emb-japan.go.jp/',
		'ztc_gallery'            => '31,junk,33',
		'ztc_faq'                => array( array( 'question' => 'Visa needed?', 'answer' => 'Yes', 'evil' => 'x' ) ),
		'ztc_seo'                => array( 'title' => 'Japan Travel Guide', 'description' => 'Everything about Japan.', 'keywords' => 'japan,travel', 'robots' => '', 'canonical' => '' ),
		'ztc_featured'           => '1',
		// ztc_show_on_homepage absent (toggle off).
	),
);
$editor->save( 20, $post );

assert( 'জাপান' === $GLOBALS['saved']['ztc_bangla_name'] );
assert( array( 20, array( 5, 6, 0 ), 'ztc_region', false ) !== $GLOBALS['saved_terms'] ); // junk filtered before save
assert( array( 20, array( 5, 6 ), 'ztc_region', false ) === $GLOBALS['saved_terms'] );    // terms via taxonomy, not meta
assert( ! array_key_exists( 'ztc_region', $GLOBALS['saved'] ) );                            // no meta row for the taxonomy
assert( '<p>Great country</p>x()' === $GLOBALS['saved']['ztc_overview'] );                 // script stripped
assert( array( 'Tokyo', 'Kyoto' ) === $GLOBALS['saved']['ztc_popular_cities'] );           // flat list, empty dropped
assert( array( 31, 33 ) === $GLOBALS['saved']['ztc_gallery'] );
assert( array( array( 'question' => 'Visa needed?', 'answer' => 'Yes' ) ) === $GLOBALS['saved']['ztc_faq'] ); // injected key stripped
assert( array( 'title' => 'Japan Travel Guide', 'description' => 'Everything about Japan.', 'keywords' => 'japan,travel', 'robots' => '', 'canonical' => '' ) === $GLOBALS['saved']['ztc_seo'] );
assert( 'https://www.bd.emb-japan.go.jp/' === $GLOBALS['saved']['ztc_embassy_website'] );
assert( true === $GLOBALS['saved']['ztc_featured'] );
assert( false === $GLOBALS['saved']['ztc_show_on_homepage'] ); // toggle off → false
assert( 23 === count( $GLOBALS['saved'] ) );                    // all 23 meta keys written
echo "save round-trip: OK (23 meta keys + region terms)\n";

// --- Guard: forged nonce writes nothing.
$GLOBALS['saved']       = array();
$GLOBALS['saved_terms'] = null;
$_POST['ztc_editor_nonce'] = 'forged';
$editor->save( 20, $post );
assert( array() === $GLOBALS['saved'] && null === $GLOBALS['saved_terms'] );
echo "nonce guard: OK\n";

echo "ALL COUNTRY EDITOR TESTS PASSED\n";
