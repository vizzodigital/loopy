<?php

declare(strict_types = 1);

namespace App\Services\CartRecovery;

use App\Services\CartRecovery\Platforms\Contracts\PlatformPromptBuilderInterface;
use App\Services\CartRecovery\Platforms\YampiPromptBuilder;

class PlatformPromptResolver
{
    public static function make(int $platform): PlatformPromptBuilderInterface
    {
        return match ($platform) {
            1 => new YampiPromptBuilder(),
            default => throw new \Exception("Platform {$platform} not supported"),
        };
    }
}
