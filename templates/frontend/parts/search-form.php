<?php
/**
 * AJAX search/filter form part.
 *
 * $data: type, type_param, type_label, type_terms (slug => name),
 * regions (slug => name), show_price (bool), placeholder.
 * Override: yourtheme/zihad-travel-cms/frontend/parts/search-form.php
 *
 * Progressive enhancement: without JavaScript the form submits as a
 * normal keyword search; with JavaScript results update in place via
 * the REST endpoint.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_uid = 'ztc-search-' . sanitize_html_class( (string) ( $data['type'] ?? 'tour' ) );
?>
<form class="ztc-search row g-2 align-items-end" role="search" method="get"
	action="<?php echo esc_url( home_url( '/' ) ); ?>"
	data-ztc-search data-ztc-type="<?php echo esc_attr( (string) ( $data['type'] ?? 'tour' ) ); ?>">

	<div class="col-12 col-md">
		<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_uid ); ?>-s">
			<?php esc_html_e( 'Search', 'zihad-travel-cms' ); ?>
		</label>
		<input type="search" class="form-control" id="<?php echo esc_attr( $ztc_uid ); ?>-s" name="s"
			placeholder="<?php echo esc_attr( (string) ( $data['placeholder'] ?? '' ) ); ?>">
	</div>

	<?php if ( ! empty( $data['regions'] ) ) : ?>
		<div class="col-6 col-md-auto">
			<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_uid ); ?>-region">
				<?php esc_html_e( 'Region', 'zihad-travel-cms' ); ?>
			</label>
			<select class="form-select" id="<?php echo esc_attr( $ztc_uid ); ?>-region" name="region">
				<option value=""><?php esc_html_e( 'All regions', 'zihad-travel-cms' ); ?></option>
				<?php foreach ( (array) $data['regions'] as $ztc_slug => $ztc_name ) : ?>
					<option value="<?php echo esc_attr( (string) $ztc_slug ); ?>"><?php echo esc_html( (string) $ztc_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $data['type_terms'] ) && ! empty( $data['type_param'] ) ) : ?>
		<div class="col-6 col-md-auto">
			<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_uid ); ?>-type">
				<?php echo esc_html( (string) ( $data['type_label'] ?? '' ) ); ?>
			</label>
			<select class="form-select" id="<?php echo esc_attr( $ztc_uid ); ?>-type" name="<?php echo esc_attr( (string) $data['type_param'] ); ?>">
				<option value=""><?php esc_html_e( 'All types', 'zihad-travel-cms' ); ?></option>
				<?php foreach ( (array) $data['type_terms'] as $ztc_slug => $ztc_name ) : ?>
					<option value="<?php echo esc_attr( (string) $ztc_slug ); ?>"><?php echo esc_html( (string) $ztc_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $data['show_price'] ) ) : ?>
		<div class="col-6 col-md-auto">
			<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_uid ); ?>-min">
				<?php esc_html_e( 'Minimum price', 'zihad-travel-cms' ); ?>
			</label>
			<input type="number" class="form-control" id="<?php echo esc_attr( $ztc_uid ); ?>-min" name="min_price" min="0"
				placeholder="<?php esc_attr_e( 'Min price', 'zihad-travel-cms' ); ?>">
		</div>
		<div class="col-6 col-md-auto">
			<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_uid ); ?>-max">
				<?php esc_html_e( 'Maximum price', 'zihad-travel-cms' ); ?>
			</label>
			<input type="number" class="form-control" id="<?php echo esc_attr( $ztc_uid ); ?>-max" name="max_price" min="0"
				placeholder="<?php esc_attr_e( 'Max price', 'zihad-travel-cms' ); ?>">
		</div>
	<?php endif; ?>

	<div class="col-12 col-md-auto">
		<button type="submit" class="btn btn-primary w-100"><?php esc_html_e( 'Search', 'zihad-travel-cms' ); ?></button>
	</div>
</form>
