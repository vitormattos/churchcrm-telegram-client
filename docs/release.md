<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Release and ChurchCRM community registry

For each version, bump `plugin.json` and `CHANGELOG.md`, run all CI checks, and sign a matching `vX.Y.Z` Git tag. The release script packages a **plugin-only ZIP** (`telegram-client-X.Y.Z.zip`) and a **separate worker archive** (`telegram-client-worker-X.Y.Z.tar.gz`), each with a checksum.

```sh
git tag -s v0.1.0 -m "Release v0.1.0"
sh scripts/package-release.sh v0.1.0 dist
(cd dist && sha256sum -c *.sha256)
unzip -l dist/telegram-client-0.1.0.zip
tar -tzf dist/telegram-client-worker-0.1.0.tar.gz
```

The ChurchCRM registry references **only** the plugin ZIP URL and its exact SHA-256. Installing the plugin ZIP does **not** install MadelineProto, a worker, Docker networks or a Telegram account. The administrator must provision the separate worker using [installation instructions](installation.md).

Before opening the catalog PR, run the ChurchCRM official `scripts/plugin/plugin-scan.php` against an extracted copy of the published ZIP. Verify the downloaded release byte-for-byte and the signed tag. Run a live ChurchCRM smoke test in a controlled instance, ideally the unverified URL-install path. Summarize test results and limitations, capabilities and their evidence, the HIGH risk classification, supply-chain provenance, and the private worker dependency in the PR body.

Submit a PR against `ChurchCRM/CRM` branch `External`, changing only `approved-plugins.json`, with `id=telegram-client`, immutable release `downloadUrl`, `sha256`, `risk=high`, `riskSummary`, `permissions=[network.outbound, network.inbound, secrets.store, fs.read]`, `minimumCRMVersion=7.0.0` and link to this audit. Leave `reviewedAt` absent until actual maintainers review the artifact. Do not merge without two independent security reviews.
