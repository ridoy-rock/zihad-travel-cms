<?php
/**
 * Card grid part.
 *
 * $data: cards (pre-escaped HTML strings), columns (2-4), heading, type.
 * Override: yourtheme/zihad-travel-cms/frontend/parts/grid.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_columns_map = array(
	1 => 'col-12',
	2 => 'col-12 col-md-6',
	3 => 'col-12 col-sm-6 col-lg-4',
	4 => 'col-12 col-sm-6 col-lg-3',
);
$ztc_columns     = max( 1, min( 4, (int) ( $data['columns'] ?? 3 ) ) );
$ztc_col_class   = $ztc_columns_map[ $ztc_columns ];
$ztc_cards       = (array) ( $data['cards'] ?? array() );
?>
<section class="ztc-grid-section">
	<?php if ( ! empty( $data['heading'] ) ) : ?>
		<h2 class="ztc-grid-section__heading"><?php echo esc_html( (string) $data['heading'] ); ?></h2>
	<?php endif; ?>

	<div class="row g-4 ztc-grid" data-ztc-results data-ztc-col-class="<?php echo esc_attr( $ztc_col_class ); ?>">
		<?php foreach ( $ztc_cards as $ztc_card ) : ?>
			<div class="<?php echo esc_attr( $ztc_col_class ); ?>">
				<?php echo $ztc_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card components escape internally. ?>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="ztc-grid__empty text-muted" data-ztc-empty <?php echo array() !== $ztc_cards ? 'hidden' : ''; ?>>
		<?php esc_html_e( 'Nothing found. Try different filters.', 'zihad-travel-cms' ); ?>
	</p>
</section>
