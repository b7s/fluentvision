# Installation Guide

## Requirements

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.3+ | Required |
| Python | 3.8+ | With pip |
| Git | Any | For cloning NanoDet repo |
| curl | Any | For downloading models |

## Step 1: Install the PHP Package

```bash
composer require b7s/fluentvision
```

## Step 2: Set Up Python Environment

FluentVision bundles a Python bridge that executes YOLO/NanoDet inference scripts. The `install` command creates a dedicated virtual environment and installs all required Python packages.

```bash
vendor/bin/fluentvision install
```

This will:

1. Create `~/.fluentvision/venv/` (Python virtual environment)
2. Create `~/.fluentvision/models/` (model storage directory)
3. Install Ultralytics packages (`ultralytics`, `opencv-python-headless`, `pyyaml`)
4. Install NanoDet packages (`opencv-python-headless`, `pyyaml`, `torch`, `torchvision`)
5. Clone the NanoDet repository to `~/.fluentvision/nanodet/`

### Install a Single Provider

If you only need one backend:

```bash
# Ultralytics only (YOLO26)
vendor/bin/fluentvision install --provider=ultralytics

# NanoDet only
vendor/bin/fluentvision install --provider=nanodet
```

### Download Models

Models are downloaded on demand or you can pre-fetch them:

```bash
# Download a YOLO model
vendor/bin/fluentvision install --model=yolo26s.pt

# Download a YOLOE model
vendor/bin/fluentvision install --model=yoloe-26s-seg.pt

# Download a NanoDet model (config + checkpoint)
vendor/bin/fluentvision install --model=nanodet-plus-m-416

# Download any model by filename
vendor/bin/fluentvision install --model=yolo26x.pt
```

Model files are stored in:

| Provider | Path | Contents |
|----------|------|----------|
| Ultralytics | `~/.fluentvision/models/yolo26s.pt` | Single `.pt` weight file |
| YOLOE | `~/.fluentvision/models/yoloe-26s-seg.pt` | Single `.pt` weight file |
| NanoDet | `~/.fluentvision/models/nanodet-plus-m_416_checkpoint.ckpt` | Config `.yml` (from repo) + checkpoint `.ckpt` |

## Step 3: Verify Your Setup

```bash
vendor/bin/fluentvision doctor
```

This checks:

- Python interpreter found and version
- Virtual environment exists
- Python packages installed (ultralytics, OpenCV, PyYAML)
- Model directory exists and is writable
- NanoDet repository cloned
- Configuration loaded

### Example Output

```
FluentVision Doctor
===================

Python Interpreter
 ✓ Python found: /home/user/.fluentvision/venv/bin/python
 ✓ Version: 3.11.5

Python Virtual Environment
 ✓ Venv exists: /home/user/.fluentvision/venv

Python Packages
 ✓ Ultralytics YOLO installed
 ✓ OpenCV (cv2) installed
 ✓ PyYAML installed

Model Directory
 ✓ Model directory: /home/user/.fluentvision/models
 ✓ Directory is writable

NanoDet Repository
 ✓ NanoDet repo: /home/user/.fluentvision/nanodet

Configuration
 ✓ Default provider: ultralytics

 [OK] All checks passed. FluentVision is ready to use!
```

## Custom Install Location

By default, everything lives under `~/.fluentvision/`. You can customize paths via the config file:

```bash
vendor/bin/fluentvision install --config=/path/to/my-config.php
```

See [Configuration Reference](configuration.md) for all available options.

## GPU Support

For GPU inference (NVIDIA CUDA):

1. Install CUDA toolkit and drivers on your system
2. Install PyTorch with CUDA support:

```bash
~/.fluentvision/venv/bin/pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121
```

3. Use GPU in your PHP code:

```php
FluentVision::make()
    ->useGpu()
    ->half()  // FP16 inference for faster GPU performance
    ->media('photo.jpg')
    ->detect();
```

## Troubleshooting

### Python Not Found

If `fluentvision doctor` reports Python not found:

1. Install Python 3.8+: `sudo apt install python3 python3-pip python3-venv`
2. Or set the path explicitly in config: `'python_path' => '/usr/bin/python3.11'`

### Venv Creation Failed

```bash
# Remove and recreate
rm -rf ~/.fluentvision/venv
vendor/bin/fluentvision install
```

### Package Install Failed

```bash
# Manually install into the venv
~/.fluentvision/venv/bin/pip install ultralytics opencv-python-headless pyyaml
```

### Model Download Failed

Downloads use `curl`. If behind a proxy or firewall:

```bash
# Download manually
curl -L -o ~/.fluentvision/models/yolo26s.pt \
    https://github.com/ultralytics/assets/releases/download/v8.4.0/yolo26s.pt
```
