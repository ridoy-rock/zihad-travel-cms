# Setup Wizard

`Modules\Wizard` is the first-run installer: eleven guided steps that
connect the plugin to an agency (company details, branding, contact
channels, tracking, homepage defaults, optional demo content). It is
**pure orchestration** — it renders the shared field components
pre-filled from `Config`, writes through the exact settings pipeline
the Settings screen and `POST /settings` use, and installs demo data
through the existing installer/import engine. It owns no settings
keys, no sanitization and no import logic.

## Components

| Class | Role |
|---|---|
| `WizardModule` | Wires everything through the existing filters (`ztc_admin_pages`, `ztc_rest_controllers`). |
| `WizardService` | Step registry (filterable), progress state, per-step saves, demo sequencing, finish summary. |
| `Admin\Pages\WizardPage` | The Travel CMS → Setup screen + `admin-post` handlers. |
| `WizardController` | REST endpoints for headless/scripted setup. |
| `WizardPrompt` | One-shot activation redirect + "finish setting up" notice. |

## Steps

Welcome → Company Information → Branding → Contact Details →
Social Media → WhatsApp → Google Maps API → Analytics / Facebook Pixel
→ Homepage Settings → Demo Data (optional) → Finish.

Every settings step maps its fields to **existing** `ztc_settings` dot
keys (`company.*`, `general.*`, `social.*`, `whatsapp.*`,
`integrations.*`, `homepage.*`) — the wizard-smoke suite asserts every
field name exists in the settings schema. The finish step shows content
counts (via `DashboardData`) and the environment checks from
`HealthService`, including the permalink check, with a link to WP's
permalink settings.

## Behaviour guarantees

- **Independent saves** — each step submits only its own fields; the
  save runs field `sanitize()` → structural `SettingsSanitizer` → one
  batched option write. Every other saved setting is preserved.
- **No silent overwrites** — every field renders pre-filled with the
  currently saved value, so what will be written is always visible
  before submitting.
- **Resumable** — progress lives in the `ztc_wizard_state` option;
  opening the wizard resumes at the first incomplete step, and
  completed steps are revisitable from the step indicator.
- **Skippable** — per step ("Skip this step") and entirely ("Skip
  setup" on the welcome step, or dismissing the notice flow).
- **Rerunnable** — Travel CMS → Setup any time; "Restart wizard" resets
  progress only. Settings are never touched by a reset.
- **No-JS** — the whole flow is plain forms and redirects. The demo
  step installs without JavaScript by processing a bounded slice of
  import batches per submission ("Continue installing"); with
  JavaScript the existing admin.js progress loop drives the same
  install live via REST.
- **First run** — `WizardPrompt` listens to the existing
  `ztc_activated` action (the `Activator` is untouched), sets a
  one-shot redirect flag, and redirects the next eligible admin
  request. Never for bulk/network activations, AJAX requests, or
  installs that already finished/skipped the wizard.

## REST API (`ztc/v1`, `manage_options`)

| Route | Method | Purpose |
|---|---|---|
| `/wizard` | GET | State, resume point, step catalogue with live values |
| `/wizard/step` | POST | `{id, values}` — save one step |
| `/wizard/skip` | POST | Skip the wizard |
| `/wizard/complete` | POST | Finish the wizard |
| `/wizard/reset` | POST | Reset progress (settings untouched) |

Demo installation reuses `POST /demo/start` + `POST /import/process`.

## Extension points

- `ztc_wizard_steps` (filter) — add/remove/reorder steps; a step is
  `{id, title, intro, fields[]}` with fields named by settings dot keys.
- `ztc_wizard_step_saved`, `ztc_wizard_step_completed` (actions) — per
  step.
- `ztc_wizard_completed` (action, `finished|skipped`) — end of the
  wizard; future modules (e.g. Booking) can chain onboarding here.
- `ztc_wizard_reset` (action).

## Multilingual & Elementor

All strings translatable; the wizard writes the same admin-texts
already declared in `wpml-config.xml`; the demo step exposes the en/bn
locale choice. The module is admin/REST-only — no frontend or Elementor
surface (the settings it writes feed the existing widgets and dynamic
tags).

## Testing

`wizard-smoke.php` (WP stubs + real hook system + in-memory content
store) covers: module wiring via filters, step-registry integrity
(every field schema-backed), the `ztc_wizard_steps` filter, independent
saves with the no-overwrite invariant, forged-nonce guards on every
handler, resume/skip/finish/reset semantics (reset keeps settings), the
admin render (pre-filled inputs, no-JS forms, step indicator, resume
point), all five REST routes, the one-shot activation redirect and
notice, and a full no-JS demo install of the committed demo dataset
through the real import engine.
