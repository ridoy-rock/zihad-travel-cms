<?php
/**
 * Single visa template.
 *
 * View-model from VisaService::page_data() via ztc_view().
 * Override: yourtheme/zihad-travel-cms/frontend/single-visa.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$view = ztc_view();

get_header();
?>
<main id="ztc-main" class="ztc-single ztc-single--visa">
	<?php
	ztc_part(
		'hero',
		array(
			'title'    => $view['title'] ?? '',
			'subtitle' => $view['country'] ?? '',
			'image'    => $view['hero'] ?? '',
			'badges'   => $view['types'] ?? array(),
		)
	);
	?>

	<div class="container ztc-single__body">
		<div class="row g-4">
			<div class="col-12 col-lg-8">
				<?php if ( ! empty( $view['requirements'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Requirements', 'zihad-travel-cms' ); ?></h2>
						<div class="ztc-section__content"><?php echo wp_kses_post( (string) $view['requirements'] ); ?></div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['required_documents'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Required Documents', 'zihad-travel-cms' ); ?></h2>
						<ul class="ztc-checklist">
							<?php foreach ( (array) $view['required_documents'] as $ztc_document ) : ?>
								<li><?php echo esc_html( (string) $ztc_document ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['application_process'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Application Process', 'zihad-travel-cms' ); ?></h2>
						<ol class="ztc-timeline">
							<?php foreach ( (array) $view['application_process'] as $ztc_step ) : ?>
								<li class="ztc-timeline__step">
									<h3 class="ztc-timeline__title"><?php echo esc_html( (string) ( $ztc_step['title'] ?? '' ) ); ?></h3>
									<div class="ztc-timeline__text"><?php echo wp_kses_post( (string) ( $ztc_step['description'] ?? '' ) ); ?></div>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['benefits'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Benefits', 'zihad-travel-cms' ); ?></h2>
						<ul class="ztc-checklist ztc-checklist--benefits">
							<?php foreach ( (array) $view['benefits'] as $ztc_benefit ) : ?>
								<li><?php echo esc_html( (string) $ztc_benefit ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['important_notes'] ) ) : ?>
					<aside class="ztc-section alert alert-warning ztc-notes" role="note">
						<h2 class="ztc-section__heading h5"><?php esc_html_e( 'Important Notes', 'zihad-travel-cms' ); ?></h2>
						<?php echo wp_kses_post( (string) $view['important_notes'] ); ?>
					</aside>
				<?php endif; ?>

				<?php ztc_part( 'faq', array( 'items' => $view['faq'] ?? array() ) ); ?>
			</div>

			<aside class="col-12 col-lg-4">
				<div class="ztc-facts card shadow-sm">
					<div class="card-body">
						<h2 class="h5 card-title"><?php esc_html_e( 'Visa Facts', 'zihad-travel-cms' ); ?></h2>
						<dl class="ztc-facts__list">
							<?php
							$ztc_facts = array(
								__( 'Processing Time', 'zihad-travel-cms' ) => $view['processing_time'] ?? '',
								__( 'Validity', 'zihad-travel-cms' )        => $view['validity'] ?? '',
								__( 'Stay Duration', 'zihad-travel-cms' )   => $view['stay_duration'] ?? '',
								__( 'Entry Type', 'zihad-travel-cms' )      => $view['entry_type'] ?? '',
								__( 'Fee', 'zihad-travel-cms' )             => $view['fee'] ?? '',
							);
							foreach ( $ztc_facts as $ztc_label => $ztc_value ) :
								if ( '' === (string) $ztc_value ) {
									continue;
								}
								?>
								<dt><?php echo esc_html( $ztc_label ); ?></dt>
								<dd><?php echo esc_html( (string) $ztc_value ); ?></dd>
							<?php endforeach; ?>
						</dl>

						<?php if ( ! empty( $view['apply_url'] ) ) : ?>
							<a class="btn btn-success w-100" href="<?php echo esc_url( (string) $view['apply_url'] ); ?>"
								target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( (string) ( $view['apply_text'] ?? '' ) ); ?>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $view['country_url'] ) ) : ?>
							<a class="btn btn-outline-secondary w-100 mt-2" href="<?php echo esc_url( (string) $view['country_url'] ); ?>">
								<?php
								/* translators: %s: country name. */
								printf( esc_html__( 'About %s', 'zihad-travel-cms' ), esc_html( (string) ( $view['country'] ?? '' ) ) );
								?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();
