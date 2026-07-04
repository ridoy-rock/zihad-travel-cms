<?php
// Visa editor smoke test: render all nine tabs with every meta field
// mapped, then run a full save round-trip.
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
	30 => array(
		'ztc_country'            => 20,
		'ztc_entry_type'         => 'single',
		'ztc_required_documents' => array( 'Passport', 'Photo' ),
		'ztc_seo'                => array( 'title' => 'Japan Visa', 'description' => '', 'keywords' => '' ),
	),
);
$GLOBALS['saved'] = array();

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
function get_posts( $args ) {
	return array(
		new WP_Post( 20, 'ztc_country', 'Japan' ),
		new WP_Post( 21, 'ztc_country', 'Malaysia & Co.' ), // ampersand: escaping check
	);
}

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Visa\VisaEditor;
use ZihadTravelCMS\Translations\SiteTranslationProvider;

$editor = new VisaEditor( new CountryRepository( new SiteTranslationProvider() ) );
$post   = new WP_Post( 30, 'ztc_visa', 'Japan Tourist Visa' );

assert( 'ztc_visa' === $editor->post_type() );

// --- Render.
ob_start();
$editor->render( $post );
$html = ob_get_clean();

// Nine tabs, in the required order.
assert( 9 === substr_count( $html, 'role="tab"' ) );
assert( 9 === substr_count( $html, 'role="tabpanel"' ) );
$order = array( 'General', 'Hero', 'Requirements', 'Documents', 'Benefits', 'Application', 'FAQ', 'SEO', 'Settings' );
$pos   = -1;
foreach ( $order as $tab_label ) {
	$next = strpos( $html, '<span class="ztc-editor__tab-text">' . $tab_label . '</span>' );
	assert( false !== $next, "missing tab: $tab_label" );
	assert( $next > $pos, "tab out of order: $tab_label" );
	$pos = $next;
}

// Every Visa meta field is mapped exactly once.
$expected_fields = array(
	'ztc_fields[ztc_country]', 'ztc_fields[ztc_hero_image]', 'ztc_fields[ztc_entry_type]',
	'ztc_fields[ztc_processing_time]', 'ztc_fields[ztc_validity]', 'ztc_fields[ztc_stay_duration]',
	'ztc_fields[ztc_visa_fee]', 'ztc_fields[ztc_requirements]', 'ztc_fields[ztc_important_notes]',
	'ztc_fields[ztc_required_documents]', 'ztc_fields[ztc_benefits]', 'ztc_fields[ztc_application_process]',
	'ztc_fields[ztc_faq]', 'ztc_fields[ztc_seo][title]', 'ztc_fields[ztc_seo][description]',
	'ztc_fields[ztc_seo][keywords]', 'ztc_fields[ztc_whatsapp_number]', 'ztc_fields[ztc_apply_button_text]',
);
foreach ( $expected_fields as $field_name ) {
	assert( str_contains( $html, 'name="' . esc_attr( $field_name ) ), "missing field: $field_name" );
}

// Country selector: populated, current value selected, output escaped.
assert( str_contains( $html, '<option value="20" selected>Japan</option>' ) );
assert( str_contains( $html, 'Malaysia &amp; Co.' ) );
// Entry type select state.
assert( str_contains( $html, '<option value="single" selected>Single Entry</option>' ) );
// Existing documents list rendered as rows.
assert( str_contains( $html, 'value="Passport"' ) && str_contains( $html, 'value="Photo"' ) );
// SEO group prefilled.
assert( str_contains( $html, 'value="Japan Visa"' ) );
echo "render: OK (9 tabs, 18 inputs, all mapped)\n";

// --- Save round-trip.
$_POST = array(
	'ztc_editor_nonce' => 'nonce-ok',
	'ztc_fields'       => array(
		'ztc_country'            => '21',
		'ztc_hero_image'         => '44',
		'ztc_entry_type'         => 'multiple',
		'ztc_processing_time'    => ' 5 days ',
		'ztc_validity'           => '90 days',
		'ztc_stay_duration'      => '30 days',
		'ztc_visa_fee'           => 'USD 80',
		'ztc_requirements'       => '<p>Rules</p><script>x()</script>',
		'ztc_important_notes'    => '<strong>Note</strong>',
		'ztc_required_documents' => array(
			array( 'value' => ' Passport ' ),
			array( 'value' => '' ),            // dropped
			array( 'value' => 'Bank statement' ),
		),
		'ztc_benefits'           => array( array( 'value' => 'Fast processing' ) ),
		'ztc_application_process' => array( array( 'title' => 'Step one', 'description' => 'Submit docs' ) ),
		'ztc_faq'                => array( array( 'question' => 'How long?', 'answer' => '5 days' ) ),
		'ztc_seo'                => array( 'title' => ' Japan Visa 2026 ', 'description' => 'Apply easily.', 'keywords' => 'japan,visa', 'injected' => 'x' ),
		'ztc_whatsapp_number'    => '+880 1711-000000',
		'ztc_apply_button_text'  => 'Apply Today',
	),
);
$editor->save( 30, $post );

assert( '21' === $GLOBALS['saved']['ztc_country'] );                                  // registered absint casts on real WP
assert( 44 === $GLOBALS['saved']['ztc_hero_image'] );
assert( 'multiple' === $GLOBALS['saved']['ztc_entry_type'] );
assert( '5 days' === $GLOBALS['saved']['ztc_processing_time'] );
assert( '<p>Rules</p>x()' === $GLOBALS['saved']['ztc_requirements'] );                // script stripped
assert( array( 'Passport', 'Bank statement' ) === $GLOBALS['saved']['ztc_required_documents'] ); // flat string list, empty dropped
assert( array( 'Fast processing' ) === $GLOBALS['saved']['ztc_benefits'] );
assert( array( array( 'title' => 'Step one', 'description' => 'Submit docs' ) ) === $GLOBALS['saved']['ztc_application_process'] );
assert( array( array( 'question' => 'How long?', 'answer' => '5 days' ) ) === $GLOBALS['saved']['ztc_faq'] );
assert( array( 'title' => 'Japan Visa 2026', 'description' => 'Apply easily.', 'keywords' => 'japan,visa', 'robots' => '', 'canonical' => '' ) === $GLOBALS['saved']['ztc_seo'] ); // injected key stripped
assert( '+880 1711-000000' === $GLOBALS['saved']['ztc_whatsapp_number'] );
assert( 16 === count( $GLOBALS['saved'] ) ); // all 16 meta fields written
echo "save round-trip: OK (16 meta keys)\n";

// --- Guard: invalid country option rejected.
$GLOBALS['saved'] = array();
$_POST['ztc_fields']['ztc_country'] = '999999';
$editor->save( 30, $post );
assert( '' === $GLOBALS['saved']['ztc_country'] ); // unknown option → default
echo "option guard: OK\n";

echo "ALL VISA EDITOR TESTS PASSED\n";
