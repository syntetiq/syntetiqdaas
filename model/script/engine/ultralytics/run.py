import os
import shutil
import sys
import time
import json
from pathlib import Path
from ultralytics import YOLO, checks
from ultralytics import settings
import torch
import ultralytics
import uuid

print(f"=INFO=Python version on run - {sys.version}", flush=True)

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

# mission-build id to create build folder
MODEL_BUILD_ID = os.environ.get('MODEL_BUILD_ID', '0_0')
EPOCH = int(os.environ.get('EPOCH', '5'))
# DATASET_OPERATION = os.environ.get('DATASET_OPERATION', 'move')
# DATASET_PATH = os.environ.get('DATASET_PATH', '../../../test_data/dataset/test')
LABEL_LIST = read_labels_from_file(f"ds/labels.txt")
MODEL_NAME = os.environ.get('MODEL_NAME', 'yolo8n')
BUILD_DIR = os.getcwd()
IMAGE_PATH = '{}/ds'.format(BUILD_DIR) 
BATCH_SIZE = int(os.environ.get('BATCH_SIZE', 4))
IMG_SIZE = int(os.environ.get('IMG_SIZE', 640))
PRETRAINED_MODEL_FILE = os.environ.get('PRETRAINED_MODEL_FILE', '').strip()

MODEL_FILENAME = "{}-e{}".format(MODEL_NAME, EPOCH)
DEEPSTREAM_EXPORT = os.environ.get('DEEPSTREAM_EXPORT', 'false').strip().lower() == 'true'
DEEPSTREAM_YOLO_UTILS_PATH = os.environ.get('DEEPSTREAM_YOLO_UTILS_PATH', '').strip()
DEEPSTREAM_OPSET = int(os.environ.get('DEEPSTREAM_OPSET', '18').strip())
DEEPSTREAM_DYNAMIC = os.environ.get('DEEPSTREAM_DYNAMIC', 'true').strip().lower() == 'true'

def resolve_deepstream_export_script(model_name: str) -> str:
    """Map MODEL_NAME to the correct DeepStream-Yolo export script."""
    name = model_name.lower()
    if name.startswith('yolo26'):
        return 'export_yolo26.py'
    if name.startswith('yolo11') or name.startswith('yolo-11'):
        return 'export_yolo11.py'
    if name.startswith('yolo10') or name.startswith('yolov10'):
        return 'export_yoloV10.py'
    if name.startswith('yolo9') or name.startswith('yolov9'):
        return 'export_yoloV9.py'
    return 'export_yoloV8.py'


TENSORBOARD = os.path.join('..', '..', '..', "tensorboard", f"{MODEL_BUILD_ID}")


def sync_tensorboard_events(trainer=None) -> None:
    target_dir = Path(TENSORBOARD)
    target_dir.mkdir(parents=True, exist_ok=True)

    source_dirs = []
    if trainer is not None and getattr(trainer, 'save_dir', None):
        source_dirs.append(Path(trainer.save_dir))

    source_dirs.append(Path(os.getcwd()) / 'runs' / 'train')

    copied_files = 0
    seen_paths = set()

    for source_dir in source_dirs:
        if source_dir in seen_paths or not source_dir.exists():
            continue

        seen_paths.add(source_dir)

        for source_path in source_dir.glob('events.*'):
            target_path = target_dir / source_path.name
            should_copy = (
                not target_path.exists()
                or source_path.stat().st_size != target_path.stat().st_size
                or source_path.stat().st_mtime > target_path.stat().st_mtime
            )

            if not should_copy:
                continue

            shutil.copy2(source_path, target_path)
            copied_files += 1

    if copied_files:
        print(f"Synced {copied_files} TensorBoard event file(s) to {target_dir}", flush=True)


# os.system(f"yolo settings tensorboard=True") # check settings in /var/www/.config/Ultralytics/settings.json in sq-phpcli
settings.update({'tensorboard': True})

# set one thread because fail with tensorboard
# os.environ["OMP_NUM_THREADS"] = "1"
# os.environ["MKL_NUM_THREADS"] = "1"

print(f"=INFO=Pytorch version - {torch.__version__}", flush=True)
print(f"=INFO=Ultralytics version - {ultralytics.__version__}", flush=True)

CUDA = torch.cuda.is_available()
print(f"=INFO=Pytorch CUDA is available - {CUDA}", flush=True)

########### Dataset prepare

# print(f"=INFO=Images dataset path - <b>{DATASET_PATH}</b>", flush=True)
print(f"=INFO=Dataset label list - <b>{LABEL_LIST}</b>", flush=True)

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

# time.sleep(1)

print(f"=INFO=Prepare images to process", flush=True)

############

print(f"=INFO=Change settings for yolo train", flush=True)
print(f"=INFO=Batch - <b>{BATCH_SIZE}</b>", flush=True)
print(f"=INFO=Image size - <b>{IMG_SIZE}</b>", flush=True)

checks()

# # Reset settings to default values
# uuid_str = str(uuid.uuid4())
# settings.reset()
# settings.update({'uuid': uuid_str})
# # settings.update({'tensorboard': False})
# settings.update({'hub': False})
# settings.update({'api_key': ''})
# # settings.update({'runs_dir': "{}/runs".format(os.getcwd())})
# # settings.update({'weights_dir': "{}/weights".format(os.getcwd())})
# settings.update({'datasets_dir': "{}/ds".format(os.getcwd())})

# # View all settings
# print(settings)

print(f"=INFO=Train model {MODEL_NAME}", flush=True)
if PRETRAINED_MODEL_FILE and not os.path.exists(PRETRAINED_MODEL_FILE):
    print(f"=ERROR=Could not resolve uploaded pretrained YOLO model file {PRETRAINED_MODEL_FILE}", flush=True)
    sys.exit(1)

if PRETRAINED_MODEL_FILE:
    print(f"=INFO=Initialize YOLO from uploaded pretrained weights - <b>{PRETRAINED_MODEL_FILE}</b>", flush=True)
    model = YOLO(PRETRAINED_MODEL_FILE)
else:
    print(f"=INFO=Initialize YOLO from scratch", flush=True)
    model = YOLO(f'{MODEL_NAME}.yaml')
# model = YOLO(f'{MODEL_NAME}.yaml').load(f'{MODEL_NAME}.pt')
# model = YOLO('yolo11s.yaml').load('yolo11s.pt')


# # Load a pretrained YOLO model (recommended for training)
# # model = YOLO('yolo8n.pt')


# Callback function
# def train_epoch_start(trainer):
#     print(f"=INFO=train_epoch_start {trainer}", flush=True)

# model.add_callback("on_train_epoch_start", train_epoch_start)
model.add_callback("on_fit_epoch_end", sync_tensorboard_events)
model.add_callback("on_train_end", sync_tensorboard_events)


# Train the model using the 'data.yaml' dataset
# https://github.com/ultralytics/ultralytics/blob/main/ultralytics/cfg/default.yaml
results = model.train(
    data="{}/ds/data.yaml".format(os.getcwd()), 
    project="{}/runs".format(os.getcwd()),
    epochs=EPOCH, 
    batch=BATCH_SIZE,
    imgsz=IMG_SIZE,
    verbose=True,
    mosaic=1.0,           # Enable mosaic augmentation
    mixup=0.5,            # Enable mixup augmentation
    hsv_h=0.015,          # HSV hue augmentation
    hsv_s=0.7,            # HSV saturation augmentation
    hsv_v=0.4,            # HSV value augmentation
    flipud=0.0,           # Vertical flip probability
    fliplr=0.5,           # Horizontal flip probability
    perspective=0.0,      # Perspective augmentation
    scale=0.5,            # Scaling augmentation
    shear=0.0,            # Shear augmentation
    translate=0.1         # Translation augmentation
)

# task=detect, mode=train, model=yolo11s.yaml, data=/data/var/model/workdir/builds/0_0/ds/data.yaml, epochs=2, time=None, patience=100, batch=16, imgsz=640, save=True, save_period=-1, cache=False, device=None, workers=8, project=/data/var/model/workdir/builds/0_0/runs, name=train, exist_ok=False, pretrained=True, optimizer=auto, verbose=True, seed=0, deterministic=True, single_cls=False, rect=False, cos_lr=False, close_mosaic=10, resume=False, amp=True, fraction=1.0, profile=False, freeze=None, multi_scale=False, overlap_mask=True, mask_ratio=4, dropout=0.0, val=True, split=val, save_json=False, save_hybrid=False, conf=None, iou=0.7, max_det=300, half=False, dnn=False, plots=True, source=None, vid_stride=1, stream_buffer=False, visualize=False, augment=False, agnostic_nms=False, classes=None, retina_masks=False, embed=None, show=False, save_frames=False, save_txt=False, save_conf=False, save_crop=False, show_labels=True, show_conf=True, show_boxes=True, line_width=None, format=torchscript, keras=False, optimize=False, int8=False, dynamic=False, simplify=True, opset=None, workspace=None, nms=False, lr0=0.01, lrf=0.01, momentum=0.937, weight_decay=0.0005, warmup_epochs=3.0, warmup_momentum=0.8, warmup_bias_lr=0.1, box=7.5, cls=0.5, dfl=1.5, pose=12.0, kobj=1.0, nbs=64, hsv_h=0.015, hsv_s=0.7, hsv_v=0.4, degrees=0.0, translate=0.1, scale=0.5, shear=0.0, perspective=0.0, flipud=0.0, fliplr=0.5, bgr=0.0, mosaic=1.0, mixup=0.5, copy_paste=0.0, copy_paste_mode=flip, auto_augment=randaugment, erasing=0.4, crop_fraction=1.0, cfg=None, tracker=botsort.yaml, save_dir=/data/var/model/workdir/builds/0_0/runs/train

print(f"=INFO=Evaluate model", flush=True)

# Validate the model
metrics = model.val(split="test")  # no arguments needed, dataset and settings remembered
metrics.box.map  # map50-95
metrics.box.map50  # map50
metrics.box.map75  # map75
metrics.box.maps  # a list contains map50-95 of each category

print(f"=INFO=mAP50-95 {metrics.box.map}", flush=True)
print(f"=INFO=mAP50 {metrics.box.map50}", flush=True)
print(f"=INFO=mAP75 {metrics.box.map75}", flush=True)
print(f"=INFO=List of mAP50-95 for each category {metrics.box.maps}", flush=True)

sync_tensorboard_events()

print(f"=INFO=Export result model", flush=True)

# Export the model to ONNX format
model.export(format="onnx", dynamic=True)

os.system(f"mkdir build")
os.system(f"cp runs/train/weights/best.onnx build/{MODEL_FILENAME}.onnx")
os.system(f"cp runs/train/weights/best.pt build/{MODEL_FILENAME}.pt")
os.system(f"cp ds/labels.txt build/")

if DEEPSTREAM_EXPORT:
    print(f"=INFO=Export model to DeepStream YOLO ONNX format", flush=True)
    if not DEEPSTREAM_YOLO_UTILS_PATH:
        print(f"=ERROR=DEEPSTREAM_YOLO_UTILS_PATH is not set, skipping DeepStream export", flush=True)
    else:
        script_name = resolve_deepstream_export_script(MODEL_NAME)
        export_script = os.path.join(DEEPSTREAM_YOLO_UTILS_PATH, script_name)
        if not os.path.exists(export_script):
            print(f"=ERROR=DeepStream export script not found at {export_script}, skipping", flush=True)
        else:
            pt_path = os.path.abspath("runs/train/weights/best.pt")
            dynamic_flag = "--dynamic " if DEEPSTREAM_DYNAMIC else ""
            ret = os.system(
                f"python3 {export_script} "
                f"-w {pt_path} "
                f"-s {IMG_SIZE} "
                f"--simplify "
                f"{dynamic_flag}"
                f"--opset {DEEPSTREAM_OPSET}"
            )
            if ret != 0:
                print(f"=ERROR=DeepStream ONNX export failed (exit code {ret})", flush=True)
            else:
                # DeepStream-Yolo script outputs the ONNX next to the .pt file
                ds_onnx_src = pt_path.replace(".pt", ".onnx")
                ds_onnx_dst = f"build/{MODEL_FILENAME}_deepstream.onnx"
                if os.path.exists(ds_onnx_src):
                    os.system(f"cp {ds_onnx_src} {ds_onnx_dst}")
                    print(f"=INFO=DeepStream ONNX saved to {ds_onnx_dst}", flush=True)
                else:
                    print(f"=ERROR=DeepStream ONNX output not found at {ds_onnx_src}", flush=True)

os.system(f"zip -r {MODEL_FILENAME}.zip build")

print("=MODEL={}/{}.zip".format(os.getcwd(), MODEL_FILENAME), flush=True)

print("=INFO=Done", flush=True)
