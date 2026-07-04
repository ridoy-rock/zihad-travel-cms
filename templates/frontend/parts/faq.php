<?php
/**
 * FAQ part: accessible native <details> accordion + FAQPage JSON-LD.
 *
 * $data: items (array of {question, answer}), heading.
 * Override: yourtheme/zihad-travel-cms/frontend/parts/faq.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

$ztc_items = array_values(
	array_filter(
		(array) ( $data['items'] ?? array() ),
		static fn( $ztc_row ): bool => is_array( $ztc_row ) && '' !== (string) ( $ztc_row['question'] ?? '' )
	)
);

if ( array() === $ztc_items ) {
	return;
}

$ztc_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map(
		static fn( array $ztc_row ): array => array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( (string) $ztc_row['question'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( (string) ( $ztc_row['answer'] ?? '' ) ),
			),
		),
		$ztc_items
	),
);
?>
<section class="ztc-faq">
	<h2 class="ztc-section__heading"><?php echo esc_html( (string) ( $data['heading'] ?? __( 'Frequently Asked Questions', 'zihad-travel-cms' ) ) ); ?></h2>

	<?php foreach ( $ztc_items as $ztc_row ) : ?>
		<details class="ztc-faq__item">
			<summary class="ztc-faq__question"><?php echo esc_html( (string) $ztc_row['question'] ); ?></summary>
			<div class="ztc-faq__answer"><?php echo wp_kses_post( (string) ( $ztc_row['answer'] ?? '' ) ); ?></div>
		</details>
	<?php endforeach; ?>

	<script type="application/ld+json"><?php echo wp_json_encode( $ztc_schema ); ?></script>
</section>
