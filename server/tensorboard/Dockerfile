FROM python:3.13.0-slim

RUN pip install --upgrade pip
RUN pip install "setuptools<82" standard-imghdr tensorboard==2.*

EXPOSE 6006

CMD tensorboard --logdir=/data --host 0.0.0.0 --port 6006 --path_prefix=/tb