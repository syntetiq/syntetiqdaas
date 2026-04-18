# Model Module Documentation

This module provides a flexible, engine-based environment for training and running machine learning models, primarily focused on object detection (YOLO, SSD) using TensorFlow and PyTorch.

## Overview

The `model` module is designed for isolated, reproducible model training runs. It uses a pluggable "engine" architecture where different training frameworks and model types can be easily integrated. The execution flow is managed by a central runner script that handles virtual environment setup and cleanup.

## Core Features

### 1. Pluggable Engine Architecture
Each training logic is encapsulated in an "engine" directory under `script/engine/`. This allows switching between different frameworks (e.g., Ultralytics for YOLO, PyTorch for SSD) by simply changing the `MODEL_TRAIN_ENGINE` environment variable.

### 2. Automated Environment Management
The `runner.sh` script automatically manages Python virtual environments:
- **Conda Support**: Creates and activates an isolated Conda environment for each build.
- **Venv Support**: Fallback to standard Python `venv`.
- **Cleanup**: Virtual environments are automatically removed after the run to save space.

### 3. Build Isolation
Each training run is performed in a dedicated directory: `workdir/builds/${MODEL_BUILD_ID}`. This prevents concurrent runs from interfering with each other and maintains a clean workspace.

### 4. TensorBoard Integration
Engines can export training telemetry to a central `tensorboard/` directory, allowing for real-time monitoring and comparison of different training runs.

### 5. Automated Dependency Installation
Each engine's `init.py` script identifies and installs the necessary GPU or CPU dependencies (TensorFlow, PyTorch, etc.) based on the detected hardware and environment configuration.

---

## How to Extend

The module is designed to be easily extended with new training engines.

### 1. Create Engine Directory
Create a new folder in `script/engine/<your_engine_name>`.

### 2. Implement `init.py` (Optional)
This script is executed before training. Use it to install specific library versions or prepare the environment.
- Access environment variables like `GPU` (enable/disable) and `PYTHON_ENV`.
- Use `os.system("pip install ...")` for installations.

### 3. Implement `run.py`
This is the main entry point for your training logic.
- **Input Data**: Typically located in `ds/` within the build directory.
- **Environment Variables**: Use these for configuration:
    - `MODEL_BUILD_ID`: Unique ID for the current build.
    - `EPOCH`: Number of training iterations.
    - `MODEL_NAME`: Base model architecture (e.g., `yolo8n`, `mb1-ssd`).
    - `BATCH_SIZE`, `IMG_SIZE`: Hyperparameters.
- **Output Artifacts**:
    - Save weights/models to a `build/` folder.
    - Export logs to `build.log` and `build.err` (handled by `runner.sh`).
    - **Crucial**: Print `=MODEL=<path_to_zip>` at the end of the script to signal the location of the resulting model archive.

### 4. Example Execution
Run your new engine using `runner.sh`:
```bash
PYTHON_ENV=venv MODEL_TRAIN_ENGINE=your_engine_name EPOCH=10 ./runner.sh
```

---

## Directory Structure

- `runner.sh`: Main entry script.
- `script/engine/`: Available training engines (e.g., `ultralytics`, `test`).
- `script/libs/`: Shared utility scripts (e.g., TFRecord generators).
- `workdir/`: Temporary workspace for builds and cached data.
- `tensorboard/`: Training telemetry data.
