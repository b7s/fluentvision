# Custom Trained Models

FluentVision supports your own trained models alongside the built-in enum models. Pass a file path or filename to `model()` and it just works.

## Ultralytics Custom Models

Any model compatible with `YOLO()` from the Ultralytics library can be used — PyTorch `.pt`, ONNX `.onnx`, TensorRT `.engine`, and more.

### Absolute Path

Pass the full path to your trained model:

```php
use B7s\FluentVision\FluentVision;

$result = FluentVision::make()
    ->model('/home/user/models/my-yolo-custom.pt')
    ->useCpu()
    ->conf(0.4)
    ->image('photo.jpg')
    ->detect();
```

### Filename in Model Directory

Place your model in the configured model directory (default: `~/.fluentvision/models/`) and pass just the filename:

```php
$result = FluentVision::make()
    ->model('my-yolo-custom.pt')
    ->useCpu()
    ->conf(0.4)
    ->image('photo.jpg')
    ->detect();
```

FluentVision resolves the full path automatically: `~/.fluentvision/models/my-yolo-custom.pt`.

### Supported Formats

| Extension | Format | Notes |
|-----------|--------|-------|
| `.pt` | PyTorch | Trained with Ultralytics API — most common |
| `.onnx` | ONNX | Exported from Ultralytics or other frameworks |
| `.engine` | TensorRT | GPU-optimized, requires CUDA |
| `.trt` | TensorRT Legacy | Older TensorRT exports |
| `.tflite` | TensorFlow Lite | Edge / mobile deployment |
| `.mlmodel` | CoreML | Apple devices |
| `.pb` | TensorFlow | SavedModel graph |
| `.h5` | Keras | HDF5 format |
| `.savedmodel` | TensorFlow SavedModel | Directory-based |

## NanoDet Custom Models

NanoDet requires two files: a **config YAML** (`.yml`) and a **checkpoint** (`.ckpt`). Use `nanodetCustom()` to provide both:

```php
use B7s\FluentVision\FluentVision;

$result = FluentVision::make()
    ->nanodetCustom(
        configPath: '/home/user/models/my-nanodet-config.yml',
        checkpointPath: '/home/user/models/my-nanodet.ckpt'
    )
    ->useCpu()
    ->conf(0.35)
    ->image('photo.jpg')
    ->detect();
```

When you call `nanodetCustom()`, the provider is automatically set to NanoDet — no need to call `useNanodet()`.

### Custom Config YAML

The config YAML defines the model architecture, training pipeline, and class names. It must match the architecture of your checkpoint. Example structure:

```yaml
model:
  arch:
    name: NanoDetPlus
    # ... architecture definition

class_names: &class_names
  - person
  - vehicle
  - custom_class
  # ... your custom classes
```

## Provider Auto-Inference

When you pass a model path string to `model()`, FluentVision infers the correct provider from the file extension:

| Extension | Inferred Provider |
|-----------|-------------------|
| `.pt`, `.onnx`, `.engine`, `.trt`, `.tflite`, `.mlmodel`, `.mlpackage`, `.pb`, `.h5`, `.savedmodel` | **Ultralytics** |
| `.ckpt` | **NanoDet** |
| Other / no extension | No inference — uses default or explicitly set provider |

```php
// Auto-infers Ultralytics from .pt extension
$result = FluentVision::make()
    ->model('/path/to/my-model.pt')
    ->image('photo.jpg')
    ->detect();

// Auto-infers NanoDet from .ckpt extension
$result = FluentVision::make()
    ->model('/path/to/my-model.ckpt')
    ->image('photo.jpg')
    ->detect();
```

### When Auto-Inference Does Not Apply

Auto-inference is skipped when you explicitly set a provider:

```php
// Explicit provider wins — no auto-inference
$result = FluentVision::make()
    ->useUltralytics()
    ->model('custom.ckpt') // Would normally infer NanoDet, but Ultralytics is explicit
    ->image('photo.jpg')
    ->detect();
```

This is intentional — if you explicitly choose a provider, FluentVision respects that decision.

### Priority Order

1. **Explicit provider** — `->useUltralytics()`, `->useNanodet()`, or `->provider(Provider::...)`
2. **`nanodetCustom()`** — automatically sets NanoDet as provider
3. **File extension inference** — `.pt` → Ultralytics, `.ckpt` → NanoDet
4. **Config default** — `default_provider` from `fluentvision-config.php`
5. **Fallback** — `ultralytics`

## Model Resolution Logic

When you pass a string to `model()`, FluentVision resolves it in this order:

### Ultralytics Provider

1. If the string matches a `YoloModel` enum value (e.g. `'yolo26s.pt'`) → resolves via ModelService (checks modelDir, falls back to Ultralytics auto-download)
2. If the string is an **absolute path** starting with `/` and the file exists → uses the path directly
3. If `modelDir/filename` exists → uses the full path from modelDir
4. Otherwise → passes the string as-is to Ultralytics (which may auto-download known models or raise an error)

### NanoDet Provider

1. If the string matches a `NanodetModel` enum value (e.g. `'nanodet-plus-m-416'`) → resolves config + checkpoint via ModelService
2. Otherwise → uses `nanodetCustom()` config and checkpoint, or falls back to the raw string

## Examples

### Detect with a Fine-Tuned YOLO Model

```php
$result = FluentVision::make()
    ->model('/data/models/yolo-screw-defects.pt')
    ->conf(0.3)
    ->image('production-line.jpg')
    ->detect();

foreach ($result->detections as $d) {
    echo "{$d->class}: " . round($d->confidence * 100, 1) . "%\n";
}
// scratched: 87.3%
// dented: 64.1%
```

### Annotate with a Custom ONNX Export

```php
$result = FluentVision::make()
    ->model('/data/exports/yolo26s-int8.onnx')
    ->image('warehouse.jpg')
    ->annotate();

echo "Saved to: {$result->annotatedPath}\n";
```

### Custom NanoDet for a Specialized Task

```php
$result = FluentVision::make()
    ->nanodetCustom(
        configPath: '/data/models/water-meter-detect.yml',
        checkpointPath: '/data/models/water-meter-detect.ckpt'
    )
    ->conf(0.25)
    ->imgsz(416)
    ->image('meter-reading.jpg')
    ->detect();
```
