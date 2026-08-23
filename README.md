# Slotera Booking

Release candidate package for Slotera Booking v1.0.1041.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- HTTPS for live payment gateways and customer account links

## Install

1. Upload this ZIP in WordPress Admin → Plugins → Add New → Upload Plugin.
2. Activate Slotera Booking.
3. Open Slotera → Onboarding / Settings and configure system pages, working hours, payment settings, email sender and security options.
4. Add the needed shortcodes to pages, for example `[slotera_booking]`, `[slotera_checkout]`, `[slotera_account]`, `[slotera_login]`.

## Release checklist

- Configure SMTP sender and send a test email.
- Use live payment credentials only on the live site.
- Configure provider webhook URLs from Slotera → Payment Diagnostics.
- Keep “Remove data on uninstall” disabled unless the owner explicitly wants full data deletion.
- Review Slotera → Security before accepting public bookings.
- Test one full booking, cancellation and reschedule flow after activation.

## Quality assurance

- `php tools/qa.php qa` runs self-contained PHP syntax, security-regression and release-content checks.
- `composer qa` provides the same reproducible entry point without third-party Composer dependencies.
- `corepack pnpm install --frozen-lockfile && pnpm test` runs the included Node regression suite.
- `sha256sum -c checksums.sha256` verifies package contents.
- `release-manifest.json` is the single source of truth for the current release version and build policy. All version-bearing runtime files are generated from it.
- Official archives are accompanied by a detached attestation, RSA-PSS signature and public key outside the ZIP.
- See `QA.md`, `release-manifest.json` and `build-provenance.json` for exact build command, builder/runtime version, VCS commit/tag state, source hash, release-tree hashes and the full transformation chain.

## License

Slotera Booking is free software licensed under **GPL-2.0-or-later**. See `LICENSE`.

Bundled third-party material is documented in `THIRD-PARTY-NOTICES.md`. A CycloneDX software bill of materials for this release is provided as `sbom.cdx.json`.
