# Usage Guide

Complete reference for the FluentVision fluent API.

## Creating an Instance

```php
use B7s\FluentVision\FluentVision;

// With auto-detected config
$vision = FluentVision::make();

// With custom config file
$vision = FluentVision::make('/path/to/config.php');
```

## Method Reference

### Provider Selection

| Method | Description |
|--------|-------------|
| `provider(Provider $provider)` | Set provider by enum |
| `useUltralytics()` | Shortcut for Ultralytics provider |
| `useNanodet()` | Shortcut for NanoDet provider |

```php
use B7s\FluentVision\Enums\Provider;

$vision->provider(Provider::Ultralytics);
$vision->provider(Provider::Nanodet);

// Shorthand
$vision->useUltralytics();
$vision->useNanodet();
```

Provider selection order:

1. Explicit `->provider()` or `->useUltralytics()`/`->useNanodet()`
2. Config file `default_provider` setting
3. Default: `ultralytics`

### Model Selection

| Method | Parameter | Description |
|--------|-----------|-------------|
| `model(YoloModel\|NanodetModel\|string $model)` | Enum or string | Set the model to use |
| `nanodetCustom(string $configPath, string $checkpointPath)` | Two absolute paths | Use a custom NanoDet model |

```php
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\NanodetModel;

// Via enum (type-safe)
$vision->model(YoloModel::YOLO26s);
$vision->model(NanodetModel::PlusM416);

// Via string (built-in or filename)
$vision->model('yolo26s.pt');
$vision->model('nanodet-plus-m-416');

// Via string (absolute path to custom trained model)
$vision->model('/path/to/my-trained-model.pt');

// Custom NanoDet model (requires config + checkpoint)
$vision->nanodetCustom('/path/config.yml', '/path/model.ckpt');
```

If no model is set, the default from config is used (`yolo26s.pt` for Ultralytics, `nanodet-plus-m-416` for NanoDet). For custom trained models, see [Custom Models](custom-models.md).

### Device Selection

| Method | Description |
|--------|-------------|
| `useCpu()` | Run inference on CPU |
| `useGpu()` | Run inference on GPU (requires CUDA) |

```php
$vision->useCpu();
$vision->useGpu();
```

### Inference Parameters

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `confidence(float $conf)` | float | 0.25 | Confidence threshold (0.0 - 1.0) |
| `iou(float $iou)` | float | 0.7 | IoU threshold for NMS (0.0 - 1.0) |
| `imgsz(int $imgsz)` | int | 640 | Inference image size in pixels |
| `maxDet(int $maxDet)` | int | 300 | Maximum detections per image |
| `classes(array $classes)` | string[] | [] | Filter detections to specific class names |
| `prompts(array $prompts)` | string[] | [] | Text prompts for YOLOE open-vocabulary detection |
| `task(YoloTask $task)` | enum | detect | YOLO task type (Ultralytics only) |

```php
use B7s\FluentVision\Enums\YoloTask;

$vision->confidence(0.5) // Only detections above 50% confidence
    ->iou(0.45) // Stricter NMS overlap threshold
    ->imgsz(1280) // Higher resolution inference
    ->maxDet(50) // Cap at 50 detections
    ->classes(['person', 'car']) // Only detect persons and cars
    ->prompts(['person wearing red', 'nighttime scene']) // YOLOE text prompts
    ->task(YoloTask::Segment); // Run segmentation instead of detection
```

### Advanced Options (Ultralytics)

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `augment(bool $augment = true)` | bool | false | Test-time augmentation |
| `agnosticNms(bool $agnosticNms = true)` | bool | false | Class-agnostic NMS |
| `half(bool $half = true)` | bool | false | FP16 (half-precision) inference |
| `end2end(bool $end2end = true)` | bool | false | End-to-end model (no NMS) |

```php
$vision->augment()       // TTA for better accuracy
    ->agnosticNms()      // Merge overlapping boxes across classes
    ->half();            // Faster GPU inference with FP16
```

### Video Options

| Method | Type | Default | Description |
|-----------------------------|------|---------|-------------------------|
| `everyNframes(int $stride)` | int | 1 | Process every Nth frame |

```php
$vision->everyNframes(5) // Process every 5th frame
->media('clip.mp4')
->detect();
```

### Streaming Options

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `stream(string $source, callable $onFrame)` | string, callable | — | Set stream source (RTSP, RTMP, HTTP, webcam) and per-frame callback |
| `maxFrames(int $maxFrames)` | int | 0 | Limit frames to process (0 = unlimited) |
| `startStream()` | — | — | **Terminal method** — start stream, returns `StreamResult` |

```php
use B7s\FluentVision\Enums\YoloModel;

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->confidence(0.5)
    ->stream('rtsp://192.168.1.100:554/live', function ($frame, $frameNumber) {
        echo sprintf("Frame %d: %d detections\n", $frameNumber, $frame->getDetectionCount());
    })
    ->maxFrames(100)
    ->startStream();

echo "Processed {$result->getFrameCount()} frames\n";
echo "Total detections: {$result->getTotalDetections()}\n";
```

Only **Ultralytics** supports streaming. See [Real-Time Streaming](realtime-streaming.md) for full details.

### Input Methods

| Method | Description |
|--------|-------------|
| `media(string $path, ?MediaType $type = null)` | Set media path — type auto-detected from extension, or pass explicit `MediaType` |

```php
use B7s\FluentVision\Enums\MediaType;

// Auto-detected from extension
$vision->media('photo.jpg');  // → MediaType::Image
$vision->media('clip.mp4');   // → MediaType::Video

// Explicit override (for unusual extensions)
$vision->media('data.raw', MediaType::Image);
```

### Output Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `detect()` | `InferenceResult \| VideoInferenceResult` | Run detection on image or video |
| `annotate()` | `AnnotatedResult` | Run detection and save annotated output |
| `process()` | `ProcessResult` | Run detection + annotation in a single call |
| `startStream()` | `StreamResult` | Start real-time stream detection (after `stream()`) |

### Process Flags

| Method | Default | Description |
|--------|---------|-------------|
| `withDetections(bool $enabled = true)` | true | Include detection data in `process()` result |
| `withAnnotation(bool $enabled = true)` | false | Include annotated image in `process()` result |

```php
use B7s\FluentVision\Results\ProcessResult;

// Both detections + annotation (single inference run)
$result = FluentVision::make()
    ->media('photo.jpg')
    ->withDetections()      // default: true
    ->withAnnotation()      // default: false — opt in
    ->process();

echo $result->getDetectionCount() . " objects found\n";
echo "Annotated: " . $result->getAnnotatedPath() . "\n";

// Annotation only (skip detection data)
$result = FluentVision::make()
    ->media('photo.jpg')
    ->withDetections(false)
    ->withAnnotation()
    ->process();
```

`process()` runs inference **once** — more efficient than calling `detect()` and `annotate()` separately.

## Complete Examples

### Basic Detection

```php
$result = FluentVision::make()
    ->media('photo.jpg')
    ->detect();

foreach ($result->detections as $d) {
    echo sprintf("%s: %.1f%% at [%.0f, %.0f, %.0f, %.0f]\n",
        $d->class,
        $d->confidence * 100,
        $d->box->x1, $d->box->y1,
        $d->box->x2, $d->box->y2
    );
}
```

### High-Confidence Person Detection

```php
$result = FluentVision::make()
    ->confidence(0.8)
    ->classes(['person'])
    ->media('crowd.jpg')
    ->detect();

echo "Found {$result->getDetectionCount()} high-confidence persons\n";
```

### Multi-Task with YOLO26

```php
use B7s\FluentVision\Enums\YoloTask;
use B7s\FluentVision\Enums\YoloModel;

// Segmentation
$segResult = FluentVision::make()
    ->model(YoloModel::YOLO26s)
    ->task(YoloTask::Segment)
    ->media('street.jpg')
    ->detect();

// Pose estimation
$poseResult = FluentVision::make()
    ->model(YoloModel::YOLO26s)
    ->task(YoloTask::Pose)
    ->media('athlete.jpg')
    ->detect();

// Oriented bounding boxes
$obbResult = FluentVision::make()
    ->model(YoloModel::YOLO26s)
    ->task(YoloTask::Obb)
    ->media('aerial.jpg')
    ->detect();
```

### YOLOE Open-Vocabulary Detection

YOLOE models detect arbitrary text concepts beyond the 80 COCO classes:

```php
use B7s\FluentVision\Enums\YoloModel;

// Text-prompted: detect specific concepts
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26s)
    ->prompts(['person wearing red', 'hard hat', 'nighttime scene'])
    ->confidence(0.25)
    ->media('factory.jpg')
    ->detect();

// Prompt-free: auto-detect without prompts
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26sPF)
    ->confidence(0.25)
    ->media('workspace.jpg')
    ->detect();
```

Both YOLOE variants run on **CPU** (~0.15s per image for YOLOE-26s). See [Providers](providers.md#yoloe-26-open-vocabulary-detection) for full details.

### Video Processing

```php
$result = FluentVision::make()
    ->media('traffic.mp4')  // .mp4 auto-detected as video
    ->confidence(0.4)
    ->everyNframes(10) // Sample every 10th frame
    ->detect();

echo "Processed {$result->getFrameCount()} frames\n";
echo "Total detections: {$result->getTotalDetections()}\n";
echo "Avg inference time: {$result->getAverageInferenceTime()}ms\n";
```

### Annotation

```php
$result = FluentVision::make()
    ->media('photo.jpg')
    ->annotate();

if ($result->hasAnnotatedImage()) {
    echo "Annotated image: {$result->annotatedPath}\n";
    echo "Detections drawn: {$result->detectionCount}\n";
}
```

### Custom Config per Request

```php
$vision = FluentVision::make('/path/to/high-accuracy-config.php');

$result = $vision
    ->media('detailed.jpg')
    ->detect();
```

### Inspecting Current State

```php
$vision = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26m)
    ->useGpu();

$vision->getProvider();  // Provider::Ultralytics
$vision->getDevice();    // Device::Gpu
$vision->getModel();     // 'yolo26m.pt'
$vision->getConfig();    // Config object
```

## Method Chaining

All setter methods return `self` for fluent chaining. Only the terminal methods (`detect()`, `annotate()`, `process()`, `startStream()`) return result objects and break the chain.

```php
// Build up configuration
$vision = FluentVision::make()
    ->useUltralytics()
    ->confidence(0.5)
    ->imgsz(640);

// Reuse the same configuration for multiple images
$result1 = $vision->media('photo1.jpg')->detect();
$result2 = $vision->media('photo2.jpg')->detect();
```

Note: After calling a terminal method, the media path remains set. You can call `detect()` again on a new media path without re-chaining all options.

## Error Handling

```php
use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Exceptions\PythonNotFoundException;
use B7s\FluentVision\Exceptions\ModelNotFoundException;

try {
    $result = FluentVision::make()
    ->media('photo.jpg')
        ->detect();
} catch (PythonNotFoundException $e) {
    // Python interpreter not found
    echo "Install Python: " . $e->getMessage();
} catch (InferenceException $e) {
    // Python script failed or returned invalid output
    echo "Inference error: " . $e->getMessage();
} catch (RuntimeException $e) {
    // No media path set
    echo "Setup error: " . $e->getMessage();
}
```
