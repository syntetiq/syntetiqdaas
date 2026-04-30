# SyntetiQ

ML dataset management and model training platform.

## Overview

SyntetiQ is an end-to-end platform built on top of [OroPlatform 7](https://github.com/oroinc/platform) that lets users create and annotate image datasets, train object detection models (YOLO, SSD), and manage the full ML lifecycle from dataset import to model deployment. Synthetic training data can be generated from 3D scenes via NVIDIA Omniverse integration.

## Repository structure

| Directory | Purpose |
|---|---|
| [`app/`](app/README.md) | Symfony 7.4 / OroPlatform 7 PHP backend. Bundles: `ModelBundle`, `DataSetBundle`, `OmniverseBundle`, `DemoDataBundle`, `SetupBundle`. Frontend tooling via Node 24 / pnpm 10. |
| [`server/`](server/README.md) | Docker Compose dev environment: PHP-FPM, Nginx, PostgreSQL 16, Redis, TensorBoard, with GPU profiles (Jetson / RTX). Setup is driven through `make`. |
| [`model/`](model/README.md) | Python training engines (Ultralytics YOLO, PyTorch SSD, test). Pluggable engine architecture invoked by `runner.sh`; per-build isolation under `workdir/builds/${MODEL_BUILD_ID}`. |
| `js-components/` | Reusable Svelte 5 components — `svelte-image-editor-component` (bounding-box annotation editor) and a test harness. |
| [`docs/`](docs/) | Architecture documentation. |

## How the pieces fit together

- `app` runs inside containers defined by [`server/docker-compose.yml`](server/docker-compose.yml), which mounts `../app` and `../model/tensorboard`.
- `app` triggers training through the consumer container, which calls [`model/runner.sh`](model/runner.sh) with environment variables (`MODEL_TRAIN_ENGINE`, `MODEL_BUILD_ID`, `EPOCH`, `BATCH_SIZE`, `IMG_SIZE`, …).
- `model` engines write artifacts to a `build/` folder and signal completion with `=MODEL=<path>`; artifacts are persisted to GCS.
- `app` embeds the Svelte annotation editor from `js-components/svelte-image-editor-component`.

## Tech stack

- **Backend:** PHP 8.5, Symfony 7.4, OroPlatform 7.0
- **Data:** PostgreSQL 16, Redis
- **Frontend:** Svelte 5, Node.js 24, pnpm 10
- **ML:** Python (conda / venv), Ultralytics, PyTorch, TensorFlow, TensorBoard
- **Infra:** Docker / Docker Compose, NVIDIA GPU runtime (Jetson / RTX profiles)

## Quick start

Full setup lives in [`server/README.md`](server/README.md). The minimal path:

```bash
cd server
cp example.env .env
docker network create syntetiq-docker
docker compose up -d
make composer-install
make db-recreate
make install
```

## Further reading

- [`app/README.md`](app/README.md) — application bundles, key concepts, and domain model
- [`server/README.md`](server/README.md) — Docker setup, certificates, XDebug, GPU configuration
- [`model/README.md`](model/README.md) — training engine architecture and how to add a new engine
- [`docs/file-storage-architecture.md`](docs/file-storage-architecture.md) — file storage layout (GCS + local `var/data/`)

## License

See [`LICENSE`](LICENSE).
