<?php
declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require __DIR__ . '/vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger;
use danog\MadelineProto\Logger as MadelineLogger;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function reply(int $http, string $state, array $other = []): never {
    http_response_code($http);
    echo json_encode(['state' => $state] + $other, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, 'error', ['message' => 'Method not allowed']);
}
$secret = getenv('GATEWAY_SECRET');
$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!is_string($secret) || strlen($secret) < 32 || !hash_equals('Bearer ' . $secret, $header)) {
    reply(403, 'error', ['message' => 'Forbidden']);
}
$action = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if (!in_array($action, ['status', 'qr', '2fa', 'send', 'resolve', 'disconnect'], true)) {
    reply(404, 'error', ['message' => 'Unsupported operation']);
}
$raw = file_get_contents('php://input', false, null, 0, 8192);
$data = json_decode($raw ?: '', true);
if (!is_array($data) || !is_int($data['api_id'] ?? null) || $data['api_id'] < 1
    || !is_string($data['api_hash'] ?? null) || !preg_match('/^[a-f0-9]{32}$/iD', $data['api_hash'])) {
    reply(422, 'error', ['message' => 'API credentials are required']);
}
$lock = fopen('/state/telegram.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    reply(423, 'error', ['message' => 'Telegram session is busy; try again later']);
}
try {
    set_time_limit(26);
    $app = (new AppInfo())->setApiId($data['api_id'])->setApiHash($data['api_hash'])
        ->setDeviceModel('ChurchCRM Telegram Client');
    $logger = (new Logger())->setType(MadelineLogger::LOGGER_CALLABLE)
        ->setExtra(static function (): void {})->setLevel(MadelineLogger::LEVEL_ERROR);
    $api = new API('/state/session.madeline', (new Settings())->setAppInfo($app)->setLogger($logger));
    $auth = $api->getAuthorization();
    $authenticated = $auth === API::LOGGED_IN;
    if ($action === 'status') {
        if ($authenticated) {
            $self = $api->getSelf();
            reply(200, 'authenticated', ['account' => [
                'name' => trim(($self['first_name'] ?? '') . ' ' . ($self['last_name'] ?? '')),
                'username' => (string) ($self['username'] ?? ''),
            ]]);
        }
        if ($auth === API::WAITING_PASSWORD) {
            reply(200, 'needs_2fa', ['hint' => $api->getHint()]);
        }
        reply(200, 'disconnected');
    }
    if ($action === 'qr') {
        if ($authenticated) {
            reply(200, 'authenticated');
        }
        if ($auth === API::WAITING_PASSWORD) {
            reply(200, 'needs_2fa', ['hint' => $api->getHint()]);
        }
        $login = $api->qrLogin();
        if ($login === null) {
            reply(409, 'error', ['message' => 'Unable to create QR login; check account state']);
        }
        $image = (new PngWriter())->write(new QrCode($login->link))->getDataUri();
        reply(200, 'qr', ['image' => $image, 'expiresIn' => max(1, min(120, $login->expiresIn()))]);
    }
    if ($action === '2fa') {
        if ($authenticated) {
            reply(200, 'authenticated');
        }
        if ($auth !== API::WAITING_PASSWORD) {
            reply(409, 'error', ['message' => 'Account is not waiting for a 2FA password']);
        }
        $password = $data['password'] ?? null;
        if (!is_string($password) || $password === '' || strlen($password) > 1024) {
            reply(422, 'error', ['message' => 'Invalid two-factor password']);
        }
        $api->complete2faLogin($password);
        reply($api->getAuthorization() === API::LOGGED_IN ? 200 : 409,
            $api->getAuthorization() === API::LOGGED_IN ? 'authenticated' : 'error');
    }
    if ($action === 'disconnect') {
        if ($authenticated) {
            $api->logout();
        }
        reply(200, 'disconnected');
    }
    if (!$authenticated) {
        reply(409, 'error', ['message' => 'Authenticate the Telegram account first']);
    }
    $peer = $data['recipient'] ?? '';
    if (!is_string($peer) || !preg_match('/^(@[A-Za-z][A-Za-z0-9_]{4,31}|\+[1-9][0-9]{7,14})$/D', $peer)) {
        reply(422, 'error', ['message' => 'Invalid recipient format']);
    }
    $api->start();
    $resolved = str_starts_with($peer, '@') ? $peer : ($api->contacts->resolvePhone(phone: $peer)['peer'] ?? null);
    if ($resolved === null) {
        reply(422, 'error', ['message' => 'Recipient not found on Telegram']);
    }
    $info = $api->getInfo($resolved);
    if (!is_array($info) || !isset($info['InputPeer']) || !is_array($info['User'] ?? null)) {
        reply(422, 'error', ['message' => 'Recipient must be a Telegram user, not a group or channel']);
    }
    if ($action === 'resolve') {
        $user = $info['User'] ?? [];
        reply(200, 'resolved', ['recipient' => [
            'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'username' => (string) ($user['username'] ?? ''),
        ]]);
    }
    $message = $data['message'] ?? null;
    if (!is_string($message) || trim($message) === '' || mb_strlen($message) > 2000) {
        reply(422, 'error', ['message' => 'Message must contain 1-2000 characters']);
    }
    // No automatic retry: a failed response can mean delivery status is uncertain.
    $api->messages->sendMessage(peer: $info['InputPeer'], message: $message);
    reply(200, 'sent');
} catch (\danog\MadelineProto\RPCErrorException $exception) {
    $text = $exception->getMessage();
    if (str_contains($text, 'FLOOD_WAIT')) {
        reply(429, 'error', ['message' => 'Telegram rate limit; sending blocked']);
    }
    if (str_contains($text, 'PHONE_NOT_OCCUPIED') || str_contains($text, 'USERNAME_NOT_OCCUPIED')) {
        reply(422, 'error', ['message' => 'Recipient not found']);
    }
    reply(502, 'error', ['message' => 'Telegram operation failed; delivery may be uncertain']);
} catch (\Throwable) {
    reply(503, 'error', ['message' => 'Telegram service unavailable or operation failed']);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
