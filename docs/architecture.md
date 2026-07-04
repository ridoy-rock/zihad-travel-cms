# Architecture

## Kernel & container

`Plugin` (singleton, booted on `plugins_loaded`) owns a reflection-autowiring
DI `Container` (`Core\Container`). Service providers run in two phases —
`register()` binds services, `boot()` attaches hooks:

`CoreServiceProvider` → `SettingsServiceProvider` → `AdminServiceProvider` →
`FrontendServiceProvider` → `RestApiServiceProvider` → `ModulesServiceProvider`.

Extension filters: `ztc_service_providers`, `ztc_modules`, `ztc_admin_pages`,
`ztc_rest_controllers`, `ztc_translation_provider`.

## Modules

A feature = a folder under `Modules/` with a class extending
`Modules\BaseModule` (`id()`, optional `is_available()`, `components()`
returning Registrables). `ModuleManager` resolves each from the container and
registers it. Content modules (Country, Visa, Tour) bundle: PostType,
Taxonomy, Meta, Repository, Service, Editor.

## Data layer (MVC model)

- `PostTypes\BasePostType`, `Taxonomies\BaseTaxonomy` — declarative CPT/tax registration.
- `PostTypes\BasePostMeta` — every field registered via `register_post_meta`
  with type, sanitizer, default and REST schema (⇒ Gutenberg/Elementor/REST/AI ready).
  Meta keys are constants on the `*Meta` classes; never raw strings elsewhere.
- `Data\BaseRepository` — the only layer touching WP data APIs. Queries run
  with `suppress_filters => false`; language routing via the
  `Contracts\TranslationProvider` null-object (swap for WPML/Polylang adapters).
- `Modules\*\*Service` — business logic and view-models (`card_data()`, `page_data()`).

## Admin UI framework (`Admin\UI`)

`Editor` renders one tabbed metabox per post type (WAI-ARIA tabs) and runs the
save pipeline: nonce → autosave/revision guard → capability → per-field
`sanitize()` → per-field `save()` (post meta by default; `TaxonomyField`
writes terms) → `after_save()` cross-field validation hook →
`ztc_editor_saved` action. Tabs are filterable per post type
(`{post_type}_editor_tabs`); toolbars inject via `ztc_editor_render_before/after`.

Field components (all in `Admin\UI\Fields`): Text, Textarea, RichEditor,
Number, Url, Select, MultiSelect, Checkbox, Toggle, Media, Gallery, Repeater,
Faq, Timeline, List, Seo, Taxonomy, Duration.

## Frontend engine (MVC view/controller)

- `Frontend\TemplateLoader` (controller): routes plugin URLs via
  `template_include`, builds view-models from services, passes them through
  `set_query_var('ztc_view')`. Every template overridable at
  `{theme}/zihad-travel-cms/frontend/…`.
- Templates (`templates/frontend/`): pure views using `ztc_view()` / `ztc_part()`.
- `Views\GridRenderer`: the single card-render path shared by archives,
  shortcodes (`Frontend\Shortcodes`), Elementor widgets and AJAX search.
- Search: `Modules\Search` — `GET ztc/v1/search`, public/read-only,
  `Cache-Control: public, max-age=60`; progressive-enhancement JS in
  `assets/js/frontend.js`.
- Elementor: `Modules\Elementor` — widget category, grid + CTA widgets,
  Field/HeroImage dynamic tags, CPT support filter. Loads only after
  `elementor/loaded`.

## Lifecycle

Activation seeds defaults (`Core\Activator`); `Core\Upgrade` runs
version-keyed migrations and the deferred rewrite flush; deactivation is
non-destructive; uninstall deletes data only when
`advanced.delete_data_on_uninstall` is enabled.
