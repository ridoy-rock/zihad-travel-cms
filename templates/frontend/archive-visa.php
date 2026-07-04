<?php
/**
 * Visa archive template (/visa/ and visa-type archives).
 *
 * Override: yourtheme/zihad-travel-cms/frontend/archive-visa.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="ztc-main" class="ztc-archive ztc-archive--visa">
	<?php ztc_part( 'archive-body', ztc_view() ); ?>
</main>
<?php
get_footer();
