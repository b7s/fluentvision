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

```php
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\NanodetModel;

// Via enum (type-safe)
$vision->model(YoloModel::YOLO26s);
$vision->model(NanodetModel::PlusM416);

// Via string
$vision->model('yolo26s.pt');
$vision->model('nanodet-plus-m-416');
```

If no model is set, the default from config is used (`yolo26s.pt` for Ultralytics, `nanodet-plus-m-416` for NanoDet).

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
| `conf(float $conf)` | float | 0.25 | Confidence threshold (0.0 - 1.0) |
| `iou(float $iou)` | float | 0.7 | IoU threshold for NMS (0.0 - 1.0) |
| `imgsz(int $imgsz)` | int | 640 | Inference image size in pixels |
| `maxDet(int $maxDet)` | int | 300 | Maximum detections per image |
| `classes(array $classes)` | string[] | [] | Filter detections to specific class names |
| `task(YoloTask $task)` | enum | detect | YOLO task type (Ultralytics only) |

```php
use B7s\FluentVision\Enums\YoloTask;

$vision->conf(0.5)              // Only detections above 50% confidence
    ->iou(0.45)                  // Stricter NMS overlap threshold
    ->imgsz(1280)                // Higher resolution inference
    ->maxDet(50)                 // Cap at 50 detections
    ->classes(['person', 'car']) // Only detect persons and cars
    ->task(YoloTask::Segment);   // Run segmentation instead of detection
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
|--------|------|---------|-------------|
| `vidStride(int $stride)` | int | 1 | Process every Nth frame |

```php
$vision->vidStride(5)  // Process every 5th frame
    ->video('clip.mp4')
    ->detectVideo();
```

### Input Methods

| Method | Description |
|--------|-------------|
| `image(string $path)` | Set image path for detection/annotation |
| `video(string $path)` | Set video path for video detection |

### Output Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `detect()` | `InferenceResult` | Run object detection on the image |
| `detectVideo()` | `VideoInferenceResult` | Run detection on a video |
| `annotate()` | `AnnotatedResult` | Run detection and save annotated image |

## Complete Examples

### Basic Detection

```php
$result = FluentVision::make()
    ->image('photo.jpg')
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
    ->conf(0.8)
    ->classes(['person'])
    ->image('crowd.jpg')
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
    ->image('street.jpg')
    ->detect();

// Pose estimation
$poseResult = FluentVision::make()
    ->model(YoloModel::YOLO26s)
    ->task(YoloTask::Pose)
    ->image('athlete.jpg')
    ->detect();

// Oriented bounding boxes
$obbResult = FluentVision::make()
    ->model(YoloModel::YOLO26s)
    ->task(YoloTask::Obb)
    ->image('aerial.jpg')
    ->detect();
```

### Video Processing

```php
$result = FluentVision::make()
    ->video('traffic.mp4')
    ->conf(0.4)
    ->vidStride(10)  // Sample every 10th frame
    ->detectVideo();

echo "Processed {$result->getFrameCount()} frames\n";
echo "Total detections: {$result->getTotalDetections()}\n";
echo "Avg inference time: {$result->getAverageInferenceTime()}ms\n";
```

### Annotation

```php
$result = FluentVision::make()
    ->image('photo.jpg')
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
    ->image('detailed.jpg')
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

All setter methods return `self` for fluent chaining. Only the terminal methods (`detect()`, `detectVideo()`, `annotate()`) return result objects and break the chain.

```php
// Build up configuration
$vision = FluentVision::make()
    ->useUltralytics()
    ->conf(0.5)
    ->imgsz(640);

// Reuse the same configuration for multiple images
$result1 = $vision->image('photo1.jpg')->detect();
$result2 = $vision->image('photo2.jpg')->detect();
```

Note: After calling a terminal method, the image/video path remains set. You can call `detect()` again on a new image without re-chaining all options.

## Error Handling

```php
use B7s\FluentVision\Exceptions\InferenceException;
use B7s\FluentVision\Exceptions\PythonNotFoundException;
use B7s\FluentVision\Exceptions\ModelNotFoundException;

try {
    $result = FluentVision::make()
        ->image('photo.jpg')
        ->detect();
} catch (PythonNotFoundException $e) {
    // Python interpreter not found
    echo "Install Python: " . $e->getMessage();
} catch (InferenceException $e) {
    // Python script failed or returned invalid output
    echo "Inference error: " . $e->getMessage();
} catch (RuntimeException $e) {
    // No image path set
    echo "Setup error: " . $e->getMessage();
}
```
