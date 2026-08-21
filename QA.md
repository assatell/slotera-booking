# Reproducible release QA

This release package includes self-contained QA entry points that do not require the private source repository.

## Requirements

- PHP 8.0 or newer
- Composer 2 (optional command runner)
- Node.js 20 or newer and pnpm 9.15.9 for JavaScript regression checks
- CPython 3.9 or newer for deterministic release packaging (`PYTHON` may point to the interpreter; otherwise QA/build tooling tries `py -3`, `python3`, then `python`)

## Commands

- `php tools/qa.php qa` — PHP syntax lint, security regression checks and release-content verification.
- `composer qa` — equivalent Composer entry point; no Composer packages are installed.
- `corepack pnpm install --frozen-lockfile && pnpm test` — Node regression checks using the included lockfile and no third-party packages.
- `sha256sum -c checksums.sha256` — verify packaged files. Run after extraction from the plugin root.
- `node tools/release-metadata.mjs verify` — verify that all version fields, provenance and file hashes match `release-manifest.json`.
- Use the exact `build.command` recorded in `build-provenance.json` to regenerate metadata and package the archive. The portable Node launcher resolves the declared Python runtime without assuming a `python3.exe` shim on Windows.
- Verify the external trust bundle with `node tools/release-attestation.mjs verify <archive.zip> <attestation.json> <signature.sig> <public.pem> [extracted-plugin-root]` and compare the reported key ID through an independent trusted channel.

`release-manifest.json` is the single version source. `tools/release-metadata.mjs prepare` synchronizes the plugin header, runtime constants, package metadata and README before regenerating provenance and checksums. Provenance records the builder version, source artifact hash, VCS availability, exact build command and the known transformation chain. Missing historical evidence is marked explicitly rather than inferred.

`checksums.sha256` includes `build-provenance.json`. The checksum manifest, provenance, release manifest and archive digest are bound by the external signed attestation. Verification is fail-closed: required materials are mandatory, and when an extracted root is supplied every extracted file is compared directly with the signed ZIP. The private signing key must remain outside the plugin tree and release output directory.

The production archive intentionally excludes PHPUnit caches, VCS metadata, local dependencies and private CI credentials. Mandatory Node QA is read-only with respect to the release tree; reproducibility builds run only in temporary sandbox copies. The release builder uses the in-tree deterministic fixed-Huffman DEFLATE encoder (independent of host zlib) and enforces a strict ZIP size gate below 8 MiB. The included tests verify release-critical behavior and package integrity; full integration testing against WordPress, databases and payment sandboxes remains part of the upstream CI pipeline.

## Release metadata pipeline

- Edit the current release version only in `release-manifest.json`.
- `node tools/release-metadata.mjs prepare` generates all runtime/package version fields from the manifest, then regenerates provenance and checksums.
- Official packaging should use `tools/build-release.ps1` and pass `-SourceArtifactPath` so the builder verifies the source artifact SHA-256 recorded in the manifest.
- `build-provenance.json` records the exact build command, builder/runtime details, VCS commit/tag/dirty state (or explicit source-archive state), source SHA-256, tree hashes and ordered transformation chain.
- The final ZIP SHA-256 remains outside the ZIP and is bound by the detached RSA-PSS attestation, avoiding self-reference.
### Future Update URI binding

Release candidates intentionally omit the WordPress `Update URI` header until the official Slotera update endpoint exists. When that endpoint is ready, canonical release builds can bind it explicitly with `SLTR_UPDATE_URI=https://...`; release metadata preparation validates HTTPS/no-credentials/no-fragment and synchronizes both the plugin header and `SLTR_UPDATE_URI` runtime constant.

