<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Configuration

Visit https://my.telegram.org/apps and register a Telegram application using the church account. Record the numeric api_id and 32-hex-character api_hash. The ChurchCRM plugin management page configures those, the private worker URL (usually http://telegram-client:8080), and GATEWAY_SECRET matching the worker's private .env. Enable sending only after login and explicit review of consent policy.

The ChurchCRM standard password setting masks values, but does not itself guarantee encryption of stored configuration. Treat ChurchCRM database access and backups as sensitive; never publish or log these values. The worker accepts credentials in authenticated request bodies and stores MTProto session state only in its private volume.
