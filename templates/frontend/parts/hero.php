<?php
/**
 * Hero part.
 *
 * $data: title, subtitle, image (URL), badges (string[]), meta (string).
 * Override: yourtheme/zihad-travel-cms/frontend/parts/hero.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_image = (string) ( $data['image'] ?? '' );
?>
<header class="ztc-hero<?php echo '' !== $ztc_image ? ' ztc-hero--image' : ''; ?>"
	<?php if ( '' !== $ztc_image ) : ?>
		style="background-image: url('<?php echo esc_url( $ztc_image ); ?>');"
	<?php endif; ?>
>
	<div class="ztc-hero__overlay">
		<div class="container">
			<?php if ( ! empty( $data['badges'] ) ) : ?>
				<div class="ztc-hero__badges mb-2">
					<?php foreach ( (array) $data['badges'] as $ztc_badge ) : ?>
						<span class="badge text-bg-primary me-1"><?php echo esc_html( (string) $ztc_badge ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h1 class="ztc-hero__title"><?php echo esc_html( (string) ( $data['title'] ?? '' ) ); ?></h1>

			<?php if ( ! empty( $data['subtitle'] ) ) : ?>
				<p class="ztc-hero__subtitle"><?php echo esc_html( (string) $data['subtitle'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $data['meta'] ) ) : ?>
				<p class="ztc-hero__meta"><?php echo esc_html( (string) $data['meta'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</header>
