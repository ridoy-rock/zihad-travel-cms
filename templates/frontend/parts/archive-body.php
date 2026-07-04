<?php
/**
 * Shared archive body: title, search form, results grid, pagination.
 *
 * $data: the archive view-model (type, cards, total, search).
 * Override: yourtheme/zihad-travel-cms/frontend/parts/archive-body.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_total = (int) ( $data['total'] ?? 0 );
?>
<header class="ztc-archive__head">
	<div class="container">
		<?php the_archive_title( '<h1 class="ztc-archive__title">', '</h1>' ); ?>
		<?php the_archive_description( '<div class="ztc-archive__description text-muted">', '</div>' ); ?>

		<?php ztc_part( 'search-form', (array) ( $data['search'] ?? array() ) ); ?>
	</div>
</header>

<div class="container ztc-archive__body">
	<p class="ztc-archive__count text-muted" data-ztc-count aria-live="polite">
		<?php
		printf(
			/* translators: %s: number of results. */
			esc_html( _n( '%s result', '%s results', $ztc_total, 'zihad-travel-cms' ) ),
			esc_html( number_format_i18n( $ztc_total ) )
		);
		?>
	</p>

	<?php
	ztc_part(
		'grid',
		array(
			'cards'   => (array) ( $data['cards'] ?? array() ),
			'columns' => 3,
			'type'    => (string) ( $data['type'] ?? '' ),
		)
	);
	?>

	<nav class="ztc-archive__pagination" aria-label="<?php esc_attr_e( 'Results pages', 'zihad-travel-cms' ); ?>">
		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	</nav>
</div>
