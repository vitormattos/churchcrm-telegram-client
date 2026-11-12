<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Authentication and session lifecycle

An administrator opens Telegram in the ChurchCRM sidebar, chooses Show login QR Code, and scans the code with the church's Telegram mobile app under Settings → Devices → Link Desktop Device. A code expires; request a new one if necessary. If Telegram requests the existing two-step verification password, enter it through the ChurchCRM page. The password is never supplied via process arguments or logged. On success the connected account name, username and profile photo appear when available. Profile photos are retrieved only once per browser session/account change. Disconnect intentionally revokes the Telegram session; restart/update alone must not reset it.

QR tokens are sensitive short-lived login credentials. Never post screenshots of an active QR code. An authenticated account must not be re-paired for ordinary status checks.
