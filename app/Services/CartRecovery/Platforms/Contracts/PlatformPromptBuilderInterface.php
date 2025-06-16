<?php

declare(strict_types = 1);

namespace App\Services\CartRecovery\Platforms\Contracts;

interface PlatformPromptBuilderInterface
{
    public function buildSystemPrompt(array $payload): string;

    public function extractCustomerName(array $payload): string;

    public function extractCartLink(array $payload): string;

    public function extractCartItems(array $payload): array;

    public function extractCartValue(array $payload): string;
}
