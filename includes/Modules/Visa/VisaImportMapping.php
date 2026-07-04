<?php
/**
 * Visa import mapping.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Contracts\ImportMapping;

defined( 'ABSPATH' ) || exit;

/**
 * Maps flat visa records onto the Visa post type. The `country` column
 * holds the destination country's title or slug and resolves to the
 * related post ID.
 */
final class VisaImportMapping implements ImportMapping {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'visa';
	}

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return VisaPostType::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fields(): array {
		return array(
			'title'               => array(
				'target'   => 'post:title',
				'required' => true,
			),
			'slug'                => array( 'target' => 'post:slug' ),
			'status'              => array( 'target' => 'post:status' ),
			'content'             => array( 'target' => 'post:content' ),
			'excerpt'             => array( 'target' => 'post:excerpt' ),
			'country'             => array( 'target' => 'relation:' . VisaMeta::COUNTRY ),
			'visa_types'          => array( 'target' => 'terms:' . VisaTypeTaxonomy::NAME ),
			'processing_time'     => array( 'target' => 'meta:' . VisaMeta::PROCESSING_TIME ),
			'validity'            => array( 'target' => 'meta:' . VisaMeta::VALIDITY ),
			'stay_duration'       => array( 'target' => 'meta:' . VisaMeta::STAY_DURATION ),
			'entry_type'          => array( 'target' => 'meta:' . VisaMeta::ENTRY_TYPE ),
			'fee'                 => array( 'target' => 'meta:' . VisaMeta::FEE ),
			'requirements'        => array( 'target' => 'meta:' . VisaMeta::REQUIREMENTS ),
			'important_notes'     => array( 'target' => 'meta:' . VisaMeta::IMPORTANT_NOTES ),
			'whatsapp_number'     => array( 'target' => 'meta:' . VisaMeta::WHATSAPP ),
			'apply_button_text'   => array( 'target' => 'meta:' . VisaMeta::APPLY_BUTTON_TEXT ),
			'required_documents'  => array( 'target' => 'list:' . VisaMeta::REQUIRED_DOCUMENTS ),
			'benefits'            => array( 'target' => 'list:' . VisaMeta::BENEFITS ),
			'application_process' => array( 'target' => 'json:' . VisaMeta::APPLICATION_PROCESS ),
			'faq'                 => array( 'target' => 'json:' . VisaMeta::FAQ ),
			'seo'                 => array( 'target' => 'json:' . VisaMeta::SEO ),
			'hero_image'          => array( 'target' => 'image:' . VisaMeta::HERO_IMAGE ),
			'thumbnail'           => array( 'target' => 'thumbnail' ),
		);
	}
}
