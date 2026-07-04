<?php
/**
 * Inquiry admin list columns.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Makes the Inquiries list useful at a glance: contact details, type,
 * the related visa/tour and the status — everything escaped on output.
 */
final class InquiryColumns implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param InquiryRepository $inquiries Inquiry data access.
	 */
	public function __construct( private InquiryRepository $inquiries ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'manage_' . InquiryPostType::NAME . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . InquiryPostType::NAME . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * The list-table columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 *
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		return array(
			'cb'          => (string) ( $columns['cb'] ?? '' ),
			'title'       => __( 'Inquiry', 'zihad-travel-cms' ),
			'ztc_contact' => __( 'Contact', 'zihad-travel-cms' ),
			'ztc_type'    => __( 'Type', 'zihad-travel-cms' ),
			'ztc_subject' => __( 'Regarding', 'zihad-travel-cms' ),
			'ztc_status'  => __( 'Status', 'zihad-travel-cms' ),
			'date'        => (string) ( $columns['date'] ?? __( 'Date', 'zihad-travel-cms' ) ),
		);
	}

	/**
	 * Print one custom cell (escaped).
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Inquiry post ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'ztc_contact':
				$email = (string) $this->inquiries->meta( $post_id, InquiryMeta::EMAIL );
				$phone = (string) $this->inquiries->meta( $post_id, InquiryMeta::PHONE );

				if ( '' !== $email ) {
					printf( '<a href="%s">%s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}

				if ( '' !== $phone ) {
					printf( '<br>%s', esc_html( $phone ) );
				}
				break;

			case 'ztc_type':
				echo esc_html( ucfirst( (string) $this->inquiries->meta( $post_id, InquiryMeta::TYPE ) ) );
				break;

			case 'ztc_subject':
				$subject = (int) $this->inquiries->meta( $post_id, InquiryMeta::SUBJECT );

				if ( $subject > 0 ) {
					printf(
						'<a href="%s">%s</a>',
						esc_url( (string) get_edit_post_link( $subject ) ),
						esc_html( get_the_title( $subject ) )
					);
				} else {
					esc_html_e( 'General', 'zihad-travel-cms' );
				}
				break;

			case 'ztc_status':
				echo esc_html( ucfirst( (string) $this->inquiries->meta( $post_id, InquiryMeta::STATUS ) ) );
				break;
		}
	}
}
