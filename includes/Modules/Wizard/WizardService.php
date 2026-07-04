<?php
/**
 * Setup wizard service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Wizard;

use ZihadTravelCMS\Admin\DashboardData;
use ZihadTravelCMS\Admin\UI\Fields\MediaField;
use ZihadTravelCMS\Admin\UI\Fields\NumberField;
use ZihadTravelCMS\Admin\UI\Fields\SelectField;
use ZihadTravelCMS\Admin\UI\Fields\TextareaField;
use ZihadTravelCMS\Admin\UI\Fields\TextField;
use ZihadTravelCMS\Admin\UI\Fields\ToggleField;
use ZihadTravelCMS\Admin\UI\Fields\UrlField;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Helpers\Arr;
use ZihadTravelCMS\Modules\DemoData\DemoDataInstaller;
use ZihadTravelCMS\Modules\Importer\ImportService;
use ZihadTravelCMS\Services\HealthService;
use ZihadTravelCMS\Settings\SettingsSanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * The wizard's orchestration layer: a declarative, filterable step
 * registry, per-step saves through the exact settings pipeline the
 * Settings screen and REST API use (field sanitize → structural
 * SettingsSanitizer → one batched option write), progress state in a
 * single option, and demo installation delegated to the existing
 * installer/import engine.
 *
 * It owns no persistence of its own beyond the progress option and
 * registers no settings keys — every value it writes already exists in
 * the settings schema. Extend it through the `ztc_wizard_steps` filter
 * and the `ztc_wizard_*` actions.
 */
final class WizardService {

	/**
	 * Progress state option (never holds settings values).
	 */
	public const STATE_OPTION = 'ztc_wizard_state';

	/**
	 * One-shot activation redirect flag.
	 */
	public const REDIRECT_OPTION = 'ztc_wizard_redirect';

	public const STEP_WELCOME = 'welcome';
	public const STEP_DEMO    = 'demo';
	public const STEP_FINISH  = 'finish';

	/**
	 * Constructor.
	 *
	 * @param Config            $config    Plugin configuration.
	 * @param SettingsSanitizer $sanitizer Structural settings gate.
	 * @param DemoDataInstaller $installer Demo data installer.
	 * @param ImportService     $import    Import engine (no-JS demo batches).
	 * @param HealthService     $health    Environment checks (finish step).
	 * @param DashboardData     $dashboard Content counts (finish step).
	 */
	public function __construct(
		private Config $config,
		private SettingsSanitizer $sanitizer,
		private DemoDataInstaller $installer,
		private ImportService $import,
		private HealthService $health,
		private DashboardData $dashboard,
	) {}

	// -----------------------------------------------------------------
	// Step registry.
	// -----------------------------------------------------------------

	/**
	 * The ordered step registry.
	 *
	 * A step is: `id`, translated `title`, translated `intro`, and
	 * `fields` (existing field components named by settings dot keys —
	 * empty for the welcome/demo/finish steps).
	 *
	 * @return array<int, array{id: string, title: string, intro: string, fields: array<\ZihadTravelCMS\Admin\UI\Fields\BaseField>}>
	 */
	public function steps(): array {
		$steps = array(
			array(
				'id'     => self::STEP_WELCOME,
				'title'  => __( 'Welcome', 'zihad-travel-cms' ),
				'intro'  => __( 'This short setup connects Zihad Travel CMS to your agency: company details, branding, contact channels and optional demo content. Every step is optional, saves on its own, and can be re-run later from Travel CMS → Setup.', 'zihad-travel-cms' ),
				'fields' => array(),
			),
			array(
				'id'     => 'company',
				'title'  => __( 'Company Information', 'zihad-travel-cms' ),
				'intro'  => __( 'Who you are and how prices are shown.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'company.name', __( 'Company Name', 'zihad-travel-cms' ), array( 'description' => __( 'Leave empty to use the site title.', 'zihad-travel-cms' ) ) ),
					new TextField( 'general.currency', __( 'Default Currency', 'zihad-travel-cms' ), array( 'placeholder' => 'USD' ) ),
					new SelectField(
						'general.currency_position',
						__( 'Currency Position', 'zihad-travel-cms' ),
						array(
							'options' => array(
								'before' => __( 'Before amount (USD 100)', 'zihad-travel-cms' ),
								'after'  => __( 'After amount (100 USD)', 'zihad-travel-cms' ),
							),
							'default' => 'before',
						)
					),
					new TextField(
						'general.language',
						__( 'Default Language', 'zihad-travel-cms' ),
						array(
							'placeholder' => 'en_US',
							'description' => __( 'Leave empty to use the site locale.', 'zihad-travel-cms' ),
						)
					),
				),
			),
			array(
				'id'     => 'branding',
				'title'  => __( 'Branding', 'zihad-travel-cms' ),
				'intro'  => __( 'Logo and brand colors, exposed to the frontend as CSS variables.', 'zihad-travel-cms' ),
				'fields' => array(
					new MediaField( 'company.logo', __( 'Logo', 'zihad-travel-cms' ) ),
					new TextField( 'company.brand_color', __( 'Brand Color', 'zihad-travel-cms' ), array( 'placeholder' => '#0d6efd' ) ),
					new TextField( 'company.secondary_color', __( 'Secondary Color', 'zihad-travel-cms' ), array( 'placeholder' => '#198754' ) ),
				),
			),
			array(
				'id'     => 'contact',
				'title'  => __( 'Contact Details', 'zihad-travel-cms' ),
				'intro'  => __( 'Shown on the frontend and used as notification fallbacks.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'company.email', __( 'Contact Email', 'zihad-travel-cms' ) ),
					new TextField( 'company.phone', __( 'Phone', 'zihad-travel-cms' ) ),
					new TextField( 'company.hotline', __( 'Hotline', 'zihad-travel-cms' ) ),
					new TextareaField( 'company.address', __( 'Office Address', 'zihad-travel-cms' ), array( 'rows' => 3 ) ),
				),
			),
			array(
				'id'     => 'social',
				'title'  => __( 'Social Media', 'zihad-travel-cms' ),
				'intro'  => __( 'Profile URLs (leave empty to hide a network).', 'zihad-travel-cms' ),
				'fields' => array(
					new UrlField( 'social.facebook', __( 'Facebook', 'zihad-travel-cms' ) ),
					new UrlField( 'social.instagram', __( 'Instagram', 'zihad-travel-cms' ) ),
					new UrlField( 'social.twitter', __( 'X / Twitter', 'zihad-travel-cms' ) ),
					new UrlField( 'social.youtube', __( 'YouTube', 'zihad-travel-cms' ) ),
					new UrlField( 'social.linkedin', __( 'LinkedIn', 'zihad-travel-cms' ) ),
					new UrlField( 'social.tiktok', __( 'TikTok', 'zihad-travel-cms' ) ),
				),
			),
			array(
				'id'     => 'whatsapp',
				'title'  => __( 'WhatsApp', 'zihad-travel-cms' ),
				'intro'  => __( 'The primary inquiry channel for most agencies.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'company.whatsapp', __( 'WhatsApp Number', 'zihad-travel-cms' ), array( 'placeholder' => __( 'e.g. +8801XXXXXXXXX', 'zihad-travel-cms' ) ) ),
					new TextareaField( 'whatsapp.default_message', __( 'Default Message', 'zihad-travel-cms' ), array( 'rows' => 3 ) ),
					new ToggleField(
						'whatsapp.floating_button',
						__( 'Floating Button', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Show a floating WhatsApp button on the frontend', 'zihad-travel-cms' ) )
					),
				),
			),
			array(
				'id'     => 'maps',
				'title'  => __( 'Google Maps API', 'zihad-travel-cms' ),
				'intro'  => __( 'Optional — used by map embeds.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'integrations.maps_api_key', __( 'Google Maps API Key', 'zihad-travel-cms' ) ),
					new NumberField(
						'integrations.maps_default_zoom',
						__( 'Default Zoom', 'zihad-travel-cms' ),
						array(
							'min'  => 1,
							'max'  => 20,
							'step' => 1,
						)
					),
				),
			),
			array(
				'id'     => 'analytics',
				'title'  => __( 'Analytics / Facebook Pixel', 'zihad-travel-cms' ),
				'intro'  => __( 'Optional tracking snippets printed by the frontend integrations.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'integrations.ga_id', __( 'Google Analytics ID', 'zihad-travel-cms' ), array( 'placeholder' => 'G-XXXXXXXXXX' ) ),
					new TextField( 'integrations.fb_pixel_id', __( 'Facebook Pixel ID', 'zihad-travel-cms' ) ),
				),
			),
			array(
				'id'     => 'homepage',
				'title'  => __( 'Homepage Settings', 'zihad-travel-cms' ),
				'intro'  => __( 'Defaults for the homepage hero and content sections.', 'zihad-travel-cms' ),
				'fields' => array(
					new TextField( 'homepage.hero_title', __( 'Hero Title', 'zihad-travel-cms' ) ),
					new TextField( 'homepage.hero_subtitle', __( 'Hero Subtitle', 'zihad-travel-cms' ) ),
					new ToggleField(
						'homepage.show_search',
						__( 'Search Widget', 'zihad-travel-cms' ),
						array( 'checkbox_label' => __( 'Show the search widget on the homepage', 'zihad-travel-cms' ) )
					),
					new NumberField(
						'homepage.featured_countries_count',
						__( 'Featured Countries', 'zihad-travel-cms' ),
						array(
							'min'  => 0,
							'max'  => 24,
							'step' => 1,
						)
					),
					new NumberField(
						'homepage.popular_tours_count',
						__( 'Popular Tours', 'zihad-travel-cms' ),
						array(
							'min'  => 0,
							'max'  => 24,
							'step' => 1,
						)
					),
				),
			),
			array(
				'id'     => self::STEP_DEMO,
				'title'  => __( 'Demo Data', 'zihad-travel-cms' ),
				'intro'  => __( 'Optional: install a bilingual demo dataset (100+ countries, 400+ visas, 100+ tours). Installs run through the import engine in update mode, so re-installing never duplicates content.', 'zihad-travel-cms' ),
				'fields' => array(),
			),
			array(
				'id'     => self::STEP_FINISH,
				'title'  => __( 'Finish', 'zihad-travel-cms' ),
				'intro'  => __( 'All set. Review the environment checks below and start adding content.', 'zihad-travel-cms' ),
				'fields' => array(),
			),
		);

		/**
		 * Filter the setup wizard steps.
		 *
		 * Add, remove or reorder steps; each entry needs `id`, `title`,
		 * `intro` and `fields` (field components named by settings dot
		 * keys).
		 *
		 * @param array<int, array<string, mixed>> $steps Wizard steps.
		 */
		$steps = (array) apply_filters( 'ztc_wizard_steps', $steps );

		return array_values(
			array_filter(
				$steps,
				static fn( $step ): bool => is_array( $step ) && '' !== (string) ( $step['id'] ?? '' )
			)
		);
	}

	/**
	 * One step by id, or null when unknown.
	 *
	 * @param string $id Step id.
	 *
	 * @return array<string, mixed>|null
	 */
	public function step( string $id ): ?array {
		foreach ( $this->steps() as $step ) {
			if ( $step['id'] === $id ) {
				return $step;
			}
		}

		return null;
	}

	/**
	 * Ordered step ids.
	 *
	 * @return array<string>
	 */
	public function step_ids(): array {
		return array_column( $this->steps(), 'id' );
	}

	// -----------------------------------------------------------------
	// Progress state.
	// -----------------------------------------------------------------

	/**
	 * The wizard progress state (settings never live here).
	 *
	 * @return array{completed: array<string>, skipped: bool, finished: bool, started_at: int, finished_at: int, demo: array<string, mixed>}
	 */
	public function state(): array {
		$defaults = array(
			'completed'   => array(),
			'skipped'     => false,
			'finished'    => false,
			'started_at'  => 0,
			'finished_at' => 0,
			'demo'        => array(),
		);

		$saved = get_option( self::STATE_OPTION, array() );

		return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Whether the wizard has been finished or skipped.
	 */
	public function is_completed(): bool {
		$state = $this->state();

		return $state['finished'] || $state['skipped'];
	}

	/**
	 * Mark a step complete (idempotent).
	 *
	 * @param string $id Step id.
	 */
	public function mark_complete( string $id ): void {
		if ( ! in_array( $id, $this->step_ids(), true ) ) {
			return;
		}

		$state = $this->state();

		if ( 0 === $state['started_at'] ) {
			$state['started_at'] = time();
		}

		if ( ! in_array( $id, $state['completed'], true ) ) {
			$state['completed'][] = $id;
		}

		$this->save_state( $state );

		/**
		 * Fires when a wizard step is marked complete.
		 *
		 * @param string        $id      Step id.
		 * @param WizardService $service The wizard service.
		 */
		do_action( 'ztc_wizard_step_completed', $id, $this );
	}

	/**
	 * The first step that has not been completed yet (the finish step
	 * when everything else is done) — the resume point.
	 */
	public function next_step(): string {
		$state = $this->state();
		$ids   = $this->step_ids();

		foreach ( $ids as $id ) {
			if ( ! in_array( $id, $state['completed'], true ) ) {
				return $id;
			}
		}

		return (string) end( $ids );
	}

	/**
	 * Save one step's submitted values through the settings pipeline
	 * and mark the step complete.
	 *
	 * Only the step's own fields are written: each runs its component
	 * sanitize(), the merged array passes the structural
	 * SettingsSanitizer, and one batched option write lands — the same
	 * pipeline as the Settings screen and `POST /settings`. Every other
	 * saved setting is preserved untouched.
	 *
	 * @param string               $id  Step id.
	 * @param array<string, mixed> $raw Submitted values keyed by dot key.
	 *
	 * @return bool False when the step id is unknown.
	 */
	public function save_step( string $id, array $raw = array() ): bool {
		$step = $this->step( $id );

		if ( null === $step ) {
			return false;
		}

		if ( array() !== $step['fields'] ) {
			$settings = $this->config->all();

			foreach ( $step['fields'] as $field ) {
				Arr::set( $settings, $field->name(), $field->sanitize( $raw[ $field->name() ] ?? null ) );
			}

			update_option( Config::OPTION_NAME, $this->sanitizer->sanitize( $settings ) );
			$this->config->refresh();
		}

		$this->mark_complete( $id );

		/**
		 * Fires after a wizard step has been saved.
		 *
		 * @param string        $id      Step id.
		 * @param WizardService $service The wizard service.
		 */
		do_action( 'ztc_wizard_step_saved', $id, $this );

		return true;
	}

	/**
	 * Skip the whole wizard.
	 */
	public function skip(): void {
		$state                = $this->state();
		$state['skipped']     = true;
		$state['finished_at'] = time();
		$this->save_state( $state );

		/**
		 * Fires when the wizard ends.
		 *
		 * @param string        $how     `finished` or `skipped`.
		 * @param WizardService $service The wizard service.
		 */
		do_action( 'ztc_wizard_completed', 'skipped', $this );
	}

	/**
	 * Finish the wizard.
	 */
	public function finish(): void {
		$state                = $this->state();
		$state['finished']    = true;
		$state['finished_at'] = time();
		$this->save_state( $state );

		/** This action is documented in skip(). */
		do_action( 'ztc_wizard_completed', 'finished', $this );
	}

	/**
	 * Reset the wizard progress so it can be re-run. Settings are never
	 * touched — only the progress option is deleted.
	 */
	public function reset(): void {
		delete_option( self::STATE_OPTION );

		/**
		 * Fires when the wizard progress has been reset.
		 *
		 * @param WizardService $service The wizard service.
		 */
		do_action( 'ztc_wizard_reset', $this );
	}

	// -----------------------------------------------------------------
	// Demo step (delegates to the installer / import engine).
	// -----------------------------------------------------------------

	/**
	 * Demo step view-model: file readiness, installed flag and the
	 * no-JS install progress (if one is underway).
	 *
	 * @return array{files_ready: bool, installed: bool, progress: array<string, mixed>}
	 */
	public function demo(): array {
		return array(
			'files_ready' => $this->installer->files_ready(),
			'installed'   => (bool) get_option( 'ztc_demo_installed', false ),
			'progress'    => $this->state()['demo'],
		);
	}

	/**
	 * Advance the no-JS demo installation by a bounded amount of work
	 * (the JS path drives the REST loop instead and never calls this).
	 *
	 * Jobs come from DemoDataInstaller::start() and batches from
	 * ImportService::process() — the wizard only sequences them and
	 * remembers where it is between requests.
	 *
	 * @param int $batch_calls Max process() calls this request.
	 * @param int $batch_size  Records per process() call.
	 *
	 * @return array{finished: bool, type: string, processed: int, total: int}
	 */
	public function advance_demo( int $batch_calls = 4, int $batch_size = 25 ): array {
		$state    = $this->state();
		$progress = $state['demo'];
		$types    = DemoDataInstaller::TYPES;

		$index      = (int) ( $progress['index'] ?? 0 );
		$job_id     = (string) ( $progress['job'] ?? '' );
		$processed  = 0;
		$total      = 0;
		$type_count = count( $types );

		while ( $batch_calls > 0 && $index < $type_count ) {
			if ( '' === $job_id ) {
				$job_id = $this->installer->start( $types[ $index ] )->id;
			}

			$job       = $this->import->process( $job_id, $batch_size );
			$processed = $job->processed;
			$total     = $job->total;
			--$batch_calls;

			if ( $job->is_finished() ) {
				++$index;
				$job_id = '';
			}
		}

		$finished = $index >= count( $types );

		$state['demo'] = $finished
			? array()
			: array(
				'index'     => $index,
				'job'       => $job_id,
				'type'      => $types[ min( $index, count( $types ) - 1 ) ],
				'processed' => $processed,
				'total'     => $total,
			);
		$this->save_state( $state );

		if ( $finished ) {
			$this->mark_complete( self::STEP_DEMO );
		}

		return array(
			'finished'  => $finished,
			'type'      => $types[ min( $index, count( $types ) - 1 ) ],
			'processed' => $processed,
			'total'     => $total,
		);
	}

	// -----------------------------------------------------------------
	// Finish step.
	// -----------------------------------------------------------------

	/**
	 * Finish-step view-model: content counts, demo status and the
	 * environment checks (incl. the permalink check) from the existing
	 * services.
	 *
	 * @return array{stats: array<string, mixed>, checks: array<string, array<string, string>>}
	 */
	public function summary(): array {
		$checks = $this->health->checks();

		return array(
			'stats'  => $this->dashboard->stats(),
			'checks' => array_intersect_key( $checks, array_flip( array( 'rewrites', 'rest', 'elementor' ) ) ),
		);
	}

	/**
	 * Persist the progress state.
	 *
	 * @param array<string, mixed> $state New state.
	 */
	private function save_state( array $state ): void {
		update_option( self::STATE_OPTION, $state );
	}
}
