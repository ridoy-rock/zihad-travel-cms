# Tour Editor

`Modules\Tour\TourEditor` — the tabbed editor for the `ztc_tour` post type,
registered by `TourModule` only in wp-admin. Replaces the default Custom
Fields metabox (the CPT no longer supports `custom-fields`; the Tour Type
classic metabox is hidden via `meta_box_cb => false`).

## Tabs & fields

| Tab | Fields (component → meta key) |
|---|---|
| General | Country (Select → `ztc_country`), Tour Type (Taxonomy → `ztc_tour_type` terms), Price (Number → `ztc_price`), Sale Price (Number → `ztc_sale_price`), Duration (Duration → `ztc_duration` `{days, nights}`) |
| Hero | Hero Image (Media → `ztc_hero_image`) |
| Gallery | Gallery Images (Gallery → `ztc_gallery`) |
| Itinerary | Highlights (List → `ztc_highlights`), Day-by-day (Timeline → `ztc_itinerary`) |
| Inclusions | Included (List → `ztc_included`), Not Included (List → `ztc_excluded`) |
| Hotels | Hotels (Repeater name/rating/description → `ztc_hotels`) |
| Travel Info | Flights (RichEditor → `ztc_flights`), Meals (RichEditor → `ztc_meals`), Map URL (Url → `ztc_map`) |
| FAQ | FAQ Builder → `ztc_faq` |
| SEO | SEO group → `ztc_seo` `{title, description, keywords}` |

## Validation

- Field level: prices clamp to ≥ 0; duration accepts non-negative integers
  (stored as digit strings); URLs via `esc_url_raw`; rich text via
  `wp_kses_post`; unknown select options and repeater properties rejected.
- Cross-field (`after_save()`): a sale price ≥ the regular price is cleared
  and a warning is queued through `NotificationService` (shown after the
  redirect).

## Frontend behaviour

`TourService::hero_url()` resolves `ztc_hero_image` → featured image → first
gallery image. All fields flow into `TourService::page_data()` /
`card_data()`, so the single template, cards, Elementor tags and the search
endpoint read the same data the editor writes.

## Extending

- Add/alter tabs: filter `ztc_tour_editor_tabs` (receives `Tab[]`).
- React to saves: action `ztc_editor_saved( $post_id, $editor )`.
- Inject a toolbar (e.g. AI auto-fill): actions
  `ztc_editor_render_before` / `ztc_editor_render_after`.

## Tests

`tour-editor-smoke.php` (scratchpad suite): renders all nine tabs in order,
asserts every field input exists once, runs a full save round-trip (duration
object, list flattening, hotels rows, taxonomy terms via
`wp_set_object_terms`), verifies the sale-price rule clears invalid values
and queues the warning, and confirms a forged nonce writes nothing.
