<?php
/**
 * Visa card template.
 *
 * Available: $data (view-model from VisaService::card_data()).
 * Override by copying to yourtheme/zihad-travel-cms/cards/visa-card.php.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $data['id'] ) ) {
	return;
}
?>
<div class="card ztc-card ztc-card--visa h-100 shadow-sm">
	<?php if ( ! empty( $data['image'] ) ) : ?>
		<a href="<?php echo esc_url( $data['url'] ); ?>" class="ztc-card__media">
			<img class="card-img-top" src="<?php echo esc_url( $data['image'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" loading="lazy">
		</a>
	<?php endif; ?>

	<div class="card-body d-flex flex-column">
		<?php if ( ! empty( $data['types'] ) ) : ?>
			<div class="mb-2">
				<?php foreach ( $data['types'] as $ztc_type ) : ?>
					<span class="badge text-bg-info me-1"><?php echo esc_html( $ztc_type ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="card-title h5 mb-1">
			<a class="text-decoration-none" href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
		</h3>

		<?php if ( ! empty( $data['country'] ) ) : ?>
			<p class="text-muted small mb-2"><?php echo esc_html( $data['country'] ); ?></p>
		<?php endif; ?>

		<ul class="list-unstyled small mb-3">
			<?php if ( ! empty( $data['processing_time'] ) ) : ?>
				<li><strong><?php esc_html_e( 'Processing:', 'zihad-travel-cms' ); ?></strong> <?php echo esc_html( $data['processing_time'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $data['validity'] ) ) : ?>
				<li><strong><?php esc_html_e( 'Validity:', 'zihad-travel-cms' ); ?></strong> <?php echo esc_html( $data['validity'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! empty( $data['fee'] ) ) : ?>
				<li><strong><?php esc_html_e( 'Fee:', 'zihad-travel-cms' ); ?></strong> <?php echo esc_html( $data['fee'] ); ?></li>
			<?php endif; ?>
		</ul>

		<div class="mt-auto d-grid gap-2">
			<?php if ( ! empty( $data['apply_url'] ) ) : ?>
				<a class="btn btn-success" href="<?php echo esc_url( $data['apply_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $data['apply_text'] ); ?></a>
			<?php else : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( $data['apply_text'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
