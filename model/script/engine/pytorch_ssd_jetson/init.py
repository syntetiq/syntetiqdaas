import os
import sys

print(f"=INFO=Python version on init - {sys.version}", flush=True)

GPU=os.environ.get('GPU', 'disable')
COMPOSE_PROFILES=os.environ.get('COMPOSE_PROFILES', 'base')
PYTHON_ENV=os.environ.get('PYTHON_ENV', 'system')
SKIP_INSTALL=os.environ.get('SKIP_INSTALL', 'false')

print(f"=INFO=Install dependencies", flush=True)

if COMPOSE_PROFILES == 'jetson':
    if PYTHON_ENV == 'conda':
        os.system("pip install --cache-dir ~/whl-cache https://github.com/ultralytics/assets/releases/download/v0.0.0/torch-2.5.0a0+872d972e41.nv24.08-cp310-cp310-linux_aarch64.whl")
        os.system("pip install --cache-dir ~/whl-cache https://github.com/ultralytics/assets/releases/download/v0.0.0/torchvision-0.20.0a0+afc54f7-cp310-cp310-linux_aarch64.whl")
        os.system("pip install --cache-dir ~/whl-cache https://github.com/ultralytics/assets/releases/download/v0.0.0/onnxruntime_gpu-1.20.0-cp310-cp310-linux_aarch64.whl")
        os.system("pip install numpy==1.23.5")
        os.system("pip install tensorboard")
else:
    if SKIP_INSTALL != 'true':
        os.system("pip install tensorboard")
        # TODO: install cuda int container
        if GPU == 'enable':
            os.system("pip3 install --pre torch torchvision torchaudio --index-url https://download.pytorch.org/whl/nightly/cu126")
            # os.system("pip install torch torchvision torchaudio")
        else:
            os.system("pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu")

os.system("pip install boto3")
os.system("pip install pandas")
os.system("pip install urllib3")
os.system("pip install packaging")
os.system("pip install opencv-python")
os.system("pip install onnx")
os.system("pip install onnxscript")
os.system("pip install onnxruntime")
os.system("pip install Pillow")

DEEPSTREAM_EXPORT = os.environ.get('DEEPSTREAM_EXPORT', 'false').strip().lower() == 'true'
if DEEPSTREAM_EXPORT:
    os.system("pip install onnxsim")
    