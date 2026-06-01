<?php

declare(strict_types=1);

namespace B7s\FluentVision;

use B7s\FluentVision\Enums\Device;
use B7s\FluentVision\Enums\MediaType;
use B7s\FluentVision\Enums\NanodetModel;
use B7s\FluentVision\Enums\Provider;
use B7s\FluentVision\Enums\UltralyticsSolution;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;
use B7s\FluentVision\Results\AnnotatedResult;
use B7s\FluentVision\Results\InferenceResult;
use B7s\FluentVision\Results\ProcessResult;
use B7s\FluentVision\Results\SolutionResult;
use B7s\FluentVision\Results\StreamResult;
use B7s\FluentVision\Results\VideoInferenceResult;
use B7s\FluentVision\Services\InferenceService;
use B7s\FluentVision\Services\InferenceServiceInterface;
use B7s\FluentVision\Services\ModelService;
use B7s\FluentVision\Services\ModelServiceInterface;
use B7s\FluentVision\Services\Providers\ProviderFactory;
use B7s\FluentVision\Services\PythonService;
use B7s\FluentVision\Services\SolutionService;
use B7s\FluentVision\Services\SolutionServiceInterface;
use B7s\FluentVision\Services\StreamService;
use B7s\FluentVision\Services\StreamServiceInterface;
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

    private bool $wantsDetections = true;

    private bool $wantsAnnotation = false;

    private bool $wantsAnnotatedFrames = false;

    private int $maxFramesToProcess = 0;

    private ?int $annotatePort = null;

    /** @var (callable(InferenceResult, int, StreamResult): void)|null */
    private $streamCallback = null;

    private InferenceServiceInterface $inferenceService;

    private ModelServiceInterface $modelService;

    private ProviderFactory $providerFactory;

    private StreamServiceInterface $streamService;

    private SolutionServiceInterface $solutionService;

    private ?UltralyticsSolution $selectedSolution = null;

    /** @var array<string, mixed> */
    private array $solutionParams = [];

    public function __construct(
        private readonly ?string $configPath = null,
        ?Config $config = null,
        ?InferenceServiceInterface $inferenceService = null,
        ?ModelServiceInterface $modelService = null,
        ?StreamServiceInterface $streamService = null,
        ?SolutionServiceInterface $solutionService = null,
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
        $this->providerFactory = $providerFactory;

        $this->inferenceService = $inferenceService ?? new InferenceService($pythonService, $providerFactory);
        $this->modelService = $modelService ?? new ModelService(
            modelDir: $this->config->modelDir(),
            nanodetRepoPath: $this->config->nanodetRepoPath(),
        );
        $this->streamService = $streamService ?? new StreamService($pythonService, $providerFactory);
        $this->solutionService = $solutionService ?? new SolutionService($pythonService);
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

    /**
     * Select an Ultralytics solution to run with optional parameters.
     *
     * Solutions are Ultralytics-only built-in features like object counting,
     * heatmaps, speed estimation, etc. See UltralyticsSolution enum for all options.
     *
     * @param  array<string, mixed>  $params  Solution-specific parameters
     */
    public function solution(UltralyticsSolution $solution, array $params = []): self
    {
        $this->selectedSolution = $solution;
        $this->solutionParams = $params;

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

    public function confidence(float $conf): self
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

    public function vidStride(int $vidStride): self
    {
        $this->vidStride = $vidStride;

        return $this;
    }

    public function everyNframes(int $vidStride): self
    {
        return $this->vidStride($vidStride);
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

    public function withDetections(bool $enabled = true): self
    {
        $this->wantsDetections = $enabled;

        return $this;
    }

    public function withAnnotation(bool $enabled = true): self
    {
        $this->wantsAnnotation = $enabled;

        return $this;
    }

    public function withAnnotatedFrames(bool $enabled = true): self
    {
        $this->wantsAnnotatedFrames = $enabled;

        return $this;
    }

    /**
     * Configure the per-frame callback, optional annotation server port, and frame limit for streaming.
     *
     * The callback receives (InferenceResult $frame, int $frameNumber, StreamResult $result).
     * Call $result->stopStream() from inside the callback to stop the stream early.
     * When startAnnotateServerOnPort is set, an MJPEG HTTP server is started on that port
     * and withAnnotation(true) is implied.
     *
     * @param  callable(InferenceResult $frame, int $frameNumber, StreamResult $result): void  $onFrame
     */
    public function streamConfig(callable $onFrame, ?int $startAnnotateServerOnPort = null, int $maxFramesToProcess = 0): self
    {
        $this->streamCallback = $onFrame;
        $this->maxFramesToProcess = $maxFramesToProcess;

        if ($startAnnotateServerOnPort !== null) {
            $this->annotatePort = $startAnnotateServerOnPort;
            $this->wantsAnnotation = true;
        }

        return $this;
    }

    /**
     * Start an MJPEG HTTP server on the given port for live annotated frame viewing.
     * Implies withAnnotation(true). Pass null to disable.
     *
     * Open http://localhost:{port}/stream in any browser to view the annotated stream.
     * Alias for passing the port via streamConfig(callable, $port).
     */
    public function annotateStream(?int $port): self
    {
        $this->annotatePort = $port;

        if ($port !== null) {
            $this->wantsAnnotation = true;
        }

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

    /**
     * @throws JsonException
     */
    public function process(): ProcessResult|StreamResult|SolutionResult
    {
        if ($this->mediaPath === '') {
            throw new RuntimeException('No media path set. Call media() before process().');
        }

        if ($this->selectedSolution !== null) {
            return $this->runSolution();
        }

        $mediaType = $this->resolveMediaType();

        if ($mediaType->isStream()) {
            return $this->runStream();
        }

        if (! $this->wantsDetections && ! $this->wantsAnnotation) {
            throw new RuntimeException('At least one of withDetections() or withAnnotation() must be enabled.');
        }

        $this->autoInferProvider();
        $resolvedModel = $this->resolveModel();
        $resolvedSavePath = $this->resolveSavePath();
        $this->validateAndEnsurePath($resolvedSavePath);

        $options = $this->buildOptions();
        $options['save'] = $this->wantsAnnotation;
        $options['save_path'] = $resolvedSavePath;

        return $this->inferenceService->detectAndAnnotate(
            providerType: $this->provider,
            mediaPath: $this->mediaPath,
            mediaType: $mediaType,
            model: $resolvedModel,
            device: $this->device,
            options: $options,
        );
    }

    private function runStream(): StreamResult
    {
        if ($this->streamCallback === null) {
            throw new RuntimeException('No stream callback set. Call streamConfig(callback) before process().');
        }

        $onFrame = $this->streamCallback;

        $this->autoInferProvider();

        $provider = $this->providerFactory->make($this->provider);
        if (! $provider->supportsStream()) {
            throw new RuntimeException(sprintf('Provider "%s" does not support streaming.', $this->provider->value));
        }

        $resolvedModel = $this->resolveModel();
        $options = $this->buildOptions();

        if ($this->maxFramesToProcess > 0) {
            $options['max_frames'] = $this->maxFramesToProcess;
        }

        if ($this->wantsAnnotatedFrames) {
            $options['annotate_frames'] = true;
        }

        if ($this->wantsAnnotation || $this->annotatePort !== null) {
            $options['annotate'] = true;
        }

        if ($this->annotatePort !== null) {
            $options['annotate_port'] = $this->annotatePort;
        }

        return $this->streamService->stream(
            providerType: $this->provider,
            source: $this->mediaPath,
            model: $resolvedModel,
            device: $this->device,
            onFrame: $onFrame,
            options: $options,
        );
    }

    /**
     * @throws JsonException
     */
    private function runSolution(): SolutionResult
    {
        if ($this->selectedSolution === null) {
            throw new RuntimeException('No solution selected. Call solution() before process().');
        }

        $solution = $this->selectedSolution;
        $this->autoInferProvider();

        if (! $this->provider->isUltralytics()) {
            throw new RuntimeException('Solutions are only available with the Ultralytics provider.');
        }

        $resolvedModel = $this->resolveModel();
        $options = $this->solutionParams;

        if ($this->conf > 0) {
            $options['conf'] = $this->conf;
        }

        if ($this->iou > 0) {
            $options['iou'] = $this->iou;
        }

        if ($this->imgsz > 0) {
            $options['imgsz'] = $this->imgsz;
        }

        if ($this->classes !== []) {
            $options['classes'] = $this->classes;
        }

        $resolvedSavePath = $this->resolveSavePath();

        if ($this->wantsAnnotation) {
            $options['save'] = true;
            $options['save_path'] = $resolvedSavePath;
        }

        return $this->solutionService->run(
            solution: $solution,
            source: $this->mediaPath,
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
