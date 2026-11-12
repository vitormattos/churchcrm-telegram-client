<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Sending

Sending defaults to disabled. Authenticate, then opt in under plugin settings. Enter a Telegram @username or a +countrycode phone number; validate the resolved recipient (phone-based resolution depends on Telegram privacy settings). A ChurchCRM person record does not establish Telegram reachability or consent. Review the text and confirm consent before confirming the one-time send. Plain text is supported, up to 2000 characters. There are no campaigns, scheduled sends or automatic retries.

Honor FLOOD_WAIT, other Telegram rate limits, account restrictions and anti-spam terms. A communication failure after a send may have an uncertain outcome: check the Telegram conversation before any manual resend.
