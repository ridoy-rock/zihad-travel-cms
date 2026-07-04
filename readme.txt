=== Zihad Travel CMS ===
Contributors: zihad
Tags: travel, tours, visa, booking, itinerary
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete travel content management system — tours, visas, countries, search, inquiries and more.

== Description ==

Zihad Travel CMS is a professional travel content management system for WordPress — a complete replacement for WP Travel Engine. It provides everything a travel agency needs:

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

== Changelog ==

= 1.0.0 =
* Initial release: Country/Visa/Tour content types with tabbed editors, frontend engine with AJAX search, tabbed search widget, inquiry forms, SEO module, setup wizard, bilingual demo data, CSV/JSON import/export, Elementor integration, REST API and WP-CLI commands.
