<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\StreamResult;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Support\ArrayNarrower;
use Symfony\Component\Process\Process;

use function array_map;
use function array_merge;
use function explode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function microtime;
use function sprintf;
use function str_contains;
use function trim;

class StreamService implements StreamServiceInterface
{
    public function __construct(
        private readonly PythonService $pythonService,
        private readonly ProviderFactory $providerFactory,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @param  callable(InferenceResult $frame, int $frameNumber): void  $onFrame
     *
     * @throws InferenceException
     */
    public function stream(
        Provider $providerType,
        string $source,
        string $model,
        Device $device,
        callable $onFrame,
        array $options = [],
    ): StreamResult {
        $provider = $this->providerFactory->make($providerType);

        if (! $provider->supportsStream()) {
            throw InferenceException::fromMessage(
                sprintf('Provider "%s" does not support streaming', $provider->name()),
            );
        }

        $arguments = $provider->buildArguments($source, MediaType::Stream, $model, $device, $options);
        $python = $this->pythonService->resolvePythonPath();
        $command = array_merge([$python, $provider->scriptPath()], $arguments);

        $process = new Process($command);
        $process->setTimeout(null);
        $process->start();

        $frames = [];
        $start = microtime(true);

        $buffer = '';
        while ($process->isRunning()) {
            $incremental = $process->getIncrementalOutput();
            if ($incremental !== '') {
                $buffer .= $incremental;
            }

            while (str_contains($buffer, "\n")) {
                $parts = explode("\n", $buffer, 2);
                $line = trim($parts[0]);
                $buffer = $parts[1] ?? '';

                if ($line === '') {
                    continue;
                }

                $parsed = $this->parseFrameLine($line, $provider->name());
                if ($parsed !== null) {
                    $frames[] = $parsed;
                    $onFrame($parsed, count($frames));
                }
            }

            usleep(10000);
        }

        $remaining = trim($process->getOutput());
        if ($remaining !== '') {
            $buffer .= $remaining;
        }

        while (str_contains($buffer, "\n")) {
            $parts = explode("\n", $buffer, 2);
            $line = trim($parts[0]);
            $buffer = $parts[1] ?? '';

            if ($line === '') {
                continue;
            }

            $parsed = $this->parseFrameLine($line, $provider->name());
            if ($parsed !== null) {
                $frames[] = $parsed;
                $onFrame($parsed, count($frames));
            }
        }

        $lastLine = trim($buffer);
        $summary = null;
        if ($lastLine !== '') {
            $summary = $this->parseSummaryLine($lastLine);
        }

        if (! $process->isSuccessful()) {
            throw InferenceException::fromProcessError(
                $provider->scriptPath(),
                $process->getErrorOutput(),
            );
        }

        $totalTime = microtime(true) - $start;
        $modelName = $summary['model'] ?? $model;
        $stopped = $summary['stopped'] ?? false;

        return new StreamResult(
            source: $source,
            provider: $provider->name(),
            model: is_string($modelName) ? $modelName : $model,
            frames: $frames,
            totalTime: round($totalTime, 4),
            stopped: is_bool($stopped) ? $stopped : false,
        );
    }

    private function parseFrameLine(string $line, string $providerName): ?InferenceResult
    {
        $data = json_decode($line, true);
        if (! is_array($data)) {
            return null;
        }

        $type = $data['type'] ?? '';
        if (is_string($type) && $type !== 'frame') {
            return null;
        }

        $rawDetections = $data['detections'] ?? [];
        $detections = $this->parseDetections(ArrayNarrower::narrowToArrayOfAssoc($rawDetections));

        $imagePath = $data['image_path'] ?? '';
        $model = $data['model'] ?? '';
        $inferenceTime = $data['inference_time'] ?? 0;

        return InferenceResult::fromArray([
            'image_path' => is_string($imagePath) ? $imagePath : '',
            'provider' => $providerName,
            'model' => is_string($model) ? $model : '',
            'inference_time' => is_float($inferenceTime) || is_int($inferenceTime) ? $inferenceTime : 0,
        ], $detections);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseSummaryLine(string $line): ?array
    {
        $data = json_decode($line, true);
        if (! is_array($data)) {
            return null;
        }

        $type = $data['type'] ?? '';
        if (is_string($type) && $type !== 'summary') {
            return null;
        }

        return ArrayNarrower::narrowToStringKeys($data);
    }

    /**
     * @param  array<array<string, mixed>>  $detections
     * @return array<DetectionResult>
     */
    private function parseDetections(array $detections): array
    {
        return array_map(
            static fn (array $d): DetectionResult => DetectionResult::fromArray($d),
            $detections,
        );
    }
}
