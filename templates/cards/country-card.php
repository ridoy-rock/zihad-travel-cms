<?php
/**
 * Country card template.
 *
 * Available: $data (view-model from CountryService::card_data()).
 * Override by copying to yourtheme/zihad-travel-cms/cards/country-card.php.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $data['id'] ) ) {
	return;
}
?>
<div class="card ztc-card ztc-card--country h-100 shadow-sm">
	<?php if ( ! empty( $data['image'] ) ) : ?>
		<a href="<?php echo esc_url( $data['url'] ); ?>" class="ztc-card__media position-relative">
			<img class="card-img-top" src="<?php echo esc_url( $data['image'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" loading="lazy">
			<?php if ( ! empty( $data['flag'] ) ) : ?>
				<img class="ztc-card__flag position-absolute bottom-0 start-0 m-2 rounded border" src="<?php echo esc_url( $data['flag'] ); ?>" alt="" width="40" loading="lazy">
			<?php endif; ?>
		</a>
	<?php endif; ?>

	<div class="card-body d-flex flex-column">
		<?php if ( ! empty( $data['regions'] ) ) : ?>
			<div class="mb-2">
				<?php foreach ( $data['regions'] as $ztc_region ) : ?>
					<span class="badge text-bg-secondary me-1"><?php echo esc_html( $ztc_region ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="card-title h5 mb-2">
			<a class="text-decoration-none" href="<?php echo esc_url( $data['url'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
		</h3>

		<?php if ( ! empty( $data['facts'] ) ) : ?>
			<ul class="list-unstyled small mb-3">
				<?php foreach ( $data['facts'] as $ztc_fact ) : ?>
					<li><strong><?php echo esc_html( $ztc_fact['label'] ); ?>:</strong> <?php echo esc_html( $ztc_fact['value'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="mt-auto">
			<a class="btn btn-outline-primary btn-sm" href="<?php echo esc_url( $data['url'] ); ?>"><?php esc_html_e( 'Explore', 'zihad-travel-cms' ); ?></a>
		</div>
	</div>
</div>
