<?php
/**
 * Admin dashboard template.
 *
 * Available: $data (from DashboardData::stats()): counts, demo, imports.
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_counts  = (array) ( $data['counts'] ?? array() );
$ztc_demo    = (array) ( $data['demo'] ?? array() );
$ztc_imports = (array) ( $data['imports'] ?? array() );
?>
<div class="wrap ztc-dashboard">
	<h1><?php esc_html_e( 'Zihad Travel CMS', 'zihad-travel-cms' ); ?></h1>
	<p><?php esc_html_e( 'Manage your tours, visa services and destination countries.', 'zihad-travel-cms' ); ?></p>

	<div class="ztc-stat-grid">
		<?php foreach ( $ztc_counts as $ztc_stat ) : ?>
			<a class="ztc-stat card" href="<?php echo esc_url( (string) $ztc_stat['url'] ); ?>">
				<span class="ztc-stat__count"><?php echo esc_html( number_format_i18n( (int) $ztc_stat['count'] ) ); ?></span>
				<span class="ztc-stat__label"><?php echo esc_html( (string) $ztc_stat['label'] ); ?></span>
			</a>
		<?php endforeach; ?>

		<div class="ztc-stat card ztc-stat--demo">
			<span class="ztc-stat__label"><?php esc_html_e( 'Demo Data', 'zihad-travel-cms' ); ?></span>
			<?php if ( ! empty( $ztc_demo['installed'] ) ) : ?>
				<span class="ztc-stat__status ztc-stat__status--good"><?php esc_html_e( 'Installed', 'zihad-travel-cms' ); ?></span>
			<?php elseif ( ! empty( $ztc_demo['job'] ) ) : ?>
				<span class="ztc-stat__status">
					<?php
					printf(
						/* translators: 1: processed count, 2: total count. */
						esc_html__( 'Import incomplete (%1$d/%2$d) — resume from Import / Export', 'zihad-travel-cms' ),
						(int) ( $ztc_demo['job']['processed'] ?? 0 ),
						(int) ( $ztc_demo['job']['total'] ?? 0 )
					);
					?>
				</span>
			<?php elseif ( ! empty( $ztc_demo['files_ready'] ) ) : ?>
				<span class="ztc-stat__status"><?php esc_html_e( 'Ready to install', 'zihad-travel-cms' ); ?></span>
			<?php else : ?>
				<span class="ztc-stat__status"><?php esc_html_e( 'Not generated', 'zihad-travel-cms' ); ?></span>
			<?php endif; ?>
			<a class="ztc-stat__action" href="<?php echo esc_url( admin_url( 'admin.php?page=zihad-travel-cms-import' ) ); ?>">
				<?php esc_html_e( 'Open Import / Export', 'zihad-travel-cms' ); ?>
			</a>
		</div>
	</div>

	<div class="card" style="max-width: 900px;">
		<h2><?php esc_html_e( 'Recent Imports', 'zihad-travel-cms' ); ?></h2>

		<?php if ( array() === $ztc_imports ) : ?>
			<p class="ztc-dashboard__empty"><?php esc_html_e( 'No imports yet.', 'zihad-travel-cms' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Type', 'zihad-travel-cms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'zihad-travel-cms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Processed', 'zihad-travel-cms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Created', 'zihad-travel-cms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Updated', 'zihad-travel-cms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Failed', 'zihad-travel-cms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ztc_imports as $ztc_job ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $ztc_job['type'] ); ?></td>
							<?php // display_status: abandoned runs read "interrupted", never a stuck "running". ?>
							<td><?php echo esc_html( (string) ( $ztc_job['display_status'] ?? $ztc_job['status'] ) ); ?></td>
							<td><?php echo esc_html( $ztc_job['processed'] . '/' . $ztc_job['total'] ); ?></td>
							<td><?php echo esc_html( (string) $ztc_job['created'] ); ?></td>
							<td><?php echo esc_html( (string) $ztc_job['updated'] ); ?></td>
							<td><?php echo esc_html( (string) $ztc_job['failed'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<p class="ztc-dashboard__actions">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ztc_tour' ) ); ?>"><?php esc_html_e( 'Add Tour', 'zihad-travel-cms' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ztc_visa' ) ); ?>"><?php esc_html_e( 'Add Visa', 'zihad-travel-cms' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ztc_country' ) ); ?>"><?php esc_html_e( 'Add Country', 'zihad-travel-cms' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=zihad-travel-cms-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'zihad-travel-cms' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=zihad-travel-cms-health' ) ); ?>"><?php esc_html_e( 'Plugin Health', 'zihad-travel-cms' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=zihad-travel-cms-setup' ) ); ?>"><?php esc_html_e( 'Setup Wizard', 'zihad-travel-cms' ); ?></a>
	</p>
</div>
