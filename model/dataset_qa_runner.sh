#!/bin/bash

set -u

init_conda() {
    __conda_setup="$("$HOME/miniconda3/bin/conda" 'shell.bash' 'hook' 2> /dev/null)"
    if [ $? -eq 0 ]; then
        eval "$__conda_setup"
    elif [ -f "$HOME/miniconda3/etc/profile.d/conda.sh" ]; then
        . "$HOME/miniconda3/etc/profile.d/conda.sh"
    else
        export PATH="$HOME/miniconda3/bin:$PATH"
    fi
    unset __conda_setup
}

create_conda() {
    local env_name=${1:-".conda"}
    local pwd=$(pwd)
    conda create -y -p "$env_name" python="$PYTHON_VERSION"
    source "$HOME/miniconda3/bin/activate" "$pwd/$env_name"
}

activate_conda() {
    local env_name=${1:-".conda"}
    local pwd=$(pwd)
    source "$HOME/miniconda3/bin/activate" "$pwd/$env_name"
}

deactivate_conda() {
    conda deactivate
}

create_venv() {
    local env_name=${1:-".venv"}
    python3 -m venv "$env_name"
    source "./$env_name/bin/activate"
    python3 -m pip install -U pip
}

activate_venv() {
    local env_name=${1:-".venv"}
    source "./$env_name/bin/activate"
}

deactivate_venv() {
    deactivate
}

remove_env() {
    local env_name=${1:-".venv"}
    rm -rf "$env_name"
}

if [[ -z "${PYTHON_VERSION:-}" ]]; then
    PYTHON_VERSION="3.10.12"
fi

if [[ -z "${PYTHON_ENV:-}" ]]; then
    PYTHON_ENV="conda"
fi

if [[ -z "${QA_WORKDIR:-}" ]]; then
    QA_WORKDIR="workdir/dataset_qa/default"
fi

MODEL_ROOT="${SQ_MODEL_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
SCRIPT_ROOT="$MODEL_ROOT/script/dataset_qa"

mkdir -p "$QA_WORKDIR"
cd "$QA_WORKDIR"
if [[ -z "${QA_OUTPUT_DIR:-}" ]]; then
    QA_OUTPUT_DIR="$(pwd)/output"
fi
mkdir -p "$QA_OUTPUT_DIR"

if [ "$PYTHON_ENV" == "conda" ]; then
    init_conda
    create_conda
    activate_conda
elif [ "$PYTHON_ENV" == "venv" ]; then
    create_venv
    activate_venv
fi

export SQ_DATASET_QA_CACHE_DIR="${SQ_DATASET_QA_CACHE_DIR:-$MODEL_ROOT/workdir/dataset_qa_cache}"
export HF_HOME="$SQ_DATASET_QA_CACHE_DIR/hf"
export TORCH_HOME="$SQ_DATASET_QA_CACHE_DIR/torch"
export FIFTYONE_HOME="$SQ_DATASET_QA_CACHE_DIR/fiftyone"
export MPLCONFIGDIR="$SQ_DATASET_QA_CACHE_DIR/matplotlib"

mkdir -p "$SQ_DATASET_QA_CACHE_DIR" "$HF_HOME" "$TORCH_HOME" "$FIFTYONE_HOME" "$MPLCONFIGDIR"

python3 "$SCRIPT_ROOT/init.py" >"$QA_OUTPUT_DIR/dataset-qa-init.log" 2>"$QA_OUTPUT_DIR/dataset-qa-init.err" || true
python3 "$SCRIPT_ROOT/run.py" \
    --dataset-path "${QA_DATASET_PATH}" \
    --output-dir "${QA_OUTPUT_DIR}" \
    --progress-path "${QA_OUTPUT_DIR}/progress.json" \
    --scope "${DATASET_QA_SCOPE:-dataset}" \
    --source-id "${DATASET_QA_SOURCE_ID:-}" \
    --input-format "yolo" \
    >"$QA_OUTPUT_DIR/dataset-qa-run.log" 2>"$QA_OUTPUT_DIR/dataset-qa-run.err"
exit_code=$?

if [ "$PYTHON_ENV" == "conda" ]; then
    deactivate_conda
    remove_env ".conda"
elif [ "$PYTHON_ENV" == "venv" ]; then
    deactivate_venv
    remove_env ".venv"
fi

exit $exit_code
