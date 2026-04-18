# https://github.com/dusty-nv/pytorch-ssd
# https://github.com/qfgaohao/pytorch-ssd
# https://drive.google.com/drive/folders/1pKn-RifvJGWiOx0ZCRLtCXM5GT5lAluu - models

# https://github.com/qfgaohao/pytorch-ssd?tab=readme-ov-file

import os
import json
import torch
import sys
import re
import shlex

print(f"=INFO=Python version on run - {sys.version}", flush=True)

# set one thread because fail with tensorboard
# os.environ["OMP_NUM_THREADS"] = "1"

print(f"=INFO=Pytorch version - {torch.__version__}", flush=True)

def find_latest_checkpoint_file(directory, model_name):
    if not os.path.isdir(directory):
        return None

    pattern = re.compile(rf"{re.escape(model_name)}-Epoch-(\d+)-Loss-.*\.pth$")
    found_file = None
    max_epoch = -1

    for file_name in os.listdir(directory):
        match = pattern.match(file_name)
        if match:
            epoch = int(match.group(1))
            if epoch > max_epoch:
                max_epoch = epoch
                found_file = file_name

    return found_file

def read_labels_from_file(file_path):
    # Initialize an empty list to store the labels
    labels = []
    
    # Open the file and read each line
    with open(file_path, 'r') as file:
        for line in file:
            # Strip any leading/trailing whitespace or newline characters
            label = line.strip()
            if label:  # Ensure the line isn't empty
                labels.append(label)
    
    return labels

CUDA = torch.cuda.is_available()
print(f"=INFO=Pytorch CUDA is available - {CUDA}", flush=True)

# mission-build id to create build folder
MODEL_BUILD_ID = os.environ.get('MODEL_BUILD_ID', '0_0')
EPOCH = int(os.environ.get('EPOCH', '5'))
# DATASET_OPERATION = os.environ.get('DATASET_OPERATION', 'move')
# DATASET_PATH = os.environ.get('DATASET_PATH', '../../../test_data/dataset/test')
LABEL_LIST = read_labels_from_file(f"ds/labels.txt")
MODEL_NAME = os.environ.get('MODEL_NAME', 'mb1-ssd') # todo: prepare get pretrained models
BUILD_DIR = os.getcwd()
IMAGE_PATH = '{}/ds'.format(BUILD_DIR)
USE_PRETRAINED = os.environ.get('USE_PRETRAINED', 'false')
BATCH_SIZE = int(os.environ.get('BATCH_SIZE', 4))
PRETRAINED_MODEL_FILE = os.environ.get('PRETRAINED_MODEL_FILE', '').strip()
if BATCH_SIZE < 1:
    BATCH_SIZE = 1

PRETRAINED_MODEL = ''
if USE_PRETRAINED == 'true':
    PRETRAINED_MODEL = PRETRAINED_MODEL_FILE if os.path.exists(PRETRAINED_MODEL_FILE) else ''
    if PRETRAINED_MODEL:
        print(f"=INFO=Will be used pre-trained model - <b>{PRETRAINED_MODEL}</b>", flush=True)
    else:
        print(f"=ERROR=Could not resolve pretrained SSD model", flush=True)
        sys.exit(1)
else:
    print(f"=INFO=The newly created model will be used", flush=True)

MODEL_FILENAME = "{}-e{}".format(MODEL_NAME, EPOCH)
DEEPSTREAM_EXPORT = os.environ.get('DEEPSTREAM_EXPORT', 'false').strip().lower() == 'true'

# print(f"=INFO=Images dataset path - <b>{DATASET_PATH}</b>", flush=True)
print(f"=INFO=Dataset label list - <b>{LABEL_LIST}</b>", flush=True)
print(f"=INFO=Batch - <b>{BATCH_SIZE}</b>", flush=True)

# print(f"=INFO=Prepare dataset", flush=True)
# if os.path.exists(DATASET_PATH):
#     if DATASET_OPERATION == 'move':
#         os.system(f"mv {DATASET_PATH} {IMAGE_PATH}")
#         print("=INFO=Dataset moved to build folder")
#     else:
#         os.system(f"cp -r {DATASET_PATH} {IMAGE_PATH}")
#         print("=INFO=Dataset copied to build folder")
# else:
#     print("=ERROR=Dataset path does not exists")
#     quit(404)


# print(f"=INFO=Prepare images to process", flush=True)

# TODO: Move to php side
# os.system(f"cd ds && python3 ../../../../script/libs/pytorch-ssd/vision/datasets/generate_vocdata.py labels_gen.txt && cd ..")

# print(f'=INFO=Train count: {len(train_data)}')
# print(f'=INFO=Validation count: {len(validation_data)}')
# print(f'test count: {len(test_data)}')

# print(f"=INFO=Load pretrained model {MODEL_NAME}", flush=True)
# os.system("mkdir pretrained")
# https://drive.google.com/drive/folders/1pKn-RifvJGWiOx0ZCRLtCXM5GT5lAluu - models
# os.system(f"wget -q https://nvidia.box.com/shared/static/djf5w54rjvpqocsiztzaandq1m3avr7c.pth -O pretrained/mobilenet-v1-ssd-mp-0_675.pth")

print(f"=INFO=Train model {MODEL_NAME}", flush=True)

TENSORBOARD = os.path.join('..', '..', '..', "tensorboard", f"{MODEL_BUILD_ID}")

# os.system(f"python3 ../../../script/libs/pytorch-ssd/train_ssd.py --net={MODEL_NAME} --pretrained-ssd=pretrained/mobilenet-v1-ssd-mp-0_675.pth --dataset-type=voc --data=ds --model-dir=models --batch-size={BATCH_SIZE} --epochs={EPOCH}")
os.system(
    "python3 ../../../script/libs/pytorch-ssd/train_ssd.py "
    f"--net={shlex.quote(MODEL_NAME)} "
    f"--pretrained-ssd={shlex.quote(PRETRAINED_MODEL)} "
    "--dataset-type=voc --data=ds --model-dir=models "
    f"--batch-size={BATCH_SIZE} --epochs={EPOCH} "
    f"--tensorboard={shlex.quote(TENSORBOARD)}"
)

found_file = find_latest_checkpoint_file("models", MODEL_NAME)
if found_file:
    print(f"Found model file: {found_file}", flush=True)
else:
    print("No matching files found.", flush=True)

print(f"=INFO=Export result model", flush=True)
os.system(f"python3 ../../../script/libs/pytorch-ssd/onnx_export.py --net={MODEL_NAME} --model-dir=models --output={MODEL_FILENAME}.onnx")

os.system("mkdir -p build")
if found_file:
    os.system(f"cp models/{found_file} build/{MODEL_FILENAME}.pth")
os.system(f"cp models/{MODEL_FILENAME}.onnx build/")
os.system("cp models/labels.txt build/")
os.system("cp ds/labels-gps.txt build/")

if DEEPSTREAM_EXPORT:
    print("=INFO=Export SSD model to DeepStream-compatible ONNX (onnxsim)", flush=True)
    try:
        import onnx
        from onnxsim import simplify

        src_onnx = f"models/{MODEL_FILENAME}.onnx"
        dst_onnx = f"build/{MODEL_FILENAME}_deepstream.onnx"

        model_proto = onnx.load(src_onnx)
        model_simplified, check = simplify(model_proto)
        if check:
            onnx.save(model_simplified, dst_onnx)
            print(f"=INFO=DeepStream ONNX saved to {dst_onnx}", flush=True)
        else:
            print("=ERROR=onnxsim check failed, DeepStream ONNX not saved", flush=True)
    except Exception as e:
        print(f"=ERROR=DeepStream ONNX export failed: {e}", flush=True)

os.system(f"zip -r {MODEL_FILENAME}.zip build")

print("=MODEL={}/{}.zip".format(os.getcwd(), MODEL_FILENAME), flush=True)

print("=INFO=Done", flush=True)
