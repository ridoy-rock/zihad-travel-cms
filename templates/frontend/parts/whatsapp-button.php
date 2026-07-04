<?php
/**
 * Floating WhatsApp button part.
 *
 * $data: url (wa.me link).
 * Override: yourtheme/zihad-travel-cms/frontend/parts/whatsapp-button.php
 *
 * @package ZihadTravelCMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $data['url'] ) ) {
	return;
}
?>
<a class="ztc-whatsapp-fab" href="<?php echo esc_url( (string) $data['url'] ); ?>"
	target="_blank" rel="noopener noreferrer"
	aria-label="<?php esc_attr_e( 'Chat with us on WhatsApp', 'zihad-travel-cms' ); ?>">
	<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">
		<path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.6 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.6-6.1c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8 1-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4 0-.5.2-.7l.5-.6c.1-.2 0-.4 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s.9 2.5 1.1 2.7c.1.2 1.8 2.8 4.5 3.9 2.6 1 2.6.7 3.1.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2l-.6-.7z"/>
	</svg>
</a>
