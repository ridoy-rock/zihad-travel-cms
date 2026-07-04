<?php
/**
 * Plugin Health page template.
 *
 * Available: $data['checks'] (from HealthService::checks()).
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_status_colors = array(
	'good'     => '#00a32a',
	'warning'  => '#dba617',
	'critical' => '#d63638',
);
?>
<div class="wrap ztc-health">
	<h1><?php esc_html_e( 'Plugin Health', 'zihad-travel-cms' ); ?></h1>
	<p><?php esc_html_e( 'Environment checks for Zihad Travel CMS. Anything marked Critical prevents parts of the plugin from working.', 'zihad-travel-cms' ); ?></p>

	<table class="widefat striped" style="max-width: 900px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Check', 'zihad-travel-cms' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Value', 'zihad-travel-cms' ); ?></th>
				<th scope="col" style="width: 120px;"><?php esc_html_e( 'Status', 'zihad-travel-cms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $data['checks'] as $ztc_check ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $ztc_check['label'] ); ?></strong></td>
					<td><?php echo esc_html( $ztc_check['value'] ); ?></td>
					<td>
						<span style="display:inline-block;padding:2px 10px;border-radius:12px;color:#fff;font-size:12px;background:<?php echo esc_attr( $ztc_status_colors[ $ztc_check['status'] ] ?? '#646970' ); ?>;">
							<?php echo esc_html( $ztc_check['status_label'] ); ?>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
