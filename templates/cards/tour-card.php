<?php
/**
 * Tour card template.
 *
 * Available: $data (view-model from TourService::card_data()).
 * Override by copying to yourtheme/zihad-travel-cms/cards/tour-card.php.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $data['id'] ) ) {
	return;
}
?>
<div class="card ztc-card ztc-card--tour h-100 shadow-sm">
	<?php if ( ! empty( $data['image'] ) ) : ?>
		<a href="<?php echo esc_url( $data['url'] ); ?>" class="ztc-card__media">
			<img class="card-img-top" src="<?php echo esc_url( $data['image'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" loading="lazy">
		</a>
	<?php endif; ?>

	<div class="card-body d-flex flex-column">
		<?php if ( ! empty( $data['types'] ) ) : ?>
			<div class="mb-2">
				<?php foreach ( $data['types'] as $ztc_type ) : ?>
					<span class="badge text-bg-primary me-1"><?php echo esc_html( $ztc_type ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="card-title h5 mb-1">
			<a class="text-decoration-none" href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
		</h3>

		<?php if ( ! empty( $data['country'] ) || ! empty( $data['duration'] ) ) : ?>
			<p class="text-muted small mb-2">
				<?php echo esc_html( trim( ( $data['country'] ?? '' ) . ( ! empty( $data['country'] ) && ! empty( $data['duration'] ) ? ' · ' : '' ) . ( $data['duration'] ?? '' ) ) ); ?>
			</p>
		<?php endif; ?>

		<div class="mt-auto d-flex justify-content-between align-items-center">
			<div class="ztc-card__price">
				<?php if ( ! empty( $data['on_sale'] ) && ! empty( $data['regular_price'] ) ) : ?>
					<span class="text-muted text-decoration-line-through small me-1"><?php echo esc_html( $data['regular_price'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $data['price'] ) ) : ?>
					<span class="fw-bold"><?php echo esc_html( $data['price'] ); ?></span>
				<?php endif; ?>
			</div>
			<a class="btn btn-primary btn-sm" href="<?php echo esc_url( $data['url'] ); ?>"><?php esc_html_e( 'View Details', 'zihad-travel-cms' ); ?></a>
		</div>
	</div>
</div>
