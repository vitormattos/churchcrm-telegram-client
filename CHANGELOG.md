<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

## 0.1.0 — 2026-11-12

Initial experimental community release.

- ChurchCRM administrator interface for Telegram Client API (MTProto) with MadelineProto and a separately deployed private worker.
- QR authentication, two-step verification, account status, optional avatar and explicit logout.
- Individual, consent-confirmed text messages; sending disabled by default and no automatic retries.
- Scoped admin routes, CSRF checks, restricted worker host, private persistent sessions, lock and integration smoke tests.
- Reproducible, separately published plugin and worker artifacts with SHA-256 checksums.

**Not yet validated:** login against the church's live Telegram account and real message delivery. No Bot API, bulk-send features or contact import.
