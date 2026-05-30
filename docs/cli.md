# CLI Reference

FluentVision ships with two console commands for setup and diagnostics.

## Entry Point

```bash
vendor/bin/fluentvision <command> [options]
```

## Commands

### `install`

Set up the Python environment, install dependencies, and download models.

```bash
vendor/bin/fluentvision install [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--provider=PROVIDER` | Install for a specific provider (`ultralytics` or `nanodet`). Default: `ultralytics` |
| `--model=MODEL` | Download a specific model. Default: `yolo26s.pt` for Ultralytics, `nanodet-plus-m-416` for NanoDet |
| `--gpu` | Install GPU dependencies (CUDA, PyTorch with CUDA support) |

#### Examples

```bash
# Default install (Ultralytics + YOLO26s)
vendor/bin/fluentvision install

# Install with GPU support
vendor/bin/fluentvision install --gpu

# Install NanoDet provider
vendor/bin/fluentvision install --provider=nanodet

# Install NanoDet with a specific model
vendor/bin/fluentvision install --provider=nanodet --model=nanodet-plus-t-416

# Install Ultralytics with a larger model
vendor/bin/fluentvision install --model=yolo26m.pt

# Full setup: both providers, GPU
vendor/bin/fluentvision install --provider=ultralytics --gpu
vendor/bin/fluentvision install --provider=nanodet --gpu
```

#### What It Does

1. Creates the Python virtual environment at `~/.fluentvision/venv/` (if not exists)
2. Installs required pip packages:
   - **Ultralytics**: `ultralytics`, `torch`, `torchvision`, `opencv-python`
   - **NanoDet**: `torch`, `torchvision`, `opencv-python`, plus clones the NanoDet repo to `~/.fluentvision/nanodet/`
   - **GPU**: installs CUDA-enabled PyTorch instead of CPU-only
3. Downloads the requested model to `~/.fluentvision/models/`
   - **Ultralytics**: downloads `.pt` weight file
   - **NanoDet**: downloads `.yml` config + `.ckpt` checkpoint

### `doctor`

Diagnose your FluentVision installation and environment.

```bash
vendor/bin/fluentvision doctor [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--provider=PROVIDER` | Check a specific provider. Default: checks both |

#### What It Checks

| Check | Description |
|-------|-------------|
| Python | Is Python 3.9+ available and in the venv? |
| Virtual environment | Does `~/.fluentvision/venv/` exist and have pip? |
| Pip packages | Are `torch`, `ultralytics`/`nanodet`, `opencv-python` installed? |
| Model files | Does the default model exist in `~/.fluentvision/models/`? |
| GPU | Is CUDA available in PyTorch? (if `--gpu` was used during install) |
| Config | Can the config file be loaded? |
| NanoDet repo | Does `~/.fluentvision/nanodet/` exist? (NanoDet only) |

#### Example Output

```
FluentVision Doctor
===================

[OK]   Python 3.12.2 found in venv
[OK]   Virtual environment active at ~/.fluentvision/venv/
[OK]   torch 2.4.0 installed
[OK]   ultralytics 8.3.0 installed
[OK]   opencv-python 4.10.0 installed
[OK]   Model yolo26s.pt found at ~/.fluentvision/models/
[OK]   CUDA available (NVIDIA GeForce RTX 4090)
[OK]   Config file loaded successfully

All checks passed.
```

#### Failure Output

```
FluentVision Doctor
===================

[OK]   Python 3.12.2 found in venv
[FAIL] Virtual environment missing at ~/.fluentvision/venv/
[SKIP] torch — venv not found
[SKIP] ultralytics — venv not found
[SKIP] opencv-python — venv not found
[FAIL] Model yolo26s.pt not found at ~/.fluentvision/models/
[OK]   Config file loaded successfully

2 checks failed. Run 'fluentvision install' to fix.
```

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All checks passed (doctor) / install succeeded |
| 1 | One or more checks failed (doctor) / install failed |

## File Locations

| Path | Description |
|------|-------------|
| `~/.fluentvision/venv/` | Python virtual environment |
| `~/.fluentvision/models/` | Downloaded model weights |
| `~/.fluentvision/nanodet/` | NanoDet repository clone |
| `fluentvision-config.php` | Project-level config file (in your project root) |

All paths are configurable via the config file. See [Configuration](configuration.md).
