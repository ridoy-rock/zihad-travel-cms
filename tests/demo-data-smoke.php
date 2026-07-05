<?php
// Demo data smoke test: generator output (counts, structure, Bangla,
// determinism, bn locale) and a full install through the import engine.
define( 'ABSPATH', '/tmp/' );
define( 'ZTC_VERSION', '1.0.0' );
define( 'ZTC_MIN_PHP', '8.2' );
define( 'ZTC_MIN_WP', '6.4' );
define( 'ZTC_PLUGIN_FILE', dirname( __DIR__ ) . '/zihad-travel-cms.php' );
define( 'ZTC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ZTC_PLUGIN_URL', 'https://example.test/' );
define( 'ZTC_PLUGIN_BASENAME', 'zihad-travel-cms/zihad-travel-cms.php' );

$SCRATCH = sys_get_temp_dir() . '/ztc-demo-test';
exec( 'rm -rf ' . escapeshellarg( $SCRATCH ) );
mkdir( $SCRATCH, 0777, true );

// --- In-memory WordPress store (same bank as the importer suite).
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

$GLOBALS['store'] = array( 'posts' => array(), 'meta' => array(), 'terms' => array(), 'thumbs' => array(), 'next_id' => 100 );
$GLOBALS['options'] = array();
$GLOBALS['source_index'] = array(); // URL → attachment id (fast sideload dedup).
$GLOBALS['transients'] = array();

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
	// Fast path for the image importer's source-URL lookup.
	if ( 'attachment' === ( $args['post_type'] ?? '' ) && ! empty( $args['meta_key'] ) ) {
		$id = $GLOBALS['source_index'][ (string) ( $args['meta_value'] ?? '' ) ] ?? 0;
		return $id > 0 ? array( $id ) : array();
	}
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
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['store']['meta'][ (int) $id ][ $key ] = $value;
	if ( '_ztc_source_url' === $key ) { $GLOBALS['source_index'][ (string) $value ] = (int) $id; }
	return true;
}
function wp_set_object_terms( $id, $terms, $tax, $append ) { $GLOBALS['store']['terms'][ (int) $id ][ $tax ] = (array) $terms; return $terms; }
function get_the_terms( $id, $tax ) {
	return array_map( static function ( $n ) { $t = new stdClass(); $t->name = $n; return $t; }, $GLOBALS['store']['terms'][ (int) $id ][ $tax ] ?? array() );
}
function set_post_thumbnail( $id, $tid ) { $GLOBALS['store']['thumbs'][ (int) $id ] = (int) $tid; return true; }
function get_post_thumbnail_id( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return $GLOBALS['store']['thumbs'][ $id ] ?? 0; }
function get_the_title( $p ) { $id = $p instanceof WP_Post ? $p->ID : (int) $p; return isset( $GLOBALS['store']['posts'][ $id ] ) ? $GLOBALS['store']['posts'][ $id ]->post_title : ''; }
function wp_get_attachment_url( $id ) { return 'https://site.test/uploads/' . (int) $id . '.jpg'; }
function get_option( $n, $d = false ) { return $GLOBALS['options'][ $n ] ?? $d; }
function update_option( $n, $v, $autoload = null ) { $GLOBALS['options'][ $n ] = $v; return true; }
function delete_option( $n ) { unset( $GLOBALS['options'][ $n ] ); return true; }
function apply_filters( $h, $v ) {
	if ( 'ztc_demo_data_dir' === $h ) { return $GLOBALS['demo_dir'] ?? $v; }
	return $v;
}
function do_action( ...$a ) {}
function __( $t, $d = 'default' ) { return $t; }
function current_user_can( ...$a ) { return true; }
function sanitize_title( $t ) { return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $t ) ), '-' ); }
function sanitize_file_name( $t ) { return (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $t ); }
function wp_basename( $p ) { return basename( (string) $p ); }
function wp_parse_url( $u, $c ) { return parse_url( (string) $u, $c ); }
function esc_url_raw( $u ) { return (string) $u; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function wp_mkdir_p( $dir ) { return is_dir( $dir ) || mkdir( $dir, 0777, true ); }
function trailingslashit( $p ) { return rtrim( (string) $p, '/' ) . '/'; }
function untrailingslashit( $p ) { return rtrim( (string) $p, '/' ); }
function wp_delete_file( $f ) {}
function download_url( $url, $timeout = 30 ) { return '/tmp/fake-download'; }
function set_transient( $k, $v, $e ) { $GLOBALS['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][ $k ] ); return true; }
function wp_count_posts( $t ) { $o = new stdClass(); $o->publish = count( array_filter( $GLOBALS['store']['posts'], static fn( $p ) => $p->post_type === $t && 'publish' === $p->post_status ) ); return $o; }
function get_locale() { return 'en_US'; }
function get_current_user_id() { return 1; }
function media_handle_sideload( $file_array, $parent_id ) {
	return wp_insert_post( array( 'post_type' => 'attachment', 'post_title' => $file_array['name'], 'post_status' => 'inherit' ) );
}

require ZTC_PLUGIN_DIR . 'includes/Autoloader.php';
ZihadTravelCMS\Autoloader::register();

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Core\Container;
use ZihadTravelCMS\Modules\DemoData\DemoContentGenerator;
use ZihadTravelCMS\Modules\DemoData\DemoDataInstaller;
use ZihadTravelCMS\Modules\DemoData\SourceRepository;
use ZihadTravelCMS\Modules\Importer\ImageImporter;
use ZihadTravelCMS\Modules\Importer\ImportJob;
use ZihadTravelCMS\Modules\Importer\ImportService;
use ZihadTravelCMS\Modules\Importer\JobRepository;
use ZihadTravelCMS\Modules\Importer\MappingRegistry;
use ZihadTravelCMS\Modules\Importer\Readers\CsvReader;
use ZihadTravelCMS\Modules\Importer\Readers\JsonReader;

$sources   = new SourceRepository( new Config() );
$generator = new DemoContentGenerator( $sources );

// --- 1. Generation: counts + structure + Bangla.
$out = $SCRATCH . '/out';
$result = $generator->generate( 'en', $out );

assert( $result['counts']['country'] >= 100, 'need >= 100 countries' );
assert( $result['counts']['visa'] >= 400, 'need >= 400 visas' );
assert( $result['counts']['tour'] >= 100, 'need >= 100 tours' );

$countries = json_decode( (string) file_get_contents( $out . '/countries.json' ), true );
assert( 'ztc-demo/1' === $countries['schema'] && 'en' === $countries['locale'] );
$japan = $countries['records'][0];
assert( 'Japan' === $japan['title'] && 'japan' === $japan['slug'] );
assert( 'জাপান' === $japan['bangla_name'] );
assert( 'https://picsum.photos/seed/ztc-japan-hero/1600/900' === $japan['hero_image'] );
assert( 'https://flagcdn.com/w320/jp.png' === $japan['flag'] );
assert( array( 'East Asia' ) === $japan['regions'] );
assert( 3 === count( $japan['gallery'] ) );
assert( str_contains( $japan['faq'][0]['question'], 'Japan' ) );
assert( str_contains( $japan['overview'], 'Tokyo' ) );
assert( true === $japan['featured'] );

$visas = json_decode( (string) file_get_contents( $out . '/visas.json' ), true );
assert( 'Japan Tourist Visa' === $visas['records'][0]['title'] );
assert( 'japan-tourist-visa' === $visas['records'][0]['slug'] );
assert( 'Japan' === $visas['records'][0]['country'] );
assert( 6 === count( $visas['records'][0]['required_documents'] ) );
assert( 4 === count( $visas['records'][0]['application_process'] ) );

$tours = json_decode( (string) file_get_contents( $out . '/tours.json' ), true );
assert( is_float( $tours['records'][0]['price'] + 0.0 ) && $tours['records'][0]['price'] > 0 );
assert( isset( $tours['records'][0]['duration']['days'], $tours['records'][0]['duration']['nights'] ) );
assert( str_contains( $tours['records'][0]['map'], 'google.com/maps' ) );
echo "generation structure: OK ({$result['counts']['country']} countries, {$result['counts']['visa']} visas, {$result['counts']['tour']} tours)\n";

// --- 2. Slug uniqueness per file.
foreach ( array( 'countries', 'visas', 'tours' ) as $file ) {
	$records = json_decode( (string) file_get_contents( $out . "/$file.json" ), true )['records'];
	$slugs   = array_column( $records, 'slug' );
	assert( count( $slugs ) === count( array_unique( $slugs ) ), "duplicate slugs in $file" );
}
echo "slug uniqueness: OK\n";

// --- 3. Determinism: regenerating produces byte-identical files.
$hashes1 = array_map( 'md5_file', glob( $out . '/*.json' ) );
$generator->generate( 'en', $out );
$hashes2 = array_map( 'md5_file', glob( $out . '/*.json' ) );
assert( $hashes1 === $hashes2 );
echo "deterministic regeneration: OK\n";

// --- 4. Bangla locale: full bn content, same latin slugs.
$bn_out = $SCRATCH . '/bn';
$generator->generate( 'bn', $bn_out );
$bn_countries = json_decode( (string) file_get_contents( $bn_out . '/countries.json' ), true );
assert( 'জাপান' === $bn_countries['records'][0]['title'] );          // Bangla title
assert( 'japan' === $bn_countries['records'][0]['slug'] );            // same slug (translation linking key)
assert( array( 'পূর্ব এশিয়া' ) === $bn_countries['records'][0]['regions'] );
$bn_visas = json_decode( (string) file_get_contents( $bn_out . '/visas.json' ), true );
assert( 'জাপান ট্যুরিস্ট ভিসা' === $bn_visas['records'][0]['title'] );
assert( str_contains( $bn_visas['records'][0]['required_documents'][0], 'পাসপোর্ট' ) );
echo "bangla locale: OK\n";

// --- 5. Install through the real import engine (no duplicated logic).
$GLOBALS['demo_dir'] = $out; // ztc_demo_data_dir filter target.
$container = new Container();
$import    = new ImportService( new MappingRegistry( $container ), new JobRepository(), new CsvReader(), new JsonReader(), new ImageImporter() );
$installer = new DemoDataInstaller( $import, $sources );

assert( true === $installer->files_ready() );

$jobs = $installer->install( 100 );
assert( array( 'country', 'visa', 'tour' ) === array_keys( $jobs ) );
foreach ( $jobs as $type => $job ) {
	assert( ImportJob::STATUS_COMPLETED === $job->status, "$type not completed" );
	assert( 0 === $job->failed, "$type had failures: " . implode( '; ', $job->errors ) );
}
assert( $jobs['country']->created >= 100 && $jobs['visa']->created >= 400 && $jobs['tour']->created >= 100 );

$japan_post = get_posts( array( 'post_type' => 'ztc_country', 'name' => 'japan' ) )[0];
assert( 'জাপান' === get_post_meta( $japan_post->ID, 'ztc_bangla_name' ) );
$visa_post = get_posts( array( 'post_type' => 'ztc_visa', 'name' => 'japan-tourist-visa' ) )[0];
assert( $japan_post->ID === get_post_meta( $visa_post->ID, 'ztc_country' ) );  // relation resolved
assert( get_post_meta( $visa_post->ID, 'ztc_hero_image' ) > 0 );               // image sideloaded
$tour_posts = get_posts( array( 'post_type' => 'ztc_tour' ) );
assert( count( $tour_posts ) >= 100 );
assert( is_array( get_post_meta( $tour_posts[0]->ID, 'ztc_itinerary' ) ) );

// Re-install: everything upserts, nothing duplicates.
$before = count( $GLOBALS['store']['posts'] );
$jobs   = $installer->install( 100 );
assert( 0 === $jobs['country']->created && $jobs['country']->updated >= 100 );
assert( count( $GLOBALS['store']['posts'] ) === $before );                     // no new posts, no new attachments
echo "install via importer + idempotent re-install: OK\n";


// --- 6. Truthful demo status (QA: the dashboard said "Ready to
//        install" after a real install because it trusted a flag).
$translations = new ZihadTravelCMS\Translations\SiteTranslationProvider();
$job_repo     = new JobRepository();
$status       = new ZihadTravelCMS\Modules\DemoData\DemoDataStatus(
	new ZihadTravelCMS\Modules\Country\CountryRepository( $translations ),
	new ZihadTravelCMS\Modules\Visa\VisaRepository( $translations ),
	new ZihadTravelCMS\Modules\Tour\TourRepository( $translations ),
	$sources,
	$job_repo,
	$installer
);

unset( $GLOBALS['options']['ztc_demo_installed'] );                     // the drifted flag from the bug report
$expected = $status->expected_counts();
assert( $expected === array_map( 'intval', $result['counts'] ), 'expected counts must come from the files' );
assert( true === $status->installed() );                                // computed from records, not the flag
assert( null === $status->active_job() );
$snapshot = $status->status();
assert( true === $snapshot['installed'] && true === $snapshot['files_ready'] && null === $snapshot['job'] && false === $snapshot['stale'] );
assert( is_array( $GLOBALS['transients']['ztc_demo_expected_counts'] ?? null ) ); // cached for later reads
echo "truthful demo status: OK\n";

// --- 7. Interrupted install: never "running" forever.
$abandoned = $installer->start( 'country' );
$import->process( $abandoned->id, 30 );                                 // one batch, then walk away
$stored = $GLOBALS['options'][ 'ztc_import_job_' . $abandoned->id ];
assert( 'running' === $stored['status'] && 30 === $stored['processed'] );
assert( ( $stored['updated_at'] ?? 0 ) > 0 );                           // heartbeat persisted with the batch

$active = $status->active_job();
assert( null !== $active && $abandoned->id === $active->id );
assert( 'running' === $active->display_status() );                      // fresh job: genuinely running

$GLOBALS['options'][ 'ztc_import_job_' . $abandoned->id ]['updated_at'] = time() - 3600;
$active = $status->active_job();
assert( true === $active->is_stale() );
assert( 'interrupted' === $active->display_status() );                  // stale job: interrupted
assert( 'interrupted' === $active->to_array()['display_status'] );
assert( 'running' === $active->status );                                // storage status untouched: resumable
assert( true === $status->status()['stale'] );
echo "interrupted display state: OK\n";

// --- 8. Resume completes the abandoned job and fills missing types,
//        without creating duplicates.
$ztc_removed = 0;
foreach ( $GLOBALS['store']['posts'] as $ztc_id => $ztc_post ) {
	if ( 'ztc_tour' === $ztc_post->post_type && $ztc_removed < 20 ) {
		unset( $GLOBALS['store']['posts'][ $ztc_id ] );
		++$ztc_removed;
	}
}
assert( false === $status->installed() );                               // 20 tours short

$actions = new ZihadTravelCMS\Modules\DemoData\DemoDataActions(
	$status, $installer, $import, $job_repo, new ZihadTravelCMS\Services\NotificationService()
);

$rounds = 0;
do {
	$resume = $actions->advance( 10, 50 );
	assert( ++$rounds < 40, 'resume did not converge' );
} while ( ! $resume['installed'] );

assert( true === $status->installed() );
assert( null === $status->active_job() );                               // abandoned job finished, not orphaned
assert( $status->actual_counts()['country'] === $expected['country'] ); // resume upserted: zero duplicates
assert( $status->actual_counts()['tour'] === $expected['tour'] );
echo "resume incomplete install: OK\n";

// --- 9. Reinstall over a complete dataset stays idempotent, and
//        resetting a stale job never touches content.
$installer->install( 100 );
assert( true === $status->installed() );
assert( $status->actual_counts() === array_map( 'intval', $result['counts'] ) ); // reinstall: no duplicates

$stale2 = $installer->start( 'visa' );
$import->process( $stale2->id, 10 );
$GLOBALS['options'][ 'ztc_import_job_' . $stale2->id ]['updated_at'] = time() - 3600;
assert( null !== $status->active_job() );

$posts_before = count( $GLOBALS['store']['posts'] );
assert( 1 === $actions->clear_unfinished_jobs() );                      // only the stale demo job record
assert( null === $status->active_job() );
assert( count( $GLOBALS['store']['posts'] ) === $posts_before );        // content untouched
assert( true === $status->installed() );
echo "reinstall + reset status: OK\n";

// --- 10. Failed counts only actual failures, never processed/total.
$bad_file = $SCRATCH . '/bad-countries.json';
file_put_contents(
	$bad_file,
	json_encode(
		array(
			'records' => array(
				array( 'title' => 'Testland A', 'slug' => 'testland-a' ),
				array( 'slug' => 'no-title-so-required-field-fails' ),
				array( 'title' => 'Testland B', 'slug' => 'testland-b' ),
			),
		)
	)
);

$fail_job = $import->start( 'country', $bad_file, 'upsert', false );
while ( ! $fail_job->is_finished() ) {
	$fail_job = $import->process( $fail_job->id, 2 );
}

assert( ImportJob::STATUS_COMPLETED === $fail_job->status );
assert( 3 === $fail_job->total && 3 === $fail_job->processed );
assert( 1 === $fail_job->failed, 'exactly the one bad record fails' );
assert( 2 === $fail_job->created );
assert( $fail_job->failed !== $fail_job->processed && $fail_job->failed !== $fail_job->total );
assert( $fail_job->created + $fail_job->updated + $fail_job->skipped + $fail_job->failed === $fail_job->processed ); // count invariant
assert( false === $status->is_demo_job( $fail_job ) );                  // a user import never colours demo status
echo "failed count accuracy: OK\n";

echo "ALL DEMO DATA TESTS PASSED\n";
