<?php
// Importer smoke test: readers, batching + resume, all field targets,
// duplicate detection, rollback, Bangla round-trip, REST controller.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );

$SCRATCH = sys_get_temp_dir() . '/ztc-importer-test';
@mkdir( $SCRATCH, 0777, true );

// --- In-memory WordPress store.
class WP_Post {
	public int $ID = 0;
	public string $post_type = '';
	public string $post_title = '';
	public string $post_name = '';
	public string $post_status = 'publish';
	public string $post_content = '';
	public string $post_excerpt = '';
	public function __construct( array $data = array() ) {
		foreach ( $data as $k => $v ) { $this->$k = $v; }
	}
}
class WP_Error {
	public function __construct( public string $code = '', public string $message = '', public mixed $data = null ) {}
	public function get_error_message(): string { return $this->message; }
}
class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; }
class WP_REST_Request {
	public function __construct( private array $params = array() ) {}
	public function get_param( $k ) { return $this->params[ $k ] ?? null; }
	public function get_params(): array { return $this->params; }
}
class WP_REST_Response {
	public function __construct( public mixed $data = null ) {}
}

$GLOBALS['store'] = array( 'posts' => array(), 'meta' => array(), 'terms' => array(), 'thumbs' => array(), 'next_id' => 100 );
$GLOBALS['options'] = array();
$GLOBALS['routes'] = array();
$GLOBALS['attached'] = array();

function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_insert_post( $arr, $wp_error = false ) {
	$id = $GLOBALS['store']['next_id']++;
	$arr['ID'] = $id;
	$GLOBALS['store']['posts'][ $id ] = new WP_Post( $arr );
	return $id;
}
function wp_update_post( $arr, $wp_error = false ) {
	$id = (int) $arr['ID'];
	foreach ( $arr as $k => $v ) { $GLOBALS['store']['posts'][ $id ]->$k = $v; }
	return $id;
}
function wp_delete_post( $id, $force = false ) { unset( $GLOBALS['store']['posts'][ (int) $id ] ); return true; }
function get_posts( $args ) {
	$out = array();
	foreach ( $GLOBALS['store']['posts'] as $p ) {
		if ( ! empty( $args['post_type'] ) && $p->post_type !== $args['post_type'] ) { continue; }
		if ( ! empty( $args['name'] ) && $p->post_name !== $args['name'] ) { continue; }
		if ( ! empty( $args['meta_key'] ) ) {
			$mv = $GLOBALS['store']['meta'][ $p->ID ][ $args['meta_key'] ] ?? null;
			if ( (string) $mv !== (string) ( $args['meta_value'] ?? '' ) ) { continue; }
		}
		$out[] = $p;
	}
	$limit = (int) ( $args['posts_per_page'] ?? -1 );
	if ( $limit > 0 ) { $out = array_slice( $out, 0, $limit ); }
	if ( 'ids' === ( $args['fields'] ?? '' ) ) { return array_map( static fn( $p ) => $p->ID, $out ); }
	return $out;
}
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['store']['meta'][ (int) $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['store']['meta'][ (int) $id ][ $key ] = $value; return true; }
function wp_set_object_terms( $id, $terms, $tax, $append ) { $GLOBALS['store']['terms'][ (int) $id ][ $tax ] = (array) $terms; return $terms; }
function get_the_terms( $id, $tax ) {
	$names = $GLOBALS['store']['terms'][ (int) $id ][ $tax ] ?? array();
	return array_map( static function ( $n ) { $t = new stdClass(); $t->name = $n; return $t; }, $names );
}
function set_post_thumbnail( $id, $tid ) { $GLOBALS['store']['thumbs'][ (int) $id ] = (int) $tid; return true; }
function get_post_thumbnail_id( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return $GLOBALS['store']['thumbs'][ $id ] ?? 0; }
function get_the_title( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return isset( $GLOBALS['store']['posts'][ $id ] ) ? $GLOBALS['store']['posts'][ $id ]->post_title : ''; }
function wp_get_attachment_url( $id ) { return 'https://site.test/uploads/' . (int) $id . '.jpg'; }

function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }

function apply_filters( $h, $v ) { return $v; }
function do_action( ...$a ) {}
function __( $t, $d = 'default' ) { return $t; }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function current_user_can( ...$a ) { return true; }
function sanitize_title( $t ) { return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $t ) ), '-' ); }
function sanitize_file_name( $t ) { return (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $t ); }
function wp_basename( $p ) { return basename( (string) $p ); }
function wp_parse_url( $u, $c ) { return parse_url( (string) $u, $c ); }
function esc_url_raw( $u ) { return (string) $u; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['routes'][ $route ] = array( $ns, $args ); }
function get_attached_file( $id ) { return $GLOBALS['attached'][ (int) $id ] ?? ''; }
function wp_delete_file( $f ) {}

// Image sideloading stubs (defined before ImageImporter runs, so it
// skips the wp-admin requires).
function download_url( $url, $timeout = 30 ) { return '/tmp/fake-download'; }
function media_handle_sideload( $file_array, $parent_id ) {
	return wp_insert_post( array( 'post_type' => 'attachment', 'post_title' => $file_array['name'], 'post_name' => sanitize_title( $file_array['name'] ), 'post_status' => 'inherit' ) );
}

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Core\Container;
use ZihadTravelCMS\Modules\Importer\ExportService;
use ZihadTravelCMS\Modules\Importer\ImageImporter;
use ZihadTravelCMS\Modules\Importer\ImportController;
use ZihadTravelCMS\Modules\Importer\ImportJob;
use ZihadTravelCMS\Modules\Importer\ImportService;
use ZihadTravelCMS\Modules\Importer\JobRepository;
use ZihadTravelCMS\Modules\Importer\MappingRegistry;
use ZihadTravelCMS\Modules\Importer\Readers\CsvReader;
use ZihadTravelCMS\Modules\Importer\Readers\JsonReader;

$container = new Container();
$registry  = new MappingRegistry( $container );
$jobs      = new JobRepository();
$import    = new ImportService( $registry, $jobs, new CsvReader(), new JsonReader(), new ImageImporter() );
$export    = new ExportService( $registry );

// --- 1. CSV import: batching, resume, all cell formats, Bangla, errors.
$csv = $SCRATCH . '/countries.csv';
$rows = array(
	array( 'title', 'slug', 'bangla_name', 'capital', 'popular_cities', 'regions', 'faq', 'hero_image' ),
	array( 'Japan', 'japan', 'জাপান', 'Tokyo', 'Tokyo|Osaka', 'Asia|East Asia', '[{"question":"Visa?","answer":"Yes"}]', 'https://img.example/japan-hero.jpg' ),
	array( 'Malaysia', 'malaysia', 'মালয়েশিয়া', 'Kuala Lumpur', 'KL|Penang', 'Asia', '', '' ),
	array( '', 'broken', '', '', '', '', '', '' ), // missing required title
);
$h = fopen( $csv, 'w' );
fwrite( $h, "\xEF\xBB\xBF" );
foreach ( $rows as $r ) { fputcsv( $h, $r ); }
fclose( $h );

$job = $import->start( 'country', $csv, 'upsert', false );
assert( 3 === $job->total && 'pending' === $job->status );

$job = $import->process( $job->id, 2 );          // batch 1
assert( 2 === $job->processed && ! $job->is_finished() );
$job = $import->process( $job->id, 2 );          // batch 2 = resume from offset
assert( ImportJob::STATUS_COMPLETED === $job->status );
assert( 2 === $job->created && 1 === $job->failed && 100.0 === $job->progress() );
assert( str_contains( implode( ' ', $job->errors ), 'Required field "title"' ) );

$japan = get_posts( array( 'post_type' => 'ztc_country', 'name' => 'japan' ) )[0];
assert( 'জাপান' === get_post_meta( $japan->ID, 'ztc_bangla_name' ) );                       // Bangla intact
assert( array( 'Tokyo', 'Osaka' ) === get_post_meta( $japan->ID, 'ztc_popular_cities' ) );  // pipe list
assert( array( 'Asia', 'East Asia' ) === $GLOBALS['store']['terms'][ $japan->ID ]['ztc_region'] ); // terms
assert( 'Visa?' === get_post_meta( $japan->ID, 'ztc_faq' )[0]['question'] );                // JSON cell
$hero_id = (int) get_post_meta( $japan->ID, 'ztc_hero_image' );
assert( $hero_id > 0 );                                                                     // image sideloaded
echo "csv import + batching + resume: OK\n";

// --- 2. Duplicate detection across modes.
$attachments_before = count( get_posts( array( 'post_type' => 'attachment' ) ) );
$job = $import->process( $import->start( 'country', $csv, 'create', false )->id, 10 );
assert( 2 === $job->skipped && 0 === $job->created );                        // create skips existing
$job = $import->process( $import->start( 'country', $csv, 'upsert', false )->id, 10 );
assert( 2 === $job->updated && 0 === $job->created );                        // upsert updates
assert( $attachments_before === count( get_posts( array( 'post_type' => 'attachment' ) ) ) ); // image reused via source URL
$job = $import->process( $import->start( 'country', $csv, 'update', false )->id, 10 );
assert( 2 === $job->updated );                                               // update touches existing only
echo "duplicate detection (create/update/upsert): OK\n";

// --- 3. JSON import: relations, structured fields, gallery, thumbnail.
$tours_json = $SCRATCH . '/tours.json';
file_put_contents( $tours_json, json_encode( array( 'records' => array(
	array(
		'title'      => 'Tokyo Adventure',
		'slug'       => 'tokyo-adventure',
		'country'    => 'Japan',
		'tour_types' => array( 'Adventure' ),
		'price'      => 1500,
		'duration'   => array( 'days' => '5', 'nights' => '4' ),
		'highlights' => array( 'Shibuya', 'Mt Fuji' ),
		'itinerary'  => array( array( 'title' => 'Day 1', 'description' => 'Arrive' ) ),
		'gallery'    => array( 'https://img.example/t1.jpg', 'https://img.example/t2.jpg' ),
		'thumbnail'  => 'https://img.example/thumb.jpg',
	),
	array(
		'title'   => 'Mystery Tour',
		'slug'    => 'mystery-tour',
		'country' => 'Atlantis', // unknown relation → soft warning
	),
) ), JSON_UNESCAPED_UNICODE ) );

$job = $import->process( $import->start( 'tour', $tours_json, 'upsert', false )->id, 10 );
assert( 2 === $job->created && 0 === $job->failed );
$tour = get_posts( array( 'post_type' => 'ztc_tour', 'name' => 'tokyo-adventure' ) )[0];
assert( $japan->ID === get_post_meta( $tour->ID, 'ztc_country' ) );          // relation resolved
assert( array( 'days' => '5', 'nights' => '4' ) === get_post_meta( $tour->ID, 'ztc_duration' ) );
assert( 2 === count( get_post_meta( $tour->ID, 'ztc_gallery' ) ) );          // gallery sideloaded
assert( get_post_thumbnail_id( $tour ) > 0 );                                // featured image
$mystery = get_posts( array( 'post_type' => 'ztc_tour', 'name' => 'mystery-tour' ) )[0];
assert( 0 === get_post_meta( $mystery->ID, 'ztc_country' ) );                // unknown relation → 0
assert( str_contains( implode( ' ', $job->errors ), 'Atlantis' ) );          // …and logged
echo "json import + relations + images: OK\n";

// --- 4. Rollback on failure (all-or-nothing).
$bad_csv = $SCRATCH . '/visas.csv';
$h = fopen( $bad_csv, 'w' );
fputcsv( $h, array( 'title', 'slug', 'country', 'faq' ) );
fputcsv( $h, array( 'Japan Tourist Visa', 'japan-tourist-visa', 'Japan', '' ) );
fputcsv( $h, array( 'Broken Visa', 'broken-visa', 'Japan', '{invalid json' ) ); // json error → row fails
fclose( $h );

$job = $import->process( $import->start( 'visa', $bad_csv, 'upsert', true )->id, 10 );
assert( ImportJob::STATUS_ROLLED_BACK === $job->status );
assert( 1 === $job->failed );
assert( array() === get_posts( array( 'post_type' => 'ztc_visa' ) ) );        // everything rolled back
echo "rollback on failure: OK\n";

// --- 5. Manual rollback.
$good_csv = $SCRATCH . '/visas-good.csv';
$h = fopen( $good_csv, 'w' );
fputcsv( $h, array( 'title', 'slug', 'country' ) );
fputcsv( $h, array( 'Japan Tourist Visa', 'japan-tourist-visa', 'Japan' ) );
fclose( $h );
$job = $import->process( $import->start( 'visa', $good_csv, 'upsert', false )->id, 10 );
assert( 1 === count( get_posts( array( 'post_type' => 'ztc_visa' ) ) ) );
$job = $import->rollback( $job->id );
assert( ImportJob::STATUS_ROLLED_BACK === $job->status );
assert( array() === get_posts( array( 'post_type' => 'ztc_visa' ) ) );
echo "manual rollback: OK\n";

// --- 6. Export + round-trip.
$result = $export->export( 'country', 'json' );
assert( 'ztc-country-export.json' === $result['filename'] && 'application/json' === $result['mime'] );
assert( str_contains( $result['body'], 'জাপান' ) );                            // unescaped Bangla
$decoded = json_decode( $result['body'], true );
assert( 2 === count( $decoded['records'] ) );
assert( 'Tokyo' === $decoded['records'][0]['capital'] );
assert( array( 'Asia', 'East Asia' ) === $decoded['records'][0]['regions'] );
assert( 'https://img.example/japan-hero.jpg' === $decoded['records'][0]['hero_image'] ); // source URL preserved

$csv_result = $export->export( 'country', 'csv' );
assert( str_starts_with( $csv_result['body'], "\xEF\xBB\xBF" ) );              // BOM
assert( str_contains( $csv_result['body'], 'Tokyo|Osaka' ) );                  // pipe list cell

$roundtrip = $SCRATCH . '/countries-roundtrip.json';
file_put_contents( $roundtrip, $result['body'] );
$job = $import->process( $import->start( 'country', $roundtrip, 'upsert', false )->id, 10 );
assert( 2 === $job->updated && 0 === $job->created && 0 === $job->failed );    // export re-imports cleanly
echo "export + round-trip: OK\n";

// --- 7. REST controller.
$controller = new ImportController( $import, $export, $jobs, $registry );
$controller->register_routes();
assert( array( '/import/start', '/import/process', '/import/status', '/import/jobs', '/import/rollback', '/export' ) === array_keys( $GLOBALS['routes'] ) );
assert( 'ztc/v1' === $GLOBALS['routes']['/import/start'][0] );

$GLOBALS['attached'][77] = $csv;
$response = $controller->start( new WP_REST_Request( array( 'type' => 'country', 'media_id' => 77, 'mode' => 'upsert', 'rollback_on_failure' => false ) ) );
assert( $response instanceof WP_REST_Response && 3 === $response->data['total'] );
$response = $controller->process( new WP_REST_Request( array( 'job_id' => $response->data['id'], 'batch' => 10 ) ) );
assert( true === $response->data['finished'] && 100.0 === $response->data['progress'] );

$error = $controller->start( new WP_REST_Request( array( 'type' => 'martian', 'media_id' => 77 ) ) );
assert( $error instanceof WP_Error && str_contains( $error->get_error_message(), 'Unknown import type' ) );
echo "rest controller: OK\n";

echo "ALL IMPORTER TESTS PASSED\n";
