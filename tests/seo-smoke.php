<?php
// SEO module smoke test: settings schema + tab, meta schema extension,
// SeoField sanitization, resolution precedence, memoization, canonical
// and robots handling, OpenGraph/Twitter output, all public filters,
// Schema.org graph validity and third-party deferral.
//
// Unlike the other suites this one runs a REAL hook system — the SEO
// module's behaviour is filter-driven.
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

$GLOBALS['posts'] = array(
	10 => new WP_Post( 10, 'ztc_tour', 'Tokyo Adventure' ),
	11 => new WP_Post( 11, 'ztc_tour', 'Dhaka Getaway' ),
	20 => new WP_Post( 20, 'ztc_country', 'Japan' ),
	30 => new WP_Post( 30, 'ztc_visa', 'Japan Tourist Visa' ),
);
$GLOBALS['postmeta'] = array(
	10 => array(
		'ztc_price'      => 1500.0,
		'ztc_sale_price' => 0.0,
		'ztc_country'    => 20,
		'ztc_itinerary'  => array(
			array( 'title' => 'Arrival in Tokyo', 'description' => '<p>Land.</p>' ),
			array( 'title' => 'Mount Fuji', 'description' => '<p>Climb.</p>' ),
			array( 'title' => '', 'description' => 'dropped' ),
		),
		'ztc_seo'        => array(
			'title'       => 'Custom Tour Title',
			'description' => 'Custom tour description.',
			'keywords'    => 'tokyo, tour',
			'robots'      => 'noindex',
			'canonical'   => 'https://example.test/custom-canonical/',
		),
	),
	11 => array( 'ztc_price' => 0.0, 'ztc_sale_price' => 0.0 ),
	20 => array(
		'ztc_bangla_name'       => 'জাপান',
		'ztc_short_description' => 'Land of the rising sun.',
	),
	30 => array(
		'ztc_country'  => 20,
		'ztc_visa_fee' => 'USD 50',
	),
);
$GLOBALS['options']         = array( 'ztc_version' => '1.0.0', 'ztc_settings' => array() );
$GLOBALS['flags']           = array( 'singular' => null, 'archive' => null, 'tax' => null );
$GLOBALS['query_vars']      = array();
$GLOBALS['hooks']           = array();
$GLOBALS['removed_actions'] = array();
$GLOBALS['meta_reads']      = 0;

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
function remove_action( $hook, $cb, $priority = 10 ) {
	$GLOBALS['removed_actions'][] = array( $hook, $cb );
	return remove_filter( $hook, $cb, $priority );
}
function did_action( $h ) { return 0; }

// --- WP stubs.
function is_admin() { return false; }
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html__( $t, $d = 'default' ) { return esc_html( $t ); }
function esc_attr__( $t, $d = 'default' ) { return esc_attr( $t ); }
function esc_html_e( $t, $d = 'default' ) { echo esc_html( $t ); }
function esc_url( $t ) { return str_replace( '"', '%22', (string) $t ); }
function esc_url_raw( $t ) { return preg_match( '#^https?://#i', (string) $t ) ? (string) $t : ''; }
function esc_textarea( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function wp_kses_post( $t ) { return (string) $t; }
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function wp_json_encode( $v, $f = 0 ) { return json_encode( $v, $f ); }
function sanitize_text_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( $t ) { return trim( strip_tags( (string) $t ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $c ) ? (string) $c : ''; }
function absint( $v ) { return abs( (int) $v ); }
function get_locale() { return 'bn_BD'; }
function get_bloginfo( $k ) { return 'Test Site'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { return true; }
function get_post( $id ) { return $GLOBALS['posts'][ (int) $id ] ?? null; }
function get_post_meta( $id, $key, $single = true ) {
	++$GLOBALS['meta_reads'];
	return $GLOBALS['postmeta'][ (int) $id ][ $key ] ?? '';
}
function get_the_title( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? $p->post_title : ''; }
function get_permalink( $p ) { $p = $p instanceof WP_Post ? $p : get_post( $p ); return $p ? "https://example.test/{$p->post_type}/{$p->ID}/" : ''; }
function get_the_excerpt( $p ) { return $GLOBALS['excerpt'] ?? 'An excerpt.'; }
function get_post_thumbnail_id( $p ) { return 7; }
function wp_get_attachment_image_url( $id, $size ) { return $id > 0 ? "https://img.test/{$id}-{$size}.jpg" : false; }
function get_the_terms( $id, $tax ) { $t = new stdClass(); $t->name = 'Tourist'; return array( $t ); }
function wp_list_pluck( $list, $field ) { return array_map( static fn( $i ) => is_object( $i ) ? $i->$field : $i[ $field ], $list ); }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function get_posts( $args ) { return array( $GLOBALS['posts'][20] ); }
function locate_template( $t ) { return ''; }
function _doing_it_wrong( ...$a ) { throw new RuntimeException( 'missing template: ' . $a[1] ); }
function set_query_var( $k, $v ) { $GLOBALS['query_vars'][ $k ] = $v; }
function get_query_var( $k, $d = '' ) { return $GLOBALS['query_vars'][ $k ] ?? $d; }
function is_singular( $pt = '' ) { return $GLOBALS['flags']['singular'] === $pt; }
function is_post_type_archive( $pt = '' ) { return $GLOBALS['flags']['archive'] === $pt; }
function is_tax( $tax = '' ) {
	if ( '' === $tax ) { return null !== $GLOBALS['flags']['tax']; }
	return $GLOBALS['flags']['tax'] === $tax;
}
function get_queried_object_id() { return $GLOBALS['queried_id'] ?? 0; }
function get_queried_object() { return $GLOBALS['queried_object'] ?? null; }
function get_post_type_archive_link( $pt ) { return 'https://example.test/archive/' . $pt . '/'; }
function get_term_link( $term ) { return 'https://example.test/region/' . ( $term->slug ?? 'term' ) . '/'; }
function is_wp_error( $x ) { return false; }
function home_url( $p = '/' ) { return 'https://example.test' . $p; }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function wp_enqueue_style( $h ) {}
function wp_enqueue_script( $h ) {}
function add_shortcode( ...$a ) {}
function shortcode_atts( $defaults, $atts, $tag = '' ) { return array_merge( $defaults, (array) $atts ); }
function register_rest_route( ...$a ) {}
function wp_enqueue_media() {}
function wp_attachment_is_image( $id ) { return true; }
function wp_get_attachment_image( $id, $s, $i = false, $a = array() ) { return '<img>'; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function wp_nonce_field( $action, $name = '_wpnonce' ) { echo '<input type="hidden">'; }
function submit_button( $label ) {}
function current_user_can( ...$a ) { return true; }

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

function ztc() { return ZihadTravelCMS\Plugin::instance(); }
require ZTC_PLUGIN_DIR . 'includes/functions.php';

use ZihadTravelCMS\Admin\Pages\SettingsPage;
use ZihadTravelCMS\Admin\UI\Fields\SeoField;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Modules\Seo\SchemaService;
use ZihadTravelCMS\Modules\Seo\SeoOutput;
use ZihadTravelCMS\Modules\Seo\SeoService;
use ZihadTravelCMS\Modules\Tour\TourMeta;
use ZihadTravelCMS\Settings\SettingsSanitizer;

$plugin = ztc();
$plugin->boot();

$config    = $plugin->get( Config::class );
$sanitizer = $plugin->get( SettingsSanitizer::class );
$seo       = $plugin->get( SeoService::class );
$schema    = $plugin->get( SchemaService::class );
$output    = $plugin->get( SeoOutput::class );

// --- 1. Settings schema: seo section registered through the filter,
//        sanitizer accepts its keys and drops unknown ones.
assert( is_array( $config->defaults()['seo'] ?? null ), 'seo defaults missing' );
assert( true === $config->get( 'seo.enabled' ) );
assert( '–' === $config->get( 'seo.title_separator' ) );

$clean = $sanitizer->sanitize(
	array(
		'seo' => array(
			'enabled'             => '1',
			'noindex_archives'    => '1',
			'default_description' => 'Fallback description.',
			'default_og_image'    => '42',
			'evil'                => 'x',
		),
	)
);
assert( true === $clean['seo']['enabled'] );
assert( true === $clean['seo']['noindex_archives'] );
assert( 42 === $clean['seo']['default_og_image'] );
assert( ! isset( $clean['seo']['evil'] ) );
echo "settings schema: OK\n";

// --- 2. Settings screen gains the SEO tab (12 tabs, seo last).
$tabs = $plugin->get( SettingsPage::class )->tabs();
assert( 12 === count( $tabs ) );
assert( 'seo' === end( $tabs )->id() );
$seo_fields = array_map( static fn( $f ) => $f->name(), end( $tabs )->fields() );
assert( in_array( 'seo.default_description', $seo_fields, true ) );
assert( in_array( 'seo.noindex_archives', $seo_fields, true ) );
echo "settings tab: OK\n";

// --- 3. Meta schema: ztc_seo gained robots + canonical, url kind
//        rejects javascript:, hostile input stripped.
$tour_meta = new TourMeta();
$reflect   = new ReflectionMethod( $tour_meta, 'fields' );
$reflect->setAccessible( true );
$seo_args = $reflect->invoke( $tour_meta )[ TourMeta::SEO ];

$props = $seo_args['show_in_rest']['schema']['properties'];
assert( isset( $props['robots'], $props['canonical'], $props['title'], $props['description'], $props['keywords'] ) );

$cleaned = $seo_args['sanitize_callback'](
	array(
		'title'     => '<script>alert(1)</script>Nice Title',
		'canonical' => 'javascript:alert(1)',
		'robots'    => 'noindex',
		'hacked'    => 'x',
	)
);
assert( 'alert(1)Nice Title' === $cleaned['title'] ); // tags stripped
assert( '' === $cleaned['canonical'] );               // scheme rejected
assert( 'noindex' === $cleaned['robots'] );
assert( ! isset( $cleaned['hacked'] ) );              // unknown key dropped
echo "meta schema: OK\n";

// --- 4. SeoField: renders all five parts, sanitize whitelists robots.
$field = new SeoField( 'ztc_seo', 'SEO' );
ob_start();
$field->render(
	array(
		'title'     => '"><script>x</script>',
		'robots'    => 'noindex',
		'canonical' => 'https://example.test/x/',
	)
);
$html = ob_get_clean();
assert( str_contains( $html, '[robots]' ) && str_contains( $html, '[canonical]' ) );
assert( ! str_contains( $html, '<script>x' ) );                         // stored value escaped
assert( str_contains( $html, 'value="https://example.test/x/"' ) );
assert( 2 === substr_count( $html, ' selected' ) ? false : true );      // exactly one selected option
assert( 1 === substr_count( $html, ' selected' ) );

$clean = $field->sanitize(
	array(
		'title'     => ' Title ',
		'robots'    => 'noarchive,junk',
		'canonical' => 'javascript:alert(1)',
	)
);
assert( 'Title' === $clean['title'] );
assert( '' === $clean['robots'] );    // junk rejected → default indexing
assert( '' === $clean['canonical'] );
assert( 'noindex,nofollow' === $field->sanitize( array( 'robots' => 'noindex,nofollow' ) )['robots'] );
echo "seo field: OK\n";

// --- 5. Resolution on a single tour: per-post meta wins everywhere.
$GLOBALS['flags']['singular'] = 'ztc_tour';
$GLOBALS['queried_id']        = 10;
$seo->refresh();

assert( $seo->applies() && $seo->enabled() );
$data = $seo->data();
assert( 'Custom Tour Title' === $data['title'] );
assert( 'Custom tour description.' === $data['description'] );
assert( 'tokyo, tour' === $data['keywords'] );
assert( 'https://example.test/custom-canonical/' === $data['canonical'] ); // meta override
assert( 'noindex' === $data['robots'] );
assert( 'https://img.test/7-full.jpg' === $data['image'] );               // featured-image fallback
assert( 'article' === $data['og_type'] );

// Memoized: a second call re-reads nothing.
$reads = $GLOBALS['meta_reads'];
$again = $seo->data();
assert( $again === $data && $reads === $GLOBALS['meta_reads'], 'data() not memoized' );
echo "single resolution + memoization: OK\n";

// --- 6. Derived fallbacks when the post has no SEO meta.
$GLOBALS['queried_id'] = 11;
$GLOBALS['excerpt']    = str_repeat( 'Beautiful riverside city break. ', 12 ); // > 160 chars
$seo->refresh();
$data = $seo->data();
assert( 'Dhaka Getaway – Test Site' === $data['title'] );                 // title + separator + site
assert( 160 >= mb_strlen( $data['description'] ) );
assert( str_ends_with( $data['description'], '…' ) );                     // trimmed at ~160
assert( 'https://example.test/ztc_tour/11/' === $data['canonical'] );     // permalink
assert( '' === $data['robots'] );
unset( $GLOBALS['excerpt'] );

// Global default description used when content offers nothing.
$GLOBALS['excerpt'] = '';
$GLOBALS['options']['ztc_settings']['seo'] = array( 'default_description' => 'Agency-wide description.' );
$config->refresh();
$seo->refresh();
assert( 'Agency-wide description.' === $seo->data()['description'] );
unset( $GLOBALS['excerpt'] );
echo "fallback precedence: OK\n";

// --- 7. Value filters run before memoization.
$title_filter = static fn( $t ) => $t . ' [filtered]';
add_filter( 'ztc_seo_title', $title_filter );
add_filter( 'ztc_seo_robots', static fn() => 'nofollow' );
$seo->refresh();
$data = $seo->data();
assert( 'Dhaka Getaway – Test Site [filtered]' === $data['title'] );
assert( 'nofollow' === $data['robots'] );
remove_filter( 'ztc_seo_title', $title_filter );
$GLOBALS['hooks']['ztc_seo_robots'] = array();
echo "value filters: OK\n";

// --- 8. Archives: settings title, paged canonical, noindex toggle.
$GLOBALS['flags']['singular'] = null;
$GLOBALS['flags']['archive']  = 'ztc_tour';
$GLOBALS['queried_id']        = 0;
$GLOBALS['options']['ztc_settings']['seo'] = array(
	'archive_tour_title'       => 'All Our Tours',
	'archive_tour_description' => 'Browse every package.',
	'noindex_archives'         => true,
);
$config->refresh();
$seo->refresh();
$data = $seo->data();
assert( 'All Our Tours' === $data['title'] );
assert( 'Browse every package.' === $data['description'] );
assert( 'https://example.test/archive/ztc_tour/' === $data['canonical'] );
assert( 'noindex' === $data['robots'] );
assert( 'website' === $data['og_type'] );

$robots = $output->filter_robots( array( 'max-image-preview' => 'large' ) );
assert( true === $robots['noindex'] && 'large' === $robots['max-image-preview'] );

$GLOBALS['query_vars']['paged'] = 3;
$seo->refresh();
assert( 'https://example.test/archive/ztc_tour/page/3/' === $seo->data()['canonical'] );
$GLOBALS['query_vars'] = array();

// Archive title derives from the post type when no setting exists.
$GLOBALS['options']['ztc_settings']['seo'] = array();
$config->refresh();
$seo->refresh();
assert( 'Tours – Test Site' === $seo->data()['title'] );
echo "archive resolution: OK\n";

// --- 9. Taxonomy archive: term name, term-link canonical.
$term           = new stdClass();
$term->name     = 'Asia';
$term->slug     = 'asia';
$GLOBALS['flags']['archive']    = null;
$GLOBALS['flags']['tax']        = 'ztc_region';
$GLOBALS['queried_object']      = $term;
$seo->refresh();
$data = $seo->data();
assert( 'country' === $data['type'] && 'archive' === $data['kind'] ); // region routes like the country archive
assert( 'Asia – Test Site' === $data['title'] );
assert( 'https://example.test/region/asia/' === $data['canonical'] );
$GLOBALS['flags']['tax']   = null;
$GLOBALS['queried_object'] = null;
echo "taxonomy archive: OK\n";

// --- 10. Head output on a single tour: meta, canonical, OG, Twitter,
//         JSON-LD — every component escaped, filterable, valid.
$GLOBALS['flags']['singular'] = 'ztc_tour';
$GLOBALS['queried_id']        = 10;
$GLOBALS['postmeta'][10]['ztc_seo']['title'] = 'Hostile "quote" <script>alert(1)</script> Title';
$seo->refresh();

$output->take_over_canonical();
assert( in_array( array( 'wp_head', 'rel_canonical' ), $GLOBALS['removed_actions'], true ) );

$parts = apply_filters( 'document_title_parts', array( 'title' => 'WP Default', 'site' => 'Test Site' ) );
assert( str_contains( $parts['title'], 'Hostile' ) && ! isset( $parts['site'] ) );

$head_filter = static fn( $head ) => $head . "<!--ztc-seo-head-filter-->\n";
add_filter( 'ztc_seo_head', $head_filter );
add_filter( 'ztc_seo_opengraph', static function ( $og ) { $og['og:custom'] = 'custom-og'; return $og; } );
add_filter( 'ztc_seo_twitter', static function ( $t ) { $t['twitter:label1'] = 'custom-tw'; return $t; } );

ob_start();
$output->render_head();
$head = ob_get_clean();

assert( str_contains( $head, '<meta name="description" content="Custom tour description.">' ) );
assert( str_contains( $head, '<meta name="keywords" content="tokyo, tour">' ) );
assert( str_contains( $head, '<link rel="canonical" href="https://example.test/custom-canonical/">' ) );
assert( str_contains( $head, '<meta property="og:type" content="article">' ) );
assert( str_contains( $head, '<meta property="og:image" content="https://img.test/7-full.jpg">' ) );
assert( str_contains( $head, '<meta property="og:locale" content="bn_BD">' ) );
assert( str_contains( $head, '<meta property="og:custom" content="custom-og">' ) );       // ztc_seo_opengraph
assert( str_contains( $head, '<meta name="twitter:card" content="summary_large_image">' ) );
assert( str_contains( $head, '<meta name="twitter:label1" content="custom-tw">' ) );      // ztc_seo_twitter
assert( str_contains( $head, '<!--ztc-seo-head-filter-->' ) );                             // ztc_seo_head
assert( ! str_contains( $head, '<script>alert' ) );                                        // hostile title escaped
assert( str_contains( $head, 'Hostile &quot;quote&quot;' ) );

// JSON-LD: one script tag, valid JSON, expected graph.
assert( 1 === substr_count( $head, '<script type="application/ld+json">' ) );
preg_match( '#<script type="application/ld\+json">(.+?)</script>#s', $head, $m );
$graph = json_decode( $m[1], true );
assert( JSON_ERROR_NONE === json_last_error(), 'invalid JSON-LD' );
assert( 'https://schema.org' === $graph['@context'] );
$types = array_column( $graph['@graph'], '@type' );
assert( array( 'TouristTrip', 'BreadcrumbList' ) === $types );
assert( ! in_array( 'FAQPage', $types, true ) );                       // FAQPage stays with the faq part

$trip = $graph['@graph'][0];
assert( 'Tokyo Adventure' === $trip['name'] );
assert( '1500.00' === $trip['offers']['price'] && 'USD' === $trip['offers']['priceCurrency'] );
assert( 'https://schema.org/InStock' === $trip['offers']['availability'] );
assert( 'TravelAgency' === $trip['provider']['@type'] );
assert( 2 === $trip['itinerary']['numberOfItems'] );                   // empty itinerary row dropped
assert( 'Arrival in Tokyo' === $trip['itinerary']['itemListElement'][0]['name'] );

$crumbs = $graph['@graph'][1]['itemListElement'];
assert( array( 1, 2, 3 ) === array_column( $crumbs, 'position' ) );
assert( 'Home' === $crumbs[0]['name'] && 'https://example.test/' === $crumbs[0]['item'] );
assert( 'Tours' === $crumbs[1]['name'] );
assert( ! isset( $crumbs[2]['item'] ) );                               // last crumb has no URL
remove_filter( 'ztc_seo_head', $head_filter );
$GLOBALS['hooks']['ztc_seo_opengraph'] = array();
$GLOBALS['hooks']['ztc_seo_twitter']   = array();
echo "head output + schema: OK\n";

// --- 11. ztc_seo_schema filter + schema/og/twitter toggles.
add_filter( 'ztc_seo_schema', static fn() => array() );
$seo->refresh();
ob_start();
$output->render_head();
assert( ! str_contains( ob_get_clean(), 'ld+json' ) );                 // filter can suppress the graph
$GLOBALS['hooks']['ztc_seo_schema'] = array();

$GLOBALS['options']['ztc_settings']['seo'] = array(
	'og_enabled'      => false,
	'twitter_enabled' => false,
	'schema_enabled'  => false,
);
$config->refresh();
$seo->refresh();
ob_start();
$output->render_head();
$head = ob_get_clean();
assert( ! str_contains( $head, 'og:' ) && ! str_contains( $head, 'twitter:' ) && ! str_contains( $head, 'ld+json' ) );
assert( str_contains( $head, 'rel="canonical"' ) );                    // canonical still ships
$GLOBALS['options']['ztc_settings']['seo'] = array();
$config->refresh();
echo "schema filter + toggles: OK\n";

// --- 12. Visa and Country schema nodes.
$visa = $schema->visa( 30 );
assert( 'GovernmentService' === $visa['@type'] );
assert( 'Japan Tourist Visa' === $visa['name'] );
assert( 'Tourist' === $visa['serviceType'] );                          // first visa-type term
assert( 'Country' === $visa['areaServed']['@type'] && 'Japan' === $visa['areaServed']['name'] );
assert( 'USD 50' === $visa['offers']['description'] );
assert( 'TravelAgency' === $visa['provider']['@type'] );

$country = $schema->country( 20 );
assert( 'Country' === $country['@type'] );
assert( 'Japan' === $country['name'] && 'জাপান' === $country['alternateName'] );
assert( 'Land of the rising sun.' === $country['description'] );

assert( array() === $schema->tour( 999 ) );                            // unknown post → empty node
assert( array() === $schema->breadcrumbs( array( array( 'name' => 'Home', 'url' => '/' ) ) ) ); // 1 crumb → none
assert( array() === $schema->graph( array( array(), array() ) ) );     // empty nodes → no envelope
echo "visa/country schema: OK\n";

// --- 13. Off plugin routes everything stays silent.
$GLOBALS['flags']['singular'] = null;
$seo->refresh();
assert( ! $seo->applies() );
assert( array() === $seo->data() );
$parts = apply_filters( 'document_title_parts', array( 'title' => 'Blog Post', 'site' => 'Test Site' ) );
assert( 'Blog Post' === $parts['title'] && 'Test Site' === $parts['site'] );
ob_start();
$output->render_head();
assert( '' === ob_get_clean() );
assert( array( 'noindex' => true ) === $output->filter_robots( array( 'noindex' => true ) ) );
echo "non-plugin routes: OK\n";

// --- 14. Deferral: kill switch, filter, then a real Yoast constant.
$GLOBALS['flags']['singular'] = 'ztc_tour';
$GLOBALS['queried_id']        = 10;

$GLOBALS['options']['ztc_settings']['seo'] = array( 'enabled' => false );
$config->refresh();
$seo->refresh();
assert( ! $seo->enabled() );                                           // settings kill switch
ob_start();
$output->render_head();
assert( '' === ob_get_clean() );
$GLOBALS['options']['ztc_settings']['seo'] = array();
$config->refresh();
$seo->refresh();
assert( $seo->enabled() );

$defer_filter = static fn() => true;
add_filter( 'ztc_seo_defer', $defer_filter );
assert( ! $seo->enabled() );                                           // filter-forced deferral
remove_filter( 'ztc_seo_defer', $defer_filter );
assert( $seo->enabled() );

define( 'WPSEO_VERSION', '22.0' );                                     // Yoast active
assert( $seo->deferred() && ! $seo->enabled() );
ob_start();
$output->render_head();
assert( '' === ob_get_clean() );
$parts = apply_filters( 'document_title_parts', array( 'title' => 'WP Default' ) );
assert( 'WP Default' === $parts['title'] );                            // title untouched while deferring
echo "deferral: OK\n";

echo "ALL SEO MODULE TESTS PASSED\n";
