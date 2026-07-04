<?php
/**
 * Country archive template (/country/ and region archives).
 *
 * Override: yourtheme/zihad-travel-cms/frontend/archive-country.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="ztc-main" class="ztc-archive ztc-archive--country">
	<?php ztc_part( 'archive-body', ztc_view() ); ?>
</main>
<?php
get_footer();
