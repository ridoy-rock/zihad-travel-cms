<?php
// Registration smoke test: boot the kernel, fire `init`, and verify
// every post type, taxonomy and meta field registers correctly.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ZTC_PLUGIN_URL', 'https://example.test/wp-content/plugins/zihad-travel-cms/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );

$GLOBALS['init_callbacks'] = array();
$GLOBALS['post_types']     = array();
$GLOBALS['taxonomies']     = array();
$GLOBALS['meta']           = array();

// --- WP stubs.
function add_filter( ...$a ) {}
function add_shortcode( ...$a ) {}
function add_action( $hook, $cb, $prio = 10 ) {
	if ( 'init' === $hook ) {
		$GLOBALS['init_callbacks'][] = array( $prio, $cb );
	}
}
function apply_filters( $hook, $value ) { return $value; }
function do_action( $hook, ...$args ) {}
function is_admin() { return false; }
function did_action( $hook ) { return 0; }
function esc_html( $s ) { return $s; }
function __( $text, $domain = 'default' ) { return $text; }
function get_option( $name, $default_value = false ) { return '1.0.0' === $default_value ? $default_value : ( 'ztc_version' === $name ? '1.0.0' : $default_value ); }
function update_option( $n, $v ) { return true; }
function delete_option( $n ) { return true; }
function load_plugin_textdomain( ...$a ) { return true; }
function register_setting( ...$a ) {}
function register_post_type( $name, $args ) { $GLOBALS['post_types'][ $name ] = $args; }
function register_taxonomy( $name, $object_types, $args ) { $GLOBALS['taxonomies'][ $name ] = array( 'object_types' => $object_types, 'args' => $args ); }
function register_post_meta( $post_type, $key, $args ) { $GLOBALS['meta'][ $post_type ][ $key ] = $args; }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function wp_kses_post( $v ) { return (string) $v; }
function esc_url_raw( $v ) { return (string) $v; }
function absint( $v ) { return abs( (int) $v ); }
function current_user_can( ...$a ) { return true; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

$plugin = ZihadTravelCMS\Plugin::instance();
$plugin->boot();

// Fire `init` in priority order.
usort( $GLOBALS['init_callbacks'], static fn( $a, $b ) => $a[0] <=> $b[0] );
foreach ( $GLOBALS['init_callbacks'] as [ $prio, $cb ] ) {
	call_user_func( $cb );
}

// --- Post types.
$expected_cpts = array( 'ztc_country' => 'country', 'ztc_visa' => 'visa', 'ztc_tour' => 'tour' );
foreach ( $expected_cpts as $name => $slug ) {
	assert( isset( $GLOBALS['post_types'][ $name ] ), "missing CPT $name" );
	$args = $GLOBALS['post_types'][ $name ];
	assert( $slug === $args['rewrite']['slug'], "wrong slug for $name" );
	assert( false === $args['rewrite']['with_front'] );
	assert( true === $args['public'] );
	assert( true === $args['show_in_rest'], "no REST/Gutenberg for $name" );
	assert( true === $args['has_archive'] );
	foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ) as $support ) {
		assert( in_array( $support, $args['supports'], true ), "$name missing support: $support" );
	}
	// All three CPTs dropped 'custom-fields': every post type now has a
	// tabbed editor and the raw metabox would duplicate it.
	assert( ! in_array( 'custom-fields', $args['supports'], true ), "$name should not support custom-fields" );
	assert( strlen( $name ) <= 20 );
}
echo 'post types: OK (' . implode( ', ', array_keys( $GLOBALS['post_types'] ) ) . ")\n";

// --- Taxonomies.
$expected_taxes = array(
	'ztc_region'    => array( 'region', array( 'ztc_country', 'ztc_tour' ) ),
	'ztc_visa_type' => array( 'visa-type', array( 'ztc_visa' ) ),
	'ztc_tour_type' => array( 'tour-type', array( 'ztc_tour' ) ),
);
foreach ( $expected_taxes as $name => [ $slug, $object_types ] ) {
	assert( isset( $GLOBALS['taxonomies'][ $name ] ), "missing taxonomy $name" );
	$tax = $GLOBALS['taxonomies'][ $name ];
	assert( $slug === $tax['args']['rewrite']['slug'], "wrong slug for $name" );
	assert( $object_types === $tax['object_types'], "wrong object types for $name" );
	assert( true === $tax['args']['show_in_rest'] );
	assert( true === $tax['args']['show_admin_column'] );
}
echo 'taxonomies: OK (' . implode( ', ', array_keys( $GLOBALS['taxonomies'] ) ) . ")\n";

// --- Meta fields.
$expected_meta = array(
	'ztc_country' => array(
		'ztc_bangla_name', 'ztc_short_description', 'ztc_currency', 'ztc_capital', 'ztc_language', 'ztc_timezone',
		'ztc_hero_image', 'ztc_flag', 'ztc_hero_subtitle',
		'ztc_overview', 'ztc_travel_tips', 'ztc_best_time_to_visit', 'ztc_popular_cities',
		'ztc_embassy_name', 'ztc_embassy_address', 'ztc_embassy_phone', 'ztc_embassy_email', 'ztc_embassy_website',
		'ztc_gallery', 'ztc_faq', 'ztc_seo', 'ztc_featured', 'ztc_show_on_homepage',
	),
	'ztc_visa'    => array( 'ztc_country', 'ztc_hero_image', 'ztc_processing_time', 'ztc_validity', 'ztc_stay_duration', 'ztc_entry_type', 'ztc_visa_fee', 'ztc_requirements', 'ztc_required_documents', 'ztc_benefits', 'ztc_application_process', 'ztc_faq', 'ztc_important_notes', 'ztc_whatsapp_number', 'ztc_apply_button_text', 'ztc_seo' ),
	'ztc_tour'    => array( 'ztc_country', 'ztc_hero_image', 'ztc_price', 'ztc_sale_price', 'ztc_duration', 'ztc_duration_days', 'ztc_gallery', 'ztc_highlights', 'ztc_itinerary', 'ztc_included', 'ztc_excluded', 'ztc_hotels', 'ztc_flights', 'ztc_meals', 'ztc_map', 'ztc_faq', 'ztc_seo' ),
);
foreach ( $expected_meta as $post_type => $keys ) {
	foreach ( $keys as $key ) {
		assert( isset( $GLOBALS['meta'][ $post_type ][ $key ] ), "missing meta $post_type/$key" );
		$args = $GLOBALS['meta'][ $post_type ][ $key ];
		assert( isset( $args['sanitize_callback'] ), "no sanitizer on $post_type/$key" );
		assert( isset( $args['auth_callback'] ), "no auth on $post_type/$key" );
		assert( ! empty( $args['show_in_rest'] ), "not in REST: $post_type/$key" );
		assert( true === $args['single'] );
	}
	assert( count( $GLOBALS['meta'][ $post_type ] ) === count( $keys ), "unexpected extra meta on $post_type" );
}
echo "meta fields: OK (country=23, visa=16, tour=17)\n";

// --- Sanitizer behaviour spot-checks.
$visa_meta = $plugin->get( ZihadTravelCMS\Modules\Visa\VisaMeta::class );
assert( array( 'Passport', 'Photo' ) === $visa_meta->sanitize_string_list( array( ' Passport ', '', 'Photo', null ) ) );
$faq_args = $GLOBALS['meta']['ztc_visa']['ztc_faq'];
$cleaned  = $faq_args['sanitize_callback']( array( array( 'question' => ' Q1 ', 'answer' => '<p>A1</p>', 'evil' => 'x' ), 'not-an-array' ) );
assert( array( array( 'question' => 'Q1', 'answer' => '<p>A1</p>' ) ) === $cleaned );
$gallery = $GLOBALS['meta']['ztc_tour']['ztc_gallery'];
assert( array( 5, 9 ) === $gallery['sanitize_callback']( array( '5', 0, '9', 'junk' ) ) );

// --- Modules loaded through the manager (3 content + 7 placeholders).
$manager = $plugin->get( ZihadTravelCMS\Modules\ModuleManager::class );
assert( array( 'country', 'visa', 'tour', 'search', 'booking', 'importer', 'demo-data', 'seo', 'wizard', 'ai', 'elementor', 'analytics' ) === array_keys( $manager->all() ) );

echo "sanitizers + module manager: OK\nALL REGISTRATION TESTS PASSED\n";
