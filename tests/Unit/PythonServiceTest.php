<?php

declare(strict_types=1);

use B7s\FluentVision\Services\PythonService;

describe('PythonService', function () {
    it('resolves python path from system', function () {
        $service = new PythonService(
            configPythonPath: null,
            venvPath: '/nonexistent/venv',
        );

        $path = $service->resolvePythonPath();

        expect($path)->toBeString();
        expect(file_exists($path))->toBeTrue();
    });

    it('throws when no python found', function () {
        $service = new PythonService(
            configPythonPath: '/nonexistent/python',
            venvPath: '/nonexistent/venv',
        );

        $service->reset();
        $path = $service->resolvePythonPath();

        expect($path)->toBeString();
    });

    it('resolves python version', function () {
        $service = new PythonService(
            configPythonPath: null,
            venvPath: '/nonexistent/venv',
        );

        $version = $service->getPythonVersion();

        expect($version)->toBeString();
        expect($version)->toContain('Python');
    });

    it('resets cached python path', function () {
        $service = new PythonService(
            configPythonPath: null,
            venvPath: '/nonexistent/venv',
        );

        $path1 = $service->resolvePythonPath();
        $service->reset();
        $path2 = $service->resolvePythonPath();

        expect($path1)->toBe($path2);
    });

    it('prefers config python path when valid', function () {
        $systemPython = (new PythonService(configPythonPath: null, venvPath: '/nonexistent/venv'))->resolvePythonPath();
        $service = new PythonService(configPythonPath: $systemPython, venvPath: '/nonexistent/venv');

        expect($service->resolvePythonPath())->toBe($systemPython);
    });
});
