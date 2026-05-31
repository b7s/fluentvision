<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\DetectionResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\VideoInferenceResult;
use B7s\FluentVision\Services\Providers\ProviderContract;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Support\ArrayNarrower;
use JsonException;

use function array_map;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;

readonly class InferenceService implements InferenceServiceInterface
{
    public function __construct(
        private PythonService $pythonService,
        private ProviderFactory $providerFactory,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws JsonException
     */
    public function detect(
        Provider $providerType,
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): InferenceResult|VideoInferenceResult {
        $provider = $this->providerFactory->make($providerType);

        if ($mediaType->isVideo() && ! $provider->supportsVideo()) {
            throw InferenceException::fromMessage('Provider does not support video inference');
        }

        $arguments = $provider->buildArguments($mediaPath, $mediaType, $model, $device, $options);
        $output = $this->pythonService->executeScript($provider->scriptPath(), $arguments);

        if ($mediaType->isVideo()) {
            return $this->parseVideoOutput($output, $provider);
        }

        return $this->parseImageOutput($output, $provider);
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws JsonException
     */
    public function annotate(
        Provider $providerType,
        string $mediaPath,
        MediaType $mediaType,
        string $model,
        Device $device,
        array $options = [],
    ): AnnotatedResult {
        $provider = $this->providerFactory->make($providerType);
        $arguments = $provider->buildArguments($mediaPath, $mediaType, $model, $device, $options);
        $output = $this->pythonService->executeScript($provider->scriptPath(), $arguments);

        return $this->parseAnnotatedOutput($output, $provider);
    }

    /**
     * @throws JsonException
     */
    private function parseImageOutput(string $output, ProviderContract $provider): InferenceResult
    {
        $data = $this->decodeJson($output);
        $rawDetections = $data['detections'] ?? [];
        $detections = $this->parseDetections(ArrayNarrower::narrowToArrayOfAssoc($rawDetections));

        $imagePath = $data['image_path'] ?? '';
        $model = $data['model'] ?? '';
        $inferenceTime = $data['inference_time'] ?? 0;

        return InferenceResult::fromArray([
            'image_path' => is_string($imagePath) ? $imagePath : '',
            'provider' => $provider->name(),
            'model' => is_string($model) ? $model : '',
            'inference_time' => is_float($inferenceTime) || is_int($inferenceTime) ? $inferenceTime : 0,
        ], $detections);
    }

    /**
     * @throws JsonException
     */
    private function parseVideoOutput(string $output, ProviderContract $provider): VideoInferenceResult
    {
        $data = $this->decodeJson($output);
        $providerName = $provider->name();
        $modelName = is_string($data['model'] ?? '') ? $data['model'] : '';
        $rawFrames = $data['frames'] ?? [];

        $frames = [];
        if (is_array($rawFrames)) {
            foreach ($rawFrames as $frame) {
                if (! is_array($frame)) {
                    $frames[] = InferenceResult::fromArray([
                        'image_path' => '',
                        'provider' => $providerName,
                        'model' => $modelName,
                        'inference_time' => 0,
                    ], []);

                    continue;
                }
                $narrowedFrame = ArrayNarrower::narrowToStringKeys($frame);
                $rawDetections = $narrowedFrame['detections'] ?? [];
                $detections = $this->parseDetections(ArrayNarrower::narrowToArrayOfAssoc($rawDetections));
                $imagePath = $narrowedFrame['image_path'] ?? '';
                $inferenceTime = $narrowedFrame['inference_time'] ?? 0;

                $frames[] = InferenceResult::fromArray([
                    'image_path' => is_string($imagePath) ? $imagePath : '',
                    'provider' => $providerName,
                    'model' => $modelName,
                    'inference_time' => is_float($inferenceTime) || is_int($inferenceTime) ? $inferenceTime : 0,
                ], $detections);
            }
        }

        $videoPath = $data['video_path'] ?? '';
        $totalInferenceTime = $data['total_inference_time'] ?? 0;

        return VideoInferenceResult::fromArray([
            'video_path' => is_string($videoPath) ? $videoPath : '',
            'provider' => $provider->name(),
            'model' => $modelName,
            'total_inference_time' => is_float($totalInferenceTime) || is_int($totalInferenceTime) ? $totalInferenceTime : 0,
        ], $frames);
    }

    /**
     * @throws JsonException
     */
    private function parseAnnotatedOutput(string $output, ProviderContract $provider): AnnotatedResult
    {
        $data = $this->decodeJson($output);
        $imagePath = $data['image_path'] ?? '';
        $annotatedPath = $data['annotated_path'] ?? '';
        $model = $data['model'] ?? '';
        $detectionCount = $data['detection_count'] ?? 0;

        return AnnotatedResult::fromArray([
            'image_path' => is_string($imagePath) ? $imagePath : '',
            'annotated_path' => is_string($annotatedPath) ? $annotatedPath : '',
            'provider' => $provider->name(),
            'model' => is_string($model) ? $model : '',
            'detection_count' => is_int($detectionCount) ? $detectionCount : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeJson(string $output): array
    {
        $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw InferenceException::fromInvalidOutput('Expected JSON object');
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
