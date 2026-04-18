#!/usr/bin/env sh
set -eu

MODEL_VARIANT="${1:-small}"

case "${MODEL_VARIANT}" in
  tiny)
    FILE_NAME="sam2.1_hiera_tiny.pt"
    DATE_PREFIX="092824"
    ;;
  small)
    FILE_NAME="sam2.1_hiera_small.pt"
    DATE_PREFIX="092824"
    ;;
  base_plus)
    FILE_NAME="sam2.1_hiera_base_plus.pt"
    DATE_PREFIX="092824"
    ;;
  large)
    FILE_NAME="sam2.1_hiera_large.pt"
    DATE_PREFIX="092824"
    ;;
  *)
    echo "Unsupported SAM2 variant: ${MODEL_VARIANT}" >&2
    echo "Supported values: tiny, small, base_plus, large" >&2
    exit 1
    ;;
esac

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
TARGET_DIR="${SCRIPT_DIR}/model/checkpoints"
TARGET_PATH="${TARGET_DIR}/${FILE_NAME}"
SOURCE_URL="https://dl.fbaipublicfiles.com/segment_anything_2/${DATE_PREFIX}/${FILE_NAME}"

mkdir -p "${TARGET_DIR}"

if [ -f "${TARGET_PATH}" ]; then
  echo "Checkpoint already exists: ${TARGET_PATH}"
  exit 0
fi

if command -v curl >/dev/null 2>&1; then
  curl -fL "${SOURCE_URL}" -o "${TARGET_PATH}"
elif command -v wget >/dev/null 2>&1; then
  wget -O "${TARGET_PATH}" "${SOURCE_URL}"
else
  echo "Neither curl nor wget is available on this machine." >&2
  exit 1
fi

echo "Downloaded ${FILE_NAME} to ${TARGET_PATH}"
