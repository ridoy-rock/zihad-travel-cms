# Distribution — Build Channels & WordPress.org Submission

Zihad Travel CMS ships through two distribution channels built from **one
source tree**. The channels never diverge in features: the free plugin is
fully functional everywhere, has no license keys, no upgrade nags and no
locked code. The only difference between the two builds is update routing.

## Channels

| | `wporg` (default) | `pro` (self-hosted / development) |
|---|---|---|
| Command | `composer build:wporg` or `bin/package.sh` | `composer build:pro` or `bin/package.sh --channel=pro` |
| Output | `build/zihad-travel-cms-{version}-wporg.zip` | `build/zihad-travel-cms-{version}.zip` |
| `Update URI:` header | **Stripped** during staging (WordPress.org serves updates itself; the header triggers `plugin_updater_detected`) | Kept (reserves the self-hosted update route) |
| Extra gate | Rejects `Update URI:` and any updater/licensing pattern (`license_key`, `plugin_updater`, `update_client`, …) anywhere in the stage; requires a valid `readme.txt` | — |
| Everything else | identical | identical |

Both channels stage the tree minus [.distignore](../.distignore), scrub OS
junk, and fail the build if any development artifact reaches the zip. The
inner folder is always `zihad-travel-cms/` (the plugin slug), so either zip
installs identically.

**The wporg gate is the enforcement point for our freemium rules:** if
updater or licensing code is ever added to this repository by mistake, the
WordPress.org build fails instead of shipping it.

## Where Pro lives later

Licensing, the update client and paid features will live in a **separate
add-on plugin** (working name `zihad-travel-cms-pro`) — never in this
codebase. The add-on:

- requires the free plugin (`Requires Plugins: zihad-travel-cms` header +
  a runtime `function_exists( 'ztc' )` guard);
- extends through the public seams that already ship: `ztc_service_providers`,
  `ztc_modules`, `ztc_settings_tabs`, `{post_type}_editor_tabs`,
  `ztc_import_mappings`, `ztc_rest_controllers`, `ztc_wizard_steps`,
  `ztc_mailer`, `ztc_translation_provider`, `ztc_editor_render_before/after`
  and the `ztc_seo_*` filters;
- checks compatibility against `ZTC_VERSION`.

Rules for this (free) repository:

1. No license-key UI, no update client, no remote calls home.
2. No upsell/upgrade UI. Extension points are silent.
3. No feature may be artificially limited to create a Pro incentive.
4. The placeholder module folders stay inert and invisible.

## WordPress.org submission checklist

- [ ] `Contributors:` in `readme.txt` set to the real wordpress.org username
      (pending — placeholder `zihad`).
- [ ] Build with `composer build:wporg`; submit that zip.
- [ ] Plugin Check: **0 ERRORs** on the built zip. Expected warnings, all
      intentional:
      - `PrefixAllGlobals` — false positives; everything uses the `ztc_`
        prefix (= **Z**ihad **T**ravel **C**MS), which the sniff cannot
        derive from the slug. Mention this in the review notes.
      - `load_plugin_textdomain` discouraged — kept so the identical zip
        also works when distributed outside wordpress.org.
- [ ] Screenshots: capture after the planned admin UI refresh; upload to
      the SVN `assets/` directory matching the numbered captions in
      `readme.txt`.
- [ ] External services (demo images from picsum.photos / flagcdn.com) are
      disclosed in `readme.txt` → External Services.
- [ ] Bundled Bootstrap 5.3.3 attribution: `assets/vendor/bootstrap/README.md`
      + `readme.txt` → Third-party libraries (MIT, GPL-compatible).
- [ ] After approval: SVN layout is `trunk/` (zip contents), `tags/{version}/`,
      `assets/` (screenshots, banner, icon — not part of the zip).

## CI

Every push builds the wporg zip (running the full channel gate) and runs
WordPress's `plugin-check-action` against the staged plugin, so a change
that would fail directory review fails CI first.
