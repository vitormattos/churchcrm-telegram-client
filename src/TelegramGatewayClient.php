<?php

declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace ChurchCRM\Plugins\TelegramClient;

final class TelegramGatewayClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $secret,
        private readonly int $apiId,
        private readonly string $apiHash,
    ) {
        $url = parse_url($baseUrl);
        if ($url === false || ($url['scheme'] ?? '') !== 'http' || !in_array($url['host'] ?? '', ['telegram-client', 'localhost', '127.0.0.1'], true)
            || isset($url['user'], $url['pass']) || isset($url['query']) || isset($url['fragment'])) {
            throw new \InvalidArgumentException('Worker URL must be private and use an approved host');
        }
        if ($secret === '' || $apiId < 1 || !preg_match('/^[a-f0-9]{32}$/iD', $apiHash)) {
            throw new \InvalidArgumentException('Missing or invalid Telegram credentials');
        }
    }

    public static function normalizeRecipient(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^@[A-Za-z][A-Za-z0-9_]{4,31}$/D', $value)) {
            return $value;
        }
        if (preg_match('/^\+[1-9][0-9]{7,14}$/D', $value)) {
            return $value;
        }
        throw new \InvalidArgumentException('Use a Telegram @username or international +phone number');
    }

    public function request(string $action, array $payload = []): array
    {
        if (!in_array($action, ['status', 'qr', '2fa', 'disconnect', 'send', 'resolve', 'avatar'], true)) {
            throw new \InvalidArgumentException('Unsupported action');
        }
        $curl = curl_init(rtrim($this->baseUrl, '/') . '/' . $action);
        if (!$curl) {
            throw new \RuntimeException('Worker unavailable');
        }
        try {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload + ['api_id' => $this->apiId, 'api_hash' => $this->apiHash], JSON_THROW_ON_ERROR),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->secret],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 28,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP,
            ]);
            $raw = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if (!is_string($raw) || strlen($raw) > 1000000) {
                throw new \RuntimeException('Worker communication failed');
            }
            $response = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($response) || !isset($response['state']) || !is_string($response['state'])) {
                throw new \RuntimeException('Invalid worker response');
            }
            if ($status >= 400 || $status === 0) {
                return ['state' => 'error', 'message' => $response['message'] ?? 'Worker request failed', 'httpStatus' => $status];
            }
            return $response;
        } finally {
            curl_close($curl);
        }
    }
}
