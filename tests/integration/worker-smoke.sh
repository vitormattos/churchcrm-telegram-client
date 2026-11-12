#!/bin/sh
# SPDX-FileCopyrightText: 2026 Vitor Mattos
# SPDX-License-Identifier: AGPL-3.0-or-later
set -eu
IMAGE="${1:-churchcrm-telegram-worker:test}"
SECRET="$(openssl rand -hex 32)"
NAME="churchcrm-telegram-smoke-$$"
docker run --rm -d --name "$NAME" -e "GATEWAY_SECRET=$SECRET" \
    --tmpfs /state:rw,uid=33,gid=33,mode=0700 \
    -p 127.0.0.1::8080 "$IMAGE" >/dev/null
trap 'docker stop "$NAME" >/dev/null 2>&1 || true' EXIT HUP INT TERM
PORT="$(docker port "$NAME" 8080/tcp | sed -n 's/^127.0.0.1://p')"
[ -n "$PORT" ]
for i in 1 2 3 4 5 6 7 8 9 10; do
    if curl --silent --output /dev/null "http://127.0.0.1:$PORT/noop"; then break; fi
    sleep 1
done
post() {
    curl --silent --output /dev/null --write-out '%{http_code}' \
        -X POST -H 'Content-Type: application/json' "$@" "http://127.0.0.1:$PORT/status"
}
UNAUTH="$(post -d '{}')"
[ "$UNAUTH" = 403 ] || { echo "unauthorized check failed: $UNAUTH"; exit 1; }
MALFORMED="$(post -H "Authorization: Bearer $SECRET" -d '{}')"
[ "$MALFORMED" = 422 ] || { echo "invalid credentials check failed: $MALFORMED"; exit 1; }
NOT_FOUND="$(curl --silent --output /dev/null --write-out '%{http_code}' \
    -X POST -H "Authorization: Bearer $SECRET" -d '{}' \
    "http://127.0.0.1:$PORT/not-a-valid-operation")"
[ "$NOT_FOUND" = 404 ] || { echo "unsupported route check failed: $NOT_FOUND"; exit 1; }
docker exec -d "$NAME" php -r '$handle=fopen("/state/telegram.lock","c"); flock($handle,LOCK_EX); touch("/state/test-locked"); sleep(8);' >/dev/null
for i in 1 2 3 4 5 6 7 8 9 10; do
    if docker exec "$NAME" test -f /state/test-locked; then break; fi
    sleep 1
done
docker exec "$NAME" test -f /state/test-locked
BUSY="$(post -H "Authorization: Bearer $SECRET" -d '{"api_id":12345,"api_hash":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"}')"
[ "$BUSY" = 423 ] || { echo "session concurrency check failed: $BUSY"; exit 1; }
echo 'Worker smoke tests passed (403, 422, 404, 423); no Telegram account or send.'
