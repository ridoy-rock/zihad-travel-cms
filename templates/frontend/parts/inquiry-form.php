<?php
/**
 * Inquiry form part.
 *
 * $data: uid, type (visa|tour), post_id, heading, action
 * (admin-post URL), form_action, status (''|sent|invalid|limited),
 * success_message.
 * Override: yourtheme/zihad-travel-cms/frontend/parts/inquiry-form.php
 *
 * Progressive enhancement: without JavaScript the form POSTs to
 * admin-post.php and redirects back with a result flag (rendered
 * below); with JavaScript frontend.js submits inline through
 * POST ztc/v1/inquiry and shows the response in place.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_uid    = (string) ( $data['uid'] ?? 'ztc-inquiry' );
$ztc_status = (string) ( $data['status'] ?? '' );
?>
<section class="ztc-inquiry card" id="<?php echo esc_attr( $ztc_uid ); ?>">
	<div class="card-body">
		<?php if ( '' !== (string) ( $data['heading'] ?? '' ) ) : ?>
			<h2 class="ztc-inquiry__heading h4"><?php echo esc_html( (string) $data['heading'] ); ?></h2>
		<?php endif; ?>

		<div class="ztc-inquiry__message" data-ztc-inquiry-message role="status" aria-live="polite"
			<?php echo '' === $ztc_status ? ' hidden' : ''; ?>>
			<?php if ( 'sent' === $ztc_status ) : ?>
				<p class="ztc-inquiry__success"><?php echo esc_html( (string) ( $data['success_message'] ?? '' ) ); ?></p>
			<?php elseif ( 'limited' === $ztc_status ) : ?>
				<p class="ztc-inquiry__error"><?php esc_html_e( 'Too many inquiries — please try again in a few minutes.', 'zihad-travel-cms' ); ?></p>
			<?php elseif ( 'invalid' === $ztc_status ) : ?>
				<p class="ztc-inquiry__error"><?php esc_html_e( 'Please check your details and try again.', 'zihad-travel-cms' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( 'sent' !== $ztc_status ) : ?>
			<form class="ztc-inquiry__form row g-3" method="post"
				action="<?php echo esc_url( (string) $data['action'] ); ?>"
				data-ztc-inquiry data-ztc-success="<?php echo esc_attr( (string) ( $data['success_message'] ?? '' ) ); ?>">

				<input type="hidden" name="action" value="<?php echo esc_attr( (string) $data['form_action'] ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( (string) $data['type'] ); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) (int) $data['post_id'] ); ?>">
				<?php wp_nonce_field( (string) $data['form_action'] ); ?>

				<?php // Honeypot: hidden from humans, irresistible to bots. ?>
				<div class="ztc-inquiry__hp" aria-hidden="true">
					<label for="<?php echo esc_attr( $ztc_uid . '-website' ); ?>"><?php esc_html_e( 'Website', 'zihad-travel-cms' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $ztc_uid . '-website' ); ?>" name="website" value="" tabindex="-1" autocomplete="off">
				</div>

				<div class="col-12 col-md-6">
					<label class="form-label" for="<?php echo esc_attr( $ztc_uid . '-name' ); ?>">
						<?php esc_html_e( 'Your name', 'zihad-travel-cms' ); ?> <span aria-hidden="true">*</span>
					</label>
					<input type="text" class="form-control" id="<?php echo esc_attr( $ztc_uid . '-name' ); ?>" name="name" required>
				</div>

				<div class="col-12 col-md-6">
					<label class="form-label" for="<?php echo esc_attr( $ztc_uid . '-email' ); ?>">
						<?php esc_html_e( 'Email', 'zihad-travel-cms' ); ?> <span aria-hidden="true">*</span>
					</label>
					<input type="email" class="form-control" id="<?php echo esc_attr( $ztc_uid . '-email' ); ?>" name="email" required>
				</div>

				<div class="col-12 col-md-6">
					<label class="form-label" for="<?php echo esc_attr( $ztc_uid . '-phone' ); ?>">
						<?php esc_html_e( 'Phone / WhatsApp', 'zihad-travel-cms' ); ?>
					</label>
					<input type="tel" class="form-control" id="<?php echo esc_attr( $ztc_uid . '-phone' ); ?>" name="phone">
				</div>

				<div class="col-12">
					<label class="form-label" for="<?php echo esc_attr( $ztc_uid . '-message' ); ?>">
						<?php esc_html_e( 'Your message', 'zihad-travel-cms' ); ?> <span aria-hidden="true">*</span>
					</label>
					<textarea class="form-control" id="<?php echo esc_attr( $ztc_uid . '-message' ); ?>" name="message" rows="4" maxlength="5000" required></textarea>
				</div>

				<div class="col-12">
					<button type="submit" class="btn btn-primary">
						<?php esc_html_e( 'Send inquiry', 'zihad-travel-cms' ); ?>
					</button>
				</div>
			</form>
		<?php endif; ?>
	</div>
</section>
