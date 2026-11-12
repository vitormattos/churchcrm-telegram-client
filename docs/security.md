<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Security, risk and privacy

The user must be a ChurchCRM admin; mutating routes use CSRF validation. Worker access is restricted by a private Docker network and a high-entropy shared secret. Worker session data belongs to UID 33 in a volume outside ChurchCRM's document root. Do not publish QR data, phone numbers, message contents, session files, password fields, API credentials or gateway secrets.

Limitations: ChurchCRM built-in plugin settings masking is not database encryption. Docker bridge HTTP transport is not cryptographically encrypted. Isolate untrusted containers; use stronger transport controls when threat modeling requires them. The dedicated worker uses PHP's single-process internal server, intended for low-volume deployments, not high throughput. Account profile picture retrieval is best-effort, capped at 512 KiB, limited to JPEG, PNG and WebP, and requested only after login. The avatar is returned to an authenticated administrator without persistent caching. Follow Telegram terms and local privacy laws, including LGPD where applicable. Obtain and document recipient consent.
