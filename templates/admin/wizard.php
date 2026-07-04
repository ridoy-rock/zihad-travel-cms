<?php
/**
 * Setup wizard screen: step indicator, the current step's fields (or
 * the demo/summary panels) and navigation. Pure view — all URLs,
 * nonce actions and view-models are prepared by WizardPage.
 *
 * $data: title, steps, step, values, position, demo, summary, actions.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_step    = (array) $data['step'];
$ztc_actions = (array) $data['actions'];
$ztc_fields  = (array) ( $ztc_step['fields'] ?? array() );
$ztc_id      = (string) $ztc_step['id'];
$ztc_demo    = (array) $data['demo'];
$ztc_summary = (array) $data['summary'];
?>
<div class="wrap ztc-wizard">
	<h1><?php echo esc_html( (string) $data['title'] ); ?></h1>

	<ol class="ztc-wizard__steps">
		<?php foreach ( (array) $data['steps'] as $ztc_item ) : ?>
			<li class="ztc-wizard__step<?php echo $ztc_item['current'] ? ' is-current' : ''; ?><?php echo $ztc_item['completed'] ? ' is-completed' : ''; ?>">
				<?php if ( $ztc_item['completed'] && ! $ztc_item['current'] ) : ?>
					<a href="<?php echo esc_url( (string) $ztc_item['url'] ); ?>"><?php echo esc_html( (string) $ztc_item['title'] ); ?></a>
				<?php else : ?>
					<span<?php echo $ztc_item['current'] ? ' aria-current="step"' : ''; ?>><?php echo esc_html( (string) $ztc_item['title'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="card ztc-wizard__card">
		<h2>
			<?php echo esc_html( (string) $ztc_step['title'] ); ?>
			<span class="ztc-wizard__count">
				<?php
				printf(
					/* translators: 1: current step number, 2: total steps. */
					esc_html__( 'Step %1$d of %2$d', 'zihad-travel-cms' ),
					(int) $data['position']['index'],
					(int) $data['position']['count']
				);
				?>
			</span>
		</h2>

		<p><?php echo esc_html( (string) $ztc_step['intro'] ); ?></p>

		<?php if ( 'demo' === $ztc_id ) : ?>

			<?php if ( $ztc_demo['installed'] ) : ?>
				<p><strong><?php esc_html_e( 'Demo data is already installed.', 'zihad-travel-cms' ); ?></strong> <?php esc_html_e( 'Installing again refreshes the existing content.', 'zihad-travel-cms' ); ?></p>
			<?php endif; ?>

			<?php if ( $ztc_demo['files_ready'] ) : ?>
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
							<?php esc_html_e( 'Install demo data', 'zihad-travel-cms' ); ?>
						</button>
					</p>

					<div class="ztc-progress" data-ztc-demo-progress hidden>
						<div class="ztc-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
							<div class="ztc-progress__bar" data-ztc-progress-bar></div>
						</div>
						<p class="ztc-progress__status" data-ztc-progress-status aria-live="polite"></p>
					</div>
				</div>

				<noscript>
					<form method="post" action="<?php echo esc_url( (string) $ztc_actions['form'] ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( (string) $ztc_actions['demo'] ); ?>">
						<?php wp_nonce_field( (string) $ztc_actions['demo'] ); ?>

						<?php if ( array() !== (array) $ztc_demo['progress'] ) : ?>
							<p>
								<?php
								printf(
									/* translators: 1: content type, 2: processed count, 3: total count. */
									esc_html__( 'Installing %1$s — %2$d of %3$d records done.', 'zihad-travel-cms' ),
									esc_html( (string) ( $ztc_demo['progress']['type'] ?? '' ) ),
									(int) ( $ztc_demo['progress']['processed'] ?? 0 ),
									(int) ( $ztc_demo['progress']['total'] ?? 0 )
								);
								?>
							</p>
							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Continue installing', 'zihad-travel-cms' ); ?></button></p>
						<?php else : ?>
							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Install demo data (without JavaScript)', 'zihad-travel-cms' ); ?></button></p>
						<?php endif; ?>
					</form>
				</noscript>
			<?php else : ?>
				<p><?php esc_html_e( 'The generated demo files are missing. Regenerate them from Travel CMS → Import / Export, or run `wp ztc demo generate`.', 'zihad-travel-cms' ); ?></p>
			<?php endif; ?>

		<?php elseif ( 'finish' === $ztc_id ) : ?>

			<ul class="ztc-wizard__stats">
				<?php foreach ( (array) ( $ztc_summary['stats']['counts'] ?? array() ) as $ztc_count ) : ?>
					<li>
						<strong><?php echo esc_html( number_format_i18n( (float) ( $ztc_count['count'] ?? 0 ) ) ); ?></strong>
						<?php echo esc_html( (string) ( $ztc_count['label'] ?? '' ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<table class="widefat striped ztc-wizard__checks">
				<tbody>
					<?php foreach ( (array) ( $ztc_summary['checks'] ?? array() ) as $ztc_check ) : ?>
						<tr>
							<td><strong><?php echo esc_html( (string) $ztc_check['label'] ); ?></strong></td>
							<td><?php echo esc_html( (string) $ztc_check['value'] ); ?></td>
							<td><span class="ztc-stat__status--<?php echo esc_attr( (string) $ztc_check['status'] ); ?>"><?php echo esc_html( (string) $ztc_check['status_label'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<a href="<?php echo esc_url( (string) $ztc_actions['permalinks'] ); ?>"><?php esc_html_e( 'Permalink settings', 'zihad-travel-cms' ); ?></a> ·
				<a href="<?php echo esc_url( (string) $ztc_actions['settings'] ); ?>"><?php esc_html_e( 'All settings', 'zihad-travel-cms' ); ?></a> ·
				<a href="<?php echo esc_url( (string) $ztc_actions['restart'] ); ?>"><?php esc_html_e( 'Restart wizard', 'zihad-travel-cms' ); ?></a>
			</p>

		<?php endif; ?>

		<?php if ( 'demo' !== $ztc_id ) : ?>
			<form method="post" action="<?php echo esc_url( (string) $ztc_actions['form'] ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( (string) $ztc_actions['save'] ); ?>">
				<input type="hidden" name="ztc_step" value="<?php echo esc_attr( $ztc_id ); ?>">
				<?php wp_nonce_field( (string) $ztc_actions['save'] ); ?>

				<?php foreach ( $ztc_fields as $ztc_field ) : ?>
					<?php $ztc_field->render_row( $data['values'][ $ztc_field->name() ] ?? null ); ?>
				<?php endforeach; ?>

				<p class="ztc-wizard__nav">
					<?php if ( '' !== (string) $ztc_actions['back'] ) : ?>
						<a class="button" href="<?php echo esc_url( (string) $ztc_actions['back'] ); ?>"><?php esc_html_e( 'Back', 'zihad-travel-cms' ); ?></a>
					<?php endif; ?>

					<button type="submit" class="button button-primary">
						<?php if ( 'welcome' === $ztc_id ) : ?>
							<?php esc_html_e( 'Start setup', 'zihad-travel-cms' ); ?>
						<?php elseif ( 'finish' === $ztc_id ) : ?>
							<?php esc_html_e( 'Finish', 'zihad-travel-cms' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Save & Continue', 'zihad-travel-cms' ); ?>
						<?php endif; ?>
					</button>

					<?php if ( 'welcome' === $ztc_id ) : ?>
						<a class="ztc-wizard__skip" href="<?php echo esc_url( (string) $ztc_actions['skip_all'] ); ?>"><?php esc_html_e( 'Skip setup', 'zihad-travel-cms' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( (string) $ztc_actions['form'] ); ?>" class="ztc-wizard__nav">
				<input type="hidden" name="action" value="<?php echo esc_attr( (string) $ztc_actions['skip_step'] ); ?>">
				<input type="hidden" name="ztc_step" value="<?php echo esc_attr( $ztc_id ); ?>">
				<?php wp_nonce_field( (string) $ztc_actions['skip_step'] ); ?>

				<?php if ( '' !== (string) $ztc_actions['back'] ) : ?>
					<a class="button" href="<?php echo esc_url( (string) $ztc_actions['back'] ); ?>"><?php esc_html_e( 'Back', 'zihad-travel-cms' ); ?></a>
				<?php endif; ?>

				<?php if ( $ztc_demo['installed'] ) : ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Continue', 'zihad-travel-cms' ); ?></button>
				<?php else : ?>
					<button type="submit" class="button"><?php esc_html_e( 'Skip this step', 'zihad-travel-cms' ); ?></button>
				<?php endif; ?>
			</form>
		<?php endif; ?>
	</div>
</div>
