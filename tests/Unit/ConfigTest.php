<?php

declare(strict_types=1);

use B7s\FluentVision\Config;

describe('Config', function () {
    it('loads defaults when no config file exists', function () {
        $config = new Config('/nonexistent/path');

        expect($config->defaultProvider())->toBe('ultralytics');
        expect($config->timeout())->toBe(0);
        expect($config->verbose())->toBeFalse();
    });

    it('loads from test config file', function () {
        $config = new Config(__DIR__.'/../fixtures/test-config.php');

        expect($config->defaultProvider())->toBe('ultralytics');
        expect($config->modelDir())->toBe('/tmp/fluentvision-test-models');
    });

    it('returns typed values', function () {
        $config = new Config('/nonexistent/path');

        expect($config->string('default_provider', 'fallback'))->toBe('ultralytics');
        expect($config->integer('timeout', 999))->toBe(0);
        expect($config->float('default_conf', 0.99))->toBe(0.25);
        expect($config->bool('verbose', true))->toBeFalse();
    });

    it('returns fallback for missing keys', function () {
        $config = new Config('/nonexistent/path');

        expect($config->get('nonexistent_key'))->toBeNull();
        expect($config->get('nonexistent_key', 'default'))->toBe('default');
        expect($config->string('nonexistent_key', 'fallback'))->toBe('fallback');
    });

    it('returns all config values', function () {
        $config = new Config('/nonexistent/path');

        expect($config->all())->toHaveKey('default_provider');
        expect($config->all())->toHaveKey('timeout');
    });

    it('resolves python venv path from home', function () {
        $config = new Config('/nonexistent/path');
        $venvPath = $config->pythonVenvPath();

        expect($venvPath)->toContain('.fluentvision/venv');
    });

    it('resolves model dir from home', function () {
        $config = new Config('/nonexistent/path');
        $modelDir = $config->modelDir();

        expect($modelDir)->toContain('.fluentvision/models');
    });
});
