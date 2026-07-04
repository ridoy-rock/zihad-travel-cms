<?php
/**
 * Abstract tabbed editor.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI;

use WP_Post;
use ZihadTravelCMS\Admin\UI\Fields\BaseField;
use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * The reusable tabbed editor that replaces long metabox pages.
 *
 * A concrete editor (VisaEditor, TourEditor…) declares its post type
 * and tabs; this class renders one metabox containing an accessible
 * tab interface (WAI-ARIA tabs pattern, keyboard navigable, mobile
 * friendly) and handles the save pipeline: nonce → autosave/revision
 * guard → capability check → per-field sanitization → post meta.
 *
 * Fields write to meta keys registered through BasePostMeta, so saved
 * values flow straight to the REST API, Gutenberg and Elementor.
 */
abstract class Editor implements Registrable {

	/**
	 * The post type this editor edits, e.g. `ztc_visa`.
	 */
	abstract public function post_type(): string;

	/**
	 * The editor's tabs, in display order.
	 *
	 * @return array<Tab>
	 */
	abstract protected function tabs(): array;

	/**
	 * Metabox title.
	 */
	protected function meta_box_title(): string {
		return __( 'Details', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . $this->post_type(), array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . $this->post_type(), array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the metabox and the media modal scripts it needs.
	 */
	public function add_meta_box(): void {
		wp_enqueue_media();

		add_meta_box(
			'ztc-editor-' . $this->post_type(),
			$this->meta_box_title(),
			array( $this, 'render' ),
			$this->post_type(),
			'normal',
			'high'
		);
	}

	/**
	 * Render the tabbed interface.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	public function render( WP_Post $post ): void {
		$tabs = $this->resolved_tabs();

		wp_nonce_field( $this->nonce_action(), self::nonce_name() );

		/**
		 * Fires before the tabbed editor renders.
		 *
		 * Extension point for toolbars above the editor — e.g. the
		 * future AI module's "Auto-fill" button, which writes values
		 * through the REST-registered meta and refreshes the fields.
		 *
		 * @param WP_Post $post   The post being edited.
		 * @param Editor  $editor The editor instance.
		 */
		do_action( 'ztc_editor_render_before', $post, $this );

		printf( '<div class="ztc-editor" data-ztc-editor="%s">', esc_attr( $this->post_type() ) );

		printf(
			'<div class="ztc-editor__tabs" role="tablist" aria-label="%s" aria-orientation="horizontal">',
			esc_attr( $this->meta_box_title() )
		);

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<button type="button" class="ztc-editor__tab" role="tab" id="%1$s" aria-controls="%2$s" aria-selected="%3$s" tabindex="%4$s">%5$s<span class="ztc-editor__tab-text">%6$s</span></button>',
				esc_attr( $this->tab_id( $tab ) ),
				esc_attr( $this->panel_id( $tab ) ),
				0 === $index ? 'true' : 'false',
				0 === $index ? '0' : '-1',
				'' !== $tab->icon() ? '<span class="dashicons ' . esc_attr( $tab->icon() ) . '" aria-hidden="true"></span>' : '',
				esc_html( $tab->label() )
			);
		}

		echo '</div><div class="ztc-editor__panels">';

		foreach ( $tabs as $index => $tab ) {
			printf(
				'<div class="ztc-editor__panel" role="tabpanel" id="%1$s" aria-labelledby="%2$s" tabindex="0"%3$s>',
				esc_attr( $this->panel_id( $tab ) ),
				esc_attr( $this->tab_id( $tab ) ),
				0 === $index ? '' : ' hidden'
			);

			foreach ( $tab->fields() as $field ) {
				$field->render_row( $field->value( $post ) );
			}

			echo '</div>';
		}

		echo '</div></div>';

		/**
		 * Fires after the tabbed editor has rendered.
		 *
		 * @param WP_Post $post   The post being edited.
		 * @param Editor  $editor The editor instance.
		 */
		do_action( 'ztc_editor_render_after', $post, $this );
	}

	/**
	 * Save every field, each sanitized by its own component.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ self::nonce_name() ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::nonce_name() ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, $this->nonce_action() ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field sanitizes its own value below.
		$raw = isset( $_POST['ztc_fields'] ) && is_array( $_POST['ztc_fields'] ) ? wp_unslash( $_POST['ztc_fields'] ) : array();

		foreach ( $this->all_fields() as $field ) {
			$field->save( $post_id, $field->sanitize( $raw[ $field->name() ] ?? null ) );
		}

		$this->after_save( $post_id );

		/**
		 * Fires after a tabbed editor has saved its fields.
		 *
		 * @param int    $post_id The saved post ID.
		 * @param Editor $editor  The editor instance.
		 */
		do_action( 'ztc_editor_saved', $post_id, $this );
	}

	/**
	 * Cross-field validation hook, called after every field has saved.
	 *
	 * Concrete editors override this to enforce business rules that
	 * span fields (e.g. sale price below regular price) and surface
	 * problems through the NotificationService.
	 *
	 * @param int $post_id The saved post ID.
	 */
	protected function after_save( int $post_id ): void {}

	/**
	 * Tabs after the extension filter has run.
	 *
	 * @return array<Tab>
	 */
	protected function resolved_tabs(): array {
		/**
		 * Filter an editor's tabs.
		 *
		 * The dynamic portion of the hook name is the post type,
		 * e.g. `ztc_visa_editor_tabs`. Extensions add tabs or append
		 * fields to existing ones.
		 *
		 * @param array<Tab> $tabs The editor tabs.
		 */
		$tabs = apply_filters( $this->post_type() . '_editor_tabs', $this->tabs() );

		return array_values( array_filter( (array) $tabs, static fn( $tab ): bool => $tab instanceof Tab ) );
	}

	/**
	 * Every field across all tabs (used by the save pipeline).
	 *
	 * @return array<BaseField>
	 */
	protected function all_fields(): array {
		$fields = array();

		foreach ( $this->resolved_tabs() as $tab ) {
			foreach ( $tab->fields() as $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Nonce action for this editor.
	 */
	protected function nonce_action(): string {
		return 'ztc_editor_' . $this->post_type();
	}

	/**
	 * Shared nonce field name.
	 */
	public static function nonce_name(): string {
		return 'ztc_editor_nonce';
	}

	/**
	 * Element id of a tab button.
	 *
	 * @param Tab $tab The tab.
	 */
	private function tab_id( Tab $tab ): string {
		return 'ztc-tab-' . $this->post_type() . '-' . $tab->id();
	}

	/**
	 * Element id of a tab panel.
	 *
	 * @param Tab $tab The tab.
	 */
	private function panel_id( Tab $tab ): string {
		return 'ztc-panel-' . $this->post_type() . '-' . $tab->id();
	}
}
