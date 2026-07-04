<?php
/**
 * Call-to-action card template.
 *
 * Available: $data (view-model from CtaCard, options merged over
 * global-settings defaults).
 * Override by copying to yourtheme/zihad-travel-cms/cards/cta-card.php.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="card ztc-card ztc-card--cta text-center shadow-sm">
	<div class="card-body p-4">
		<h3 class="card-title h4 mb-2"><?php echo esc_html( $data['title'] ); ?></h3>

		<?php if ( ! empty( $data['text'] ) ) : ?>
			<p class="card-text text-muted mb-3"><?php echo esc_html( $data['text'] ); ?></p>
		<?php endif; ?>

		<div class="d-flex flex-wrap justify-content-center gap-2">
			<?php if ( ! empty( $data['button_url'] ) ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $data['button_url'] ); ?>"><?php echo esc_html( $data['button_text'] ); ?></a>
			<?php endif; ?>

			<?php if ( ! empty( $data['whatsapp_url'] ) ) : ?>
				<a class="btn btn-success" href="<?php echo esc_url( $data['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Chat on WhatsApp', 'zihad-travel-cms' ); ?></a>
			<?php endif; ?>

			<?php if ( ! empty( $data['phone'] ) ) : ?>
				<a class="btn btn-outline-secondary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $data['phone'] ) ); ?>"><?php echo esc_html( $data['phone'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
