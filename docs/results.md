# Results API

All FluentVision terminal methods return typed, immutable result objects. Regardless of which provider you use, the PHP types are identical.

## InferenceResult

Returned by `->detect()` when processing image input.

```php
readonly class InferenceResult
{
    public function __construct(
        public string $imagePath,
        public string $provider,
        public string $model,
        public array $detections,
        public float $inferenceTime,
    ) {}
}
```

### Accessing Detections

```php
$result = FluentVision::make()
    ->media('photo.jpg')
    ->detect();

// Count
$result->getDetectionCount();       // int — number of detections

// Iterate
foreach ($result->detections as $d) {
    echo $d->class;                  // string — e.g. 'person'
    echo $d->confidence;             // float — 0.0 to 1.0
    echo $d->box->x1;               // float — left
    echo $d->box->y1;               // float — top
    echo $d->box->x2;               // float — right
    echo $d->box->y2;               // float — bottom
}

// Filter by confidence
$high = array_filter(
    $result->detections,
    static fn (DetectionResult $d): bool => $d->confidence >= 0.8,
);

// Filter by class
$persons = array_filter(
    $result->detections,
    static fn (DetectionResult $d): bool => $d->class === 'person',
);
```

### Serialization

```php
$result->toArray();                  // array — full structured output
$result->toJson();                   // string — compact JSON
$result->toJson(JSON_PRETTY_PRINT);  // string — pretty-printed JSON
```

### Factory Method

```php
$result = InferenceResult::fromArray([
    'image_path' => 'photo.jpg',
    'provider' => 'ultralytics',
    'model' => 'yolo26s.pt',
    'inference_time' => 45.2,
], [
    DetectionResult::fromArray([
        'class' => 'person',
        'confidence' => 0.92,
        'box' => ['x1' => 10, 'y1' => 20, 'x2' => 100, 'y2' => 300],
    ]),
]);
```

## DetectionResult

Each item in `InferenceResult::detections`.

```php
readonly class DetectionResult
{
    public function __construct(
        public string $class,
        public float $confidence,
        public BoundingBox $box,
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `toArray()` | array | Serialize to associative array |
| `fromArray(array $data)` | self | Create from raw data |

```php
$d = $result->detections[0];

$d->class;          // 'person'
$d->confidence;     // 0.92
$d->box->x1;        // 10.0
$d->box->y1;        // 20.0
$d->box->x2;        // 100.0
$d->box->y2;        // 300.0

$d->toArray();
// ['class' => 'person', 'confidence' => 0.92, 'box' => ['x1' => 10, ...]]
```

## BoundingBox

Immutable value object representing axis-aligned bounding box coordinates.

```php
readonly class BoundingBox
{
    public function __construct(
        public float $x1,
        public float $y1,
        public float $x2,
        public float $y2,
    ) {}
}
```

### Derived Properties

| Method | Return | Description |
|--------|--------|-------------|
| `width()` | float | `x2 - x1` |
| `height()` | float | `y2 - y1` |
| `area()` | float | `width() * height()` |
| `centerX()` | float | `(x1 + x2) / 2` |
| `centerY()` | float | `(y1 + y2) / 2` |
| `toArray()` | array | `['x1' => ..., 'y1' => ..., 'x2' => ..., 'y2' => ...]` |

```php
$box = $d->box;

$box->width();     // 90.0
$box->height();    // 280.0
$box->area();      // 25200.0
$box->centerX();   // 55.0
$box->centerY();   // 160.0
```

## VideoInferenceResult

Returned by `->detect()` when processing video input.

```php
readonly class VideoInferenceResult
{
    public function __construct(
        public string $videoPath,
        public string $provider,
        public string $model,
        public array $frames,
        public float $totalInferenceTime,
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getFrameCount()` | int | Number of processed frames |
| `getTotalDetections()` | int | Sum of detections across all frames |
| `getAverageInferenceTime()` | float | Mean inference time in ms |
| `toArray()` | array | Full structured output |
| `toJson()` | string | Compact JSON |

```php
$result = FluentVision::make()
    ->media('clip.mp4')  // auto-detected as video
    ->detect();

$result->getFrameCount();            // 42
$result->getTotalDetections();       // 187
$result->getAverageInferenceTime();  // 38.5

// Per-frame access
foreach ($result->frames as $frameResult) {
    echo "Frame: {$frameResult->imagePath}\n";
    echo "Detections: " . count($frameResult->detections) . "\n";
    echo "Time: {$frameResult->inferenceTime}ms\n";
}
```

## AnnotatedResult

Returned by `->annotate()`. Also available as the `annotation` property of `ProcessResult`.

```php
readonly class AnnotatedResult
{
    public function __construct(
        public string $imagePath,
        public string $annotatedPath,
        public string $provider,
        public string $model,
        public int $detectionCount,
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `hasAnnotatedImage()` | bool | Whether the annotated file exists on disk |
| `toArray()` | array | Full structured output |

```php
$result = FluentVision::make()
    ->media('photo.jpg')
    ->annotate();

if ($result->hasAnnotatedImage()) {
    echo "Saved to: {$result->annotatedPath}\n";
    echo "Detections drawn: {$result->detectionCount}\n";
}
```

## ProcessResult

Returned by `->process()`. Combines detection results and annotation in a single object — produced by a single inference run.

```php
readonly class ProcessResult
{
    public function __construct(
        public InferenceResult|VideoInferenceResult $detections,
        public AnnotatedResult $annotation,
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `hasAnnotatedImage()` | bool | Whether the annotated file exists on disk |
| `getDetectionCount()` | int | Number of detections (total across frames for video) |
| `getTotalTime()` | float | Inference time in ms (image: `inferenceTime`, video: `totalInferenceTime`) |
| `getAnnotatedPath()` | string | Path to the annotated image/video |
| `toArray()` | array | Full structured output |
| `toJson(int $flags = 0)` | string | Compact JSON |
| `fromArray(array $data)` | self | Create from raw data |

```php
use B7s\FluentVision\Results\ProcessResult;

$result = FluentVision::make()
    ->media('photo.jpg')
    ->withDetections()
    ->withAnnotation()
    ->process();

echo "Detections: " . $result->getDetectionCount() . "\n";
echo "Annotated: " . $result->getAnnotatedPath() . "\n";

// Access individual parts
$detections = $result->detections;  // InferenceResult or VideoInferenceResult
$annotation = $result->annotation;  // AnnotatedResult

// For image input
foreach ($result->detections->detections as $d) {
    echo $d->class . ": " . ($d->confidence * 100) . "%\n";
}

// For video input
if ($result->detections instanceof VideoInferenceResult) {
    echo "Frames: " . $result->detections->getFrameCount() . "\n";
}

// Serialize
$data = $result->toArray();
$json = $result->toJson();
```

## StreamResult

Returned by `->process()` when processing real-time streams (RTSP, RTMP, HTTP, webcam). Only available with the Ultralytics provider. Unlike other result types, `StreamResult` is **mutable** — it accumulates frames as the stream processes and supports `stopStream()` to halt the stream from within the callback.

```php
class StreamResult
{
    public function __construct(
        public string $source,   // Stream URL or webcam index
        public string $provider, // 'ultralytics'
        public string $model,    // Model filename
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `addFrame(InferenceResult $frame)` | void | Accumulate a processed frame |
| `setTotalTime(float $time)` | void | Set total wall-clock time |
| `setStopped(bool $stopped)` | void | Set whether the stream was stopped early |
| `setStreamUrl(?string $url)` | void | Set the MJPEG annotation URL |
| `setRunning(bool $running)` | void | Set whether the stream is currently running |
| `setKillCallback(\Closure $callback)` | void | Set the callback that kills the Python process |
| `stopStream()` | void | Stop the stream — sets stop flag + kills process via SIGKILL |
| `getFrames()` | `array<InferenceResult>` | All processed frames |
| `getFrameCount()` | int | Number of frames processed |
| `getTotalDetections()` | int | Sum of detections across all frames |
| `getAverageInferenceTime()` | float | Mean inference time per frame |
| `getTotalTime()` | float | Total wall-clock time in seconds |
| `isStopped()` | bool | Whether stopped by `maxFramesToProcess` limit or `stopStream()` |
| `isRunning()` | bool | Whether the stream is currently processing |
| `isStopRequested()` | bool | Whether `stopStream()` has been called |
| `getStreamUrl()` | ?string | MJPEG annotation URL (when `startAnnotateStreamServer` enabled) |
| `toArray()` | array | Full structured output |
| `toJson(int $flags = 0)` | string | JSON output |
| `fromArray(array $data, array $frames = [])` | self | Create from raw data |

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $num, $result) {
        echo "Frame $num: " . $frame->getDetectionCount() . " detections\n";
    }, null, 100)
    ->process();

$result->getFrameCount(); // 100
$result->getTotalDetections(); // 247
$result->getTotalTime(); // 12.345 (seconds)
$result->isStopped(); // true (stopped by maxFramesToProcess)
$result->getStreamUrl(); // 'http://0.0.0.0:8080' (if startAnnotateStreamServer enabled)

// Per-frame access
foreach ($result->getFrames() as $frameResult) {
    echo "Detections: " . count($frameResult->detections) . "\n";
}
```

### Stopping a Stream Early

Call `$result->stopStream()` from inside the per-frame callback:

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $num, $result) {
        if ($result->getFrameCount() >= 5) {
            $result->stopStream();
            return;
        }
    })
    ->process();

echo "Stopped early: " . ($result->isStopped() ? 'yes' : 'no') . "\n";
```

See [Real-Time Streaming](realtime-streaming.md) for complete usage guide.

## Common Patterns

### Count by Class

```php
$counts = array_reduce(
    $result->detections,
    static fn (array $carry, DetectionResult $d): array => {
        $carry[$d->class] = ($carry[$d->class] ?? 0) + 1;
        return $carry;
    },
    [],
);
// ['person' => 3, 'car' => 2, 'dog' => 1]
```

### Highest Confidence Detection

```php
$best = array_reduce(
    $result->detections,
    static fn (?DetectionResult $best, DetectionResult $d): DetectionResult =>
        $best === null || $d->confidence > $best->confidence ? $d : $best,
    null,
);
```

### Export to CSV

```php
$fp = fopen('detections.csv', 'w');
fputcsv($fp, ['class', 'confidence', 'x1', 'y1', 'x2', 'y2']);

foreach ($result->detections as $d) {
    fputcsv($fp, [
        $d->class,
        $d->confidence,
        $d->box->x1,
        $d->box->y1,
        $d->box->x2,
        $d->box->y2,
    ]);
}

fclose($fp);
```

### Merge Results from Multiple Images

```php
$allDetections = [];

foreach (glob('images/*.jpg') as $path) {
    $result = FluentVision::make()->media($path)->detect();
    $allDetections = array_merge($allDetections, $result->detections);
}

echo "Total detections across all images: " . count($allDetections) . "\n";
```
