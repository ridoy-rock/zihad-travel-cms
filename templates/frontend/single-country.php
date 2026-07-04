<?php
/**
 * Single country template.
 *
 * View-model from CountryService::page_data() via ztc_view().
 * Override: yourtheme/zihad-travel-cms/frontend/single-country.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$view = ztc_view();

get_header();
?>
<main id="ztc-main" class="ztc-single ztc-single--country">
	<?php
	ztc_part(
		'hero',
		array(
			'title'    => $view['title'] ?? '',
			'subtitle' => ( $view['hero_subtitle'] ?? '' ) !== '' ? $view['hero_subtitle'] : ( $view['bangla_name'] ?? '' ),
			'image'    => $view['hero'] ?? '',
			'badges'   => $view['regions'] ?? array(),
		)
	);
	?>

	<div class="container ztc-single__body">
		<div class="row g-4">
			<div class="col-12 col-lg-8">
				<?php if ( ! empty( $view['short_description'] ) ) : ?>
					<p class="ztc-single__lead lead"><?php echo esc_html( (string) $view['short_description'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $view['overview'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Overview', 'zihad-travel-cms' ); ?></h2>
						<div class="ztc-section__content"><?php echo wp_kses_post( (string) $view['overview'] ); ?></div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['travel_tips'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Travel Tips', 'zihad-travel-cms' ); ?></h2>
						<div class="ztc-section__content"><?php echo wp_kses_post( (string) $view['travel_tips'] ); ?></div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $view['popular_cities'] ) ) : ?>
					<section class="ztc-section">
						<h2 class="ztc-section__heading"><?php esc_html_e( 'Popular Cities', 'zihad-travel-cms' ); ?></h2>
						<ul class="ztc-chips">
							<?php foreach ( (array) $view['popular_cities'] as $ztc_city ) : ?>
								<li class="ztc-chips__item"><?php echo esc_html( (string) $ztc_city ); ?></li>
							<?php endforeach; ?>
						</ul>
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
				<?php if ( ! empty( $view['facts'] ) ) : ?>
					<div class="ztc-facts card shadow-sm mb-4">
						<div class="card-body">
							<h2 class="h5 card-title">
								<?php if ( ! empty( $view['flag'] ) ) : ?>
									<img class="ztc-facts__flag me-1" src="<?php echo esc_url( (string) $view['flag'] ); ?>" alt="" width="28">
								<?php endif; ?>
								<?php esc_html_e( 'Quick Facts', 'zihad-travel-cms' ); ?>
							</h2>
							<dl class="ztc-facts__list">
								<?php foreach ( (array) $view['facts'] as $ztc_fact ) : ?>
									<dt><?php echo esc_html( (string) $ztc_fact['label'] ); ?></dt>
									<dd><?php echo esc_html( (string) $ztc_fact['value'] ); ?></dd>
								<?php endforeach; ?>
								<?php if ( ! empty( $view['best_time'] ) ) : ?>
									<dt><?php esc_html_e( 'Best Time to Visit', 'zihad-travel-cms' ); ?></dt>
									<dd><?php echo esc_html( (string) $view['best_time'] ); ?></dd>
								<?php endif; ?>
							</dl>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $view['embassy'] ) ) : ?>
					<div class="ztc-embassy card shadow-sm">
						<div class="card-body">
							<h2 class="h5 card-title"><?php esc_html_e( 'Embassy Information', 'zihad-travel-cms' ); ?></h2>
							<?php if ( ! empty( $view['embassy']['name'] ) ) : ?>
								<p class="fw-bold mb-1"><?php echo esc_html( (string) $view['embassy']['name'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $view['embassy']['address'] ) ) : ?>
								<p class="mb-2"><?php echo nl2br( esc_html( (string) $view['embassy']['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br. ?></p>
							<?php endif; ?>
							<ul class="list-unstyled mb-0">
								<?php if ( ! empty( $view['embassy']['phone'] ) ) : ?>
									<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) $view['embassy']['phone'] ) ); ?>"><?php echo esc_html( (string) $view['embassy']['phone'] ); ?></a></li>
								<?php endif; ?>
								<?php if ( ! empty( $view['embassy']['email'] ) ) : ?>
									<li><a href="mailto:<?php echo esc_attr( (string) $view['embassy']['email'] ); ?>"><?php echo esc_html( (string) $view['embassy']['email'] ); ?></a></li>
								<?php endif; ?>
								<?php if ( ! empty( $view['embassy']['website'] ) ) : ?>
									<li><a href="<?php echo esc_url( (string) $view['embassy']['website'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Official website', 'zihad-travel-cms' ); ?></a></li>
								<?php endif; ?>
							</ul>
						</div>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();
