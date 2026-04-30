<?php

namespace App\Services;

class BladeAssetService
{
    private array $versions = [];

    public function url(string $path): string
    {
        return asset($path) . '?v=' . $this->version($path);
    }

    public function version(string $path): string
    {
        $configuredVersion = (string) config('app.asset_version', '');
        if ($configuredVersion !== '') {
            return $configuredVersion;
        }

        if (isset($this->versions[$path])) {
            return $this->versions[$path];
        }

        $absolutePath = public_path($path);
        $this->versions[$path] = file_exists($absolutePath)
            ? (string) filemtime($absolutePath)
            : '1';

        return $this->versions[$path];
    }
}
