<?php

declare(strict_types=1);

return [
    'default_provider' => 'ultralytics',
    'python_path' => null,
    'python_venv_path' => '/tmp/fluentvision-test-venv',
    'ultralytics_default_model' => 'yolo26s.pt',
    'nanodet_default_model' => 'nanodet-plus-m-416',
    'default_task' => 'detect',
    'default_device' => 'cpu',
    'default_conf' => 0.4,
    'default_iou' => 0.7,
    'default_imgsz' => 640,
    'default_max_det' => 300,
    'model_dir' => '/tmp/fluentvision-test-models',
    'nanodet_repo_path' => '/tmp/fluentvision-test-nanodet',
    'timeout' => 0,
    'verbose' => false,
];
