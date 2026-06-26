<?php

declare(strict_types=1);

namespace App\Util;

class PathBuilder
{
    public function buildPath(string ...$paths): string
    {
        $paths[0] = rtrim($paths[0], '/');
        $path = array_reduce(
            array: $paths,
            callback: fn ($carry, $path) => $carry.'/'.ltrim($path, '/'),
        );

        return $path;
    }
}
