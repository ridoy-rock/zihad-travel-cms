<?php
/**
 * Visa service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Helpers\Str;
use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Services\MediaService;
use ZihadTravelCMS\Settings\GlobalSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Business logic for Visas: contact fallbacks to global settings,
 * apply-button behaviour and the card view-model. Data access goes
 * through the repositories.
 */
final class VisaService {

	/**
	 * Constructor.
	 *
	 * @param VisaRepository    $repository Visa repository.
	 * @param CountryRepository $countries  Country repository.
	 * @param MediaService      $media      Media service.
	 * @param GlobalSettings    $settings   Global settings.
	 */
	public function __construct(
		private VisaRepository $repository,
		private CountryRepository $countries,
		private MediaService $media,
		private GlobalSettings $settings,
	) {}

	/**
	 * The WhatsApp number for a visa, falling back to the agency-wide
	 * number.
	 *
	 * @param int $visa_id Visa post ID.
	 */
	public function whatsapp_number( int $visa_id ): string {
		$number = (string) $this->repository->meta( $visa_id, VisaMeta::WHATSAPP );

		return '' !== $number ? $number : $this->settings->whatsapp();
	}

	/**
	 * A wa.me link pre-filled with an enquiry about this visa, or ''
	 * when no number is configured anywhere.
	 *
	 * @param int $visa_id Visa post ID.
	 */
	public function whatsapp_link( int $visa_id ): string {
		$message = sprintf(
			/* translators: %s: visa title. */
			__( 'Hello! I would like to apply for: %s', 'zihad-travel-cms' ),
			get_the_title( $visa_id )
		);

		return Str::wa_me( $this->whatsapp_number( $visa_id ), $message );
	}

	/**
	 * The apply-button label, falling back to a translated default.
	 *
	 * @param int $visa_id Visa post ID.
	 */
	public function apply_button_text( int $visa_id ): string {
		$text = (string) $this->repository->meta( $visa_id, VisaMeta::APPLY_BUTTON_TEXT );

		return '' !== $text ? $text : __( 'Apply Now', 'zihad-travel-cms' );
	}

	/**
	 * The linked destination country's name, or ''.
	 *
	 * @param int $visa_id Visa post ID.
	 */
	public function country_name( int $visa_id ): string {
		$country = $this->countries->find( (int) $this->repository->meta( $visa_id, VisaMeta::COUNTRY ) );

		return null !== $country ? get_the_title( $country ) : '';
	}

	/**
	 * The visa's hero image URL, falling back to the featured image.
	 *
	 * @param int $visa_id Visa post ID.
	 */
	public function hero_url( int $visa_id ): string {
		$hero = $this->media->hero_url( (int) $this->repository->meta( $visa_id, VisaMeta::HERO_IMAGE ) );

		if ( '' !== $hero ) {
			return $hero;
		}

		return $this->media->image_url( (int) get_post_thumbnail_id( $visa_id ), MediaService::SIZE_HERO );
	}

	/**
	 * The full view-model for the single visa page.
	 *
	 * @param int $visa_id Visa post ID.
	 *
	 * @return array<string, mixed> Empty when the visa does not exist.
	 */
	public function page_data( int $visa_id ): array {
		$post = $this->repository->find( $visa_id );

		if ( null === $post ) {
			return array();
		}

		$country    = $this->countries->find( (int) $this->repository->meta( $post->ID, VisaMeta::COUNTRY ) );
		$repository = $this->repository;

		return array(
			'id'                  => $post->ID,
			'title'               => get_the_title( $post ),
			'hero'                => $this->hero_url( $post->ID ),
			'country'             => null !== $country ? get_the_title( $country ) : '',
			'country_url'         => null !== $country ? (string) get_permalink( $country ) : '',
			'types'               => wp_list_pluck( $repository->terms( $post->ID, VisaTypeTaxonomy::NAME ), 'name' ),
			'processing_time'     => (string) $repository->meta( $post->ID, VisaMeta::PROCESSING_TIME ),
			'validity'            => (string) $repository->meta( $post->ID, VisaMeta::VALIDITY ),
			'stay_duration'       => (string) $repository->meta( $post->ID, VisaMeta::STAY_DURATION ),
			'entry_type'          => (string) $repository->meta( $post->ID, VisaMeta::ENTRY_TYPE ),
			'fee'                 => (string) $repository->meta( $post->ID, VisaMeta::FEE ),
			'requirements'        => (string) $repository->meta( $post->ID, VisaMeta::REQUIREMENTS ),
			'required_documents'  => array_map( 'strval', (array) $repository->meta( $post->ID, VisaMeta::REQUIRED_DOCUMENTS ) ),
			'benefits'            => array_map( 'strval', (array) $repository->meta( $post->ID, VisaMeta::BENEFITS ) ),
			'application_process' => (array) $repository->meta( $post->ID, VisaMeta::APPLICATION_PROCESS ),
			'faq'                 => (array) $repository->meta( $post->ID, VisaMeta::FAQ ),
			'important_notes'     => (string) $repository->meta( $post->ID, VisaMeta::IMPORTANT_NOTES ),
			'apply_text'          => $this->apply_button_text( $post->ID ),
			'apply_url'           => $this->whatsapp_link( $post->ID ),
		);
	}

	/**
	 * The view-model consumed by the Visa card and, later, Elementor
	 * widgets.
	 *
	 * @param int $visa_id Visa post ID.
	 *
	 * @return array<string, mixed> Empty when the visa does not exist.
	 */
	public function card_data( int $visa_id ): array {
		$post = $this->repository->find( $visa_id );

		if ( null === $post ) {
			return array();
		}

		return array(
			'id'              => $post->ID,
			'title'           => get_the_title( $post ),
			'url'             => (string) get_permalink( $post ),
			'image'           => $this->media->image_url( (int) get_post_thumbnail_id( $post ), MediaService::SIZE_CARD ),
			'country'         => $this->country_name( $post->ID ),
			'types'           => wp_list_pluck( $this->repository->terms( $post->ID, VisaTypeTaxonomy::NAME ), 'name' ),
			'processing_time' => (string) $this->repository->meta( $post->ID, VisaMeta::PROCESSING_TIME ),
			'validity'        => (string) $this->repository->meta( $post->ID, VisaMeta::VALIDITY ),
			'fee'             => (string) $this->repository->meta( $post->ID, VisaMeta::FEE ),
			'apply_text'      => $this->apply_button_text( $post->ID ),
			'apply_url'       => $this->whatsapp_link( $post->ID ),
		);
	}
}
