#!/bin/bash

##### CONDA

init_conda() {
    # !! Contents within this block are managed by 'conda init' !!
    __conda_setup="$("$HOME/miniconda3/bin/conda" 'shell.bash' 'hook' 2> /dev/null)"
    if [ $? -eq 0 ]; then
        eval "$__conda_setup"
    else
        if [ -f "$HOME/miniconda3/etc/profile.d/conda.sh" ]; then
            . "$HOME/miniconda3/etc/profile.d/conda.sh"
        else
            export PATH="$HOME/miniconda3/bin:$PATH"
        fi
    fi
    unset __conda_setup
}

create_conda() {
    local env_name=${1:-".conda"}

    if [ -d "$env_name" ]; then
        echo "Virtual conda environment '$env_name' already exists. Aborting."
        return 1
    fi

    PWD=`pwd`
    conda create -y -p "$env_name" python=$PYTHON_VERSION
    source $HOME/miniconda3/bin/activate "$PWD/$env_name"
}

remove_conda() {
    local env_name=${1:-".conda"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual conda environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    PWD=`pwd`
    # conda env remove -y -p $PWD/$env_name
    rm -rf $PWD/$env_name
    echo "Virtual conda environment '$env_name' removed."
}

activate_conda() {
    local env_name=${1:-".conda"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual conda environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    PWD=`pwd`
    source $HOME/miniconda3/bin/activate "$PWD/$env_name"
}

deactivate_conda() {
    local env_name=${1:-".conda"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual conda environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    # source $HOME/miniconda3/bin/deactivate
    conda deactivate
}

###### VENV

check_virtualenv() {
    if ! command -v virtualenv &> /dev/null; then
        echo "virtualenv is not installed. Installing..."
        python3 -m pip install --user virtualenv
        echo "virtualenv installation complete."
    fi
}

create_venv() {
    # Check if virtualenv is installed, if not, install it
    check_virtualenv
    
    local env_name=${1:-".venv"}

    if [ -d "$env_name" ]; then
        echo "Virtual environment '$env_name' already exists. Aborting."
        return 1
    fi

    python3 -m venv "$env_name"
    source "./$env_name/bin/activate"
    pip install -U pip
}

activate_venv() {
    local env_name=${1:-".venv"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    source "./$env_name/bin/activate"
}

install_deps() {
    local env_name=${1:-".venv"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    source "./$env_name/bin/activate"

    if [ -f "requirements.txt" ]; then
        pip install -r ./requirements.txt
    fi

    if [ -f "setup.py" ]; then
        pip install -e .
    fi
}

export_deps() {
    local env_name=${1:-".venv"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    source "./$env_name/bin/activate"
    pip freeze > requirements.txt
    echo "Dependencies exported to requirements.txt"
}

remove_venv() {
    local env_name=${1:-".venv"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual environment '$env_name' not found."
        return 1
    fi

    rm -rf "$env_name"
}

deactivate_venv() {
    local env_name=${1:-".venv"}

    if [ ! -d "$env_name" ]; then
        echo "Virtual environment '$env_name' not found. Use '$0 create [env_name]' to create one."
        return 1
    fi

    deactivate
}

cleanup_build_artifacts() {
    local exit_code=$?
    trap - EXIT

    if [ -d "./ds" ] || [ -d "./dataset_qa_input" ]; then
        echo "=INFO=Removing transient dataset directories"
        rm -rf "./ds" "./dataset_qa_input"
    fi

    if [ "$PYTHON_ENV" == "conda" ] && [ -d ".conda" ]; then
        CURRENT_ENV=${CONDA_DEFAULT_ENV:-"$(pwd)/.conda"}
        echo "=INFO=Deactivate and Remove conda env: $CURRENT_ENV"
        deactivate_conda || true
        remove_conda || true
    elif [ "$PYTHON_ENV" == "venv" ] && [ -d ".venv" ]; then
        CURRENT_ENV=${VIRTUAL_ENV:-"$(pwd)/.venv"}
        echo "=INFO=Deactivate and Remove venv: $CURRENT_ENV"
        deactivate_venv || true
        remove_venv || true
    fi

    exit $exit_code
}

#### MAIN
if [[ -z "$PYTHON_VERSION" ]]; then
  PYTHON_VERSION='3.9.17'
fi

if [[ -z "$MODEL_TRAIN_ENGINE" ]]; then
  MODEL_TRAIN_ENGINE="tf2cli"
fi

if [[ -z "$MODEL_BUILD_ID" ]]; then
  MODEL_BUILD_ID="0_0"
fi

BUILDS_PATH="workdir/builds/$MODEL_BUILD_ID"

mkdir -p $BUILDS_PATH
# cp scripts/${MODEL_TRAIN_SCRIPT} ${BUILDS_PATH}
cd $BUILDS_PATH
trap cleanup_build_artifacts EXIT


if [ "$PYTHON_ENV" == "conda" ]; then
    #### CONDA env
    init_conda
    create_conda
    activate_conda
    echo "=INFO=Created and Activated conda env: $CONDA_DEFAULT_ENV"
elif [ "$PYTHON_ENV" == "venv" ]; then
    ### VENV env
    create_venv
    activate_venv
    echo "=INFO=Created and Activated venv: $VIRTUAL_ENV"
else
    echo "=INFO=No virtual environment was selected"
fi

echo "=INFO=Preset Python version - $PYTHON_VERSION"
PYTHON_VERSION=`python3 -V`
echo "=INFO=Detected Python version - $PYTHON_VERSION"

INIT_SCRIPT="../../../script/engine/$MODEL_TRAIN_ENGINE/init.py"
RUN_SCRIPT="../../../script/engine/$MODEL_TRAIN_ENGINE/run.py"
DATASET_QA_INIT_SCRIPT="../../../script/dataset_qa/init.py"
DATASET_QA_RUN_SCRIPT="../../../script/dataset_qa/run.py"
DATASET_QA_ENABLED="${DATASET_QA_ENABLED:-true}"
DATASET_QA_INPUT_RELATIVE_PATH="${DATASET_QA_INPUT_RELATIVE_PATH:-ds}"
DATASET_QA_OUTPUT_DIR="$(pwd)/dataset_qa"
DATASET_QA_STATUS_FILE="$(pwd)/dataset_qa.status"
DATASET_QA_STARTED_AT_FILE="$(pwd)/dataset_qa.started_at"
DATASET_QA_FINISHED_AT_FILE="$(pwd)/dataset_qa.finished_at"
DATASET_QA_HEARTBEAT_FILE="$(pwd)/dataset_qa.heartbeat_at"
DATASET_QA_ERROR_FILE="$(pwd)/dataset_qa.err"
DATASET_QA_LOG_FILE="$(pwd)/dataset-qa-run.log"
DATASET_QA_RUNTIME_ERROR_FILE="$(pwd)/dataset-qa-run.err"
DATASET_QA_PROGRESS_FILE="$DATASET_QA_OUTPUT_DIR/progress.json"
SQ_DATASET_QA_CACHE_DIR="${SQ_DATASET_QA_CACHE_DIR:-$(pwd)/../../dataset_qa_cache}"
SQ_DEEPSTREAM_YOLO_CACHE_DIR="${SQ_DEEPSTREAM_YOLO_CACHE_DIR:-$(pwd)/../../deepstream_yolo_cache}"
DEEPSTREAM_EXPORT="${DEEPSTREAM_EXPORT:-false}"

if [[ "$DEEPSTREAM_EXPORT" == "true" ]]; then
    export SQ_DEEPSTREAM_YOLO_CACHE_DIR
    export DEEPSTREAM_YOLO_UTILS_PATH="$SQ_DEEPSTREAM_YOLO_CACHE_DIR/utils"
    mkdir -p "$SQ_DEEPSTREAM_YOLO_CACHE_DIR"

    if [[ -d "$SQ_DEEPSTREAM_YOLO_CACHE_DIR/.git" ]]; then
        echo "=INFO=Updating DeepStream-Yolo library"
        git -C "$SQ_DEEPSTREAM_YOLO_CACHE_DIR" pull --ff-only
    else
        echo "=INFO=Cloning DeepStream-Yolo library"
        git clone https://github.com/marcoslucianops/DeepStream-Yolo.git "$SQ_DEEPSTREAM_YOLO_CACHE_DIR"
    fi
fi

if [[ "$DATASET_QA_ENABLED" == "true" ]]; then
    export SQ_DATASET_QA_CACHE_DIR
    export HF_HOME="$SQ_DATASET_QA_CACHE_DIR/hf"
    export TORCH_HOME="$SQ_DATASET_QA_CACHE_DIR/torch"
    export FIFTYONE_HOME="$SQ_DATASET_QA_CACHE_DIR/fiftyone"
    export MPLCONFIGDIR="$SQ_DATASET_QA_CACHE_DIR/matplotlib"

    mkdir -p "$SQ_DATASET_QA_CACHE_DIR" "$HF_HOME" "$TORCH_HOME" "$FIFTYONE_HOME" "$MPLCONFIGDIR"
fi

if [[ -f "$INIT_SCRIPT" ]]; then
    python3 $INIT_SCRIPT >build-init.log 2>build-init.err
fi

if [[ "$DATASET_QA_ENABLED" == "true" ]]; then
    echo "running" > "$DATASET_QA_STATUS_FILE"
    date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_STARTED_AT_FILE"
    date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_HEARTBEAT_FILE"
    : > "$DATASET_QA_ERROR_FILE"
    mkdir -p "$DATASET_QA_OUTPUT_DIR"

    if [[ -f "$DATASET_QA_INIT_SCRIPT" && -f "$DATASET_QA_RUN_SCRIPT" && -d "$DATASET_QA_INPUT_RELATIVE_PATH" ]]; then
        python3 "$DATASET_QA_INIT_SCRIPT" >dataset-qa-init.log 2>dataset-qa-init.err || true
        python3 "$DATASET_QA_RUN_SCRIPT" \
            --dataset-path "$(pwd)/$DATASET_QA_INPUT_RELATIVE_PATH" \
            --output-dir "$DATASET_QA_OUTPUT_DIR" \
            --scope "${DATASET_QA_SCOPE:-build}" \
            --source-id "${DATASET_QA_SOURCE_ID:-$MODEL_BUILD_ID}" \
            --input-format "yolo" \
            --progress-path "$DATASET_QA_PROGRESS_FILE" \
            >"$DATASET_QA_LOG_FILE" 2>"$DATASET_QA_RUNTIME_ERROR_FILE" &
        DATASET_QA_PID=$!
        (
            while kill -0 "$DATASET_QA_PID" 2>/dev/null; do
                date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_HEARTBEAT_FILE"
                sleep 10
            done
        ) &
        DATASET_QA_HEARTBEAT_PID=$!

        wait "$DATASET_QA_PID"
        DATASET_QA_EXIT_CODE=$?
        kill "$DATASET_QA_HEARTBEAT_PID" 2>/dev/null || true
        wait "$DATASET_QA_HEARTBEAT_PID" 2>/dev/null || true
        date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_HEARTBEAT_FILE"

        if [ $DATASET_QA_EXIT_CODE -eq 0 ]; then
            echo "succeeded" > "$DATASET_QA_STATUS_FILE"
            : > "$DATASET_QA_ERROR_FILE"
        else
            echo "failed" > "$DATASET_QA_STATUS_FILE"
            cat dataset-qa-init.err "$DATASET_QA_RUNTIME_ERROR_FILE" 2>/dev/null | tail -c 4000 > "$DATASET_QA_ERROR_FILE"
        fi

        date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_FINISHED_AT_FILE"
    else
        echo "failed" > "$DATASET_QA_STATUS_FILE"
        echo "Dataset QA input directory or scripts are missing." > "$DATASET_QA_ERROR_FILE"
        date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_HEARTBEAT_FILE"
        date -u +"%Y-%m-%dT%H:%M:%SZ" > "$DATASET_QA_FINISHED_AT_FILE"
    fi
fi

if [[ -f "$RUN_SCRIPT" ]]; then
    # python3 -u $RUN_SCRIPT >build.log 2>build.err
    python3 -u "$RUN_SCRIPT" \
        > >(sed -u 's/\x1B\[[0-9;]*[JKmsu]//g' > build.log) \
        2> >(sed -u 's/\x1B\[[0-9;]*[JKmsu]//g' > build.err)
else
    echo "=ERROR=Script $RUN_SCRIPT not found"
fi
