<?php

declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Plugins\TelegramClient\TelegramClientPlugin;
use ChurchCRM\Plugins\TelegramClient\TelegramGatewayClient;
use ChurchCRM\Slim\Middleware\Request\Auth\AdminRoleAuthMiddleware;
use ChurchCRM\Slim\SlimUtils;
use ChurchCRM\Utils\CSRFUtils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

$plugin = TelegramClientPlugin::instance();
if ($plugin === null) {
    return;
}
$app->get('/telegram-client/manage', function (Request $request, Response $response): Response {
    return (new PhpRenderer(__DIR__ . '/../views/'))->render($response, 'manage.php', [
        'sRootPath' => SystemURLs::getRootPath(), 'sPageTitle' => 'Telegram',
    ]);
})->add(AdminRoleAuthMiddleware::class);

$app->group('/telegram-client/api', function (RouteCollectorProxy $group) use ($plugin): void {
    $action = static function (Request $request, Response $response, string $command) use ($plugin): Response {
        $isMutation = $request->getMethod() === 'POST';
        $payload = $request->getParsedBody();
        $data = is_array($payload) ? $payload : [];
        if ($isMutation && !CSRFUtils::validateToken($request->getHeaderLine('X-CSRF-Token'))) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        if (!$plugin->isConfigured()) {
            return SlimUtils::renderJSON($response->withHeader('Cache-Control', 'no-store'), [
                'success' => true, 'data' => ['state' => 'unconfigured', 'sendingEnabled' => false],
            ]);
        }
        if ($command === 'send' || $command === 'resolve') {
            if ($command === 'send' && !$plugin->canSend()) {
                return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Sending disabled'], 403);
            }
            if (($data['consent'] ?? '') !== 'yes') {
                return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Recipient consent required'], 422);
            }
            try {
                $data['recipient'] = TelegramGatewayClient::normalizeRecipient((string) ($data['recipient'] ?? ''));
            } catch (\InvalidArgumentException) {
                return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Invalid Telegram recipient'], 422);
            }
            if ($command === 'send' && (
                !is_string($data['message'] ?? null) || trim($data['message']) === ''
                || mb_strlen($data['message']) > 2000 || ($data['confirmed'] ?? '') !== 'yes'
            )) {
                return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Invalid or unconfirmed message'], 422);
            }
        }
        if ($command === '2fa' && (!is_string($data['password'] ?? null) || $data['password'] === '')) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Two-factor password required'], 422);
        }
        try {
            $result = $plugin->gateway()->request($command, match ($command) {
                '2fa' => ['password' => $data['password']],
                'send' => ['recipient' => $data['recipient'], 'message' => $data['message']],
                'resolve' => ['recipient' => $data['recipient']],
                default => [],
            });
            $result['sendingEnabled'] = $plugin->canSend();
            return SlimUtils::renderJSON($response->withHeader('Cache-Control', 'no-store'), [
                'success' => $result['state'] !== 'error', 'data' => $result,
                'message' => $result['message'] ?? null,
            ], $result['state'] === 'error' ? ($result['httpStatus'] ?? 503) : 200);
        } catch (\Throwable) {
            return SlimUtils::renderJSON($response->withHeader('Cache-Control', 'no-store'), [
                'success' => false, 'message' => 'Telegram service unavailable',
            ], 503);
        }
    };
    $group->get('/status', static fn (Request $req, Response $res): Response => $action($req, $res, 'status'));
    foreach (['qr', '2fa', 'disconnect', 'resolve', 'send'] as $operation) {
        $group->post('/' . $operation, static fn (Request $req, Response $res): Response => $action($req, $res, $operation));
    }
})->add(AdminRoleAuthMiddleware::class);
