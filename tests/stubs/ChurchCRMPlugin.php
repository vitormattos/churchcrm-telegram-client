<?php
declare(strict_types=1);
/** SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace ChurchCRM\Plugin;

abstract class AbstractPlugin
{
    public function __construct(string $basePath = '') {}
    abstract public function getId(): string;
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    public function getConfigValue(string $key): string { return ''; }
    public function getBooleanConfigValue(string $key): bool { return false; }
    public function isEnabled(): bool { return true; }
}
