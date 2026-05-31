# FluentVision - Progress Tracker

## Description

FluentVision is a PHP 8.3+ fluent API package (`b7s/fluentvision`) that provides a **unified interface** to two object detection backends:

- **Ultralytics** (YOLO26 family) — full-featured, multi-task, high accuracy
- **NanoDet** (NanoDet-Plus family) — ultra-lightweight, super fast, edge-optimized

The user selects the provider via config or fluent API, then the same chainable methods work regardless of which backend runs inference. Both providers share the same result types (InferenceResult, DetectionResult, BoundingBox).

## How It Works

1. **PHP Fluent API** — User chains methods like `FluentVision::make()->provider(Provider::Ultralytics)->model(YoloModel::YOLO26s)->useCpu()->confidence(0.5)->media('photo.jpg')->detect()`
2. **Provider Resolution** — FluentVision resolves which provider to use (Ultralytics or NanoDet) and builds the correct Python command
3. **Python Bridge** — PHP executes a bundled Python script via Symfony Process:
   - `scripts/ultralytics_inference.py` for Ultralytics provider
   - `scripts/nanodet_inference.py` for NanoDet provider
4. **JSON Output** — Python scripts run YOLO/NanoDet inference and output structured JSON to stdout
5. **PHP Parsing** — InferenceService parses the JSON into typed PHP result objects (InferenceResult, DetectionResult, BoundingBox)
6. **Same Results** — Regardless of provider, the PHP result objects are identical in structure

### Provider Selection Order
1. Explicit `->provider(Provider::Nanodet)` in fluent chain
2. Config file `default_provider` setting
3. Default: `ultralytics`

### Python Environment Resolution
1. Explicit `python_path` from config
2. `python_venv_path/bin/python` (default: `~/.fluentvision/venv/bin/python`)
3. System `python3`
4. System `python`

### Model Storage
- Ultralytics models: `~/.fluentvision/models/yolo26s.pt` etc.
- NanoDet models: config from cloned repo (`~/.fluentvision/nanodet/config/`), checkpoints in `~/.fluentvision/models/` (flat)

### Key Design Principles
- Entities own transitions (state machine pattern from Laravel Statecraft)
- Events are facts — no hidden side effects
- Context is immutable — WorkflowContext is readonly
- Fail fast — guard clauses, typed exceptions
- Readonly by default — value objects use readonly
- Provider-agnostic results — same DetectionResult regardless of backend

---

## Phase 1: Project Scaffold
- [x] 1.1 Create composer.json
- [x] 1.2 Create .gitignore
- [x] 1.3 Create version
- [x] 1.4 Create LICENSE
- [x] 1.5 Create phpstan.neon (level max)
- [x] 1.6 Create phpunit.xml
- [x] 1.7 Create Makefile
- [x] 1.8 Create catraca_baseline.json
- [x] 1.9 Create AGENTS.md
- [x] 1.10 Create fluentvision-config.php
- [x] 1.11 Create bin/fluentvision
- [x] 1.12 Run composer install

## Phase 2: Enums
- [x] 2.1 Create Provider enum
- [x] 2.2 Create YoloModel enum
- [x] 2.3 Create NanodetModel enum
- [x] 2.4 Create YoloTask enum
- [x] 2.5 Create Device enum
- [x] 2.6 Run tests and catraca and fix any failures

## Phase 3: Exceptions
- [x] 3.1 Create FluentVisionException
- [x] 3.2 Create PythonNotFoundException
- [x] 3.3 Create ModelNotFoundException
- [x] 3.4 Create ProviderNotFoundException
- [x] 3.5 Create InferenceException
- [x] 3.6 Run tests and catraca and fix any failures

## Phase 4: Support & Results
- [x] 4.1 Create BoundingBox
- [x] 4.2 Create DetectionResult
- [x] 4.3 Create InferenceResult
- [x] 4.4 Create AnnotatedResult
- [x] 4.5 Create VideoInferenceResult
- [x] 4.6 Run tests and catraca and fix any failures

## Phase 5: Services
- [x] 5.1 Create PythonService
- [x] 5.2 Create ModelService
- [x] 5.3 Create ProviderContract interface
- [x] 5.4 Create UltralyticsProvider
- [x] 5.5 Create NanodetProvider
- [x] 5.6 Create ProviderFactory
- [x] 5.7 Create InferenceService
- [x] 5.8 Run tests and catraca and fix any failures

## Phase 6: Python Scripts
- [x] 6.1 Create scripts/ultralytics_inference.py
- [x] 6.2 Create scripts/nanodet_inference.py
- [x] 6.3 Run tests and catraca and fix any failures

## Phase 7: Config + Console
- [x] 7.1 Create Config.php
- [x] 7.2 Create Console/Application.php
- [x] 7.3 Create Console/Commands/DoctorCommand.php
- [x] 7.4 Create Console/Commands/InstallCommand.php
- [x] 7.5 Run tests and catraca and fix any failures

## Phase 8: Main Class
- [x] 8.1 Create FluentVision.php
- [x] 8.2 Run tests and catraca and fix any failures

## Phase 9: Tests
- [x] 9.1 Create tests/Pest.php
- [x] 9.2 Create tests/TestCase.php
- [x] 9.3 Create tests/Unit/EnumsTest.php
- [x] 9.4 Create tests/Unit/BoundingBoxTest.php
- [x] 9.5 Create tests/Unit/DetectionResultTest.php
- [x] 9.6 Create tests/Unit/InferenceResultTest.php
- [x] 9.7 Create tests/Unit/ConfigTest.php
- [x] 9.8 Create tests/Unit/FluentVisionTest.php
- [x] 9.9 Create tests/Unit/PythonServiceTest.php
- [x] 9.10 Create tests/Unit/ProviderFactoryTest.php
- [x] 9.11 Run tests and catraca and fix any failures

## Phase 10: Quality Gates + Git
- [x] 10.1 Run pest tests — 71 passing
- [x] 10.2 Run phpstan level max — 0 errors
- [x] 10.3 Run catraca — 8/8 gates pass (duplication 0.00%)
- [x] 10.4 Fix any failures — all green
- [x] 10.5 Create README.md with basic usage and a "docs/" folder with a complete guide
  - [x] README.md
  - [x] docs/installation.md
  - [x] docs/configuration.md
  - [x] docs/usage.md
  - [x] docs/providers.md
  - [x] docs/results.md
  - [x] docs/cli.md
- [ ] 10.6 Git push to main

## Phase 11: Real Examples with Both Providers
- [x] 11.1 Create examples/ultralytics_detect.php — YOLO26s detection on all 3 example images
- [x] 11.2 Create examples/nanodet_detect.php — NanoDet-Plus M 416 detection on all 3 example images
- [x] 11.3 Create examples/compare_providers.php — side-by-side Ultralytics vs NanoDet comparison
- [x] 11.4 Create examples/annotate_example.php — Ultralytics annotation output
- [x] 11.5 Add examples/output/ to .gitignore (annotated images)
- [x] 11.6 Run `php examples/ultralytics_detect.php` and verify expected items are detected
- factory-workers...jpg → person (91%, 89%)
- modern-workspace...jpg → cup, potted plant (6), laptop, book, dining table
- woman-cup-coffe.jpg → person, cup
- [x] 11.7 Run `php examples/nanodet_detect.php` and verify expected items are detected
- factory-workers...jpg → person (82%, 64%)
- modern-workspace...jpg → cup, potted_plant (7), laptop, dining_table, vase
- woman-cup-coffe.jpg → person (82%)
- [x] 11.8 Run `php examples/compare_providers.php` and review both providers output
- [x] 11.9 Run pest tests + phpstan + catraca — all green

## Phase 11 Bug Fixes (during example testing)
- [x] Fixed NanodetModel::checkpointUrl() — release tag changed from v1.0.0 to v1.0.0-alpha-1
- [x] Fixed nanodet_inference.py parse_detections() — enumerate(dets) changed to dets.items() (dict keyed by class_id)
- [x] Fixed nanodet_inference.py stdout pollution — redirected non-JSON output to stderr (sys.stdout = sys.stderr)
- [x] Fixed ultralytics_inference.py stdout pollution — same redirect pattern for Ultralytics YOLO output
- [x] Updated NanodetModel tests — configFilename/checkpointFilename match actual repo file names
- [x] Removed configUrl() test (method removed from enum), replaced with checkpointUrl() test
- [x] Cleaned up stale files: project-root yolo26s.pt, ~/.fluentvision/models/nanodet-plus-m-416/
- [x] Added *.pt, *.ckpt, /runs/ to .gitignore
- [x] Added "package vs direct" usage docblocks to all 4 example files
- [x] Re-downloaded NanoDet checkpoint with correct filename (35MB, v1.0.0-alpha-1 release)
- [x] Pint style fixes applied to EnumTest, InstallCommand, ModelService
