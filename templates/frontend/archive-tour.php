<?php
/**
 * Tour archive template (/tour/ and tour-type archives).
 *
 * Override: yourtheme/zihad-travel-cms/frontend/archive-tour.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="ztc-main" class="ztc-archive ztc-archive--tour">
	<?php ztc_part( 'archive-body', ztc_view() ); ?>
</main>
<?php
get_footer();
