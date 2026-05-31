# Configuration Reference

FluentVision loads configuration from a PHP config file. Settings can also be overridden at runtime via the fluent API.

## Config File Locations

FluentVision searches for `fluentvision-config.php` in this order:

1. Path passed to `FluentVision::make($path)`
2. Current working directory: `getcwd() . '/fluentvision-config.php'`
3. Package root: `dirname(__DIR__) . '/fluentvision-config.php'`

The first file found wins. If no config file exists, defaults are used.

## Creating a Config File

Create `fluentvision-config.php` in your project root:

```php
<?php

declare(strict_types=1);

return [
    'default_provider'          => 'ultralytics',
    'ultralytics_default_model' => 'yolo26s.pt',
    'nanodet_default_model'     => 'nanodet-plus-m-416',
    'default_task'              => 'detect',
    'default_device'            => 'cpu',
    'default_conf'              => 0.25,
    'default_iou'               => 0.7,
    'default_imgsz'             => 640,
    'default_max_det'           => 300,
    'python_path'               => null,
    'python_venv_path'          => null,
    'model_dir'                 => null,
    'nanodet_repo_path'         => null,
    'timeout'                   => 0,
    'verbose'                   => false,
];
```

## All Options

### Provider Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `default_provider` | string | `'ultralytics'` | Default backend: `'ultralytics'` or `'nanodet'` |
| `ultralytics_default_model` | string | `'yolo26s.pt'` | Default YOLO model filename |
| `nanodet_default_model` | string | `'nanodet-plus-m-416'` | Default NanoDet model identifier |
| `default_task` | string | `'detect'` | Default YOLO task: `detect`, `segment`, `classify`, `pose`, `obb` |
| `default_device` | string | `'cpu'` | Default device: `'cpu'` or `'gpu'` |

### Inference Defaults

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `default_conf` | float | `0.25` | Confidence threshold (0.0 - 1.0) |
| `default_iou` | float | `0.7` | IoU threshold for NMS (0.0 - 1.0) |
| `default_imgsz` | int | `640` | Inference image size in pixels |
| `default_max_det` | int | `300` | Maximum detections per image |

### Python Environment

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `python_path` | string\|null | `null` | Explicit Python binary path. `null` = auto-detect |
| `python_venv_path` | string\|null | `null` | Virtual environment path. Default: `~/.fluentvision/venv` |

Python resolution order:

1. `python_path` from config (if set)
2. `{python_venv_path}/bin/python` (if venv exists)
3. System `python3`
4. System `python`

### Storage Paths

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `model_dir` | string\|null | `null` | Model storage directory. Default: `~/.fluentvision/models` |
| `nanodet_repo_path` | string\|null | `null` | NanoDet Git repo path. Default: `~/.fluentvision/nanodet` |

### Process Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `timeout` | int | `0` | Python process timeout in seconds. `0` = no timeout |
| `verbose` | bool | `false` | Enable verbose output from Python scripts |

## Runtime Override

Config defaults can be overridden at runtime via the fluent API. Fluent API calls always take precedence over config file values:

```php
// Config says conf=0.25, but this uses 0.5
FluentVision::make()
    ->confidence(0.5)
    ->media('photo.jpg')
    ->detect();
```

## Custom Config Path

Load a config from a specific file:

```php
$vision = FluentVision::make('/path/to/custom-config.php');
```

Or via CLI:

```bash
vendor/bin/fluentvision doctor --config=/path/to/config.php
vendor/bin/fluentvision install --config=/path/to/config.php
```

## Accessing Config Values

You can read config values directly:

```php
$vision = FluentVision::make();

$config = $vision->getConfig();
echo $config->string('default_provider');     // 'ultralytics'
echo $config->integer('default_imgsz');       // 640
echo $config->float('default_conf');          // 0.25
echo $config->bool('verbose');                // false

// Convenience methods
echo $config->defaultProvider();              // 'ultralytics'
echo $config->pythonPath();                   // null or '/usr/bin/python3'
echo $config->pythonVenvPath();               // '/home/user/.fluentvision/venv'
echo $config->modelDir();                     // '/home/user/.fluentvision/models'
echo $config->nanodetRepoPath();              // '/home/user/.fluentvision/nanodet'
echo $config->timeout();                      // 0
echo $config->verbose();                      // false
```

## Directory Structure

Default `~/.fluentvision/` layout:

```
~/.fluentvision/
├── venv/                          # Python virtual environment
│   ├── bin/
│   │   └── python                 # Venv Python binary
│   └── lib/
├── models/
│   ├── yolo26n.pt                 # Ultralytics model weights
│   ├── yolo26s.pt
│   ├── yolo26m.pt
│   ├── yolo26l.pt
│   ├── yolo26x.pt
│   └── nanodet-plus-m-416/        # NanoDet model directory
│       ├── nanodet-plus-m-416.yml   # Config file
│       └── nanodet-plus-m-416.ckpt  # Checkpoint weights
└── nanodet/                       # NanoDet Git repository
    ├── nanodet/
    ├── config/
    └── ...
```
