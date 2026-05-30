<?php

declare(strict_types=1);

return [
    'default_provider' => 'ultralytics',
    'python_path' => null,
    'python_venv_path' => null,
    'ultralytics_default_model' => 'yolo26s.pt',
    'nanodet_default_model' => 'nanodet-plus-m_416',
    'default_task' => 'detect',
    'default_device' => 'cpu',
    'default_conf' => 0.25,
    'default_iou' => 0.7,
    'default_imgsz' => 640,
    'default_max_det' => 300,
    'model_dir' => null,
    'nanodet_repo_path' => null,
    'timeout' => 0,
    'verbose' => false,
];
