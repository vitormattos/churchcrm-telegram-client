<?php
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Utils\CSRFUtils;
require SystemURLs::getDocumentRoot() . '/Include/Header.php';
$token = CSRFUtils::generateToken();
?>
<div class="card mb-3">
  <div class="card-header"><h3 class="card-title"><i class="fa-brands fa-telegram me-2"></i>Telegram Client API</h3></div>
  <div class="card-body">
    <p>Configure API ID, API hash and private worker settings in <a href="<?= htmlspecialchars(SystemURLs::getRootPath(), ENT_QUOTES) ?>/plugins/management">Plugin Management</a>. Only administrators can access this page.</p>
    <div id="tg-status" class="alert alert-info" role="status" aria-live="polite">Checking connection…</div>
    <div id="tg-account" class="mb-2"></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="button" class="btn btn-outline-secondary" id="tg-refresh">Refresh</button>
      <button type="button" class="btn btn-primary" id="tg-qr-button">Show login QR Code</button>
      <button type="button" class="btn btn-outline-danger" id="tg-disconnect" disabled>Disconnect</button>
    </div>
    <div id="tg-qr-area" class="d-none text-center">
      <img id="tg-qr" width="280" height="280" class="border rounded bg-white p-2" alt="Telegram device login QR Code">
      <p>On the church phone: Telegram → Settings → Devices → Link Desktop Device.</p>
      <p id="tg-qr-expires" class="text-body-secondary"></p>
    </div>
    <form id="tg-2fa-area" class="d-none">
      <label for="tg-password">Telegram two-step verification password</label>
      <input id="tg-password" class="form-control mb-2" type="password" autocomplete="off" maxlength="1024">
      <p id="tg-hint" class="text-body-secondary"></p>
      <button class="btn btn-primary" type="submit">Complete login</button>
    </form>
    <div id="tg-action-status" role="status" aria-live="polite"></div>
  </div>
</div>
<div class="card">
  <div class="card-header"><h3 class="card-title">Send individual message</h3></div>
  <div class="card-body">
    <div id="tg-send-status" class="alert alert-warning">Sending is disabled until account authentication and explicit admin opt-in.</div>
    <form id="tg-send-form">
      <label for="tg-recipient">Recipient: @username or +international number</label>
      <input id="tg-recipient" type="text" autocomplete="off" class="form-control mb-2" required maxlength="35">
      <label for="tg-message">Message</label>
      <textarea id="tg-message" class="form-control mb-2" maxlength="2000" rows="5" required></textarea>
      <label><input type="checkbox" id="tg-consent" required> I confirm this recipient consented to receiving Telegram messages.</label>
      <div class="my-3 d-flex gap-2">
        <button id="tg-resolve" type="button" class="btn btn-outline-secondary" disabled>Review recipient</button>
        <button id="tg-send" class="btn btn-primary" type="submit" disabled>Send once</button>
      </div>
      <div id="tg-send-feedback" role="status" aria-live="polite"></div>
    </form>
  </div>
</div>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
(() => {
  'use strict';
  const base = window.CRM.root + '/plugins/telegram-client/api';
  const csrf = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const $ = (name) => document.getElementById('tg-' + name);
  let loggedIn = false, sendEnabled = false, polling = null, qrRefreshTimer = null;
  function status(message, level = 'info') {
    $('status').className = 'alert alert-' + level;
    $('status').textContent = message;
  }
  async function api(path, data) {
    const options = data === undefined ? {} : {
      method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrf},
      body: new URLSearchParams(data),
    };
    const response = await fetch(base + path, {credentials: 'same-origin', cache: 'no-store', ...options});
    if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
      throw new Error('Unexpected response. Verify your ChurchCRM login.');
    }
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Telegram operation failed.');
    return result.data;
  }
  function removeQr() {
    if (qrRefreshTimer !== null) clearTimeout(qrRefreshTimer);
    qrRefreshTimer = null;
    $('qr').removeAttribute('src');
    $('qr-area').classList.add('d-none');
  }
  async function refresh() {
    try {
      const result = await api('/status');
      loggedIn = result.state === 'authenticated';
      sendEnabled = loggedIn && Boolean(result.sendingEnabled);
      $('qr-button').disabled = loggedIn || result.state === 'unconfigured';
      $('disconnect').disabled = !loggedIn;
      $('resolve').disabled = !loggedIn;
      $('send').disabled = !sendEnabled;
      $('account').textContent = loggedIn && result.account
        ? 'Connected account: ' + result.account.name + (result.account.username ? ' (@' + result.account.username + ')' : '')
        : '';
      $('2fa-area').classList.toggle('d-none', result.state !== 'needs_2fa');
      $('hint').textContent = result.hint ? 'Password hint: ' + result.hint : '';
      if (loggedIn) {
        status('Telegram account connected.', 'success');
        removeQr();
      } else if (result.state === 'needs_2fa') status('Telegram requires your two-step verification password.', 'warning');
      else if (result.state === 'unconfigured') status('Configure Telegram API credentials and the private worker in Plugin Management.', 'warning');
      else status('Telegram worker available; account not authenticated.', 'warning');
      $('send-status').textContent = sendEnabled ? 'Individual sending enabled.' : 'Sending disabled or Telegram account not connected.';
      $('send-status').className = 'alert alert-' + (sendEnabled ? 'success' : 'warning');
    } catch (error) {
      loggedIn = false;
      $('send').disabled = $('resolve').disabled = $('qr-button').disabled = true;
      status(error.message, 'danger');
    }
  }
  $('refresh').addEventListener('click', refresh);
  $('qr-button').addEventListener('click', async () => {
    $('qr-button').disabled = true;
    removeQr();
    $('action-status').textContent = 'Creating QR Code…';
    try {
      const data = await api('/qr', {});
      if (data.state === 'authenticated') {
        await refresh();
      } else if (data.state === 'needs_2fa') {
        await refresh();
      } else if (data.state === 'qr' && /^data:image\/png;base64,[a-zA-Z0-9+/=]+$/.test(data.image)) {
        $('qr').src = data.image;
        $('qr-area').classList.remove('d-none');
        $('qr-expires').textContent = 'Expires in approximately ' + data.expiresIn + ' seconds. A new code will be generated if still needed.';
        qrRefreshTimer = setTimeout(() => {
          qrRefreshTimer = null;
          if (!loggedIn && !$('qr-area').classList.contains('d-none') && document.visibilityState === 'visible') $('qr-button').click();
        }, Math.max(1000, (Number(data.expiresIn) + 2) * 1000));
        $('action-status').textContent = 'Scan with the Telegram app.';
      } else throw new Error('Invalid QR response.');
    } catch (error) {
      $('action-status').textContent = error.message;
    } finally {
      $('qr-button').disabled = loggedIn;
    }
  });
  $('2fa-area').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      await api('/2fa', {password: $('password').value});
      $('action-status').textContent = 'Login completed.';
    } catch (error) {
      $('action-status').textContent = error.message;
    } finally {
      $('password').value = '';
      await refresh();
    }
  });
  $('disconnect').addEventListener('click', async () => {
    if (!confirm('Revoke this ChurchCRM Telegram session?')) return;
    try {
      await api('/disconnect', {});
      removeQr();
      await refresh();
    } catch (error) {
      $('action-status').textContent = error.message;
    }
  });
  function fields() {
    if (!$('consent').checked) throw new Error('Recipient consent is required.');
    return {recipient: $('recipient').value, consent: 'yes'};
  }
  $('resolve').addEventListener('click', async () => {
    try {
      const info = await api('/resolve', fields());
      $('send-feedback').textContent = 'Resolved: ' + (info.recipient?.name || info.recipient?.username || 'Telegram account');
    } catch (error) {
      $('send-feedback').textContent = error.message;
    }
  });
  $('send-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!sendEnabled) return;
    let payload;
    try {
      payload = fields();
      payload.message = $('message').value;
      if (!confirm('Send this exact message once to ' + payload.recipient + '?\n\n' + payload.message)) return;
      $('send').disabled = true;
      $('send-feedback').textContent = 'Sending; do not retry until the outcome is known.';
      const result = await api('/send', {...payload, confirmed: 'yes'});
      $('send-feedback').textContent = result.state === 'sent' ? 'Telegram accepted the message.' : 'Result uncertain; do not retry automatically.';
    } catch (error) {
      $('send-feedback').textContent = 'Delivery could not be confirmed. Do not retry automatically. ' + error.message;
    } finally {
      $('send').disabled = !sendEnabled;
    }
  });
  refresh();
  polling = setInterval(refresh, 12000);
  window.addEventListener('pagehide', () => { clearInterval(polling); removeQr(); }, {once: true});
})();
</script>
<?php require SystemURLs::getDocumentRoot() . '/Include/Footer.php'; ?>
