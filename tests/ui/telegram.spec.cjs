// SPDX-FileCopyrightText: 2026 Vitor Mattos
// SPDX-License-Identifier: AGPL-3.0-or-later
const {test, expect} = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

function fixture() {
  const source = fs.readFileSync(path.resolve(__dirname, '../../views/manage.php'), 'utf8');
  return source
    .replace(/<\?=\s*json_encode\(\$token,[\s\S]*?\)\s*\?>/g, JSON.stringify('test-csrf'))
    .replace(/<\?[\s\S]*?\?>/g, '')
    .replace('<script nonce="">', '<script>');
}

async function setup(page, state) {
  await page.addInitScript(() => { window.CRM = {root: ''}; });
  await page.route('http://churchcrm.test/', (route) =>
    route.fulfill({status: 200, contentType: 'text/html', body: fixture()}));
  await page.route('http://churchcrm.test/plugins/telegram-client/api/status', (route) =>
    route.fulfill({status: 200, contentType: 'application/json', body: JSON.stringify({success: true, data: state})}));
  await page.goto('http://churchcrm.test/');
}

test('unconfigured worker is explained and sending stays disabled', async ({page}) => {
  await setup(page, {state: 'unconfigured', sendingEnabled: false});
  await expect(page.locator('#tg-status')).toContainText('Configure Telegram API credentials');
  await expect(page.locator('#tg-qr-button')).toBeDisabled();
  await expect(page.locator('#tg-send')).toBeDisabled();
});

test('disconnected account displays safe QR image in-page', async ({page}) => {
  await setup(page, {state: 'disconnected', sendingEnabled: false});
  await page.route('http://churchcrm.test/plugins/telegram-client/api/qr', (route) =>
    route.fulfill({status: 200, contentType: 'application/json',
      body: JSON.stringify({success: true, data: {state: 'qr', image: 'data:image/png;base64,aGVsbG8=', expiresIn: 25}})}));
  await page.locator('#tg-qr-button').click();
  await expect(page.locator('#tg-qr-area')).toBeVisible();
  await expect(page.locator('#tg-qr-expires')).toContainText('25 seconds');
});

test('two-factor required state exposes password form but not sending', async ({page}) => {
  await setup(page, {state: 'needs_2fa', hint: 'church', sendingEnabled: false});
  await expect(page.locator('#tg-2fa-area')).toBeVisible();
  await expect(page.locator('#tg-hint')).toContainText('church');
  await expect(page.locator('#tg-send')).toBeDisabled();
});

test('authenticated but sending disabled keeps review allowed and send blocked', async ({page}) => {
  await setup(page, {state: 'authenticated', account: {name: 'Church', username: 'church_account'}, sendingEnabled: false});
  await expect(page.locator('#tg-status')).toContainText('connected');
  await expect(page.locator('#tg-account')).toContainText('church_account');
  await expect(page.locator('#tg-resolve')).toBeEnabled();
  await expect(page.locator('#tg-send')).toBeDisabled();
});

test('expired QR code is automatically renewed while page remains open', async ({page}) => {
  await setup(page, {state: 'disconnected', sendingEnabled: false});
  let requests = 0;
  await page.route('http://churchcrm.test/plugins/telegram-client/api/qr', (route) => {
    requests++;
    return route.fulfill({status: 200, contentType: 'application/json',
      body: JSON.stringify({success: true, data: {state: 'qr', image: 'data:image/png;base64,aGVsbG8=', expiresIn: 1}})});
  });
  await page.locator('#tg-qr-button').click();
  await expect.poll(() => requests, {timeout: 7000}).toBeGreaterThan(1);
});

test('two-factor password is erased from the form after completion', async ({page}) => {
  const account = {state: 'needs_2fa', sendingEnabled: false};
  await setup(page, account);
  await page.route('http://churchcrm.test/plugins/telegram-client/api/2fa', (route) => {
    account.state = 'authenticated';
    account.account = {name: 'Church', username: 'church_account'};
    return route.fulfill({status: 200, contentType: 'application/json',
      body: JSON.stringify({success: true, data: {state: 'authenticated'}})});
  });
  await page.locator('#tg-password').fill('password-used-only-for-login');
  await page.locator('#tg-2fa-area button').click();
  await expect(page.locator('#tg-password')).toHaveValue('');
  await expect(page.locator('#tg-status')).toContainText('connected');
});

test('an uncertain send response does not trigger automatic retry', async ({page}) => {
  await setup(page, {state: 'authenticated', account: {name: 'Church'}, sendingEnabled: true});
  let sends = 0;
  await page.route('http://churchcrm.test/plugins/telegram-client/api/send', (route) => {
    sends++;
    return route.fulfill({status: 503, contentType: 'application/json',
      body: JSON.stringify({success: false, message: 'Telegram service unavailable'})});
  });
  page.on('dialog', dialog => dialog.accept());
  await page.locator('#tg-recipient').fill('@recipient_user');
  await page.locator('#tg-message').fill('Test message');
  await page.locator('#tg-consent').check();
  await page.locator('#tg-send').click();
  await expect(page.locator('#tg-send-feedback')).toContainText('Do not retry automatically');
  expect(sends).toBe(1);
});
