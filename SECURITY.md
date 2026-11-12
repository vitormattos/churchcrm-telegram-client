<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Security policy

## Reporting vulnerabilities

Please use the repository's [private vulnerability reporting](https://github.com/vitormattos/churchcrm-telegram-client/security/advisories/new) when available. If it is unavailable, contact the maintainer privately through the contact methods linked in their GitHub profile; never open a public issue containing a live token, API hash, two-step password, private session, QR login code, recipient information or message text.

Describe the affected tag/commit, reproduction steps using dummy data, the trust boundary affected and the potential impact. Do not access anyone else's Telegram account to demonstrate a problem. Maintainers will triage the report and coordinate a fix before disclosure.

## Supported versions

The initial v0.1.0 release is experimental. Only the latest published release receives security maintenance; the ChurchCRM catalog requires a fresh review and SHA-256 pin for each version.

The ChurchCRM administrator is responsible for protecting stored plugin settings, private worker state, network access, host backups and recipient consent. The MTProto session is an account credential and must not be uploaded to issues, logs or public artifacts. The worker uses an unofficial Telegram client integration and does not claim endorsement by Telegram.
