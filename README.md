<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# ChurchCRM Telegram Client

A community ChurchCRM plugin for connecting a real Telegram user account, such as the church's phone, through **Telegram MTProto** and [MadelineProto](https://github.com/danog/MadelineProto). No Telegram Bot API or BotFather tokens.

## Features
- Administrator-only QR login, two-step verification, account status and explicit session logout.
- Persistent, dedicated Telegram session isolated in a private PHP worker.
- Individually reviewed, consented plain-text messages to resolvable Telegram usernames or phones.
- Sending disabled by default, no bulk campaigns or automatic retries.
- Restricted files, administrative access, CSRF protection and private worker authentication.

**Status: experimental.** Live Telegram authentication and delivery require authorized real-world testing.

## Requirements
ChurchCRM 7, Docker Compose, API credentials from [my.telegram.org/apps](https://my.telegram.org/apps), and a Telegram account controlled by the church.

## Documentation
[Installation](docs/installation.md) · [Configuration](docs/configuration.md) · [QR authentication](docs/authentication.md) · [Sending](docs/sending.md) · [Operations](docs/operations.md) · [Security](docs/security.md) · [Development](docs/development.md)

## Sponsor the maintainer

This independent community project is maintained by **Vitor Mattos**. Support ongoing development and maintenance through [GitHub Sponsors](https://github.com/sponsors/vitormattos). Sponsorship is for the individual maintainer, not LibreCode.

Contributions welcome. Never share API credentials, live QR tokens, session files or message content in issues.
