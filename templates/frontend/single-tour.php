<?php
/**
 * Single tour template.
 *
 * View-model from TourService::page_data() via ztc_view().
 * Override: yourtheme/zihad-travel-cms/frontend/single-tour.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$view = ztc_view();

get_header();
?>
<main id="ztc-main" class="ztc-single ztc-single--tour">
	<?php
	ztc_part(
		'hero',
		array(
			'title'    => $view['title'] ?? '',
			'subtitle' => $view['country'] ?? '',
			'image'    => $view['hero'] ?? '',
			'badges'   => $view['types'] ?? array(),
			'meta'     => $view['duration'] ?? '',
		)
	);
	?>

	<div class="container ztc-single__body">
		<div class="row g-4">
			<div class="col-12 col-lg-8">
				<?php if ( ! empty( $view['highlights'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Highlights', 'zihad-travel-cms' ); ?></h2>
						<ul class="ztc-checklist ztc-checklist--benefits">
							<?php foreach ( (array) $view['highlights'] as $ztc_highlight ) : ?>
								<li><?php echo esc_html( (string) $ztc_highlight ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['itinerary'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Itinerary', 'zihad-travel-cms' ); ?></h2>
						<ol class="ztc-timeline">
							<?php foreach ( (array) $view['itinerary'] as $ztc_day ) : ?>
								<li class="ztc-timeline__step">
									<h3 class="ztc-timeline__title"><?php echo esc_html( (string) ( $ztc_day['title'] ?? '' ) ); ?></h3>
									<div class="ztc-timeline__text"><?php echo wp_kses_post( (string) ( $ztc_day['description'] ?? '' ) ); ?></div>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['included'] ) || ! empty( $view['excluded'] ) ) : ?>
					<section class="ztc-section">
						<div class="row g-4">
							<?php if ( ! empty( $view['included'] ) ) : ?>
								<div class="col-12 col-md-6">
									<h2 class="ztc-section__heading h5"><?php esc_html_e( 'Included', 'zihad-travel-cms' ); ?></h2>
									<ul class="ztc-checklist ztc-checklist--included">
										<?php foreach ( (array) $view['included'] as $ztc_item ) : ?>
											<li><?php echo esc_html( (string) $ztc_item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $view['excluded'] ) ) : ?>
								<div class="col-12 col-md-6">
									<h2 class="ztc-section__heading h5"><?php esc_html_e( 'Not Included', 'zihad-travel-cms' ); ?></h2>
									<ul class="ztc-checklist ztc-checklist--excluded">
										<?php foreach ( (array) $view['excluded'] as $ztc_item ) : ?>
											<li><?php echo esc_html( (string) $ztc_item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['hotels'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Hotels', 'zihad-travel-cms' ); ?></h2>
						<?php foreach ( (array) $view['hotels'] as $ztc_hotel ) : ?>
							<div class="ztc-hotel">
								<h3 class="ztc-hotel__name h6">
									<?php echo esc_html( (string) ( $ztc_hotel['name'] ?? '' ) ); ?>
									<?php if ( ! empty( $ztc_hotel['rating'] ) ) : ?>
										<span class="badge text-bg-warning"><?php echo esc_html( (string) $ztc_hotel['rating'] ); ?></span>
									<?php endif; ?>
								</h3>
								<div class="ztc-hotel__text"><?php echo wp_kses_post( (string) ( $ztc_hotel['description'] ?? '' ) ); ?></div>
							</div>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['flights'] ) || ! empty( $view['meals'] ) ) : ?>
					<section class="ztc-section">
						<div class="row g-4">
							<?php if ( ! empty( $view['flights'] ) ) : ?>
								<div class="col-12 col-md-6">
									<h2 class="ztc-section__heading h5"><?php esc_html_e( 'Flights', 'zihad-travel-cms' ); ?></h2>
									<div class="ztc-section__content"><?php echo wp_kses_post( (string) $view['flights'] ); ?></div>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $view['meals'] ) ) : ?>
								<div class="col-12 col-md-6">
									<h2 class="ztc-section__heading h5"><?php esc_html_e( 'Meals', 'zihad-travel-cms' ); ?></h2>
									<div class="ztc-section__content"><?php echo wp_kses_post( (string) $view['meals'] ); ?></div>
								</div>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['gallery'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Gallery', 'zihad-travel-cms' ); ?></h2>
						<div class="ztc-gallery-grid">
							<?php foreach ( (array) $view['gallery'] as $ztc_image_url ) : ?>
								<img class="ztc-gallery-grid__image" src="<?php echo esc_url( (string) $ztc_image_url ); ?>" alt="" loading="lazy">
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php ztc_part( 'faq', array( 'items' => $view['faq'] ?? array() ) ); ?>
			</div>

			<aside class="col-12 col-lg-4">
				<div class="ztc-facts card shadow-sm">
					<div class="card-body">
						<h2 class="h5 card-title"><?php esc_html_e( 'Tour Summary', 'zihad-travel-cms' ); ?></h2>

						<?php if ( ! empty( $view['price'] ) ) : ?>
							<p class="ztc-price">
								<?php if ( ! empty( $view['on_sale'] ) && ! empty( $view['regular_price'] ) ) : ?>
									<span class="text-muted text-decoration-line-through me-1"><?php echo esc_html( (string) $view['regular_price'] ); ?></span>
								<?php endif; ?>
								<span class="ztc-price__amount"><?php echo esc_html( (string) $view['price'] ); ?></span>
								<span class="text-muted"><?php esc_html_e( 'per person', 'zihad-travel-cms' ); ?></span>
							</p>
						<?php endif; ?>

						<dl class="ztc-facts__list">
							<?php if ( ! empty( $view['duration'] ) ) : ?>
								<dt><?php esc_html_e( 'Duration', 'zihad-travel-cms' ); ?></dt>
								<dd><?php echo esc_html( (string) $view['duration'] ); ?></dd>
							<?php endif; ?>
							<?php if ( ! empty( $view['country'] ) ) : ?>
								<dt><?php esc_html_e( 'Destination', 'zihad-travel-cms' ); ?></dt>
								<dd>
									<?php if ( ! empty( $view['country_url'] ) ) : ?>
										<a href="<?php echo esc_url( (string) $view['country_url'] ); ?>"><?php echo esc_html( (string) $view['country'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( (string) $view['country'] ); ?>
									<?php endif; ?>
								</dd>
							<?php endif; ?>
						</dl>

						<?php if ( ! empty( $view['map'] ) ) : ?>
							<a class="btn btn-outline-secondary w-100" href="<?php echo esc_url( (string) $view['map'] ); ?>"
								target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'View on Map', 'zihad-travel-cms' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</aside>
		</div>

		<?php if ( ! empty( $view['inquiry'] ) ) : ?>
			<?php ztc_part( 'inquiry-form', (array) $view['inquiry'] ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
