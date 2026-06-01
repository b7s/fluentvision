<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\UltralyticsSolution;
use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Results\SolutionResult;
use B7s\FluentVision\Services\Providers\OptionBuilder;
use B7s\FluentVision\Support\ArrayNarrower;
use JsonException;

use function is_array;
use function json_decode;

readonly class SolutionService implements SolutionServiceInterface
{
    private const string SCRIPT_PATH = __DIR__.'/../../scripts/ultralytics_solution.py';

    public function __construct(
        private PythonService $pythonService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws JsonException
     */
    public function run(
        UltralyticsSolution $solution,
        string $source,
        string $model,
        Device $device,
        array $options = [],
    ): SolutionResult {
        $arguments = $this->buildArguments($solution, $source, $model, $device, $options);
        $output = $this->pythonService->executeScript(self::SCRIPT_PATH, $arguments);
        $data = $this->decodeJson($output);

        return SolutionResult::fromArray($data);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function buildArguments(
        UltralyticsSolution $solution,
        string $source,
        string $model,
        Device $device,
        array $options,
    ): array {
        $args = [
            '--solution', $solution->value,
            '--source', $source,
            '--model', $model,
            '--device', $device->toUltralyticsArg(),
        ];

        OptionBuilder::appendFloatOption($options, $args, 'conf', '--conf');
        OptionBuilder::appendFloatOption($options, $args, 'iou', '--iou');
        OptionBuilder::appendIntOption($options, $args, 'imgsz', '--imgsz');
        OptionBuilder::appendClassesOption($options, $args);
        OptionBuilder::appendBoolOption($options, $args, 'save', '--save');
        OptionBuilder::appendStringOption($options, $args, 'save_path', '--save-path');
        OptionBuilder::appendIntOption($options, $args, 'max_frames', '--max-frames');
        OptionBuilder::appendStringOption($options, $args, 'region', '--region');
        OptionBuilder::appendIntOption($options, $args, 'colormap', '--colormap');
        OptionBuilder::appendFloatOption($options, $args, 'blur_ratio', '--blur-ratio');
        OptionBuilder::appendStringOption($options, $args, 'crop_dir', '--crop-dir');
        OptionBuilder::appendStringOption($options, $args, 'vision_point', '--vision-point');
        OptionBuilder::appendStringOption($options, $args, 'kpts', '--kpts');
        OptionBuilder::appendFloatOption($options, $args, 'up_angle', '--up-angle');
        OptionBuilder::appendFloatOption($options, $args, 'down_angle', '--down-angle');
        OptionBuilder::appendFloatOption($options, $args, 'fps', '--fps');
        OptionBuilder::appendIntOption($options, $args, 'max_hist', '--max-hist');
        OptionBuilder::appendFloatOption($options, $args, 'meter_per_pixel', '--meter-per-pixel');
        OptionBuilder::appendIntOption($options, $args, 'max_speed', '--max-speed');
        OptionBuilder::appendStringOption($options, $args, 'analytics_type', '--analytics-type');
        OptionBuilder::appendStringOption($options, $args, 'json_file', '--json-file');
        OptionBuilder::appendIntOption($options, $args, 'records', '--records');
        OptionBuilder::appendStringOption($options, $args, 'tracker', '--tracker');

        return $args;
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
}
