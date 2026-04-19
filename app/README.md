# SyntetiQ Application

SyntetiQ is a machine learning dataset management and model training platform built on top of OroPlatform 7. It allows users to create and annotate image datasets, train object detection models (YOLO, SSD), and manage the full ML lifecycle from dataset import to model deployment.

## Bundles

| Bundle | Purpose |
|--------|---------|
| `ModelBundle` | ML model management: create models, configure engines (Ultralytics/PyTorch SSD), trigger builds, view training artifacts and QA reports |
| `DataSetBundle` | Dataset management: import images (ZIP/chunked upload), annotate items with bounding boxes in a Svelte-based editor, label management, export |
| `OmniverseBundle` | Synthetic data generation via NVIDIA Omniverse integration: generate images from 3D scenes to augment datasets |
| `DemoDataBundle` | Demo fixtures for development/onboarding |
| `SetupBundle` | Application setup and initial configuration |

## Key Concepts

- **Dataset → Items → Annotations**: a `DataSet` contains `DataSetItem` records (images), each with `ItemObjectConfiguration` bounding box annotations
- **Model → Build → Artifacts**: a `Model` defines engine + hyperparameters; a `ModelBuild` is a single training run with logs and output artifacts stored in GCS
- **Engine architecture**: model training runs are isolated per-build via `runner.sh` in the `model/` directory; engines are pluggable (`ultralytics`, `pytorch_ssd_jetson`, `test`)
- **Storage**: GCS for persistent files (attachments, artifacts), local `var/data/` for in-flight operations (chunks, imports, model workdir)

## Tech Stack

- **PHP 8.5** / Symfony 7.4 / OroPlatform 7.0
- **PostgreSQL 16** + **Redis**
- **Svelte 5** (image annotation editor component)
- **Node.js 24 / pnpm 10** (frontend tooling)
- **Python / uv** (model training scripts)
- **Docker** (development environment via `server/`)

## Setup

See `server/README.md` for Docker environment setup and `CLAUDE.md` for all build/development commands.

```bash
cd server
cp example.env .env
make up
make composer-install
make db-recreate
make install
```
