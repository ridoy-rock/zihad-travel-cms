<?php
/**
 * Tabbed Visa/Tour search widget part.
 *
 * $data: uid, heading, default, tabs (each: type, label, action,
 * placeholder, countries (id => title), type_param, type_label,
 * type_terms (slug => name), durations, budgets ("min-max" => label)).
 * Override: yourtheme/zihad-travel-cms/frontend/parts/search-widget.php
 *
 * Progressive enhancement, twice over: the tabs are CSS-only radio
 * tabs (no JavaScript required), and each form submits normally to its
 * type's archive where the same filters apply server-side; with
 * JavaScript, frontend.js updates the results grid below in place via
 * the public REST search.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_uid  = (string) ( $data['uid'] ?? 'ztc-search-widget' );
$ztc_tabs = (array) ( $data['tabs'] ?? array() );

if ( array() === $ztc_tabs ) {
	return;
}
?>
<section class="ztc-search-widget">
	<?php if ( '' !== (string) ( $data['heading'] ?? '' ) ) : ?>
		<h2 class="ztc-search-widget__heading"><?php echo esc_html( (string) $data['heading'] ); ?></h2>
	<?php endif; ?>

	<div class="ztc-search-widget__box">
		<?php foreach ( $ztc_tabs as $ztc_tab ) : ?>
			<input type="radio"
				class="ztc-search-widget__radio ztc-search-widget__radio--<?php echo esc_attr( (string) $ztc_tab['type'] ); ?>"
				name="<?php echo esc_attr( $ztc_uid ); ?>"
				id="<?php echo esc_attr( $ztc_uid . '-' . $ztc_tab['type'] ); ?>"
				<?php checked( (string) $ztc_tab['type'], (string) ( $data['default'] ?? '' ) ); ?>>
		<?php endforeach; ?>

		<div class="ztc-search-widget__labels">
			<?php foreach ( $ztc_tabs as $ztc_tab ) : ?>
				<label class="ztc-search-widget__label" for="<?php echo esc_attr( $ztc_uid . '-' . $ztc_tab['type'] ); ?>">
					<?php echo esc_html( (string) $ztc_tab['label'] ); ?>
				</label>
			<?php endforeach; ?>
		</div>

		<div class="ztc-search-widget__panels">
			<?php foreach ( $ztc_tabs as $ztc_tab ) : ?>
				<?php $ztc_id = $ztc_uid . '-' . $ztc_tab['type']; ?>
				<div class="ztc-search-widget__panel ztc-search-widget__panel--<?php echo esc_attr( (string) $ztc_tab['type'] ); ?>">
					<form class="ztc-search row g-2 align-items-end" role="search" method="get"
						action="<?php echo esc_url( (string) $ztc_tab['action'] ); ?>"
						data-ztc-search data-ztc-type="<?php echo esc_attr( (string) $ztc_tab['type'] ); ?>">

						<div class="col-12 col-md">
							<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_id . '-s' ); ?>">
								<?php esc_html_e( 'Search', 'zihad-travel-cms' ); ?>
							</label>
							<input type="search" class="form-control" id="<?php echo esc_attr( $ztc_id . '-s' ); ?>" name="s"
								placeholder="<?php echo esc_attr( (string) $ztc_tab['placeholder'] ); ?>">
						</div>

						<?php if ( array() !== (array) $ztc_tab['countries'] ) : ?>
							<div class="col-6 col-md-auto">
								<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_id . '-country' ); ?>">
									<?php esc_html_e( 'Country', 'zihad-travel-cms' ); ?>
								</label>
								<select class="form-select" id="<?php echo esc_attr( $ztc_id . '-country' ); ?>" name="country">
									<option value=""><?php esc_html_e( 'All countries', 'zihad-travel-cms' ); ?></option>
									<?php foreach ( (array) $ztc_tab['countries'] as $ztc_country_id => $ztc_country ) : ?>
										<option value="<?php echo esc_attr( (string) $ztc_country_id ); ?>"><?php echo esc_html( (string) $ztc_country ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( array() !== (array) $ztc_tab['type_terms'] ) : ?>
							<div class="col-6 col-md-auto">
								<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_id . '-type' ); ?>">
									<?php echo esc_html( (string) $ztc_tab['type_label'] ); ?>
								</label>
								<select class="form-select" id="<?php echo esc_attr( $ztc_id . '-type' ); ?>" name="<?php echo esc_attr( (string) $ztc_tab['type_param'] ); ?>">
									<option value=""><?php esc_html_e( 'All types', 'zihad-travel-cms' ); ?></option>
									<?php foreach ( (array) $ztc_tab['type_terms'] as $ztc_slug => $ztc_name ) : ?>
										<option value="<?php echo esc_attr( (string) $ztc_slug ); ?>"><?php echo esc_html( (string) $ztc_name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( array() !== (array) $ztc_tab['durations'] ) : ?>
							<div class="col-6 col-md-auto">
								<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_id . '-duration' ); ?>">
									<?php esc_html_e( 'Duration', 'zihad-travel-cms' ); ?>
								</label>
								<select class="form-select" id="<?php echo esc_attr( $ztc_id . '-duration' ); ?>" name="duration">
									<option value=""><?php esc_html_e( 'Any duration', 'zihad-travel-cms' ); ?></option>
									<?php foreach ( (array) $ztc_tab['durations'] as $ztc_value => $ztc_label ) : ?>
										<option value="<?php echo esc_attr( (string) $ztc_value ); ?>"><?php echo esc_html( (string) $ztc_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( array() !== (array) $ztc_tab['budgets'] ) : ?>
							<div class="col-6 col-md-auto">
								<label class="form-label visually-hidden" for="<?php echo esc_attr( $ztc_id . '-budget' ); ?>">
									<?php esc_html_e( 'Budget', 'zihad-travel-cms' ); ?>
								</label>
								<select class="form-select" id="<?php echo esc_attr( $ztc_id . '-budget' ); ?>" name="budget">
									<option value=""><?php esc_html_e( 'Any budget', 'zihad-travel-cms' ); ?></option>
									<?php foreach ( (array) $ztc_tab['budgets'] as $ztc_value => $ztc_label ) : ?>
										<option value="<?php echo esc_attr( (string) $ztc_value ); ?>"><?php echo esc_html( (string) $ztc_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<div class="col-12 col-md-auto">
							<button type="submit" class="btn btn-primary w-100"><?php esc_html_e( 'Search', 'zihad-travel-cms' ); ?></button>
						</div>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="row ztc-grid ztc-search-widget__results" data-ztc-results data-ztc-col-class="col-12 col-sm-6 col-lg-4"></div>
	<p class="ztc-search-widget__empty" data-ztc-empty hidden><?php esc_html_e( 'No results found. Try different filters.', 'zihad-travel-cms' ); ?></p>
</section>
