# Demo Data Generator & Installer

`Modules\DemoData` — a reusable content **generator** plus a thin **installer**.
The generator produces importer-format JSON from data files; the installer
feeds that JSON through the existing Importer module. Nothing here parses
files, creates posts, sideloads images or detects duplicates — that is all
`Modules\Importer`.

## Architecture

```
demo-data/
├── sources/                  ← hand-curated inputs (data, never PHP)
│   ├── countries.json        100 seed countries: English + Bangla names,
│   │                         ISO code, capital, currency, language,
│   │                         timezone, region (en/bn), major cities
│   └── templates.json        every generated sentence/list/pool, keyed by
│                             locale (en, bn) with {tokens}; also the
│                             placeholder-image URL patterns
├── countries.json            ← generated output (importer format)
├── visas.json                ← generated output
└── tours.json                ← generated output
```

| Class | Responsibility |
|---|---|
| `SourceRepository` | Loads/validates the source files; resolves the output dir (`ztc_demo_data_dir` filter) |
| `DemoContentGenerator` | seeds × templates → records; writes the three output files |
| `DemoDataInstaller` | `ImportService::start()/process()` over the generated files, in dependency order (country → visa → tour) |
| `DemoDataController` | REST: `POST ztc/v1/demo/generate`, `POST ztc/v1/demo/start` |
| `DemoCliCommand` | `wp ztc demo generate`, `wp ztc demo install` |

## Determinism & regeneration

The generator is **pure**: no randomness, no timestamps. Every choice (visa
fee, tour duration, FAQ set…) is picked from a template pool by seed index, so:

- regenerating produces byte-identical files (tested);
- slugs are stable (`japan`, `japan-tourist-visa`, `5-day-japan-discovery`),
  so re-installing **upserts** through the importer instead of duplicating;
- editing `sources/*.json` and regenerating updates the whole dataset at any
  time.

Volumes: 100 countries, 4–5 visas per country (≥ 400), 1–2 tours per country
(≥ 100).

## Images

Records reference images **only by URL**; the importer downloads them
(`ImageImporter`, deduped by `_ztc_source_url`). Patterns live in
`templates.json → images`:

- hero/gallery/thumbnails: seeded picsum.photos URLs (stable per slug),
- flags: `https://flagcdn.com/w320/{iso}.png` (real flags via ISO code).

Because they are data, the URLs can be swapped for real photography later and
re-imported — same slugs → update-in-place, new source URLs → new attachments.

## Localization

`DemoContentGenerator::generate( string $locale = 'en' )`:

- Every template node is `{"en": …, "bn": …}`; the generator resolves the
  requested locale (falling back to `en`).
- `en` output still carries Bangla where the schema has it (`bangla_name`,
  region names from the seed).
- `bn` output is a fully Bangla content set — titles, descriptions,
  documents, FAQs — with the **same latin slugs**, which is the linking key a
  future WPML/Polylang translation import needs. Adding another language =
  adding a locale key to `templates.json` (and optionally names to the seeds);
  no PHP changes.

## Install flow (importer reuse)

- CLI: `wp ztc demo install [--batch=25]` — loops
  `ImportService::process()` per type with a progress bar. `--regenerate`
  rebuilds the JSON first; `--locale=bn` for Bangla content.
- Admin (Travel CMS → Import / Export → Demo Data card): "Generate" hits
  `POST /demo/generate`; "Install" calls `POST /demo/start {type}` per type
  and then drives the **existing** `POST /import/process` loop — the same
  progress bar, error log, resume and rollback semantics as any other import.
- Generated files ship with the plugin, so installing works even when the
  plugin directory is read-only (generation is optional).

## Tests

`demo-data-smoke.php`: generator counts (≥ 100/400/100), record structure
(Bangla fields, image URL patterns, unique stable slugs), byte-identical
regeneration, `bn` locale output, and a full install through the import
engine with an in-memory store (countries → visas → tours, relations
resolved, Bangla meta intact).
