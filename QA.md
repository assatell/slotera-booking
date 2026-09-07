# Reproducible release QA

The public tagged source includes self-contained QA entry points that do not require a private repository. The WordPress installation ZIP is a runtime-only artifact and intentionally excludes source tests, build tools and development command runners.

## Requirements

- PHP 8.0 or newer
- Composer 2 (optional command runner)
- Node.js 20 or newer and pnpm 9.15.9 for JavaScript regression checks
- CPython 3.9 or newer for deterministic release packaging (`PYTHON` may point to the interpreter; otherwise QA/build tooling tries `py -3`, `python3`, then `python`)

## Commands from the exact tagged source checkout

- `php tools/qa.php qa` — PHP syntax lint, security regression checks and release-content verification.
- `composer qa` — equivalent Composer entry point; no Composer packages are installed.
- `corepack pnpm install --frozen-lockfile && pnpm test` — Node regression checks using the included lockfile and no third-party packages.
- `sha256sum -c checksums.sha256` — verify the canonical installation payload. Run after extracting the ZIP from the plugin root.
- `node tools/release-metadata.mjs verify` — verify that all version fields, provenance and file hashes match `release-manifest.json`.
- Use the exact `build.command` recorded in `build-provenance.json` to package the already committed metadata. The portable Node launcher resolves the declared Python runtime without assuming a `python3.exe` shim on Windows.
- Verify the external trust bundle with `node tools/release-attestation.mjs verify <archive.zip> <attestation.json> <signature.sig> <public.pem> [extracted-plugin-root]` and compare the reported key ID through an independent trusted channel.
- Official release signing key and SHA-256 fingerprint: `https://getslotera.com/.well-known/slotera-release-signing-key.txt`. Treat a public key bundled with a release as untrusted until it matches this HTTPS trust anchor.

`release-manifest.json` is the single version source. Before the release commit, `tools/release-metadata.mjs prepare` synchronizes version fields and regenerates provenance/checksums. These generated files are committed. `node tools/release-gate.mjs pretag` then runs mandatory QA without changing the future tag target. After tagging, `node tools/release-gate.mjs tag` additionally requires a clean checkout at the exact manifest tag. Packaging only verifies committed metadata; it never regenerates it. Provenance binds the expected tag and release-tree hash without an impossible in-commit commit-hash self-reference. The detached signed attestation records the actual tag and commit.

`checksums.sha256` includes `build-provenance.json`. The checksum manifest, provenance, release manifest and archive digest are bound by the external signed attestation. Verification is fail-closed: required materials are mandatory, and when an extracted root is supplied every extracted file is compared directly with the signed ZIP. The private signing key must remain outside the plugin tree and release output directory.

The production archive intentionally excludes source tests, build/QA tools, development command runners, VCS metadata, local dependencies and private CI credentials. Those materials remain available in the exact public Git tag for independent reproduction. Mandatory Node QA is read-only with respect to the source tree; reproducibility builds run only in temporary sandbox copies. Before hashing and packaging, the builder canonicalizes text payloads to LF, then uses the in-tree deterministic fixed-Huffman DEFLATE encoder (independent of host checkout line endings and host zlib). It also enforces a strict ZIP size gate below 8 MiB. The tagged tests verify release-critical behavior and package integrity; full integration testing against WordPress, databases and payment sandboxes remains part of the upstream CI pipeline.

## Release metadata pipeline

- Edit the current release version only in `release-manifest.json`.
- `node tools/release-metadata.mjs prepare` generates all runtime/package version fields, provenance and checksums before the release commit.
- Commit every generated metadata change, then run `node tools/release-gate.mjs pretag`. The command must finish with a clean tree.
- After creating the immutable tag, run `node tools/release-gate.mjs tag`; exact tag, metadata identities, PHP QA, all Node tests and `git diff --exit-code` are mandatory.
- Official packaging must run from the clean tagged Git checkout named by `release-manifest.json`. `tools/build-release.ps1` enforces VCS-bound provenance; optional `-SourceArtifactPath` verifies only the historical lineage artifact and is not the direct release source.
- `build-provenance.json` records the expected tag, exact build command, builder/runtime details, tree hashes and ordered transformation chain. The external signed attestation records the resolved exact tag commit.
- The final ZIP SHA-256 remains outside the ZIP and is bound by the detached RSA-PSS attestation, avoiding self-reference.
### Update URI binding

Release candidates bind the WordPress `Update URI` header to the official first-party Slotera namespace at `https://getslotera.com/?plugin=slotera-booking`. Release metadata preparation validates HTTPS/no-credentials/no-fragment and synchronizes both the plugin header and `SLTR_UPDATE_URI` runtime constant. `SLTR_UPDATE_URI=https://...` remains available as an explicit controlled-build override.

Release candidates use manual update delivery. Security fixes are published as a newly tagged and signed archive; administrators must verify the detached signature and install the update promptly. Automatic signed metadata delivery, revocation and rollback are required before the stable public production channel.
