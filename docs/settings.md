# Global Dashboard & Settings

## Settings data layer

All settings live in one option (`ztc_settings`), read through
`Core\Config` (dot notation, defaults-merged, request-cached). The schema *is*
`Config::defaults()` — sections: `general`, `homepage`, `company`, `social`,
`whatsapp`, `integrations`, `booking`, `performance`, `custom_code`,
`display`, `advanced`.

Every write path runs through `Settings\SettingsSanitizer`, which recursively
intersects the payload with the defaults shape (unknown keys dropped at every
depth, scalars coerced to the default's type):

- the admin form (`SettingsPage::persist()`),
- REST (`GET/POST ztc/v1/settings`, manage_options),
- the Settings API registration (`register_setting` sanitize callback).

Typed reads for consumers stay in `Settings\GlobalSettings` (company identity,
contact, branding, WhatsApp, Maps/GA/Pixel ids).

## Settings screen

`Admin\Pages\SettingsPage` — eleven tabs (General, Homepage, Branding,
Contact, Social Media, WhatsApp, Maps, Analytics, Booking, Performance,
Custom CSS/JS) rendered with the same `Tab` + field components as the content
editors (the tab JS/CSS is shared too). Field names are Config dot keys;
values load from `Config::get()`; saving runs each field's `sanitize()`, then
the structural sanitizer, then **one** batched `update_option`. Submission
goes through `admin-post.php` with nonce + `manage_options` guards. Extend
with the `ztc_settings_tabs` filter.

`CodeField` (new reusable component) backs the Custom CSS/JS tab: verbatim
storage apart from `</script>`/`</style>` injection guards.

## Consumers (reuse, not duplication)

- `Frontend\Integrations` (frontend-only Registrable): brand-color CSS
  variables (`--ztc-brand`, `--ztc-secondary`), custom CSS in `wp_head`,
  custom JS in `wp_footer`, GA (gtag) + Facebook Pixel snippets, and the
  floating WhatsApp button (theme-overridable part
  `frontend/parts/whatsapp-button.php`, link built by
  `GlobalSettings::whatsapp_link()` with the default message).
- `Core\Assets`: `performance.load_bootstrap` removes the bundled Bootstrap
  dependency for themes that ship their own.
- `Modules\Search\SearchController`: `performance.cache_ttl` drives the
  public search endpoint's `Cache-Control` header.
- Elementor: the `Travel CMS Setting` dynamic tag (`SettingTag`) exposes
  company/contact/branding values to any text control.

## Dashboard

`Admin\DashboardData` builds the view-model — content counts from the three
repositories, demo-data status (`DemoDataInstaller::files_ready()` + the
`ztc_demo_installed` flag, set by the DemoData module from the importer's
`ztc_import_batch_processed` hook), and the latest five import jobs from
`JobRepository`. `Admin\Menu` renders it via `templates/admin/dashboard.php`:
stat cards, demo widget, recent-imports table, quick actions.

## Tests

`settings-smoke.php`: defaults schema, sanitizer hardening (unknown keys,
type coercion, nesting), the settings page (11 tabs render, values prefill,
persist() writes one batched option, nonce guard), REST get/partial-update,
GlobalSettings getters, Integrations output (brand vars, GA/Pixel, custom
code guards, WhatsApp button), Assets bootstrap toggle and DashboardData.
