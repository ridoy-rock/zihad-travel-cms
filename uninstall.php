<?php
/**
 * Uninstall routine for Zihad Travel CMS.
 *
 * Runs when the plugin is deleted from the Plugins screen. All content
 * and settings are preserved unless the user explicitly enabled
 * "Delete data on uninstall" in the plugin settings.
 *
 * @package ZihadTravelCMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove all plugin data for the current site.
 */
function ztc_uninstall_site() {
	$settings = get_option( 'ztc_settings', array() );

	if ( empty( $settings['advanced']['delete_data_on_uninstall'] ) ) {
		return;
	}

	// Delete every post belonging to the plugin's post types.
	$post_types = array( 'ztc_tour', 'ztc_visa', 'ztc_country' );

	foreach ( $post_types as $post_type ) {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	// Delete the plugin's taxonomy terms.
	$taxonomies = array( 'ztc_visa_type', 'ztc_tour_type', 'ztc_region' );

	foreach ( $taxonomies as $taxonomy ) {
		$term_ids = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}
	}

	// Delete options.
	delete_option( 'ztc_settings' );
	delete_option( 'ztc_version' );
	delete_option( 'ztc_installed_at' );
	delete_option( 'ztc_flush_rewrite_rules' );

	// Delete transients created by the plugin.
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\_transient\_ztc\_%'
		    OR option_name LIKE '\_transient\_timeout\_ztc\_%'"
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
	$ztc_site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $ztc_site_ids as $ztc_site_id ) {
		switch_to_blog( (int) $ztc_site_id );
		ztc_uninstall_site();
		restore_current_blog();
	}
} else {
	ztc_uninstall_site();
}
