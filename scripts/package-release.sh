#!/bin/sh
# SPDX-FileCopyrightText: 2026 Vitor Mattos
# SPDX-License-Identifier: AGPL-3.0-or-later
set -eu

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
    echo "Usage: $0 <signed-tag-or-commit> [output-directory]" >&2
    exit 2
fi
ref=$1
output=${2:-dist}
root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$root"
git rev-parse --verify "${ref}^{commit}" >/dev/null
version=$(git show "${ref}:plugin.json" | python3 -c 'import json,sys; print(json.load(sys.stdin)["version"])')
case "$ref" in
    v*) [ "$ref" = "v$version" ] || { echo "Release tag and plugin version disagree" >&2; exit 1; } ;;
esac
case "$output" in
    /*) destination=$output ;;
    *) destination="$root/$output" ;;
esac
mkdir -p "$destination"
stage=$(mktemp -d "$root/.packaging.XXXXXX")
trap 'rm -rf "$stage"' EXIT HUP INT TERM

mkdir -p "$stage/plugin/telegram-client" "$stage/worker"
git archive --format=tar "$ref" plugin.json src routes views README.md CHANGELOG.md LICENSE SECURITY.md docs \
    | tar -xf - -C "$stage/plugin/telegram-client"
git archive --format=tar "$ref" worker/.env.example worker/Dockerfile worker/compose.yaml \
    worker/composer.json worker/composer.lock worker/server.php \
    | tar -xf - -C "$stage/worker"
mv "$stage/worker/worker" "$stage/worker/telegram-client-worker"

timestamp=$(git show -s --format=%ct "${ref}^{commit}")
find "$stage/plugin" "$stage/worker" -exec touch -h -d "@$timestamp" {} +

plugin_file="telegram-client-${version}.zip"
worker_file="telegram-client-worker-${version}.tar.gz"
(cd "$stage/plugin" && find telegram-client -type f | LC_ALL=C sort \
    | zip -X -q "$destination/$plugin_file" -@)
(cd "$stage/worker" && tar --sort=name --mtime="@$timestamp" \
    --owner=0 --group=0 --numeric-owner --format=ustar \
    -cf - telegram-client-worker | gzip -n > "$destination/$worker_file")
(cd "$destination" && sha256sum "$plugin_file" > "$plugin_file.sha256" \
    && sha256sum "$worker_file" > "$worker_file.sha256")
printf 'PLUGIN=%s\nWORKER=%s\n' "$destination/$plugin_file" "$destination/$worker_file"
cat "$destination/$plugin_file.sha256" "$destination/$worker_file.sha256"
