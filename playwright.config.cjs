// SPDX-FileCopyrightText: 2026 Vitor Mattos
// SPDX-License-Identifier: AGPL-3.0-or-later
const {defineConfig} = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/ui',
  timeout: 12000,
  retries: 0,
  use: {browserName: 'chromium', headless: true},
  reporter: [['list']],
});
