=== Zihad Travel CMS ===
Contributors: zihad
Tags: travel, tours, visa, booking, itinerary
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zihad Travel CMS helps travel agencies manage tours, visa services, and destination guides, with built-in search and inquiry forms.

== Description ==

Zihad Travel CMS is a professional travel content management system for WordPress. It provides everything a travel agency needs:

* Tour packages with itineraries, pricing, hotels and galleries
* Visa services with requirements, documents and application steps
* Country destination guides with embassy details
* Tabbed Visa/Tour search widget with AJAX filtering (works without JavaScript)
* Visa and tour inquiry forms with spam protection and email notifications
* SEO built in: meta tags, OpenGraph, Twitter Cards, Schema.org structured data — auto-defers to Yoast SEO / Rank Math
* Guided setup wizard with installable bilingual (English/Bangla) demo data
* CSV/JSON import/export with progress, resume and rollback (REST + WP-CLI)
* Elementor widgets and dynamic tags; shortcodes for every component
* Translation-ready (WPML/Polylang config included, Bangla-first demo data)
* Bootstrap 5 frontend, fully theme-overridable templates

= Privacy =

Inquiry form submissions (name, email, phone, message) are stored as private entries in your own WordPress database and emailed to the address you configure. The plugin does not send visitor data to any external service and does not track visitors itself.

= Third-party libraries =

The plugin bundles the Bootstrap 5 CSS/JS framework (https://getbootstrap.com/), copyright the Bootstrap Authors, released under the MIT license. The bundled build can be disabled under Settings → Performance if your theme ships its own.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/zihad-travel-cms`.
2. Activate it through the Plugins screen.
3. The Setup Wizard opens automatically — company details, branding, tracking and optional demo data (re-run anytime from Travel CMS → Setup).
4. Fine-tune under **Travel CMS → Settings**; check **Travel CMS → Health**.

== Frequently Asked Questions ==

= Does it work without Elementor? =

Yes. Elementor is optional — the widgets simply appear when Elementor is active. Shortcodes and theme-overridable templates cover everything else.

= Does it conflict with Yoast SEO or Rank Math? =

No. The built-in SEO output automatically steps aside when either plugin is active.

= Can I use my theme's Bootstrap? =

Yes — disable the bundled build under Settings → Performance.

= Does the plugin contact external services? =

Only if you choose to install the optional demo content. See the External Services section below. Normal operation makes no external requests.

== Screenshots ==

1. Dashboard with content statistics, demo data status and quick actions.
2. Tabbed tour editor with itinerary, pricing, hotels and gallery.
3. Homepage search widget with tabbed visa and tour search.
4. Settings screen with branding, contact and integration options.
5. Guided setup wizard.
6. Import / Export screen with live progress.

== External Services ==

The plugin only contacts external services during the optional, user-initiated demo content installation (Setup Wizard demo step, or Travel CMS → Import / Export → Demo Data). Demo placeholder images are downloaded once into your media library from:

* Lorem Picsum (https://picsum.photos/) — placeholder photos for demo tours, visas and countries. See https://picsum.photos/ for its terms.
* FlagCDN (https://flagcdn.com/) — country flag images for demo countries. See https://flagcdn.com/ for its terms.

Only the image URLs are requested; no personal data is transmitted. If you never install demo data, the plugin makes no external requests. Integrations you configure yourself (Google Analytics, Facebook Pixel, Google Maps, WhatsApp links) load on your site under their respective vendors' terms only after you enter your own IDs.

== Changelog ==

= 1.0.0 =
* Initial release: Country/Visa/Tour content types with tabbed editors, frontend engine with AJAX search, tabbed search widget, inquiry forms, SEO module, setup wizard, bilingual demo data, CSV/JSON import/export, Elementor integration, REST API and WP-CLI commands.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
