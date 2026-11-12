<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Operations and troubleshooting

- Worker unavailable: inspect docker compose ps, internal URL, network attachment and sanitized worker logs.
- No QR: verify API credentials, persistent directory ownership and session lock.
- 2FA requested: supply the Telegram account's existing two-step password, not a new password.
- Session disconnected: check Telegram Devices, access restrictions and retained worker/state.
- Busy session: wait for the active operation. Never start multiple workers against one session.
- FLOOD_WAIT: do not evade Telegram limits or automatically retry.
- Unknown delivery: do not blindly resend.
- Upgrade and rollback: preserve state, stop the old worker, change the plugin/worker versions, and restart only affected services.

The browser refreshes authentication state approximately every 12 seconds while the page is open. The worker serializes requests and does not support high-volume traffic. Enforce log rotation and restrict access to session directory backups.
