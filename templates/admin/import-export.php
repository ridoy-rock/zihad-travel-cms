<?php
/**
 * Import/Export page template.
 *
 * Available: $data['types'], $data['modes'], $data['formats'].
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ztc-import-export">
	<h1><?php esc_html_e( 'Import / Export', 'zihad-travel-cms' ); ?></h1>

	<div class="card" style="max-width: 720px;">
		<h2><?php esc_html_e( 'Import', 'zihad-travel-cms' ); ?></h2>
		<p><?php esc_html_e( 'Upload a CSV or JSON file, choose the content type, and run the import. Large files are processed in batches with live progress; interrupted imports can be resumed from the Jobs list.', 'zihad-travel-cms' ); ?></p>

		<div class="ztc-import" data-ztc-import>
			<p>
				<button type="button" class="button" data-ztc-import-file>
					<?php esc_html_e( 'Choose file…', 'zihad-travel-cms' ); ?>
				</button>
				<span class="ztc-import__filename" data-ztc-import-filename aria-live="polite"></span>
				<input type="hidden" data-ztc-import-media-id value="0">
			</p>

			<p>
				<label for="ztc-import-type"><strong><?php esc_html_e( 'Content type', 'zihad-travel-cms' ); ?></strong></label>
				<select id="ztc-import-type" data-ztc-import-type>
					<?php foreach ( (array) $data['types'] as $ztc_type ) : ?>
						<option value="<?php echo esc_attr( (string) $ztc_type ); ?>"><?php echo esc_html( ucfirst( (string) $ztc_type ) ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="ztc-import-mode"><strong><?php esc_html_e( 'Mode', 'zihad-travel-cms' ); ?></strong></label>
				<select id="ztc-import-mode" data-ztc-import-mode>
					<?php foreach ( (array) $data['modes'] as $ztc_mode ) : ?>
						<option value="<?php echo esc_attr( (string) $ztc_mode ); ?>" <?php echo 'upsert' === $ztc_mode ? 'selected' : ''; ?>><?php echo esc_html( (string) $ztc_mode ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label>
					<input type="checkbox" data-ztc-import-rollback>
					<?php esc_html_e( 'Roll back everything created by this import if any record fails (all-or-nothing)', 'zihad-travel-cms' ); ?>
				</label>
			</p>

			<p>
				<button type="button" class="button button-primary" data-ztc-import-start disabled>
					<?php esc_html_e( 'Start import', 'zihad-travel-cms' ); ?>
				</button>
			</p>

			<div class="ztc-progress" data-ztc-import-progress hidden>
				<div class="ztc-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
					<div class="ztc-progress__bar" data-ztc-progress-bar></div>
				</div>
				<p class="ztc-progress__status" data-ztc-progress-status aria-live="polite"></p>
			</div>

			<div class="ztc-import__errors" data-ztc-import-errors hidden>
				<h3><?php esc_html_e( 'Error log', 'zihad-travel-cms' ); ?></h3>
				<ul data-ztc-import-error-list></ul>
			</div>
		</div>
	</div>

	<div class="card" style="max-width: 720px;">
		<h2><?php esc_html_e( 'Demo Data', 'zihad-travel-cms' ); ?></h2>
		<p><?php esc_html_e( 'Install a full demo dataset (100+ countries, 400+ visas, 100+ tours) with placeholder images. Content is generated from data files and installed through the importer, so re-installing updates instead of duplicating.', 'zihad-travel-cms' ); ?></p>

		<?php $ztc_demo = (array) ( $data['demo'] ?? array() ); ?>

		<?php if ( ! empty( $ztc_demo['installed'] ) ) : ?>
			<p class="ztc-demo__state">
				<strong><?php esc_html_e( 'Demo data is installed.', 'zihad-travel-cms' ); ?></strong>
				<?php
				printf(
					/* translators: 1-3: record counts. */
					esc_html__( '%1$d countries, %2$d visas and %3$d tours are present. Reinstalling updates the existing content in place — nothing is duplicated.', 'zihad-travel-cms' ),
					(int) ( $ztc_demo['counts']['country'] ?? 0 ),
					(int) ( $ztc_demo['counts']['visa'] ?? 0 ),
					(int) ( $ztc_demo['counts']['tour'] ?? 0 )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $ztc_demo['job'] ) ) : ?>
			<p class="ztc-demo__state">
				<strong>
					<?php
					printf(
						/* translators: 1: job status, 2: content type, 3: processed count, 4: total count. */
						esc_html__( 'An earlier demo import is %1$s: %2$s stopped at %3$d of %4$d records.', 'zihad-travel-cms' ),
						esc_html( (string) ( $ztc_demo['job']['display_status'] ?? '' ) ),
						esc_html( (string) ( $ztc_demo['job']['type'] ?? '' ) ),
						(int) ( $ztc_demo['job']['processed'] ?? 0 ),
						(int) ( $ztc_demo['job']['total'] ?? 0 )
					);
					?>
				</strong>
			</p>
			<div class="ztc-demo__recovery">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
					<input type="hidden" name="action" value="ztc_demo_resume">
					<?php wp_nonce_field( 'ztc_demo_resume' ); ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Resume demo import', 'zihad-travel-cms' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline"
					onsubmit="return window.confirm( '<?php echo esc_js( __( 'Clear the incomplete import record? Your content will not be touched.', 'zihad-travel-cms' ) ); ?>' );">
					<input type="hidden" name="action" value="ztc_demo_reset">
					<?php wp_nonce_field( 'ztc_demo_reset' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Reset demo import status', 'zihad-travel-cms' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<div class="ztc-demo" data-ztc-demo>
			<p>
				<label for="ztc-demo-locale"><strong><?php esc_html_e( 'Language', 'zihad-travel-cms' ); ?></strong></label>
				<select id="ztc-demo-locale" data-ztc-demo-locale>
					<option value="en"><?php esc_html_e( 'English (with Bangla names)', 'zihad-travel-cms' ); ?></option>
					<option value="bn"><?php esc_html_e( 'Bangla', 'zihad-travel-cms' ); ?></option>
				</select>

				<button type="button" class="button" data-ztc-demo-generate>
					<?php esc_html_e( 'Regenerate files', 'zihad-travel-cms' ); ?>
				</button>

				<button type="button" class="button button-primary" data-ztc-demo-install>
					<?php echo ! empty( $ztc_demo['installed'] ) ? esc_html__( 'Reinstall demo data', 'zihad-travel-cms' ) : esc_html__( 'Install demo data', 'zihad-travel-cms' ); ?>
				</button>
			</p>

			<div class="ztc-progress" data-ztc-demo-progress hidden>
				<div class="ztc-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
					<div class="ztc-progress__bar" data-ztc-progress-bar></div>
				</div>
				<p class="ztc-progress__status" data-ztc-progress-status aria-live="polite"></p>
			</div>
		</div>
	</div>

	<div class="card" style="max-width: 720px;">
		<h2><?php esc_html_e( 'Export', 'zihad-travel-cms' ); ?></h2>
		<p><?php esc_html_e( 'Exports round-trip: an exported file can be imported back on this or another site.', 'zihad-travel-cms' ); ?></p>

		<p class="ztc-export" data-ztc-export>
			<label for="ztc-export-type" class="screen-reader-text"><?php esc_html_e( 'Content type', 'zihad-travel-cms' ); ?></label>
			<select id="ztc-export-type" data-ztc-export-type>
				<?php foreach ( (array) $data['types'] as $ztc_type ) : ?>
					<option value="<?php echo esc_attr( (string) $ztc_type ); ?>"><?php echo esc_html( ucfirst( (string) $ztc_type ) ); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="ztc-export-format" class="screen-reader-text"><?php esc_html_e( 'Format', 'zihad-travel-cms' ); ?></label>
			<select id="ztc-export-format" data-ztc-export-format>
				<?php foreach ( (array) $data['formats'] as $ztc_format ) : ?>
					<option value="<?php echo esc_attr( (string) $ztc_format ); ?>"><?php echo esc_html( strtoupper( (string) $ztc_format ) ); ?></option>
				<?php endforeach; ?>
			</select>

			<button type="button" class="button" data-ztc-export-download>
				<?php esc_html_e( 'Download export', 'zihad-travel-cms' ); ?>
			</button>
		</p>
	</div>
</div>
