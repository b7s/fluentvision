<?php

declare(strict_types=1);

namespace B7s\FluentVision;

use B7s\FluentVision\Support\ArrayNarrower;

use function array_merge;
use function dirname;
use function file_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

class Config
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly ?string $configPath = null,
    ) {
        $defaults = $this->defaults();
        $userConfig = $this->loadUserConfig();

        $this->config = array_merge($defaults, $userConfig);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->config[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->config[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->config[$key] ?? $default;

        return is_float($value) || is_int($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->config[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }

    public function defaultProvider(): string
    {
        return $this->string('default_provider', 'ultralytics');
    }

    public function pythonPath(): ?string
    {
        $path = $this->string('python_path');

        return $path !== '' ? $path : null;
    }

    public function pythonVenvPath(): ?string
    {
        $path = $this->string('python_venv_path');

        if ($path !== '') {
            return $path;
        }

        $home = $this->getHomeDir();

        return $home.'/.fluentvision/venv';
    }

    public function modelDir(): string
    {
        $dir = $this->string('model_dir');

        if ($dir !== '') {
            return $dir;
        }

        $home = $this->getHomeDir();

        return $home.'/.fluentvision/models';
    }

    public function nanodetRepoPath(): string
    {
        $path = $this->string('nanodet_repo_path');

        if ($path !== '') {
            return $path;
        }

        $home = $this->getHomeDir();

        return $home.'/.fluentvision/nanodet';
    }

    public function timeout(): int
    {
        return $this->integer('timeout', 0);
    }

    public function verbose(): bool
    {
        return $this->bool('verbose', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'default_provider' => 'ultralytics',
            'python_path' => null,
            'python_venv_path' => null,
            'ultralytics_default_model' => 'yolo26s.pt',
            'nanodet_default_model' => 'nanodet-plus-m-416',
            'default_task' => 'detect',
            'default_device' => 'cpu',
            'default_conf' => 0.4,
            'default_iou' => 0.7,
            'default_imgsz' => 640,
            'default_max_det' => 300,
            'model_dir' => null,
            'nanodet_repo_path' => null,
            'timeout' => 0,
            'verbose' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadUserConfig(): array
    {
        $paths = $this->getConfigPaths();

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $loaded = require $path;

                if (is_array($loaded)) {
                    return ArrayNarrower::narrowToStringKeys($loaded);
                }
            }
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function getConfigPaths(): array
    {
        $paths = [];

        if ($this->configPath !== null) {
            $paths[] = $this->configPath;
        }

        $paths[] = getcwd().'/fluentvision-config.php';
        $paths[] = dirname(__DIR__).'/fluentvision-config.php';

        return $paths;
    }

    private function getHomeDir(): string
    {
        $home = $_SERVER['HOME'] ?? '';

        return is_string($home) ? $home : '';
    }
}
