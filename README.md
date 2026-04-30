# SyntetiQ DaaS — Dataset & Model Lifecycle Platform

End-to-end web platform for ML dataset management and model training,
with synthetic-data generation via NVIDIA Omniverse / Isaac Sim and
robotic-demonstration ingest from RoboLab. Built on top of
[OroPlatform 7](https://github.com/oroinc/platform).

This repository is one of three components of the SyntetiQ AI-driven
robotics stack delivered for the [euROBIN](https://www.eurobin-project.eu/)
3rd Open Call Technology Exchange Programme (Sub-Grant Agreement
`euROBIN_3OC_8`, Horizon Europe Grant `101070596`).

## SyntetiQ stack at a glance

```
                    ┌──────────────────────────┐
                    │     SyntetiQ DaaS        │
                    │     (this repository)    │
                    │                          │
     ┌──────────┐   │  • Dataset import / QA   │  ┌───────────┐
     │ isaacsim │──▶│  • Annotation editor     │◀─│  robolab  │
     │   app    │   │  • Model training (YOLO/ │  │           │
     └──────────┘   │     SSD/Jetson engines)  │  └───────────┘
   Synthetic image  │  • Artefact registry     │  TIAGo episodes:
   generator (REST  │  • TensorBoard, GCS      │  HDF5 + manifests
   on Omniverse /   │                          │  + multi-camera
   Isaac Sim)       └──────────────────────────┘  RGB / depth / sem
```

| Repository | Role |
|---|---|
| [`syntetiq/syntetiqdaas`](https://github.com/syntetiq/syntetiqdaas) | DaaS portal — this repo |
| [`syntetiq/isaacsimapp`](https://github.com/syntetiq/isaacsimapp) | Synthetic-data generator (Omniverse / Isaac Sim Replicator); the `OmniverseBundle` calls its REST API |
| [`syntetiq/robolab`](https://github.com/syntetiq/robolab) | Robotic data-collection platform (PAL TIAGo, MoveIt 2, VR teleop); `DataSetBundle` ingests its episode artefacts |

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
- `model` engines write artefacts to a `build/` folder and signal completion with `=MODEL=<path>`; artefacts are persisted to GCS.
- `app` embeds the Svelte annotation editor from `js-components/svelte-image-editor-component`.
- `OmniverseBundle` issues `POST /load-stage` to a running `isaacsimapp` host (configured in the bundle parameters) to trigger synthetic-image generation; results are imported back as a Pascal-VOC dataset.
- `DataSetBundle` ingests RoboLab episodes (HDF5 + per-episode manifests) through its dataset import workflow.

## Tech stack

- **Backend:** PHP 8.5, Symfony 7.4, OroPlatform 7.0
- **Data:** PostgreSQL 16, Redis
- **Frontend:** Svelte 5, Node.js 24, pnpm 10
- **ML:** Python (conda / venv), Ultralytics, PyTorch, TensorFlow, TensorBoard
- **Infra:** Docker / Docker Compose, NVIDIA GPU runtime (Jetson / RTX profiles)

## Prerequisites

- Docker Engine ≥ 24 with Docker Compose v2.
- [`mkcert`](https://github.com/FiloSottile/mkcert) for local TLS certificates (the dev compose stack expects HTTPS on `*.docker.localhost`).
- 8 GB RAM and ~10 GB free disk for the dev stack; an NVIDIA GPU is **optional** (only required if you exercise the GPU profiles or run TensorBoard against a real training).
- On Linux, add your user to the `docker` group; on Windows, use WSL 2.

## Quick start (Docker)

The full setup lives in [`server/README.md`](server/README.md), including XDebug and GPU profiles. The minimal happy path:

```bash
git clone https://github.com/syntetiq/syntetiqdaas.git
cd syntetiqdaas/server

# 1. Generate a local self-signed TLS cert and trust it
cd certs
mkcert -cert-file docker.localhost-cert.pem \
       -key-file docker.localhost-key.pem \
       docker.localhost "*.docker.localhost"
mkcert -install
cp docker.localhost-cert.pem ../nginx/ssl/
cp docker.localhost-key.pem  ../nginx/ssl/
cd ..

# 2. Create the docker network (one-time per machine)
docker network create syntetiq-docker

# 3. Bring up the stack
cp example.env .env
cp ../app/example.env-app.local ../app/.env-app.local
docker compose up -d

# 4. Initialise the application (composer deps, DB schema, OAuth keys, fixtures)
make composer-install
make oauth-keys-generate
make db-recreate
make install
```

When everything is up, the OroPlatform admin UI is available at
`https://app.docker.localhost/` (accept the local cert) — log in with
the admin user printed by `make install`. TensorBoard is exposed at
`https://tensorboard.docker.localhost/`.

To stop and clean up:

```bash
cd server
docker compose down            # stop + remove containers
make help                      # discover other targets
```

## Configuration files

| File | Used by | Purpose |
|---|---|---|
| `server/.env`                | Docker Compose      | Image tags, ports, project name (`COMPOSE_PROJECT_NAME=sq`), GPU runtime selection |
| `app/.env-app`               | Symfony, all envs   | Default app config; **do not commit secrets here** |
| `app/.env-app.prod`          | Symfony, prod build | Production overrides |
| `app/.env-app.test`          | Symfony, test       | Test overrides used by Behat / PHPUnit |
| `app/.env-app.local`         | Symfony, local dev  | **Per-developer**; copy from `example.env-app.local`, gitignored |
| `app/.env-build`             | Build pipeline      | Build-only flags (e.g. asset compilation) |

Secrets policy: never commit credentials. Use `.env-app.local`
for development; in production, inject environment variables via the
host orchestrator.

## Verifying the install

After `make install`, sanity-check by:

1. Opening `https://app.docker.localhost/` — login screen should render.
2. Logging in as the admin user → **Datasets** menu → **All Datasets**:
   the list should be empty (or populated by `DemoDataBundle` if you
   loaded fixtures).
3. **System → User Management → Users** must show your admin user.
4. `docker ps` should list seven `sq-*` containers (postgres, redis,
   nginx, traefik, php-fpm, php-cli, php-nv-cli) plus tensorboard and
   sam-api in healthy state.
5. `docker compose logs --tail 50 sq-php-fpm` should show no fatals.

If any of these fail, see the **XDEBUG** and **Docker Nvidia GPU**
sections of [`server/README.md`](server/README.md) and the troubleshooting
notes in [`app/README.md`](app/README.md).

## Local development

For frontend / asset development without a full Docker stack:

```bash
cd app
pnpm install
pnpm dev          # Webpack/Vite dev server with HMR
```

The PHP runtime still needs to come from Docker (PHP 8.5 + the
configured extensions); attach via XDebug as documented in
`server/README.md`.

## Integration — `isaacsimapp` (synthetic data generator)

The `OmniverseBundle` calls a running `isaacsimapp` instance to
generate synthetic frames. Configure the endpoint in the bundle
parameters and POST to `/load-stage` to trigger a generation run. See
[the isaacsimapp README](https://github.com/syntetiq/isaacsimapp/blob/main/README.md)
for the full request schema, augmentation knobs, and Pascal-VOC /
YOLO export converters.

Sample request:

```bash
curl -X POST http://<isaacsimapp-host>:8000/load-stage \
  -H 'Content-Type: application/json' \
  -d '{ "stage_path": "...usd",
        "frames": 700,
        "resolution": [640, 640],
        "augmentations": { "motion_blur": [...], "brightness": [...] } }'
```

The DaaS importer ingests the resulting ZIP and registers a
`DataSet` with `DataSetItem` rows + bounding-box `ItemObjectConfiguration`.

## Integration — `robolab` (robotic demonstrations)

`DataSetBundle` ingests robotic episode recordings (HDF5 + per-episode
manifests) produced by [`syntetiq/robolab`](https://github.com/syntetiq/robolab).
Each episode becomes a Dataset whose Items reference the per-frame
artefacts (RGB / depth / pointcloud / semantic / telemetry).

See [`docs/file-storage-architecture.md`](docs/file-storage-architecture.md)
for the on-disk layout that `DataSetBundle` expects.

## Further reading

- [`app/README.md`](app/README.md) — application bundles, key concepts, and domain model.
- [`server/README.md`](server/README.md) — Docker setup, certificates, XDebug, GPU configuration.
- [`model/README.md`](model/README.md) — training-engine architecture and how to add a new engine.
- [`docs/file-storage-architecture.md`](docs/file-storage-architecture.md) — file-storage layout (GCS + local `var/data/`).

## License

[Apache License 2.0](LICENSE) — Copyright SyntetiQ Limited.
