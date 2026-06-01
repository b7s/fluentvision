# Real-Time Streaming

FluentVision supports frame-by-frame object detection on live video streams (RTSP, RTMP, HTTP) and webcam devices. Only the **Ultralytics** provider supports streaming — NanoDet does not.

## How It Works

1. Set the stream source via `media()` — the media type is auto-detected from the URL scheme
2. Set the per-frame callback via `streamConfig()` — this activates streaming mode
3. Call `process()` — it detects the stream media type and runs the stream pipeline
4. The PHP `StreamService` launches the Python inference script as a long-running process
5. The Python script outputs one NDJSON line per processed frame (`{"type":"frame",...}`)
6. PHP reads incremental output from the process, parses each line, and calls your callback with an `InferenceResult`
7. When the stream ends (or `maxFramesToProcess` is reached), a final summary JSON line is emitted
8. `process()` returns a `StreamResult` with aggregate stats

## Usage

```php
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Enums\YoloModel;

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->confidence(0.5)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $frameNumber, $result) {
        echo sprintf("Frame %d: %d detections\n", $frameNumber, $frame->getDetectionCount());

        foreach ($frame->detections as $d) {
            echo sprintf(" - %s (%.1f%%)\n", $d->class, $d->confidence * 100);
        }
    }, null, 300)
    ->process();

echo sprintf("Stream finished: %d frames, %d total detections\n",
    $result->getFrameCount(),
    $result->getTotalDetections()
);
```

## Method Reference

### `media(string $source, ?MediaType $type = null): self`

Set the stream source. The media type is auto-detected from the URL scheme (RTSP, RTMP, HTTP, HTTPS, TCP, UDP) or numeric webcam index. Returns `self` for chaining.

**Supported sources:**

| Protocol | Example | Auto-detected as |
|----------|---------|-------------------|
| RTSP | `rtsp://192.168.1.100:554/live` | Stream |
| RTMP | `rtmp://server/live/stream` | Stream |
| TCP | `tcp://192.168.1.100:5000` | Stream |
| UDP | `udp://192.168.1.100:5000` | Stream |
| HTTP | `http://example.com/stream.mjpg` | Stream |
| HTTPS | `https://example.com/stream.mjpg` | Stream |
| Webcam | `"0"`, `"1"` (numeric index as string) | Stream |

### `streamConfig(callable $onFrame, ?int $startAnnotateServerOnPort = null, int $maxFramesToProcess = 0): self`

Set the per-frame callback, optionally enable real-time annotation streaming, and limit the number of frames to process. Returns `self` for chaining.

**Callback signature:**

```php
function (InferenceResult $frame, int $frameNumber, StreamResult $result): void
```

The callback is invoked for every processed frame with the detection results, the 1-based frame number, and the mutable `StreamResult` object. Call `$result->stopStream()` from inside the callback to stop the stream early.

**Annotation streaming:** If `$startAnnotateServerOnPort` is provided, an MJPEG annotation server starts on that port, making annotated frames available in real time at the `streamUrl` returned in `StreamResult`.

**Frame limit:** If `$maxFramesToProcess` is greater than 0, the stream stops after processing that many frames. Default: `0` (unlimited — runs until the stream ends or errors).

```php
->streamConfig(function ($frame, $num, $result) { ... })                          // Detection callback only
->streamConfig(function ($frame, $num, $result) { ... }, 8080)                    // Callback + annotation server on port 8080
->streamConfig(function ($frame, $num, $result) { ... }, null, 100)               // Callback + limit to 100 frames
->streamConfig(function ($frame, $num, $result) { ... }, 8080, 100)               // Callback + annotation server + 100 frame limit
```

### `startAnnotateStreamServer(?int $port): self`

Fluent alias to enable real-time annotation streaming. When called without a port, the annotation server uses a random port.

```php
->startAnnotateStreamServer(8765) // Annotation server on port 8765
->startAnnotateStreamServer(null) // Annotation server on random port
```

Equivalent to passing the port as the second argument to `streamConfig()`.

### `withAnnotatedFrames(bool $enabled = true): self`

Enable base64-encoded annotated frame data in each stream frame's `InferenceResult::$annotatedFrame` property. Default: `false` (no base64 payload to reduce overhead). The annotation server (`streamConfig` port) renders overlays regardless of this setting — this flag only controls whether the raw base64 data is sent back to PHP.

```php
->withAnnotatedFrames()     // Include base64 annotated frame in each InferenceResult
->withAnnotatedFrames(false) // Skip base64 payload (default)
```

### `process(): ProcessResult|StreamResult`

Terminal method that auto-detects the media type. When the media is a stream and `streamConfig()` has been called, it runs the streaming pipeline and returns a `StreamResult`. Otherwise, it returns a `ProcessResult` with detections and annotation.

## StreamResult

The result object returned by `process()` when running in streaming mode. Unlike other result types, `StreamResult` is **mutable** — it accumulates frames as the stream processes and supports `stopStream()` to halt the stream from within the callback.

```php
class StreamResult
{
    public function __construct(
        public string $source,   // The stream URL/index
        public string $provider, // 'ultralytics'
        public string $model,    // Model filename
    ) {}
}
```

### Mutable Properties (set via methods)

| Method | Description |
|--------|-------------|
| `addFrame(InferenceResult $frame)` | Accumulate a processed frame |
| `setTotalTime(float $time)` | Set total wall-clock time |
| `setStopped(bool $stopped)` | Set whether the stream was stopped early |
| `setStreamUrl(?string $url)` | Set the MJPEG annotation URL |
| `setRunning(bool $running)` | Set whether the stream is currently running |
| `setKillCallback(\Closure $callback)` | Set the callback that kills the Python process |
| `stopStream()` | Stop the stream — sets stop flag + kills process via SIGKILL |

### Accessor Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getFrames()` | `array<InferenceResult>` | All processed frames |
| `getFrameCount()` | int | Number of frames processed |
| `getTotalDetections()` | int | Sum of detections across all frames |
| `getAverageInferenceTime()` | float | Mean inference time per frame |
| `getTotalTime()` | float | Total wall-clock time in seconds |
| `isStopped()` | bool | Whether the stream was stopped early (by `maxFramesToProcess` or `stopStream()`) |
| `isRunning()` | bool | Whether the stream is currently processing |
| `isStopRequested()` | bool | Whether `stopStream()` has been called |
| `getStreamUrl()` | ?string | MJPEG annotation URL (when `startAnnotateStreamServer` enabled) |
| `toArray()` | array | Full structured output |
| `toJson(int $flags = 0)` | string | JSON output |
| `fromArray(array $data, array $frames = [])` | self | Create from raw data |

### Stopping a Stream Early

Call `$result->stopStream()` from inside the per-frame callback to halt the stream immediately. This sets a stop flag and kills the Python process via SIGKILL:

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $num, $result) {
        if ($result->getFrameCount() >= 10) {
            $result->stopStream(); // Stop after 10 frames
            return;
        }
        echo "Frame $num: " . $frame->getDetectionCount() . " detections\n";
    })
    ->process();
```

> **Note:** `StreamService` blocks synchronously — `stopStream()` only works from within the `$onFrame` callback or a signal handler in the same thread.

## Real-Time Annotation Streaming

When you enable the annotation server, `StreamResult->getStreamUrl()` contains the MJPEG URL where annotated frames are served in real time. This is useful for displaying live detection overlays in a browser or video player.

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $num, $result) {
        echo "Frame $num: " . $frame->getDetectionCount() . " detections\n";
    }, 8080, 100)
    ->process();

echo "Annotate URL: " . $result->getStreamUrl() . "\n";
// e.g. http://0.0.0.0:8080
```

Open `getStreamUrl()` in a browser to see annotated frames in real time while the callback processes detections. The MJPEG URL is compatible with:
- **Web browsers** — open directly in Chrome, Firefox, Safari
- **VLC** — Media → Open Network Stream → paste the URL
- **ffmpeg** — `ffmpeg -i http://0.0.0.0:8080/stream output.mp4`

## Examples

### RTSP Security Camera

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->confidence(0.6)
    ->classes(['person', 'car'])
    ->media('rtsp://admin:password@192.168.1.50:554/cam1')
    ->streamConfig(function ($frame, $num, $result) {
        if ($frame->isNotEmpty()) {
            echo sprintf("[Frame %d] Alert: %d objects detected\n", $num, $frame->getDetectionCount());
        }
    })
    ->process();
```

### Webcam

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26n)
    ->media('0')
    ->streamConfig(function ($frame, $num, $result) {
        foreach ($frame->detections as $d) {
            echo sprintf("%s: %.0f%%\n", $d->class, $d->confidence * 100);
        }
    }, null, 50)
    ->process();
```

### HTTP MJPEG Stream

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('http://192.168.1.100:8080/video')
    ->streamConfig(function ($frame, $num, $result) {
        echo sprintf("Frame %d: %.1fms\n", $num, $frame->inferenceTime);
    })
    ->process();
```

### Stop Stream from Callback

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26s)
    ->media('rtsp://192.168.1.100:554/live')
    ->streamConfig(function ($frame, $num, $result) {
        echo sprintf("Frame %d: %d detections\n", $num, $frame->getDetectionCount());

        if ($result->getFrameCount() >= 5) {
            $result->stopStream();
        }
    })
    ->process();

echo "Stopped early: " . ($result->isStopped() ? 'yes' : 'no') . "\n";
```

## Provider Support

| Provider | Streaming | Notes |
|----------|-----------|-------|
| **Ultralytics** | Yes | Uses OpenCV capture via YOLO `model(stream_url)` |
| **NanoDet** | No | Throws `RuntimeException` if attempted |

Calling `process()` with a stream source and NanoDet provider throws a `RuntimeException`:

```php
FluentVision::make()
    ->useNanodet()
    ->media('rtsp://test')
    ->streamConfig(fn () => null)
    ->process();
// RuntimeException: Provider "nanodet" does not support streaming.
```

## Architecture

```
PHP (StreamService)                          Python (ultralytics_inference.py)
─────────────────────                        ──────────────────────────────────
media('rtsp://...')                          Parse --stream flag
streamConfig(callback)                       Open cv2.VideoCapture(source)
process() ──────────────────────► Launch process
                                             Parse --max-frames N (if set)
                                             Parse --annotate-port P (if set)
Poll getIncrementalOutput()       ◄────
Parse JSON line by line                       Print NDJSON per frame
Call onFrame(InferenceResult)                {"type":"frame","detections":[...]}
                                              {"type":"frame","detections":[...]}
                                              ...
                                              {"type":"summary","model":"...","stopped":true}
Process exits                   ◄────────────────── Print summary JSON
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
        ->media('rtsp://invalid-url')
        ->streamConfig(function ($frame, $num, $result) {
            // ...
        })
        ->process();
} catch (InferenceException $e) {
    echo "Python process error: " . $e->getMessage();
} catch (RuntimeException $e) {
    echo "Setup error: " . $e->getMessage();
}
```
