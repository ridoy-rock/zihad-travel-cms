<?php
/**
 * Dashboard data provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin;

use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\DemoData\DemoDataStatus;
use ZihadTravelCMS\Modules\Importer\ImportJob;
use ZihadTravelCMS\Modules\Importer\JobRepository;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Visa\VisaRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the dashboard view-model from the existing repositories and
 * services — content counts, demo data status and recent import jobs.
 * Pure data; rendering lives in templates/admin/dashboard.php.
 */
final class DashboardData {

	/**
	 * Constructor.
	 *
	 * @param CountryRepository $countries Country repository.
	 * @param VisaRepository    $visas     Visa repository.
	 * @param TourRepository    $tours     Tour repository.
	 * @param JobRepository     $jobs      Import job repository.
	 * @param DemoDataStatus    $demo      Truthful demo data state.
	 */
	public function __construct(
		private CountryRepository $countries,
		private VisaRepository $visas,
		private TourRepository $tours,
		private JobRepository $jobs,
		private DemoDataStatus $demo,
	) {}

	/**
	 * The dashboard view-model.
	 *
	 * @return array<string, mixed>
	 */
	public function stats(): array {
		return array(
			'counts'  => array(
				'country' => array(
					'label' => __( 'Countries', 'zihad-travel-cms' ),
					'count' => $this->countries->count(),
					'url'   => admin_url( 'edit.php?post_type=' . $this->countries->post_type() ),
				),
				'visa'    => array(
					'label' => __( 'Visas', 'zihad-travel-cms' ),
					'count' => $this->visas->count(),
					'url'   => admin_url( 'edit.php?post_type=' . $this->visas->post_type() ),
				),
				'tour'    => array(
					'label' => __( 'Tours', 'zihad-travel-cms' ),
					'count' => $this->tours->count(),
					'url'   => admin_url( 'edit.php?post_type=' . $this->tours->post_type() ),
				),
			),
			// Computed from actual record counts (never a flag or the
			// latest job) — see DemoDataStatus.
			'demo'    => $this->demo->status(),
			'imports' => array_map(
				static fn( ImportJob $job ): array => $job->to_array(),
				array_slice( $this->jobs->all(), 0, 5 )
			),
		);
	}
}
