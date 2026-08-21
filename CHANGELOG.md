## 1.0.1038 RC66.12.9 — GPL licensing, notices & SBOM

- License Slotera Booking under **GPL-2.0-or-later** and publish the complete GPLv2 license text with the “or later” grant.
- Add WordPress plugin, Composer and QA package license metadata for GPL-2.0-or-later.
- Add `THIRD-PARTY-NOTICES.md` documenting the bundled Noto Sans font under OFL-1.1 and distinguish external service integrations from bundled code.
- Add `sbom.cdx.json` (CycloneDX 1.5) for the release package, including the bundled Noto Sans file hash and license.
- Keep runtime booking, payment, email, Promotions and translation behavior unchanged.

## 1.0.1038 RC66.12.8 — Update URI readiness

- Prepare future first-party WordPress `Update URI` support without publishing a temporary or placeholder endpoint.
- Add an optional `SLTR_UPDATE_URI` build-time binding that validates an absolute HTTPS URI, injects the WordPress plugin header, and synchronizes the runtime update namespace.
- Keep current release-candidate builds intentionally unbound until the official Slotera website/update service is available, and add fail-closed regression coverage for unsafe URI values.

## 1.0.1038 RC66.12.7 — Mollie metadata privacy cleanup

- Stop sending the customer email address in Mollie payment metadata; retain only the internal booking ID and payment mode required for payment linkage.
- Keep local Slotera booking/transaction email records unchanged and preserve webhook reconciliation through payment ID plus metadata booking ID.
- Add regression coverage proving customer PII is excluded from the outbound Mollie metadata payload.

## 1.0.1038 RC66.12.6 — Theme-aware marketing consent styling

- Make the booking-form marketing consent card inherit Slotera theme tokens instead of forcing a light surface.
- Dark theme now uses the dark Form background/text/border palette, and Custom theme uses the configured **Form background** value.
- Keep consent wording, checkbox behavior, translations, and explicit unchecked default unchanged.

## 1.0.1038 RC66.12.5 — Manual Promotions UX, deferred-license menu, ICS default & consent styling

- Let Manual Promotions `Send now` and test-send use the current unsaved form values, including a newly selected common Media Library image; scheduled sends continue to use saved settings.
- Remove deferred-license `PRO` badges and the `Slotera PRO Features` submenu while licensing enforcement remains intentionally postponed.
- Default Calendar invite attachments (ICS) to off for installations/settings where the option has never been saved, without changing an existing saved choice.
- Present the optional marketing-consent checkbox in a compact booking-form card similar to the notes field, slightly reduce the consent text size, and keep the checkbox explicitly unchecked by default.

## 1.0.1038 RC66.12.4 — Package save & Promotions link fallback fix

- Keep an already locked package slug canonical on later section/full-form saves so Booking blocks can be edited without a false `slug_locked` rejection.
- In Promotions, link to the solo package page when available; otherwise link to the configured Slotera Booking page with the package preselected.
- Omit the promotion CTA entirely when neither a solo package page nor a configured Booking page exists instead of falling back to the site home page.

## 1.0.1038 RC66.12.3 — Promotions Standard discount localization fix

- Replace the remaining hard-coded `Discount` promotion label with the customer-facing `Standard discount` label.
- Add `Standard discount` translations across all supported frontend locales and use the localized label for both percentage and fixed package discounts.
- Refresh the strict frontend translation baseline/freeze while leaving promotion pricing, images, scheduling, and email layout unchanged.

## 1.0.1038 RC66.12.2 — Promotions email image compatibility & offer line layout

- Percent-encode non-ASCII characters in promotion image URLs before rendering email HTML so clients with stricter URL handling can load WordPress media with Unicode filenames.
- Render simultaneous Weekend and Seasonal promotion conditions on separate lines instead of joining them into one compact metadata sentence.
- Keep existing customer-facing translations and CTA behavior unchanged; translation baselines remain frozen.

## 1.0.1038 RC66.12.1 — Promotions localization & CTA fix

- Reuse the existing localized `Weekend offer` and `Seasonal offer` customer-facing strings in Promotions instead of hard-coded English labels.
- Remove the redundant English `Weekends` suffix; seasonal promotion metadata keeps the actual end date without adding a new untranslated phrase.
- Prevent localized promotion CTA labels such as `Забронировать` from wrapping inside the email button.
- Keep customer-facing translation registries and their frozen baselines unchanged.

## 1.0.1038 RC66.12 — Promotions digest campaigns

- Add **Marketing Emails → Promotions** to automatically collect active package `Discount`, `Weekend offer discount %`, and active `Seasonal offer period` offers.
- Use `Slotera Booking page image` first and `Package page image` second; packages without either image are grouped below one common Media Library fallback image.
- Add Manual, weekly, every-two-weeks, and monthly promotion modes; automatic campaigns run on Friday and skip sending when there are no active offers.
- Show current package title, old/new price, offer type and validity; refresh offer data at preview/send time instead of persisting stale prices.
- Add full email Preview, test-email delivery, eligible-recipient count, and `Send now`; real campaigns reuse the existing marketing consent/suppression queue and the same renderer used by Preview/Test.

## 1.0.1038 RC66.11 — Release metadata consistency fix

- Restore the previously omitted RC66.4 entry in `CHANGELOG.md` so the user-facing release history matches the signed release transformation chain.
- Add regression coverage that requires every RC66.x candidate declared in `release-manifest.json` through the current candidate to appear exactly once in changelog order.
- Keep RC66.10 deterministic compressed packaging and all runtime behavior unchanged.

## 1.0.1038 RC66.10 — Deterministic compressed release packaging

- Replace uncompressed `ZIP_STORED` release packaging with the in-tree deterministic fixed-Huffman DEFLATE encoder; no host zlib compressor participates in canonical archive creation.
- Preserve canonical UTF-8 path ordering and fixed Unix ZIP metadata while restoring a compact release size intended to reduce WordPress upload/install overhead.
- Update signed-release verification to hash decompressed canonical ZIP entries for both stored and DEFLATE methods.
- Keep the strict `<8 MiB` release gate and add regression coverage for deterministic compressed packaging.

## 1.0.1038 RC66.9 — Admin translation payload cleanup

- Remove unused localized Admin UI values while retaining every English admin default and runtime translation key.
- Keep the editable/frozen translation contract limited to customer-facing Frontend UI and Email settings; Admin UI remains intentionally English-only.
- Remove Admin UI keys from the translation lock payload without changing frontend/email catalogs, `.mo` files, or their strict freeze baselines.
- Add regression coverage proving admin lookups remain English-only and customer-facing translation baselines remain unchanged.

## 1.0.1038 RC66.8 — Partial-payment balance notice localization

- Show a clear customer-facing notice for partial payments: `The remaining balance is paid on site.` in the client account and thank-you summary.
- Include the same localized balance notice in customer emails and PDF invoices when the booking payment status is `partial`.
- Add translations for the new notice across all supported locales and refresh the strict frontend/email translation freeze baselines.

## 1.0.1038 RC66.7 — Customer activity & date/time display cleanup

- Localize deposit-payment history through the existing `Payment partially paid` customer translation in every supported frontend locale, and hide the internal `payment_completed_notified` reservation event from the client timeline.
- Prevent Simple / Booking Request invoice output from exposing internal placeholder date/time values such as `00:00`; omit empty date/time rows rather than presenting non-customer-selected data.
- Keep full-day Fixed bookings date-only in PDF invoices and add regression coverage for customer activity visibility and sentinel-time suppression.
- Re-verify the strict frontend translation freeze across every frozen locale after the customer-activity mapping change.

## 1.0.1038 RC66.6 — Runtime security & privacy hardening

- Prevent public REST HMAC booking requests from consuming the same replay nonce twice between WordPress `permission_callback` authorization and the booking callback; each request object is authenticated once while callback-only invocations still authenticate fail-closed.
- Apply `privacy_visitor_analytics_require_consent` to basic visitor analytics collection; when consent is required, collection remains disabled until the site consent filter explicitly grants it.
- Keep booking-access session cookies `Secure` when WordPress is behind a TLS-terminating proxy by also honoring an HTTPS canonical `home_url`, while retaining `HttpOnly` and `SameSite=Lax`.
- Add direct-access `ABSPATH` guards to every PHP admin view/partial.
- Make licensing status explicit as `development_placeholder`: feature enforcement stays disabled, saved keys are placeholders only, and the admin UI no longer presents local key storage as a validated yearly license.
- Add runtime/source regression coverage for all RC66.6 hardening changes.

## 1.0.1038 RC66.5 — Attestation & release pipeline hardening

- Make detached attestation verification fail closed: required schema/subject/signature/material fields are mandatory and malformed or incomplete attestations are rejected.
- When an extracted plugin root is supplied, compare the complete extracted file set and every file SHA-256 directly against the signed ZIP rather than trusting regenerated local metadata.
- Run reproducibility builds only inside temporary sandbox copies and guard that mandatory Node QA leaves the source release tree byte-for-byte unchanged.
- Bind candidate identity across release manifest, provenance, changelog and attestation subject naming.
- Keep platform-neutral `ZIP_STORED` packaging, but remove duplicated per-file inventories and release-history payloads from provenance because `checksums.sha256` is the canonical immutable tree manifest.
- Enforce a release ZIP size gate below 8 MiB while preserving cross-host deterministic ordering and ZIP metadata.
- Add negative tamper coverage proving a modified extracted production file cannot receive a positive signed-release verdict even after local metadata regeneration.

## 1.0.1038 RC66.4 — QA & cross-platform reproducibility fix

- Make the SettingsRepository memoization runtime fixture complete and treat PHP warnings as explicit test failures instead of allowing environment-dependent stdout mismatches.
- Make the release build regression portable across Windows Python launchers and declare CPython 3.9+ as an explicit build dependency.
- Canonicalize ZIP entry ordering and platform metadata so identical inputs no longer depend on host path ordering or DOS/Unix ZIP defaults.
- Add cross-platform reproducibility guards while preserving the existing runtime plugin behavior.

## 1.0.1038 RC66.3 — Release integrity & exposure fix

- SEO redirect-loop trace cookies now store only the request path, never query strings, and are issued with explicit `Secure` (on HTTPS), `HttpOnly`, `SameSite=Lax`, and site-wide `/` scope.
- Added the previously omitted RC66.2 change and this RC66.3 change to `release-manifest.json`, so generated provenance transformation chains include both release steps.
- Removed host-specific Node/platform/architecture observations from in-archive provenance to keep identical source + `SOURCE_DATE_EPOCH` builds byte-for-byte reproducible across supported build hosts.
- Corrected the canonical release-candidate build command to place the ZIP outside the plugin root (`../...`) as required by the builder.
- Made build completion output UTF-8-safe on Windows so Unicode destination paths cannot raise `UnicodeEncodeError` after a successful ZIP build.
- Added regression coverage for redirect-cookie privacy/security, release lineage completeness, canonical build-command executability, host-independent provenance, Unicode-safe builder output, and deterministic repeated builds.

## 1.0.1038 RC66.2 — Windows Unicode lint fix

- Replaced child-process `php -l` execution with PHP's in-process parser via `token_get_all(..., TOKEN_PARSE)`.
- PHP QA no longer depends on spawning `PHP_BINARY`, so a Windows `php.exe` path containing spaces or Unicode characters cannot break the lint stage.
- Added regression guards preventing shell/process-based lint from returning.

## 1.0.1038 RC66.1 — Release tooling & assets

- PHP lint now launches `PHP_BINARY -l <file>` through a `proc_open()` argument array instead of a shell command, avoiding Windows `cmd.exe` quoting/codepage failures for Unicode and space-containing paths.
- Replaced the 1.82 MB default contact PNG with an approximately 80 KB WebP and updated admin/frontend production references.
- Hardened the Simple-extras runtime test fixture to use an isolated temporary directory with guaranteed cleanup in `finally`, preventing stale or partial fixtures from leaking into later test runs.
- Added RC66.1 regression guards for shell-free linting, default asset size/format, and fixture cleanup.

## 1.0.1038 RC66.0 — Security / data-exposure hardening

- Observability now records only the request path and never persists URL query strings, preventing booking/account bearer tokens, OAuth codes and WordPress nonces from entering activity logs through `REQUEST_URI`.
- Booking CSV export now uses an explicit allowlist of operational/customer/pricing columns; redirect URLs, external payment identifiers, policy snapshots, cancellation/reschedule tokens, active-slot hashes and other internal state are excluded.
- Facebook OAuth exchanges authorization codes and client secrets via POST body, and sends the profile access token in the `Authorization: Bearer` header instead of URL query parameters.
- Added RC66 regression guards for observability URL hygiene, CSV export boundaries and Facebook OAuth transport.

## RC65.3.1 — Cron self-healing

- Kept the 10-minute full cron audit throttle while adding a cheap persistent-cron self-healing pass on throttled requests.
- Missing required Slotera jobs are now rescheduled immediately, including secure attachment cleanup and PayPal reconciliation.
- Added a cron registry schema marker so upgrading this build invalidates stale audit throttles and forces one full schedule audit.
- Extended Performance diagnostics to report throttled self-heal checks and restored jobs.

## 1.0.1038 RC65.3

- Add rolling performance baselines for representative request contexts (frontend, booking, client account, REST, AJAX, admin, admin-post and cron) while profiling is explicitly enabled.
- Keep only the latest ten samples per request context and surface last/average SQL count, Plugin::init timing, service bootstrap counts and peak memory in Diagnostics.
- Allow explicitly enabled profiling to capture real WP-Cron requests without enabling profiling for anonymous public visitors.
- Display the last PayPal reconciliation timestamp in the configured WordPress timezone instead of mixing a raw UTC timestamp into otherwise local Diagnostics output.
- Keep profiling disabled by default on normal installations; booking, pricing, payment, privacy and lazy-bootstrap behavior are unchanged.
- Add RC65.3 regression coverage for baseline capture, request-context classification and PayPal timestamp localization.

## 1.0.1038 RC65.2

- Lazy-load the heavy MarketingEmailService object only when the marketing queue cron actually runs; normal requests register only lightweight cron callbacks/schedules.
- Centralize persistent Slotera cron schedule auditing behind a ten-minute throttle instead of calling `wp_next_scheduled()` independently from multiple services on every request.
- Preserve cron callbacks, custom schedules, PayPal reconciliation, privacy retention, email reminders, marketing automation, and all RC65.1.x request-context fixes.
- Add regression coverage for lazy marketing bootstrap and centralized cron scheduling.

## 1.0.1038 RC65.1.3

- Fixed magic-link confirmation redirects under RC65.1 lazy admin-post bootstrap by detecting configured account/login shortcode markers directly from published page content instead of relying on runtime shortcode registration.
- Added regression coverage ensuring successful magic-link confirmation redirects to the configured client account rather than the site home page.

## 1.0.1038 RC65.1.2

- Fixed the RC65.1/RC65.1.1 regression where frontend actions posted through `wp-admin/admin-post.php` could lose their Slotera handlers.
- Restored magic-link request/confirmation, client-account logout, cancel/reschedule, customer invoice PDF download, and booking contact-form submission through a strict frontend action whitelist.
- Ordinary wp-admin requests still skip the heavy frontend bundle; only the required AccountController or BookingShortcode component is registered for a matching frontend `admin-post.php` action.
- Added executable runtime coverage for every whitelisted frontend admin-post action and guards proving unrelated admin actions do not enable frontend handlers.
- No pricing, payment, PayPal, privacy, REST/AJAX, or page-detection behavior changed.

## 1.0.1038 RC65.1.1

- Fixed the RC65.1 System Status regression that reported all configured Slotera system pages as missing on ordinary wp-admin requests.
- Page validation now inspects shortcode markup directly instead of depending on WordPress runtime shortcode registration, preserving RC65.1 frontend lazy initialization.
- Added executable regression coverage proving configured system pages remain detectable in admin even when the frontend shortcode bundle is intentionally not registered.
- No booking, pricing, payment, PayPal, privacy, REST/AJAX, or performance behavior changed beyond the page-detection fix.

## 1.0.1038 RC65.1

- Added service-level bootstrap profiling so Diagnostics reports initialized/skipped components and the slowest service registrations with per-registration SQL counts.
- Skip the frontend shortcode/controller bundle on ordinary wp-admin and cron requests, while preserving it for public/REST and admin-ajax requests.
- Register the heavy AdminServiceProvider only for WordPress admin contexts instead of every public request.
- Preserved all booking, payment, PayPal reconciliation, privacy and pricing behavior; this release changes bootstrap registration only.
- Added source and executable runtime regression coverage for public, admin, admin-ajax and cron request gates.

## 1.0.1038 RC65.0.1

- Refreshed only the frozen Frontend UI translation baselines after the intentional `Add extras` localization increased the frozen frontend catalog from 373 to 374 keys.
- Preserved Email Settings and Email Template freeze baselines unchanged.
- No runtime, pricing, payment, privacy, or performance behavior changed.

## 1.0.1038 RC65.0

- Extended the existing admin/dev performance profiler to time the full Slotera `Plugin::init()` path and expose request-local settings-resolution counters in Diagnostics.
- Memoized the profiling enablement gate per request so instrumentation does not repeatedly query capabilities/options.
- Added request-local SettingsRepository memoization across repository instances while still detecting same-request `sltr_settings` mutations via a raw-option fingerprint.
- Avoid repeated legacy normalization, secret decryption, default merging, and email-template localization when settings are unchanged within the request.
- Added executable regression coverage proving repeated settings reads resolve once and re-resolve after an in-request settings change.

## 1.0.1038 RC63.3

- UI: constrain the weekend/dynamic-offer summary container to the same 520px field width as booking form fields and extras; pricing and payment logic are unchanged.

## 1.0.1038 RC63.2

- Localized the customer-facing `Add extras` heading across all bundled frontend locales.
- Matched Simple-mode extras and the discount summary row to the canonical customer-details field width.
- Kept each extra compact as name, price, then checkbox immediately to the right of the price.
- Prevented dynamic offers from being mislabeled as a package discount when package Discount is `None`.
- Added regression guards for extras localization, compact layout, and package-discount labeling.

## 1.0.1038 RC63.1

- Made Booking Request (Simple mode) extra services selectable on the customer details/payment step before checkout.
- Added a server-authoritative Simple booking quote so extras, dynamic offers, coupons, tax, deposits, and full-payment totals stay aligned with the amount sent to the payment gateway.
- Persist selected extras and their line amounts on the booking.
- Show selected extras on Thank You, Checkout, Client Account, admin booking pricing, and email price summaries.
- Added executable regression coverage for a 75 EUR package + 25 EUR delivery extra + 5% dynamic offer + 10% coupon = 85.50 EUR.

## 1.0.1038 RC63

- Unified public AJAX and REST booking payload normalization through one canonical request normalizer.
- REST booking creation now preserves `end_date`, `extra_ids`, `coupon_code`, and `payment_choice` with the same sanitization and defaults as the frontend AJAX path.
- Added aliases for frontend/headless field names while keeping the transport-specific booking `source` marker.
- Normalized extras from REST arrays or comma/whitespace-delimited values into unique positive IDs.
- Added executable PHP runtime regression coverage proving equivalent AJAX/REST payloads reach BookingService with identical booking semantics.

## 1.0.1038 RC62.3

- Added PayPal reconciliation diagnostics: scheduled/next run, last run, checked/completed/pending/failed/error counters, and recent capture results.
- Added a capability- and nonce-protected `Reconcile PayPal now` action to Diagnostics for immediate verification of processing captures against the PayPal API.
- Added `PAYMENT.CAPTURE.PENDING` to required PayPal webhook subscription diagnostics.
- Persist reconciliation run summaries so missed WP-Cron runs and PayPal-side `PENDING_REVIEW` states can be distinguished without guessing.
- Added focused regression coverage for RC62.3 diagnostics and manual reconciliation.

## 1.0.1038 RC62.2

- Fixed the magic-link confirmation hop by using `SameSite=Lax` for the short-lived HttpOnly confirmation cookie, preserving the direct email → confirmation → account flow.
- Added 15-minute PayPal reconciliation for processing captures older than five minutes so missed webhooks do not leave bookings stuck indefinitely.
- Added `PAYMENT.CAPTURE.REVERSED` handling and fail-closed reconciliation for terminal non-success capture states.
- Added focused regression coverage for RC62.2 behavior.

## 1.0.1038 RC62.1

- Fixed client-account magic-link session persistence by making Slotera-owned confirmation/session cookies host-only with site-wide `/` scope instead of inheriting WordPress `COOKIE_DOMAIN`/`COOKIEPATH`.
- Preserve the `Secure` flag when the canonical WordPress home URL is HTTPS, including common reverse-proxy setups.
- Added focused regression coverage for account cookie scope.

## 1.0.1038 RC62
- Privacy erasure now revokes marketing consent while retaining a minimal do-not-contact suppression marker, preventing erased contacts from becoming marketable again without fresh consent.
- WordPress personal-data export/erasure now covers payment invoices and transactions; retained financial records have customer PII fields anonymized while accounting values remain intact.
- Magic-link credentials are exchanged for a short-lived HttpOnly confirmation handle and redirected to a clean account URL before the WordPress frontend stack is rendered; confirmation forms no longer contain email or bearer token fields.
- Temporary PDF/ICS mail attachments are now stored only in writable temporary storage outside the web root and fail closed instead of falling back to public uploads.
- Added focused RC62 regression coverage for the security/privacy changes above.

## Earlier release-candidate history

Detailed pre-RC62 transformation history is retained in `release-manifest.json`, which is included in the signed release materials.
