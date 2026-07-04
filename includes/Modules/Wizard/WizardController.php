<?php
/**
 * Setup wizard REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Wizard;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only endpoints under `ztc/v1` so headless/scripted setups can
 * drive the same wizard:
 *
 *  - GET  /wizard          — state, resume point and step catalogue.
 *  - POST /wizard/step     — save one step's values (settings pipeline).
 *  - POST /wizard/skip     — skip the wizard.
 *  - POST /wizard/complete — finish the wizard.
 *  - POST /wizard/reset    — reset progress (settings untouched).
 *
 * Demo installation reuses the existing `/demo/start` +
 * `/import/process` endpoints — nothing is duplicated here.
 */
final class WizardController {

	/**
	 * Constructor.
	 *
	 * @param WizardService $wizard Wizard orchestration.
	 * @param Config        $config Plugin configuration (current values).
	 */
	public function __construct(
		private WizardService $wizard,
		private Config $config,
	) {}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$permission = static fn(): bool => current_user_can( 'manage_options' );

		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/wizard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_state' ),
				'permission_callback' => $permission,
			)
		);

		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/wizard/step',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_step' ),
				'permission_callback' => $permission,
				'args'                => array(
					'id'     => array(
						'type'     => 'string',
						'required' => true,
					),
					'values' => array(
						'type'    => 'object',
						'default' => array(),
					),
				),
			)
		);

		foreach ( array(
			'/wizard/skip'     => 'skip',
			'/wizard/complete' => 'complete',
			'/wizard/reset'    => 'reset',
		) as $route => $handler ) {
			register_rest_route(
				RestApiServiceProvider::REST_NAMESPACE,
				$route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, $handler ),
					'permission_callback' => $permission,
				)
			);
		}
	}

	/**
	 * Current state, resume point and step catalogue (with the live
	 * values each field would pre-fill with).
	 */
	public function get_state(): WP_REST_Response {
		$state = $this->wizard->state();
		$steps = array();

		foreach ( $this->wizard->steps() as $step ) {
			$fields = array();

			foreach ( $step['fields'] as $field ) {
				$fields[] = array(
					'name'  => $field->name(),
					'label' => $field->label(),
					'value' => $this->config->get( $field->name() ),
				);
			}

			$steps[] = array(
				'id'        => $step['id'],
				'title'     => $step['title'],
				'completed' => in_array( $step['id'], $state['completed'], true ),
				'fields'    => $fields,
			);
		}

		return new WP_REST_Response(
			array(
				'completed' => $this->wizard->is_completed(),
				'next'      => $this->wizard->next_step(),
				'state'     => $state,
				'steps'     => $steps,
			)
		);
	}

	/**
	 * Save one step.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function save_step( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id     = (string) $request->get_param( 'id' );
		$values = $request->get_param( 'values' );

		if ( ! $this->wizard->save_step( $id, is_array( $values ) ? $values : array() ) ) {
			return new WP_Error(
				'ztc_wizard_unknown_step',
				__( 'Unknown wizard step.', 'zihad-travel-cms' ),
				array( 'status' => 404 )
			);
		}

		return $this->get_state();
	}

	/**
	 * Skip the wizard.
	 */
	public function skip(): WP_REST_Response {
		$this->wizard->skip();

		return $this->get_state();
	}

	/**
	 * Finish the wizard.
	 */
	public function complete(): WP_REST_Response {
		$this->wizard->finish();

		return $this->get_state();
	}

	/**
	 * Reset progress (settings untouched).
	 */
	public function reset(): WP_REST_Response {
		$this->wizard->reset();

		return $this->get_state();
	}
}
