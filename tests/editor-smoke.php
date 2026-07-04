<?php
// Admin UI framework smoke test: render a full tabbed editor with all
// 14 field types, then run the save pipeline against simulated input.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

class WP_Post {
	public int $ID = 55;
	public string $post_type = 'ztc_test';
}

$GLOBALS['postmeta'] = array(
	55 => array(
		'ztc_capital'  => 'Tokyo',
		'ztc_gallery'  => array( 3, 7 ),
		'ztc_faq'      => array( array( 'question' => 'Q1', 'answer' => 'A1' ) ),
		'ztc_entry'    => 'single',
		'ztc_featured' => true,
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

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Admin\UI\Editor;
use ZihadTravelCMS\Admin\UI\Tab;
use ZihadTravelCMS\Admin\UI\Fields\CheckboxField;
use ZihadTravelCMS\Admin\UI\Fields\FaqField;
use ZihadTravelCMS\Admin\UI\Fields\GalleryField;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\MultiSelectField;
use ZihadTravelCMS\Admin\UI\Fields\NumberField;
use ZihadTravelCMS\Admin\UI\Fields\RepeaterField;
use ZihadTravelCMS\Admin\UI\Fields\RichEditorField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\TextareaField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\TimelineField;
use ZihadTravelCMS\Admin\UI\Fields\ToggleField;
use ZihadTravelCMS\Admin\UI\Fields\UrlField;

// A test editor using every field type across three of the standard tabs.
final class TestEditor extends Editor {
	public function post_type(): string { return 'ztc_test'; }
	protected function tabs(): array {
		return array(
			new Tab( Tab::GENERAL, 'General', array(
				new TextField( 'ztc_capital', 'Capital', array( 'description' => 'The capital city.' ) ),
				new TextareaField( 'ztc_notes', 'Notes' ),
				new RichEditorField( 'ztc_requirements', 'Requirements' ),
				new NumberField( 'ztc_price', 'Price', array( 'min' => 0 ) ),
				new UrlField( 'ztc_map', 'Map URL' ),
				new SelectField( 'ztc_entry', 'Entry Type', array( 'options' => array( 'single' => 'Single', 'multiple' => 'Multiple' ) ) ),
				new MultiSelectField( 'ztc_langs', 'Languages', array( 'options' => array( 'en' => 'English', 'bn' => 'Bangla' ) ) ),
				new CheckboxField( 'ztc_featured', 'Featured' ),
				new ToggleField( 'ztc_active', 'Active' ),
			) ),
			new Tab( Tab::GALLERY, 'Gallery', array(
				new MediaField( 'ztc_hero_image', 'Hero Image' ),
				new GalleryField( 'ztc_gallery', 'Gallery' ),
			), 'dashicons-format-gallery' ),
			new Tab( Tab::FAQ, 'FAQ & Steps', array(
				new FaqField( 'ztc_faq', 'FAQ' ),
				new TimelineField( 'ztc_itinerary', 'Itinerary', array( 'row_label' => 'Day' ) ),
				new RepeaterField( 'ztc_hotels', 'Hotels', array( 'fields' => array(
					array( 'key' => 'name', 'label' => 'Name', 'type' => 'text' ),
					array( 'key' => 'rating', 'label' => 'Rating', 'type' => 'number' ),
				) ) ),
			) ),
		);
	}
}

$editor = new TestEditor();
$post   = new WP_Post();

// --- Render.
ob_start();
$editor->render( $post );
$html = ob_get_clean();

assert( str_contains( $html, 'role="tablist"' ) );
assert( 3 === substr_count( $html, 'role="tab"' ) );
assert( 3 === substr_count( $html, 'role="tabpanel"' ) );
assert( 1 === substr_count( $html, 'aria-selected="true"' ) );
assert( 2 === substr_count( $html, 'tabindex="-1"' ) );                    // roving tabindex
assert( 2 === substr_count( $html, 'tabindex="0" hidden>' ) );             // inactive panels hidden
assert( str_contains( $html, 'name="ztc_editor_nonce" value="nonce-ok"' ) ); // nonce
assert( str_contains( $html, 'name="ztc_fields[ztc_capital]" value="Tokyo"' ) );
assert( str_contains( $html, 'aria-describedby="ztc-field-ztc-capital-description"' ) );
assert( str_contains( $html, 'role="switch"' ) );                          // toggle
assert( str_contains( $html, 'data-ztc-repeater-template' ) );             // repeater blueprint
assert( str_contains( $html, '[__i__]' ) );                                // index placeholder
assert( str_contains( $html, 'value="3,7" data-ztc-gallery-input' ) );     // gallery csv
assert( str_contains( $html, '<option value="single" selected>' ) );       // select state
assert( substr_count( $html, '<legend' ) >= 4 );                           // group fields use fieldset/legend
assert( str_contains( $html, 'checked' ) );                                // checkbox state
assert( ! str_contains( $html, '<script>' ) );
echo "render: OK\n";

// --- Save: happy path with messy input.
$_POST = array(
	'ztc_editor_nonce' => 'nonce-ok',
	'ztc_fields'       => array(
		'ztc_capital'      => '  <b>Dhaka</b>  ',
		'ztc_notes'        => "line1\n<script>x</script>line2",
		'ztc_requirements' => '<p>Valid passport</p><script>evil()</script>',
		'ztc_price'        => '-50',
		'ztc_map'          => 'javascript:alert(1)',
		'ztc_entry'        => 'hacked-value',
		'ztc_langs'        => array( 'en', 'zz' ),
		// ztc_featured / ztc_active absent (unchecked).
		'ztc_hero_image'   => '12abc',
		'ztc_gallery'      => '3,junk,7,0',
		'ztc_faq'          => array(
			array( 'question' => ' Q one ', 'answer' => '<p>A one</p>', 'injected' => 'x' ),
			array( 'question' => '', 'answer' => '' ), // empty row dropped
		),
		'ztc_itinerary'    => array( array( 'title' => 'Day 1', 'description' => 'Arrive' ) ),
		'ztc_hotels'       => 'not-an-array',
	),
);

$editor->save( 55, $post );

assert( 'Dhaka' === $GLOBALS['saved']['ztc_capital'] );
assert( "line1\nxline2" === $GLOBALS['saved']['ztc_notes'] );
assert( '<p>Valid passport</p>evil()' === $GLOBALS['saved']['ztc_requirements'] ); // script tag stripped
assert( 0.0 === $GLOBALS['saved']['ztc_price'] );                                  // clamped to min
assert( '' === $GLOBALS['saved']['ztc_map'] );                                      // bad URL rejected
assert( '' === $GLOBALS['saved']['ztc_entry'] );                                    // unknown option rejected
assert( array( 'en' ) === $GLOBALS['saved']['ztc_langs'] );                         // unknown choice stripped
assert( false === $GLOBALS['saved']['ztc_featured'] );                              // unchecked → false
assert( false === $GLOBALS['saved']['ztc_active'] );
assert( 12 === $GLOBALS['saved']['ztc_hero_image'] );
assert( array( 3, 7 ) === $GLOBALS['saved']['ztc_gallery'] );
assert( array( array( 'question' => 'Q one', 'answer' => '<p>A one</p>' ) ) === $GLOBALS['saved']['ztc_faq'] ); // empty row + unknown key dropped
assert( array( array( 'title' => 'Day 1', 'description' => 'Arrive' ) ) === $GLOBALS['saved']['ztc_itinerary'] );
assert( array() === $GLOBALS['saved']['ztc_hotels'] );
assert( 14 === count( $GLOBALS['saved'] ) );
echo "save + sanitization: OK\n";

// --- Save guards: bad nonce writes nothing.
$GLOBALS['saved'] = array();
$_POST['ztc_editor_nonce'] = 'forged';
$editor->save( 55, $post );
assert( array() === $GLOBALS['saved'] );
echo "nonce guard: OK\n";

echo "ALL EDITOR FRAMEWORK TESTS PASSED\n";
