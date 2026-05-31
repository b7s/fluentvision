# Real-Time Streaming

FluentVision supports frame-by-frame object detection on live video streams (RTSP, RTMP, HTTP) and webcam devices. Only the **Ultralytics** provider supports streaming — NanoDet does not.

## How It Works

1. The PHP `StreamService` launches the Python inference script as a long-running process
2. The Python script outputs one NDJSON line per processed frame (`{"type":"frame",...}`)
3. PHP reads incremental output from the process, parses each line, and calls your callback with an `InferenceResult`
4. When the stream ends (or `maxFrames` is reached), a final summary JSON line is emitted
5. `startStream()` returns a `StreamResult` with aggregate stats

## Usage

```php
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Enums\YoloModel;

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->confidence(0.5)
    ->stream('rtsp://192.168.1.100:554/live', function ($frame, $frameNumber) {
        echo sprintf("Frame %d: %d detections\n", $frameNumber, $frame->getDetectionCount());

        foreach ($frame->detections as $d) {
            echo sprintf("  - %s (%.1f%%)\n", $d->class, $d->confidence * 100);
        }
    })
    ->maxFrames(300)
    ->startStream();

echo sprintf("Stream finished: %d frames, %d total detections\n",
    $result->getFrameCount(),
    $result->getTotalDetections()
);
```

## Method Reference

### `stream(string $source, callable $onFrame): self`

Set the stream source and per-frame callback. Returns `self` for chaining.

**Supported sources:**

| Protocol | Example |
|----------|---------|
| RTSP | `rtsp://192.168.1.100:554/live` |
| RTMP | `rtmp://server/live/stream` |
| TCP | `tcp://192.168.1.100:5000` |
| UDP | `udp://192.168.1.100:5000` |
| HTTP | `http://example.com/stream.mjpg` |
| HTTPS | `https://example.com/stream.mjpg` |
| Webcam | `"0"`, `"1"` (numeric index as string) |

**Callback signature:**

```php
function (InferenceResult $frame, int $frameNumber): void
```

The callback is invoked for every processed frame with the detection results and the 1-based frame number.

### `maxFrames(int $maxFrames): self`

Limit the number of frames to process. Default: `0` (unlimited — runs until the stream ends or errors).

```php
->maxFrames(100)  // Process exactly 100 frames, then stop
->maxFrames(0)    // Unlimited (default)
```

### `startStream(): StreamResult`

Start the stream and block until completion. This is the **terminal method** — it returns a `StreamResult` and breaks the fluent chain.

## StreamResult

The result object returned by `startStream()`:

```php
readonly class StreamResult
{
    public function __construct(
        public string $source,       // The stream URL/index
        public string $provider,     // 'ultralytics'
        public string $model,        // Model filename
        public array $frames,        // InferenceResult[] — all processed frames
        public float $totalTime,     // Total wall-clock time in seconds
        public bool $stopped,        // Whether the stream was stopped by maxFrames
    ) {}
}
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getFrameCount()` | int | Number of frames processed |
| `getTotalDetections()` | int | Sum of detections across all frames |
| `getAverageInferenceTime()` | float | Mean inference time per frame |
| `toArray()` | array | Full structured output |
| `toJson(int $flags = 0)` | string | JSON output |
| `fromArray(array $data, array $frames = [])` | self | Create from raw data |

## Examples

### RTSP Security Camera

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->confidence(0.6)
    ->classes(['person', 'car'])
    ->stream('rtsp://admin:password@192.168.1.50:554/cam1', function ($frame, $num) {
        if ($frame->isNotEmpty()) {
            echo sprintf("[Frame %d] Alert: %d objects detected\n", $num, $frame->getDetectionCount());
        }
    })
    ->startStream();
```

### Webcam

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26n)
    ->stream('0', function ($frame, $num) {
        foreach ($frame->detections as $d) {
            echo sprintf("%s: %.0f%%\n", $d->class, $d->confidence * 100);
        }
    })
    ->maxFrames(50)
    ->startStream();
```

### HTTP MJPEG Stream

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->stream('http://192.168.1.100:8080/video', function ($frame, $num) {
        echo sprintf("Frame %d: %.1fms\n", $num, $frame->inferenceTime);
    })
    ->startStream();
```

## Provider Support

| Provider | Streaming | Notes |
|----------|-----------|-------|
| **Ultralytics** | Yes | Uses OpenCV capture via YOLO `model(stream_url)` |
| **NanoDet** | No | Throws `InferenceException` if attempted |

Calling `startStream()` with the NanoDet provider throws a `RuntimeException`:

```php
FluentVision::make()
    ->useNanodet()
    ->stream('rtsp://test', fn () => null)
    ->startStream();
// RuntimeException: Provider "nanodet" does not support streaming.
```

## Architecture

```
PHP (StreamService)                Python (ultralytics_inference.py)
─────────────────────              ──────────────────────────────────
Launch process ──────────────────► Parse --stream flag
                                   Open cv2.VideoCapture(source)
Poll getIncrementalOutput()  ◄──── Print NDJSON per frame
  Parse JSON line by line          {"type":"frame","detections":[...]}
  Call onFrame(InferenceResult)    {"type":"frame","detections":[...]}
  Buffer partial lines              ...
                                   {"type":"summary","model":"...","stopped":true}
Process exits ◄────────────────── Print summary JSON
Build StreamResult from frames
Return to caller
```

The Python process has no timeout — it runs until the stream ends or `--max-frames` is reached. PHP polls the process output every 10ms and dispatches parsed frames to the callback in real time.

## Error Handling

```php
use B7s\FluentVision\Exceptions\InferenceException;

try {
    $result = FluentVision::make()
        ->useUltralytics()
        ->model(YoloModel::YOLO26s)
        ->stream('rtsp://invalid-url', function ($frame, $num) {
            // ...
        })
        ->startStream();
} catch (InferenceException $e) {
    echo "Python process error: " . $e->getMessage();
} catch (RuntimeException $e) {
    echo "Setup error: " . $e->getMessage();
}
```
