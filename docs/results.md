# Results API

All FluentVision terminal methods return typed, immutable result objects. Regardless of which provider you use, the PHP types are identical.

## InferenceResult

Returned by `->detect()` when processing image input.

```php
readonly class InferenceResult
{
    public function __construct(
        public array $detections,
        public string $imagePath,
        public string $model,
        public float $inferenceTime,
        public Device $device,
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
    'model' => 'yolo26s.pt',
    'inference_time' => 45.2,
    'device' => 'cpu',
    'detections' => [
        [
            'class' => 'person',
            'confidence' => 0.92,
            'box' => ['x1' => 10, 'y1' => 20, 'x2' => 100, 'y2' => 300],
        ],
    ],
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
        public array $frames,
        public string $videoPath,
        public string $model,
        public Device $device,
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

Returned by `->annotate()`.

```php
readonly class AnnotatedResult
{
    public function __construct(
        public string $annotatedPath,
        public int $detectionCount,
        public string $model,
        public float $inferenceTime,
        public Device $device,
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
