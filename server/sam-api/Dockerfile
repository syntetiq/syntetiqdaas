ARG BASE_IMAGE=pytorch/pytorch:2.5.1-cuda12.4-cudnn9-runtime
FROM ${BASE_IMAGE}

ARG TORCH_PACKAGES=""
ARG SAM2_BUILD_CUDA=1

ENV PYTHONUNBUFFERED=1 \
    PORT=8010 \
    SAM2_BUILD_CUDA=${SAM2_BUILD_CUDA}

WORKDIR /app

RUN if command -v apt-get >/dev/null 2>&1; then \
        apt-get update && \
        DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
            git \
            libglib2.0-0 \
            libsm6 \
            libxext6 \
            libxrender1 && \
        rm -rf /var/lib/apt/lists/*; \
    fi

COPY requirements.txt /tmp/requirements.txt
RUN if [ -n "${TORCH_PACKAGES}" ]; then \
        pip install --no-cache-dir ${TORCH_PACKAGES}; \
    fi
RUN pip install --no-cache-dir -r /tmp/requirements.txt
RUN pip install --no-cache-dir "git+https://github.com/facebookresearch/sam2.git"

COPY app /app/app

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8010"]
