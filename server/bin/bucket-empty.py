#!/usr/bin/env python3
import json
import os
import sys
from urllib import error, parse, request


bucket = os.environ.get("GCS_BUCKET_NAME")
if not bucket:
    sys.stderr.write("GCS_BUCKET_NAME env variable is required\n")
    sys.exit(1)

host = os.environ.get("STORAGE_EMULATOR_HOST", "http://localhost:4443").rstrip("/")
base = f"{host}/storage/v1/b/{bucket}"
folder = os.environ.get("GCS_BUCKET_DIRECTORY", "").strip("/")


def list_objects(query_suffix=""):
    try:
        with request.urlopen(f"{base}/o{query_suffix}") as resp:
            return json.load(resp).get("items", [])
    except error.HTTPError as exc:
        if exc.code == 404:
            return []
        sys.stderr.write(f"List objects failed: {exc.read().decode()}\n")
        sys.exit(1)


def delete_object(name):
    url = f"{base}/o/{parse.quote(name, safe='')}"
    req = request.Request(url, method="DELETE")
    try:
        request.urlopen(req)
        print(f"Deleted object: {name}")
    except error.HTTPError as exc:
        sys.stderr.write(f"Delete {name} failed: {exc.read().decode()}\n")
        sys.exit(1)


def purge(query_suffix=""):
    items = list_objects(query_suffix)
    for item in items:
        delete_object(item.get("name"))
    return bool(items)


if folder:
    prefix = parse.quote(f"{folder}/", safe="")
    while purge(f"?prefix={prefix}"):
        pass

if not os.environ.get("GCS_BUCKET_ONLY_PREFIX"):
    while purge():
        pass
