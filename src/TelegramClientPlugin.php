<?php

declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace ChurchCRM\Plugins\TelegramClient;

use ChurchCRM\Plugin\AbstractPlugin;

final class TelegramClientPlugin extends AbstractPlugin
{
    private static ?self $instance = null;

    public function __construct(string $basePath = '')
    {
        parent::__construct($basePath);
        self::$instance = $this;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    #[\Override]
    public function getId(): string
    {
        return 'telegram-client';
    }

    #[\Override]
    public function getName(): string
    {
        return 'Telegram (MTProto)';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Connect a real Telegram account to send individual, consented messages.';
    }

    public function getSettingsSchema(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true, 512, JSON_THROW_ON_ERROR)['settings'];
    }

    public function getMenuItems(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true, 512, JSON_THROW_ON_ERROR)['menuItems'];
    }

    public function boot(): void
    {
    }

    public function isConfigured(): bool
    {
        return $this->getConfigValue('apiId') !== '' && $this->getConfigValue('apiHash') !== ''
            && $this->getConfigValue('gatewayUrl') !== '' && $this->getConfigValue('gatewaySecret') !== '';
    }

    public function canSend(): bool
    {
        return $this->isEnabled() && $this->isConfigured() && $this->getBooleanConfigValue('sendingEnabled');
    }

    public function gateway(): TelegramGatewayClient
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Configure Telegram API credentials in Plugin Management first');
        }
        return new TelegramGatewayClient(
            $this->getConfigValue('gatewayUrl'),
            $this->getConfigValue('gatewaySecret'),
            (int) $this->getConfigValue('apiId'),
            $this->getConfigValue('apiHash'),
        );
    }

    public function testWithSettings(array $settings): array
    {
        try {
            $lookup = fn (string $key): string => (string) (($settings[$key] ?? '') ?: $this->getConfigValue($key));
            $client = new TelegramGatewayClient(
                $lookup('gatewayUrl'),
                $lookup('gatewaySecret'),
                (int) $lookup('apiId'),
                $lookup('apiHash'),
            );
            $state = $client->request('status');
            return ['success' => $state['state'] !== 'error', 'message' => $state['state'] !== 'error'
                ? 'Telegram worker available' : 'Telegram worker is not available'];
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Telegram worker is not available or credentials are incomplete'];
        }
    }
}
