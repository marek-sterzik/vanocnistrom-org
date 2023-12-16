<?php

namespace App\Utility;

use Exception;
use Symfony\Component\Yaml\Yaml;

class ResourceLoader
{
    const EXTENSIONS = [
        "yml" => 'yaml',
        'yaml' => 'yaml',
        'json' => 'json',
        'php' => 'php',
    ];

    const LOADER_METHODS = [
        'yaml' => 'loadYAML',
        'json' => 'loadJSON',
        'php' => 'loadPHP',
    ];

    public function __construct(private string $resourceDir)
    {
    }

    public function loadResource(string $path, ?string $dirResource = null): array
    {
        $path = $this->parsePath($path);
        list($type, $fullPath) = $this->identifyFile($path);
        if ($type === 'dir') {
            if ($dirResource !== null) {
                $path = $path . '/' . $dirResource;
                list($type, $fullPath) = $this->identifyFile($path);
                if ($type === 'dir') {
                    $type = 'unknown';
                    $fullPath = null;
                }
            }
        }

        if ($type === 'unknown') {
            throw new Exception(sprintf("Resource '%s' not found", $path));
        }

        return $this->loadFile($fullPath, $type);
    }

    private function loadFile(string $fullPath, string $type): array
    {
        if (!isset(self::LOADER_METHODS[$type])) {
            throw new Exception(sprintf("Invalid resource loader type: %s", $type));
        }
        $method = self::LOADER_METHODS[$type];
        /** @phpstan-ignore-next-line */
        if (!is_callable([$this, $method])) {
            throw new Exception(sprintf("Unknown resource lader method: %s", $method));
        }
        $data = $this->$method($fullPath);
        if (!is_array($data)) {
            throw new Exception(sprintf("Resource contains invalid data: %s", $fullPath));
        }
        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function loadYAML(string $fullPath): mixed
    {
        return YAML::parseFile($fullPath);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function loadJSON(string $fullPath): mixed
    {
        $data = @file_get_contents($fullPath);
        if (!is_string($data)) {
            throw new Exception(sprintf("cannot read file: %s", $fullPath));
        }
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function loadPHP(string $fullPath): mixed
    {
        return @include($fullPath);
    }

    private function identifyFile(string $path): array
    {
        $fullPath = $this->resourceDir . "/" . $path;
        if (is_dir($fullPath)) {
            return ["dir", $fullPath];
        }
        foreach (self::EXTENSIONS as $extension => $type) {
            $fullPathWithExtension = $fullPath . "." . $extension;
            if (is_file($fullPathWithExtension)) {
                return [$type, $fullPathWithExtension];
            }
        }
        return ["unknown", null];
    }

    private function parsePath(string $path): string
    {
        $pathParts = [];
        foreach (explode("/", $path) as $name) {
            if ($name === '.' || $name === '') {
                continue;
            } elseif ($name === '..') {
                if (!empty($pathParts)) {
                    array_pop($pathParts);
                }
            } else {
                $pathParts[] = $name;
            }
        }
        return implode("/", $pathParts);
    }
}
