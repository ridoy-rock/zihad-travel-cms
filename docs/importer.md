# Import / Export Module

`Modules\Importer` — a generic, batched import/export engine for CSV and JSON,
shared by the admin UI (progress bar), REST, WP-CLI and the demo-data
installer. Content types plug in through mappings; nothing in the engine knows
about countries, visas or tours specifically.

## Mappings

A mapping implements `Contracts\ImportMapping` (`type()`, `post_type()`,
`fields()`) and is registered via the `ztc_import_mappings` filter. Built-ins:
`CountryImportMapping`, `VisaImportMapping`, `TourImportMapping` (each lives
in its content module, keyed to the module's meta constants).

Field targets: `post:title|slug|status|content|excerpt`, `meta:{key}`,
`list:{key}` (pipe string `"Tokyo|Osaka"` or JSON array), `json:{key}`
(structured — FAQ/itinerary/SEO; JSON string in CSV cells, plain arrays in
JSON files), `terms:{taxonomy}` (names, auto-created), `relation:{key}`
(related post title/slug → ID), `image:{key}`, `gallery:{key}`, `thumbnail`
(URLs sideloaded to attachments).

## Engine semantics

- **Jobs** (`ImportJob`, persisted per option): total/processed counters,
  created/updated/skipped/failed, error log (capped at 100), created post IDs.
- **Batching / progress / resume**: `ImportService::process($job_id, $batch)`
  handles the next batch from the stored offset and saves state. The progress
  bar loops it; an interrupted import resumes by calling it again (REST) or
  `wp ztc import --resume=<job_id>` (CLI).
- **Duplicate detection**: by slug — explicit `slug` column, else the slug
  derived from `title` — within the mapping's post type. Modes: `create`
  (skip existing), `update` (skip new), `upsert` (default).
- **Update semantics**: absent or empty columns never erase existing data.
- **Rollback**: `rollback($job_id)` force-deletes every post the job created.
  With `rollback_on_failure`, a job that finishes with any failed record rolls
  back automatically (all-or-nothing).
- **Images**: `ImageImporter` sideloads URLs once per source
  (`_ztc_source_url` meta), so re-imports reuse attachments and placeholder
  images can be replaced later by importing new URLs.
- **Bangla/UTF-8**: readers and writers are UTF-8 end to end; CSV exports get
  a BOM (Excel-safe), JSON exports use `JSON_UNESCAPED_UNICODE`.

## REST (`ztc/v1`, manage_options)

| Route | Method | Body/args |
|---|---|---|
| `/import/start` | POST | `type`, `media_id` (uploaded file), `mode`, `rollback_on_failure` |
| `/import/process` | POST | `job_id`, `batch` (1–100) |
| `/import/status` | GET | `job_id` |
| `/import/jobs` | GET | — |
| `/import/rollback` | POST | `job_id` |
| `/export` | GET | `type`, `format` → `{filename, mime, body}` |

REST imports only accept media-library attachment IDs (no raw paths).

## WP-CLI

```
wp ztc import demo-data/tours.json --type=tour [--mode=upsert] [--batch=50] [--rollback-on-failure] [--resume=<job_id>]
wp ztc export --type=tour --format=json [--output=tours.json]
wp ztc import-status <job_id>
wp ztc import-rollback <job_id>
```

## Admin UI

Travel CMS → Import / Export: media-library file picker, type/mode selection,
all-or-nothing checkbox, live progress bar, inline error log, and export
downloads.

## Tests

`importer-smoke.php`: CSV/JSON readers (BOM, Bangla, JSON cells), batch +
resume flow, every field target, duplicate detection across all three modes,
required-field errors, manual rollback and rollback-on-failure, export
round-trip (JSON export re-imports as an update), REST route registration and
error mapping.
