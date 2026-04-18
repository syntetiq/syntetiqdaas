import os
import json

print(f"=INFO=Test info", flush=True)
print(f"=WARN=Test warning", flush=True)

MODEL_BUILD_ID=os.environ.get('MODEL_BUILD_ID', '0_0')
EPOCH=int(os.environ.get('EPOCH', '5'))
DATASET_OPERATION=os.environ.get('DATASET_OPERATION', 'move')
DATASET_PATH=os.environ.get('DATASET_PATH', '../../../test_data/dataset/test')
LABEL_LIST=os.environ.get('LABEL_LIST', '["object"]')
LABEL_LIST=json.loads(LABEL_LIST)
MODEL_NAME=os.environ.get('MODEL_NAME', 'mb1-ssd') # todo: prepare get pretrained models
BUILD_DIR=os.getcwd()
IMAGE_PATH='{}/ds'.format(BUILD_DIR) 

if os.path.exists(DATASET_PATH):
    os.system(f"rm -rf {DATASET_PATH}")

import torch
CUDA = torch.cuda.is_available()
print(f"=INFO=Pytorch CUDA is available - {CUDA}", flush=True)


import tensorflow as tf
TENSOR = tf.reduce_sum(tf.random.normal([1000, 1000]))
GPU = tf.config.list_physical_devices('GPU')
print(f"=INFO=Tensorflow tensor - {TENSOR}", flush=True)
print(f"=INFO=Tensorflow GPUs - {GPU}", flush=True)

print(f"=INFO=Export result model", flush=True)

os.system(f"touch model.txt")

print("=MODEL={}/model.txt".format(os.getcwd()), flush=True)

print("=ERROR=Test error", flush=True)

raise Exception("Exception test")
