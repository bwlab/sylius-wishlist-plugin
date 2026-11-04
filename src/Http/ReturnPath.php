<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Http;

/** Accepts only same-site absolute paths as redirect targets (no open redirect). */
final class ReturnPath
{
    public static function sanitize(mixed $path, string $fallback): string
    {
        if (!\is_string($path) || '' === $path || '/' !== $path[0]) {
            return $fallback;
        }

        if (isset($path[1]) && ('/' === $path[1] || '\\' === $path[1])) {
            return $fallback;
        }

        return 1 === preg_match('/[\x00-\x1F\x7F]/', $path) ? $fallback : $path;
    }
}
