<!-- SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later -->
# Installation

1. Review and install the pinned plugin ZIP through the ChurchCRM installer when it is approved, or mount the plugin code read-only at /var/www/html/src/plugins/community/telegram-client for development. Do not alter ChurchCRM core. The release ZIP does not provision the worker.
2. Extract the matching telegram-client-worker-X.Y.Z.tar.gz release into a private deployment directory (or use worker/ from a checked-out source revision). Place the worker files on the same host in their own directory. Create worker/state with owner UID/GID 33:33 and mode 0700. Keep the state volume outside web roots and never share Two Factor Gateway sessions.
3. In the worker deployment directory, copy .env.example to .env; set GATEWAY_SECRET to a random secret of at least 32 characters. Restrict .env to mode 0600.
4. Attach the worker to the pre-existing church-crm_internal Docker bridge, adapting compose.yaml if the network name differs. Start only the worker with docker compose up -d --build. The source-tree layout keeps worker/ as the Compose directory; the release archive places the same files in telegram-client-worker/. Never publish port 8080. Never expose host ports.
5. Recreate only ChurchCRM PHP with the new plugin mount. Enable the plugin under Admin → Plugin Management, provide API ID, API hash, internal worker URL http://telegram-client:8080 and the matching secret.
6. Keep sending disabled until successful login and verification.

The plugin is not necessarily in the ChurchCRM official approved-list registry; a local bind mount is appropriate for an audited development installation.

## Updates and rollback
Retain worker/state and worker/.env across upgrades. Rebuild only the worker and restore the previous plugin directory and worker image to roll back. Do not delete the session directory to troubleshoot transient errors. Never operate two workers on the same session.
