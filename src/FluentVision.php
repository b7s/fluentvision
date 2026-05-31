<?php

declare(strict_types=1);

namespace B7s\FluentVision;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\VideoInferenceResult;
use B7s\FluentVision\Services\InferenceService;
use B7s\FluentVision\Services\InferenceServiceInterface;
use B7s\FluentVision\Services\ModelService;
use B7s\FluentVision\Services\ModelServiceInterface;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Services\PythonService;
use JsonException;
use RuntimeException;
use Throwable;

use function file_exists;
use function is_dir;
use function mkdir;
use function sprintf;
use function str_starts_with;

class FluentVision
{
    private Config $config;

    private Provider $provider;

    private bool $providerExplicitlySet = false;

    private Device $device;

    private string $model = '';

    private string $task = 'detect';

    private float $conf;

    private float $iou;

    private int $imgsz;

    private int $maxDet;

    /** @var array<int, string> */
    private array $classes = [];

    /** @var array<int, string> */
    private array $prompts = [];

    private string $customConfig = '';

    private string $customCheckpoint = '';

    private bool $augment = false;

    private bool $agnosticNms = false;

    private bool $half = false;

    private bool $end2end = false;

    private int $vidStride = 5;

    private string $mediaPath = '';

    private ?MediaType $mediaType = null;

    private string $savePath = '';

    private InferenceServiceInterface $inferenceService;

    private ModelServiceInterface $modelService;

    public function __construct(
        private readonly ?string $configPath = null,
        ?Config $config = null,
        ?InferenceServiceInterface $inferenceService = null,
        ?ModelServiceInterface $modelService = null,
    ) {
        $this->config = $config ?? new Config($this->configPath);
        $this->provider = Provider::from($this->config->defaultProvider());
        $this->device = Device::from($this->config->string('default_device', 'cpu'));
        $this->conf = $this->config->float('default_conf', 0.4);
        $this->iou = $this->config->float('default_iou', 0.7);
        $this->imgsz = $this->config->integer('default_imgsz', 640);
        $this->maxDet = $this->config->integer('default_max_det', 300);

        $pythonService = new PythonService(
            configPythonPath: $this->config->pythonPath(),
            venvPath: $this->config->pythonVenvPath(),
            timeout: $this->config->timeout(),
        );

        $providerFactory = new ProviderFactory($this->config->nanodetRepoPath());

        $this->inferenceService = $inferenceService ?? new InferenceService($pythonService, $providerFactory);
        $this->modelService = $modelService ?? new ModelService(
            modelDir: $this->config->modelDir(),
            nanodetRepoPath: $this->config->nanodetRepoPath(),
        );
    }

    public static function make(?string $configPath = null): self
    {
        return new self(configPath: $configPath);
    }

    public function provider(Provider $provider): self
    {
        $this->provider = $provider;
        $this->providerExplicitlySet = true;

        return $this;
    }

    public function useUltralytics(): self
    {
        return $this->provider(Provider::Ultralytics);
    }

    public function useNanodet(): self
    {
        return $this->provider(Provider::Nanodet);
    }

    public function model(YoloModel|NanodetModel|string $model): self
    {
        if ($model instanceof YoloModel) {
            $this->model = $model->filename();
        } elseif ($model instanceof NanodetModel) {
            $this->model = $model->dirname();
        } else {
            $this->model = $model;
        }

        if (! $this->providerExplicitlySet) {
            $this->inferProviderFromModel();
        }

        return $this;
    }

    public function task(YoloTask $task): self
    {
        $this->task = $task->value;

        return $this;
    }

    public function useCpu(): self
    {
        $this->device = Device::Cpu;

        return $this;
    }

    public function useGpu(): self
    {
        $this->device = Device::Gpu;

        return $this;
    }

    public function conf(float $conf): self
    {
        $this->conf = $conf;

        return $this;
    }

    public function iou(float $iou): self
    {
        $this->iou = $iou;

        return $this;
    }

    public function imgsz(int $imgsz): self
    {
        $this->imgsz = $imgsz;

        return $this;
    }

    public function maxDet(int $maxDet): self
    {
        $this->maxDet = $maxDet;

        return $this;
    }

    /**
     * @param  array<int, string>  $classes
     */
    public function classes(array $classes): self
    {
        $this->classes = $classes;

        return $this;
    }

    /**
     * Set text prompts for YOLOE open-vocabulary detection.
     *
     * Only works with YOLOE models (yoloe-26*-seg.pt).
     * Prompt-free variants (-pf) ignore this option.
     *
     * @param  array<int, string>  $prompts
     */
    public function prompts(array $prompts): self
    {
        $this->prompts = $prompts;

        return $this;
    }

    /**
     * Use a custom NanoDet model with explicit config and checkpoint paths.
     *
     * @param  string  $configPath  Absolute path to NanoDet config YAML
     * @param  string  $checkpointPath  Absolute path to NanoDet checkpoint (.ckpt)
     */
    public function nanodetCustom(string $configPath, string $checkpointPath): self
    {
        $this->customConfig = $configPath;
        $this->customCheckpoint = $checkpointPath;

        if (! $this->providerExplicitlySet) {
            $this->provider = Provider::Nanodet;
        }

        return $this;
    }

    public function augment(bool $augment = true): self
    {
        $this->augment = $augment;

        return $this;
    }

    public function agnosticNms(bool $agnosticNms = true): self
    {
        $this->agnosticNms = $agnosticNms;

        return $this;
    }

    public function half(bool $half = true): self
    {
        $this->half = $half;

        return $this;
    }

    public function end2end(bool $end2end = true): self
    {
        $this->end2end = $end2end;

        return $this;
    }

    /**
     * Alias for everyNframes()
     * @param int $vidStride
     * @return $this
     */
    public function vidStride(int $vidStride): self
    {
        return $this->everyNframes($vidStride);
    }

    public function everyNframes(int $vidStride): self
    {
        $this->vidStride = $vidStride;

        return $this;
    }

    /**
     * Set the media input path. Media type is auto-detected from the file extension.
     * Override auto-detection by passing an explicit MediaType.
     */
    public function media(string $path, ?MediaType $type = null): self
    {
        $this->mediaPath = $path;
        $this->mediaType = $type ?? MediaType::inferFromPath($path);

        return $this;
    }

    public function savePath(string $path): self
    {
        $this->savePath = $path;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function detect(): InferenceResult|VideoInferenceResult
    {
        if ($this->mediaPath === '') {
            throw new RuntimeException('No media path set. Call media() before detect().');
        }

        $this->autoInferProvider();
        $resolvedModel = $this->resolveModel();

        return $this->inferenceService->detect(
            providerType: $this->provider,
            mediaPath: $this->mediaPath,
            mediaType: $this->resolveMediaType(),
            model: $resolvedModel,
            device: $this->device,
            options: $this->buildOptions(),
        );
    }

    /**
     * @throws JsonException
     */
    public function annotate(): AnnotatedResult
    {
        if ($this->mediaPath === '') {
            throw new RuntimeException('No media path set. Call media() before annotate().');
        }

        $this->autoInferProvider();
        $resolvedModel = $this->resolveModel();
        $resolvedSavePath = $this->resolveSavePath();
        $this->validateAndEnsurePath($resolvedSavePath);

        $options = $this->buildOptions();
        $options['save'] = true;
        $options['save_path'] = $resolvedSavePath;

        return $this->inferenceService->annotate(
            providerType: $this->provider,
            mediaPath: $this->mediaPath,
            mediaType: $this->resolveMediaType(),
            model: $resolvedModel,
            device: $this->device,
            options: $options,
        );
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function getSavePath(): string
    {
        return $this->resolveSavePath();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    private function resolveMediaType(): MediaType
    {
        return $this->mediaType ?? MediaType::inferFromPath($this->mediaPath);
    }

    private function resolveModel(): string
    {
        $modelValue = $this->model;

        if ($modelValue === '') {
            $modelValue = $this->provider->isUltralytics()
                ? $this->config->string('ultralytics_default_model', 'yolo26s.pt')
                : $this->config->string('nanodet_default_model', 'nanodet-plus-m-416');
        }

        if ($this->provider->isUltralytics()) {
            $yoloModel = YoloModel::tryFrom($modelValue);

            if ($yoloModel !== null) {
                return $this->modelService->resolveUltralyticsModel($yoloModel);
            }

            if (str_starts_with($modelValue, '/') && file_exists($modelValue)) {
                return $modelValue;
            }

            $fullPath = $this->config->modelDir().'/'.$modelValue;

            if (file_exists($fullPath)) {
                return $fullPath;
            }

            return $modelValue;
        }

        return $modelValue;
    }

    private function autoInferProvider(): void
    {
        if ($this->providerExplicitlySet) {
            return;
        }

        if ($this->customConfig !== '' && $this->customCheckpoint !== '') {
            $this->provider = Provider::Nanodet;

            return;
        }

        $this->inferProviderFromModel();
    }

    private function inferProviderFromModel(): void
    {
        if ($this->model === '') {
            return;
        }

        $inferred = Provider::inferFromModel($this->model);

        if ($inferred !== null) {
            $this->provider = $inferred;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOptions(): array
    {
        $options = [
            'task' => $this->task,
            'conf' => $this->conf,
            'iou' => $this->iou,
            'imgsz' => $this->imgsz,
            'max_det' => $this->maxDet,
            'classes' => $this->classes,
            'prompts' => $this->prompts,
            'augment' => $this->augment,
            'agnostic_nms' => $this->agnosticNms,
            'half' => $this->half,
            'end2end' => $this->end2end,
            'vid_stride' => $this->vidStride,
        ];

        if ($this->provider->isNanodet()) {
            if ($this->customConfig !== '' && $this->customCheckpoint !== '') {
                $options['config'] = $this->customConfig;
                $options['checkpoint'] = $this->customCheckpoint;
            } else {
                $nanodetModel = $this->resolveNanodetModelPaths();

                if ($nanodetModel !== null) {
                    $options['config'] = $nanodetModel['config'];
                    $options['checkpoint'] = $nanodetModel['checkpoint'];
                }
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>|null
     */
    private function resolveNanodetModelPaths(): ?array
    {
        $nanodetModel = NanodetModel::tryFrom($this->resolveModel());

        if ($nanodetModel === null) {
            return null;
        }

        try {
            return $this->modelService->resolveNanodetModel($nanodetModel);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveSavePath(): string
    {
        if ($this->savePath !== '') {
            if (str_starts_with($this->savePath, '/')) {
                return $this->savePath;
            }

            return getcwd().'/'.$this->savePath;
        }

        $configPath = $this->config->savePath();

        if (str_starts_with($configPath, '/')) {
            return $configPath;
        }

        return getcwd().'/'.$configPath;
    }

    private function validateAndEnsurePath(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Output directory "%s" could not be created.', $path));
        }
    }
}
