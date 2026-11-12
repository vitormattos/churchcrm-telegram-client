<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Development

The ChurchCRM adapter and administration route/view are under src/, routes/ and views/. worker/server.php implements the MadelineProto-facing private API, with a persistent session directory and advisory lock. Worker dependencies are not loaded into ChurchCRM's vendor tree. Tests live in tests/php/Unit, tests/php/Integration and tests/ui. Tool Composer installations use vendor-bin/.

Two Factor Gateway provided reference contracts for qrLogin(), getAuthorization(), complete2faLogin(), getSelf(), contacts.resolvePhone and messages.sendMessage. Nextcloud-specific app-data and its insecure CLI 2FA password option were deliberately not copied.

Run composer install, composer bin all install, composer test:unit, composer lint:php, npm ci and npm run test:ui. Build the isolated worker via docker build worker/. Automated tests must never send real messages or require Telegram credentials.
