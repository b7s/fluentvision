# Providers

FluentVision supports two object detection backends. Both return the same PHP result types, so you can switch providers without changing downstream code.

## Provider Comparison

| Feature | Ultralytics | NanoDet |
|---------|-------------|---------|
| Backend | YOLO26 / YOLOE-26 | NanoDet-Plus |
| Best for | High accuracy, multi-task, open-vocabulary | Edge devices, real-time |
| Tasks | detect, segment, classify, pose, obb | detect only |
| Model sizes | n (2.7M) to x (68M params) | Lightweight only |
| Open-vocabulary | YOLOE models with text prompts | No |
| GPU support | CUDA | CUDA |
| Model format | `.pt` weights | `.yml` config + `.ckpt` weights |
| Python bridge | `scripts/ultralytics_inference.py` | `scripts/nanodet_inference.py` |

## Ultralytics Provider

The default provider. Powered by the Ultralytics YOLO26 family — the latest generation of YOLO models.

### Available Models

| Model | Enum Value | Size | Description |
|-------|-----------|------|-------------|
| YOLO26n | `YoloModel::YOLO26n` | 2.7M | Nano — smallest, fastest |
| YOLO26s | `YoloModel::YOLO26s` | — | Small — good balance (default) |
| YOLO26m | `YoloModel::YOLO26m` | — | Medium — higher accuracy |
| YOLO26l | `YoloModel::YOLO26l` | — | Large — high accuracy |
| YOLO26x | `YoloModel::YOLO26x` | 68M | Extra Large — maximum accuracy |
| YOLOE-26s | `YoloModel::YOLOE26s` | 29.6 MB | YOLOE Small — open-vocabulary with text prompts |
| YOLOE-26m | `YoloModel::YOLOE26m` | 67.2 MB | YOLOE Medium — open-vocabulary with text prompts |
| YOLOE-26l | `YoloModel::YOLOE26l` | 75.7 MB | YOLOE Large — open-vocabulary with text prompts |
| YOLOE-26s PF | `YoloModel::YOLOE26sPF` | 31.2 MB | YOLOE Small — prompt-free auto-detection |
| YOLOE-26m PF | `YoloModel::YOLOE26mPF` | 69.5 MB | YOLOE Medium — prompt-free auto-detection |
| YOLOE-26l PF | `YoloModel::YOLOE26lPF` | 77.9 MB | YOLOE Large — prompt-free auto-detection |

### Supported Tasks

| Task | Enum Value | Description |
|------|-----------|-------------|
| Detect | `YoloTask::Detect` | Object detection (default) |
| Segment | `YoloTask::Segment` | Instance segmentation |
| Classify | `YoloTask::Classify` | Image classification |
| Pose | `YoloTask::Pose` | Pose/keypoint estimation |
| Obb | `YoloTask::Obb` | Oriented bounding boxes |

### Ultralytics-Specific Options

These options are only available with the Ultralytics provider:

| Option | Method | Description |
|--------|--------|-------------|
| Task | `->task(YoloTask::Segment)` | Set YOLO task type |
| Augment | `->augment()` | Test-time augmentation (slower, more accurate) |
| Agnostic NMS | `->agnosticNms()` | Class-agnostic non-maximum suppression |
| Half precision | `->half()` | FP16 inference (GPU only, faster) |
| End-to-end | `->end2end()` | Use end-to-end model (skip NMS) |

### Example

```php
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Enums\YoloModel;
use B7s\FluentVision\Enums\YoloTask;

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLO26m)
    ->task(YoloTask::Segment)
    ->confidence(0.5)
    ->useGpu()
    ->half()
    ->media('street.jpg')
    ->detect();
```

### YOLOE-26 Open-Vocabulary Detection

YOLOE models support **text prompts** — you can detect arbitrary concepts beyond the standard 80 COCO classes. This enables:

- **Attribute detection**: clothing color ("person wearing red"), materials ("wooden table"), patterns ("striped shirt")
- **Scene attributes**: "nighttime scene", "daytime scene", "indoor", "outdoor"
- **Domain-specific objects**: "hard hat", "safety vest", "medical equipment"
- **Fine-grained categories**: "coffee cup" vs "mug", "office desk" vs "dining table"

There are two YOLOE variants:

| Variant | Suffix | `set_classes()` | Best For |
|---------|--------|----------------|----------|
| **Text-prompted** | `-seg.pt` | Required | Targeted detection with custom text prompts |
| **Prompt-free** | `-seg-pf.pt` | Not supported | Auto-detect using learned vocabulary (no prompts needed) |

#### Text-Prompted YOLOE

Use `->prompts()` to specify what to detect:

```php
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Enums\YoloModel;

$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26s)
    ->useCpu()
    ->confidence(0.25)
    ->prompts(['person', 'hard hat', 'person wearing yellow', 'nighttime scene'])
    ->media('factory.jpg')
    ->detect();

foreach ($result->detections as $d) {
    echo sprintf("%s: %.1f%%\n", $d->class, $d->confidence * 100);
}
// Output:
// person: 88.5%
// person: 84.1%
// hard hat: 36.5%
// person wearing yellow: 30.3%
// nighttime scene: 83.0%
```

#### Prompt-Free YOLOE

Prompt-free models auto-detect using a learned vocabulary without requiring text prompts:

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26sPF)
    ->useCpu()
    ->confidence(0.25)
    ->media('workspace.jpg')
    ->detect();
```

#### CPU vs GPU

All YOLOE models run on **CPU** (~0.15-0.20s per image for YOLOE-26s). Use GPU for faster inference on large batches:

```php
$result = FluentVision::make()
    ->useUltralytics()
    ->model(YoloModel::YOLOE26m)
    ->useGpu()
    ->prompts(['person wearing blue', 'safety vest'])
    ->media('construction-site.jpg')
    ->detect();
```

#### YOLOE Limitations

- The `->agnosticNms()` and `->end2end()` options are **not compatible** with YOLOE models (they are silently ignored)
- Prompt-free models do **not** support `->prompts()` (calling it has no effect)
- Text-prompted models **require** `->prompts()` — without prompts, they detect the standard 80 COCO classes

## NanoDet Provider

Ultra-lightweight detector designed for edge devices and real-time applications. Based on [NanoDet-Plus](https://github.com/RangiLyu/nanodet).

### Available Models

| Model | Enum Value | Description |
|-------|-----------|-------------|
| Plus M 320 | `NanodetModel::PlusM320` | Plus-M at 320px |
| Plus M 416 | `NanodetModel::PlusM416` | Plus-M at 416px (default) |
| Plus M 1.5x | `NanodetModel::PlusM1x5` | Plus-M 1.5x wider |
| Plus T 416 | `NanodetModel::PlusT416` | Plus-T at 416px (tiny) |
| G 416 | `NanodetModel::G416` | NanoDet-G at 416px |
| EfficientLite 320 | `NanodetModel::EfficientLite320` | EfficientLite at 320px |
| RepVGG-A 416 | `NanodetModel::RepVGGA416` | RepVGG-A at 416px |

### NanoDet Setup

NanoDet requires cloning the NanoDet repository (done automatically by `fluentvision install`):

```bash
# Install NanoDet provider
vendor/bin/fluentvision install --provider=nanodet

# Download a model
vendor/bin/fluentvision install --model=nanodet-plus-m-416
```

Each NanoDet model consists of two files:

- **Config**: `.yml` file (model architecture and inference settings)
- **Checkpoint**: `.ckpt` file (trained weights)

These are stored in `~/.fluentvision/models/{model-name}/`.

### Example

```php
use B7s\FluentVision\FluentVision;
use B7s\FluentVision\Enums\NanodetModel;

$result = FluentVision::make()
    ->useNanodet()
    ->model(NanodetModel::PlusM416)
    ->confidence(0.4)
    ->useCpu()
    ->media('photo.jpg')
    ->detect();
```

## Switching Providers

Switch at runtime without changing result handling code:

```php
use B7s\FluentVision\Enums\Provider;

// Use Ultralytics for high-accuracy batch processing
$yoloResult = FluentVision::make()
    ->provider(Provider::Ultralytics)
    ->media('detailed.jpg')
    ->detect();

// Use NanoDet for real-time edge inference
$nanoResult = FluentVision::make()
    ->provider(Provider::Nanodet)
    ->media('detailed.jpg')
    ->detect();

// Both return InferenceResult — same API
echo $yoloResult->getDetectionCount();  // works
echo $nanoResult->getDetectionCount();  // works
```

## How It Works Internally

1. FluentVision resolves the provider and builds CLI arguments
2. PHP executes the provider's Python script via Symfony Process:
   - Ultralytics: `scripts/ultralytics_inference.py`
   - NanoDet: `scripts/nanodet_inference.py`
3. The Python script runs inference and outputs JSON to stdout
4. `InferenceService` parses JSON into typed PHP result objects
5. Results are always `InferenceResult` with `DetectionResult[]`, regardless of provider

### Device Mapping

| Device | Ultralytics Arg | NanoDet Arg |
|--------|----------------|-------------|
| CPU | `cpu` | `cpu` |
| GPU | `0` (CUDA device index) | `cuda:0` |
