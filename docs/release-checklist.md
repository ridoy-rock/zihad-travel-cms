# Release Checklist — 1.0.0

Status of every hardening item. ✅ = done and verified in this repo,
🖐 = manual QA on a running WordPress site (steps below).

| # | Item | Status |
|---|---|---|
| 1 | Production Bootstrap build | ✅ Bootstrap 5.3.3 dist in `assets/vendor/bootstrap/` (MIT, documented) |
| 2 | Composer optimization | ✅ `optimize-autoloader` on; dev-deps split (PHPCS/WPCS/PHPStan/stubs); `lint`/`stan`/`test` scripts |
| 3 | CI workflow | ✅ `.github/workflows/ci.yml`: syntax + JS + XML, all suites on PHP 8.2/8.3, PHPCS, PHPStan |
| 4 | PHPCS | ✅ 0 errors / 0 warnings on shipped code (WPCS + PHPCompatibilityWP; documented deviations in `phpcs.xml.dist`) |
| 5 | PHPStan | ✅ Level 5, 0 errors (`phpstan.neon.dist`, WordPress stubs, Elementor/WP-CLI stubs) |
| 6 | Capability audit | ✅ `manage_options` on every admin page + admin REST route; `edit_post` meta auth; inquiry CPT `create_posts => do_not_allow`; the two public routes are by design (read-only `/search`, defended `/inquiry`). Custom "Travel Manager" role deferred to v1.1 |
| 7 | Security audit | ✅ See "Security review" below |
| 8 | Performance audit | ✅ See "Performance review" below |
| 9 | Translation | ✅ `.pot` regenerated with wp-cli make-pot — 534 strings across all modules |
| 10 | Licensing | ✅ GPL-2.0 `LICENSE` shipped; headers consistent |
| 11 | Update mechanism | ✅ `Update URI` header reserves the commercial update endpoint; version single-sourced (`ZTC_VERSION` = header = readme stable tag). Update client itself ships with the licensing server (post-1.0 infra) |
| 12 | Packaging | ✅ `.distignore` (root-anchored) + `bin/package.sh` → verified zip: 272 files, no dev files, Bootstrap/demo sources/`.pot` included |
| 13 | Release checklist | ✅ This document |
| 14 | Installation verification | ✅/🖐 Package staged into the Local "testing" site (all 194 PHP files parse under PHP 8.2); activation requires the site running |
| 15 | Fresh WordPress installation test | 🖐 |
| 16 | Demo data installation verification | 🖐 |
| 17 | Elementor verification | 🖐 |
| 18 | Search verification | 🖐 |
| 19 | Mobile verification | 🖐 |
| 20 | Documentation review | ✅ README, readme.txt, CONTRIBUTING, docs/ index + 9 module docs current |

## Manual QA script (items 14–19)

Run on the Local **testing** site (the built package is already staged
in its plugins directory):

1. **Fresh install** — start the site in Local, activate *Zihad Travel
   CMS*. Expect: no notices/fatals, redirect to the Setup Wizard,
   Travel CMS menu with Dashboard/Settings/Import-Export/Setup/Health.
2. **Wizard** — complete all 11 steps incl. demo install; verify with
   JavaScript disabled too (forms + redirects; demo installs via
   "Continue installing").
3. **Demo data** — expect 105 countries / 473 visas / 132 tours;
   re-install must update, not duplicate; check Bangla content renders
   (e.g. জাপান) and permalinks `/tour/`, `/visa/`, `/country/` resolve
   (flush permalinks once if Health warns).
4. **Search** — archive filters with and without JS (URLs like
   `/tour/?duration=4-7&budget=500-1000` must filter server-side);
   `[ztc_search_widget]` tab switching with JS disabled (CSS radio
   tabs); AJAX results replace the grid.
5. **Inquiries** — submit visa + tour inquiries (JS on and off),
   verify inquiry records + notification email (use an SMTP logger),
   honeypot (fill hidden "website" field → nothing stored), rate limit
   (6th rapid submit → friendly error).
6. **SEO** — view-source a tour: one JSON-LD graph (validate at
   validator.schema.org / Rich Results Test), canonical, OG/Twitter
   tags; activate Yoast → all ztc SEO output disappears.
7. **Elementor** — with Elementor active: Travel CMS category shows 6
   widgets; place grids, Travel Search and Inquiry Form; confirm
   nothing renders inside the editor preview head.
8. **Mobile** — 375px viewport: search widget stacks, cards single
   column, wizard usable, inquiry form usable; Lighthouse mobile pass
   on a tour page.
9. **Uninstall** — with "Delete data on uninstall" off, deactivate +
   delete keeps content; with it on, content and `ztc_*` options are
   removed.

## Security review (summary)

- Nonce + capability on every state-changing form (`admin-post`
  handlers) — forged-nonce paths covered by the test suites.
- REST: permission callbacks on every route; only `/search` (read-only,
  cacheable) and `/inquiry` (honeypot + hashed-IP rate limit + strict
  arg validation) are public.
- All input sanitized per field; all output escaped (PHPCS
  EscapeOutput clean); exceptions never printed raw.
- No `eval`/`exec`/`unserialize`; only `wp_safe_redirect`; raw SQL
  confined to `uninstall.php` (prefixed LIKE cleanup).
- Inquiries (personal data) are non-public, non-REST, meta hidden;
  visitor IPs stored only as salted-length MD5 rate keys with TTL.
- File imports reference media-library IDs, never raw paths.

## Performance review (summary)

- Options: settings autoloaded (read every request); import jobs,
  wizard state/flag non-autoloaded.
- Caching: country options transient (30 min, invalidated on change),
  health REST loopback (5 min), REST search `Cache-Control` from
  settings; term queries ride core caches.
- Queries: search capped at 24/page; duration filtering via the
  numeric mirror (no object parsing); no queries in templates; single
  card-render path. `posts_per_page => -1` only in admin selects
  (cached where hot) and full exports (intentional).
- Assets: enqueued only on plugin routes/render; Bootstrap toggleable;
  no new JS for the search widget (CSS tabs).

## Known deferrals (v1.1+)

- "Travel Manager" custom role/capabilities.
- Inquiry management screen (status workflow, notes, export).
- Update-mechanism client + licensing server integration.
- WPML/Polylang adapters (contract + config already shipped).
