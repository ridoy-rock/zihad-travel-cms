# Booking / Inquiry Module

`Modules\Booking` turns visitor interest into records and
notifications: visa and tour inquiry forms feeding a private Inquiry
post type, with admin emails through a swappable mail contract. It is
the foundation the v2.0 booking platform (availability, payments)
builds on.

## One pipeline, two entry points

Every submission — the no-JS form POST and the REST call — funnels
through `InquiryService::submit()`:

```
sanitize every field → honeypot check → per-IP rate limit
  → server-side validation → persist (InquiryRepository)
  → notify (Mailer contract) → ztc_inquiry_created
```

Validation rules exist exactly once: required name, valid email,
message (≤ 5000 chars), type whitelist (`visa`/`tour`), the Booking
settings toggles (`enable_visa_inquiry`/`enable_tour_inquiry`), and the
related post must exist and match the type. Extend with the
`ztc_inquiry_validate` filter.

## Entry points

- **No-JS baseline** — the form POSTs to `admin-post.php`
  (`ztc_inquiry`, logged-in and logged-out), nonce-verified, then
  redirects back with a `?ztc_inquiry=sent|invalid|limited` flag and a
  stable `#ztc-inquiry-{type}-{id}` anchor the part renders as a
  message. JavaScript **only enhances**: frontend.js intercepts the
  submit, posts to REST and shows the result inline (network failure
  falls back to the native submit).
- **REST** — `POST ztc/v1/inquiry`, public by design (visitors submit),
  every arg typed/validated/sanitized/described (`name`, `email`
  (format-validated), `phone`, `message`, `type` enum, `post_id`,
  `website` honeypot). Responses: 200 success, 400 validation errors
  (field map), 429 rate-limited.

## Spam & abuse controls

- **Nonce** on the form path.
- **Honeypot** (`website` field, visually hidden): filled → the
  pipeline reports success but stores and sends nothing
  (`ztc_inquiry_spam_blocked` fires) — bots are never tipped off.
- **Rate limit**: 5 submissions per 10 minutes per IP (transient
  counter keyed by an MD5 of the address — the raw IP is never
  stored). Tune via `ztc_inquiry_rate_limit` (`[limit, window]`).

## Storage (privacy-first)

`ztc_inquiry` is a private CPT: no frontend, excluded from search,
**`show_in_rest` false and every meta field REST-hidden** — inquiries
carry personal data and never leave wp-admin. "Add New" is disabled
(`create_posts => do_not_allow`); records arrive only through the
pipeline. The admin list shows contact (mailto), type, related
visa/tour and status columns; full management (status workflow, notes,
export) is the v1.1 inquiry screen.

## Mail abstraction

Nothing calls `wp_mail()` directly. `Contracts\Mailer` is bound in the
container to `Services\WpMailer` (a `wp_mail` wrapper, so existing SMTP
plugins keep working); transactional providers swap in via the
`ztc_mailer` filter (class name) without touching calling code.
Notification content is filterable: `ztc_inquiry_email_recipient`
(defaults: Booking notification email → company email → site admin),
`ztc_inquiry_email_subject`, `ztc_inquiry_email_message` (plain text,
includes an edit link), `ztc_inquiry_email_headers` (default Reply-To:
the visitor).

## Rendering (one path)

`Views\InquiryFormRenderer` + the theme-overridable
`frontend/parts/inquiry-form.php` part serve every surface:

| Surface | Entry point |
|---|---|
| Single visa/tour pages | Injected into the view-model via the `ztc_template_view` seam (`InquiryTemplateData`) — no service/loader changes; templates render the part when data is present |
| Shortcode | `[ztc_inquiry_form type="visa" post_id="0" heading=""]` |
| Elementor | "Inquiry Form" widget (Travel CMS category) |

The renderer returns nothing when the type is disabled in Booking
settings, so every surface hides consistently. View-model filterable
via `ztc_inquiry_form_data`.

## Actions & filters

`ztc_inquiry_created( $id, $data )`,
`ztc_inquiry_spam_blocked( $data )`, `ztc_inquiry_validate`,
`ztc_inquiry_rate_limit`, `ztc_inquiry_form_data`,
`ztc_inquiry_email_recipient|subject|message|headers`, `ztc_mailer`.

## Testing

`booking-smoke.php` covers: the Mailer contract (wp_mail passthrough +
filter swap), the happy path (sanitized persistence, notification
content, Reply-To, `ztc_inquiry_created`), the recipient fallback
chain, every validation rule (nothing stored/sent on failure), the
honeypot (silent success), the rate limit (+ filter), REST args and
200/400/429 codes, the nonce-guarded no-JS handler (redirect flag +
stable anchor), renderer gating/escaping/result states, byte-equal
shortcode/Elementor output, `ztc_template_view` injection and the
admin columns.
