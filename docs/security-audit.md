<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Initial release security assessment: v0.1.0

This is an author assessment for catalog review, **not** an independent security approval. ChurchCRM maintainers must inspect the pinned ZIP and independently verify its SHA-256.

**Proposed classification: HIGH.** Message text and personally identifying Telegram identifiers are transferred through a privileged private worker and then to Telegram servers using MTProto. Account credentials, a shared worker secret and a highly sensitive persistent session exist outside the ZIP. A compromised worker or session can impersonate the connected Telegram account.

## Permission inventory

| ChurchCRM capability | Used? | Reason |
| --- | --- | --- |
| `network.outbound` | Yes | `src/TelegramGatewayClient.php` makes authenticated HTTP requests to a private worker; worker makes MTProto connections |
| `network.inbound` | Yes | `routes/routes.php` exposes protected admin pages and API endpoints |
| `secrets.store` | Yes | `plugin.json` and `src/TelegramClientPlugin.php` configure API hash and worker secret using ChurchCRM settings |
| `db.read` | No | No ChurchCRM domain record reads |
| `db.write` | No | Uses only ChurchCRM settings; no direct database mutation |
| `fs.read` | Yes | `src/TelegramClientPlugin.php` reads its bundled manifest; worker reads private session outside ChurchCRM |
| `fs.write` | No (plugin); Yes (worker) | The separately deployed worker writes sensitive session state to `/state` |
| `ui.inject` | No | Uses its own admin page and declared menu |
| `cron` | No | No scheduled jobs |
| `hooks.person` | No | No person hook |
| `hooks.family` | No | No family hook |
| `hooks.event` | No | No event hook |
| `hooks.financial` | No | No financial hook |
| `hooks.email` | No | No email hook |
| `email.send` | No | No email sending |
| `sms.send` | No | Telegram only |
| `calendar.register` | No | No calendar registration |

## Security boundaries

- Admin pages and API require ChurchCRM admin authorization. Mutating routes enforce a CSRF token.
- Worker uses a private Docker network, an authorization bearer key of at least 32 characters, a restricted internal hostname and disabled HTTP redirects.
- MTProto credentials are not shipped in release assets. A successful login creates reusable session credentials in the separate, private worker state volume.
- QR login links are account-authorization credentials. They are generated on demand and must not be logged or included in screenshots.
- Browser receives profile name, optional username/avatar and short-lived QR image, but not API hash, worker secret or session files.
- No recipient harvesting, batch messaging, automated sending, or automatic retry after ambiguous delivery.
- Administrator checkbox is not persistent evidence of consent. Consent must be tracked separately.
- Plugin settings may be stored unencrypted in ChurchCRM's database: protect database access and backups. Docker bridge traffic is not encrypted.
- Worker uses PHP's single-process development server and is designed only for low-volume, private-network use. Keep it unreachable from public interfaces.
- Telegram can rate-limit, block or alter unofficial client behavior. Do not attempt to bypass restrictions.

## Release review evidence

Confirm signed tag and commit, deterministic runtime ZIP and worker archive, SHA-256 checksum, no embedded .env/session/vendor, syntactic checks, PHPUnit, Psalm taint, browser tests, worker build and smoke, and ChurchCRM official plugin scanner on the *extracted runtime ZIP*.

**Limits:** Real-account QR pairing, live 2FA and actual Telegram delivery have not been validated as part of automated tests. No full ChurchCRM URL-installer lifecycle has yet been asserted. Do not mark these steps successful without live evidence. Two ChurchCRM maintainer reviews are required by the high-risk approval process.

See [SECURITY.md](../SECURITY.md) for private reports.
