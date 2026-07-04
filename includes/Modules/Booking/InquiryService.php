<?php
/**
 * Inquiry service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Contracts\Mailer;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Visa\VisaRepository;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The single inquiry pipeline: sanitize → spam checks (honeypot + IP
 * rate limit) → server-side validation → persist → notify. Both entry
 * points (the no-JS admin-post form handler and the REST endpoint)
 * funnel through submit(), so validation rules exist exactly once.
 */
final class InquiryService {

	/**
	 * Result codes surfaced to the form part.
	 */
	public const SENT    = 'sent';
	public const INVALID = 'invalid';
	public const LIMITED = 'limited';

	private const MESSAGE_MAX_LENGTH = 5000;
	private const RATE_PREFIX        = 'ztc_inquiry_rate_';

	/**
	 * Constructor.
	 *
	 * @param Config            $config    Plugin configuration.
	 * @param GlobalSettings    $settings  Recipient fallbacks.
	 * @param Mailer            $mailer    Outbound mail (contract).
	 * @param InquiryRepository $inquiries Inquiry persistence.
	 * @param VisaRepository    $visas     Subject validation.
	 * @param TourRepository    $tours     Subject validation.
	 */
	public function __construct(
		private Config $config,
		private GlobalSettings $settings,
		private Mailer $mailer,
		private InquiryRepository $inquiries,
		private VisaRepository $visas,
		private TourRepository $tours,
	) {}

	/**
	 * Whether inquiries are enabled for a content type (settings
	 * toggles from the Booking tab).
	 *
	 * @param string $type Content type: visa|tour.
	 */
	public function enabled( string $type ): bool {
		return match ( $type ) {
			'visa'  => (bool) $this->config->get( 'booking.enable_visa_inquiry', true ),
			'tour'  => (bool) $this->config->get( 'booking.enable_tour_inquiry', true ),
			default => false,
		};
	}

	/**
	 * Process one submission (already nonce-checked on the form path).
	 *
	 * @param array<string, mixed> $raw Raw request values: name, email,
	 *                                  phone, message, type, post_id,
	 *                                  website (honeypot).
	 *
	 * @return array{status: string, id: int, errors: array<string, string>, message: string}
	 */
	public function submit( array $raw ): array {
		$data = $this->sanitize( $raw );

		// Honeypot: bots fill the hidden "website" field. Report success
		// without storing or sending anything — never tip them off.
		if ( '' !== $data['honeypot'] ) {
			/**
			 * Fires when a submission is silently dropped as spam.
			 *
			 * @param array<string, mixed> $data Sanitized submission.
			 */
			do_action( 'ztc_inquiry_spam_blocked', $data );

			return $this->result( self::SENT, 0 );
		}

		if ( ! $this->within_rate_limit() ) {
			return $this->result( self::LIMITED, 0, array( 'rate' => __( 'Too many inquiries — please try again in a few minutes.', 'zihad-travel-cms' ) ) );
		}

		$errors = $this->validate( $data );

		if ( array() !== $errors ) {
			return $this->result( self::INVALID, 0, $errors );
		}

		$data['subject_title'] = $data['subject'] > 0 ? get_the_title( $data['subject'] ) : '';

		$inquiry_id = $this->inquiries->create( $data );

		if ( $inquiry_id <= 0 ) {
			return $this->result( self::INVALID, 0, array( 'save' => __( 'Something went wrong — please try again.', 'zihad-travel-cms' ) ) );
		}

		$this->bump_rate_counter();
		$this->notify( $inquiry_id, $data );

		/**
		 * Fires after an inquiry has been stored and the notification
		 * dispatched.
		 *
		 * @param int                  $inquiry_id Inquiry post ID.
		 * @param array<string, mixed> $data       Sanitized submission.
		 */
		do_action( 'ztc_inquiry_created', $inquiry_id, $data );

		return $this->result( self::SENT, $inquiry_id );
	}

	/**
	 * The visitor-facing confirmation message (Booking settings, with a
	 * translated default).
	 */
	public function success_message(): string {
		$message = (string) $this->config->get( 'booking.success_message', '' );

		return '' !== $message
			? $message
			: __( 'Thank you! Your inquiry has been received — we will get back to you shortly.', 'zihad-travel-cms' );
	}

	/**
	 * Sanitize every input (validation happens separately, on the
	 * sanitized values).
	 *
	 * @param array<string, mixed> $raw Raw request values.
	 *
	 * @return array<string, mixed>
	 */
	private function sanitize( array $raw ): array {
		$scalar = static fn( mixed $value ): string => is_scalar( $value ) ? (string) $value : '';

		return array(
			'name'     => sanitize_text_field( $scalar( $raw['name'] ?? '' ) ),
			'email'    => sanitize_email( $scalar( $raw['email'] ?? '' ) ),
			'phone'    => sanitize_text_field( $scalar( $raw['phone'] ?? '' ) ),
			'message'  => sanitize_textarea_field( $scalar( $raw['message'] ?? '' ) ),
			'type'     => sanitize_key( $scalar( $raw['type'] ?? '' ) ),
			'subject'  => absint( $scalar( $raw['post_id'] ?? '0' ) ),
			'honeypot' => sanitize_text_field( $scalar( $raw['website'] ?? '' ) ),
		);
	}

	/**
	 * Server-side validation on sanitized values.
	 *
	 * @param array<string, mixed> $data Sanitized submission.
	 *
	 * @return array<string, string> Field => translated error message.
	 */
	private function validate( array $data ): array {
		$errors = array();

		if ( '' === $data['name'] ) {
			$errors['name'] = __( 'Please enter your name.', 'zihad-travel-cms' );
		}

		if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
			$errors['email'] = __( 'Please enter a valid email address.', 'zihad-travel-cms' );
		}

		if ( '' === $data['message'] ) {
			$errors['message'] = __( 'Please tell us about your travel plans.', 'zihad-travel-cms' );
		} elseif ( mb_strlen( $data['message'] ) > self::MESSAGE_MAX_LENGTH ) {
			$errors['message'] = __( 'Your message is too long.', 'zihad-travel-cms' );
		}

		if ( ! in_array( $data['type'], array( 'visa', 'tour' ), true ) ) {
			$errors['type'] = __( 'Unknown inquiry type.', 'zihad-travel-cms' );
		} elseif ( ! $this->enabled( $data['type'] ) ) {
			$errors['type'] = __( 'Inquiries are currently disabled.', 'zihad-travel-cms' );
		} elseif ( $data['subject'] > 0 ) {
			$repository = 'visa' === $data['type'] ? $this->visas : $this->tours;

			if ( null === $repository->find( (int) $data['subject'] ) ) {
				$errors['post_id'] = __( 'The selected item no longer exists.', 'zihad-travel-cms' );
			}
		}

		/**
		 * Filter the inquiry validation errors (add custom rules here).
		 *
		 * @param array<string, string> $errors Field => message.
		 * @param array<string, mixed>  $data   Sanitized submission.
		 */
		return (array) apply_filters( 'ztc_inquiry_validate', $errors, $data );
	}

	/**
	 * Send the admin notification through the Mailer contract.
	 *
	 * @param int                  $inquiry_id Inquiry post ID.
	 * @param array<string, mixed> $data       Sanitized submission.
	 */
	private function notify( int $inquiry_id, array $data ): void {
		$recipient = (string) $this->config->get( 'booking.notification_email', '' );
		$recipient = '' !== $recipient ? $recipient : $this->settings->email();

		$subject = 'visa' === $data['type']
			/* translators: %s: visitor name or visa/tour title. */
			? sprintf( __( 'New visa inquiry: %s', 'zihad-travel-cms' ), '' !== (string) $data['subject_title'] ? (string) $data['subject_title'] : (string) $data['name'] )
			/* translators: %s: visitor name or visa/tour title. */
			: sprintf( __( 'New tour inquiry: %s', 'zihad-travel-cms' ), '' !== (string) $data['subject_title'] ? (string) $data['subject_title'] : (string) $data['name'] );

		$lines = array(
			__( 'Name:', 'zihad-travel-cms' ) . ' ' . $data['name'],
			__( 'Email:', 'zihad-travel-cms' ) . ' ' . $data['email'],
		);

		if ( '' !== (string) $data['phone'] ) {
			$lines[] = __( 'Phone:', 'zihad-travel-cms' ) . ' ' . $data['phone'];
		}

		if ( '' !== (string) $data['subject_title'] ) {
			$lines[] = __( 'Regarding:', 'zihad-travel-cms' ) . ' ' . $data['subject_title'];
		}

		$lines[] = '';
		$lines[] = (string) $data['message'];
		$lines[] = '';
		$lines[] = __( 'Manage this inquiry:', 'zihad-travel-cms' ) . ' ' . admin_url( 'post.php?post=' . $inquiry_id . '&action=edit' );

		$headers = array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' );

		/**
		 * Filter the notification recipient.
		 *
		 * @param string               $recipient Recipient address.
		 * @param array<string, mixed> $data      Sanitized submission.
		 */
		$recipient = (string) apply_filters( 'ztc_inquiry_email_recipient', $recipient, $data );

		/**
		 * Filter the notification subject.
		 *
		 * @param string               $subject Subject line.
		 * @param array<string, mixed> $data    Sanitized submission.
		 */
		$subject = (string) apply_filters( 'ztc_inquiry_email_subject', $subject, $data );

		/**
		 * Filter the plain-text notification body.
		 *
		 * @param string               $message Body.
		 * @param array<string, mixed> $data    Sanitized submission.
		 */
		$message = (string) apply_filters( 'ztc_inquiry_email_message', implode( "\n", $lines ), $data );

		/**
		 * Filter the notification headers.
		 *
		 * @param array<string>        $headers Mail headers.
		 * @param array<string, mixed> $data    Sanitized submission.
		 */
		$headers = (array) apply_filters( 'ztc_inquiry_email_headers', $headers, $data );

		$this->mailer->send( $recipient, $subject, $message, $headers );
	}

	/**
	 * Whether this client is still under the submission cap
	 * (default: 5 inquiries per 10 minutes per IP).
	 */
	private function within_rate_limit(): bool {
		[ $limit ] = $this->rate_settings();

		return (int) get_transient( $this->rate_key() ) < $limit;
	}

	/**
	 * Count this submission against the client's window.
	 */
	private function bump_rate_counter(): void {
		[ , $window ] = $this->rate_settings();
		$key          = $this->rate_key();

		set_transient( $key, (int) get_transient( $key ) + 1, $window );
	}

	/**
	 * The rate limit and window.
	 *
	 * @return array{0: int, 1: int} Max submissions, window in seconds.
	 */
	private function rate_settings(): array {
		/**
		 * Filter the inquiry rate limit.
		 *
		 * @param array{0: int, 1: int} $rate Max submissions, window seconds.
		 */
		$rate = (array) apply_filters( 'ztc_inquiry_rate_limit', array( 5, 10 * MINUTE_IN_SECONDS ) );

		return array( max( 1, (int) ( $rate[0] ?? 5 ) ), max( 60, (int) ( $rate[1] ?? 600 ) ) );
	}

	/**
	 * A per-client transient key (hashed — the raw address is never
	 * stored).
	 */
	private function rate_key(): string {
		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders -- REMOTE_ADDR is server-set; used only as an opaque hash.
		$address = sanitize_text_field( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		return self::RATE_PREFIX . md5( $address );
	}

	/**
	 * Shape one pipeline result.
	 *
	 * @param string                $status Result code (SENT/INVALID/LIMITED).
	 * @param int                   $id     Inquiry ID (0 when nothing stored).
	 * @param array<string, string> $errors Field errors.
	 *
	 * @return array{status: string, id: int, errors: array<string, string>, message: string}
	 */
	private function result( string $status, int $id, array $errors = array() ): array {
		return array(
			'status'  => $status,
			'id'      => $id,
			'errors'  => $errors,
			'message' => self::SENT === $status ? $this->success_message() : implode( ' ', $errors ),
		);
	}
}
