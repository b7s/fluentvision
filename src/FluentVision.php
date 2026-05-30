<?php

declare(strict_types=1);

namespace B7s\FluentVision;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\VideoInferenceResult;
use B7s\FluentVision\Services\InferenceService;
use B7s\FluentVision\Services\ModelService;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Services\PythonService;
use RuntimeException;
use Throwable;

class FluentVision
{
    private Config $config;

    private Provider $provider;

    private Device $device;

    private string $model = '';

    private string $task = 'detect';

    private float $conf = 0.25;

    private float $iou = 0.7;

    private int $imgsz = 640;

    private int $maxDet = 300;

    /** @var array<int, string> */
    private array $classes = [];

    private bool $augment = false;

    private bool $agnosticNms = false;

    private bool $half = false;

    private bool $end2end = false;

    private int $vidStride = 1;

    private string $imagePath = '';

    private string $videoPath = '';

    private InferenceService $inferenceService;

    private ModelService $modelService;

    public function __construct(
        private readonly ?string $configPath = null,
        ?Config $config = null,
        ?InferenceService $inferenceService = null,
        ?ModelService $modelService = null,
    ) {
        $this->config = $config ?? new Config($this->configPath);
        $this->provider = Provider::from($this->config->defaultProvider());
        $this->device = Device::from($this->config->string('default_device', 'cpu'));
        $this->conf = $this->config->float('default_conf', 0.25);
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

    public function vidStride(int $vidStride): self
    {
        $this->vidStride = $vidStride;

        return $this;
    }

    public function image(string $path): self
    {
        $this->imagePath = $path;

        return $this;
    }

    public function video(string $path): self
    {
        $this->videoPath = $path;

        return $this;
    }

    public function detect(): InferenceResult
    {
        if ($this->imagePath === '') {
            throw new RuntimeException('No image path set. Call image() before detect().');
        }

        $resolvedModel = $this->resolveModel();

        return $this->inferenceService->detectImage(
            providerType: $this->provider,
            imagePath: $this->imagePath,
            model: $resolvedModel,
            device: $this->device,
            options: $this->buildOptions(),
        );
    }

    public function detectVideo(): VideoInferenceResult
    {
        if ($this->videoPath === '') {
            throw new RuntimeException('No video path set. Call video() before detectVideo().');
        }

        $resolvedModel = $this->resolveModel();

        return $this->inferenceService->detectVideo(
            providerType: $this->provider,
            videoPath: $this->videoPath,
            model: $resolvedModel,
            device: $this->device,
            options: $this->buildOptions(),
        );
    }

    public function annotate(): AnnotatedResult
    {
        if ($this->imagePath === '') {
            throw new RuntimeException('No image path set. Call image() before annotate().');
        }

        $resolvedModel = $this->resolveModel();

        return $this->inferenceService->annotateImage(
            providerType: $this->provider,
            imagePath: $this->imagePath,
            model: $resolvedModel,
            device: $this->device,
            options: $this->buildOptions(),
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

    public function getConfig(): Config
    {
        return $this->config;
    }

    private function resolveModel(): string
    {
        if ($this->model !== '') {
            return $this->model;
        }

        if ($this->provider->isUltralytics()) {
            return $this->config->string('ultralytics_default_model', 'yolo26s.pt');
        }

        return $this->config->string('nanodet_default_model', 'nanodet-plus-m-416');
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
            'augment' => $this->augment,
            'agnostic_nms' => $this->agnosticNms,
            'half' => $this->half,
            'end2end' => $this->end2end,
            'vid_stride' => $this->vidStride,
        ];

        if ($this->provider->isNanodet()) {
            $nanodetModel = $this->resolveNanodetModelPaths();

            if ($nanodetModel !== null) {
                $options['config'] = $nanodetModel['config'];
                $options['checkpoint'] = $nanodetModel['checkpoint'];
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
}
