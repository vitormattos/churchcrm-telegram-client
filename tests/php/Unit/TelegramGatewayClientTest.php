<?php

declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use ChurchCRM\Plugins\TelegramClient\TelegramGatewayClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelegramGatewayClientTest extends TestCase
{
    public static function validRecipients(): array
    {
        return [['@example_user'], ['+5521999999999']];
    }

    #[DataProvider('validRecipients')]
    public function testAcceptedRecipients(string $recipient): void
    {
        self::assertSame($recipient, TelegramGatewayClient::normalizeRecipient($recipient));
    }

    public static function invalidRecipients(): array
    {
        return [[''], ['555199999999'], ['@bad'], ['@example user'], ['https://evil.org'], ['+00000000000']];
    }

    #[DataProvider('invalidRecipients')]
    public function testRejectsRecipients(string $recipient): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TelegramGatewayClient::normalizeRecipient($recipient);
    }

    public function testRefusesPublicWorkerHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelegramGatewayClient('https://example.org', 'secret', 123, str_repeat('a', 32));
    }

    public function testRejectsInvalidApiCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TelegramGatewayClient('http://telegram-client:8080', 'secret', 0, 'bad');
    }

    public function testRefusesUnexpectedActions(): void
    {
        $client = new TelegramGatewayClient('http://telegram-client:8080', 'secret', 123, str_repeat('a', 32));
        $this->expectException(\InvalidArgumentException::class);
        $client->request('delete-everything');
    }
}
