<?php
declare(strict_types=1);

namespace App\System;

final class PathMatcher
{
    public static function doesPathMatch(string $path, string $uri): bool
    {
        if ($path === $uri) {
            return true;
        }

        $pathParts = explode('/', ltrim($path, '/'));
        $uriParts = explode('/', ltrim($uri, '/'));

        if (\count($pathParts) !== \count($uriParts)) {
            return false;
        }

        foreach ($pathParts as $index => $pathPart) {
            if ($pathPart === $uriParts[$index]) {
                continue;
            }

            if (str_starts_with($pathPart, '{') && str_ends_with($pathPart, '}')) {
                continue;
            }

            return false;
        }

        return true;
    }
}
