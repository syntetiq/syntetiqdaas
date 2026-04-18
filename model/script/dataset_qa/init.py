import importlib.util
import os
import subprocess
import sys


def has_module(module_name: str) -> bool:
    return importlib.util.find_spec(module_name) is not None


def run_pip_install(*packages: str) -> None:
    if not packages:
        return
    subprocess.call([sys.executable, "-m", "pip", "install", *packages])


def main() -> int:
    skip_install = os.environ.get("SKIP_INSTALL", "false").lower() == "true"
    gpu = os.environ.get("GPU", "disable")

    print(f"=INFO=Dataset QA Python version - {sys.version}", flush=True)
    if skip_install:
        print("=INFO=Skipping dataset QA dependency installation", flush=True)
        return 0

    if not has_module("torch"):
        if gpu == "enable":
            run_pip_install("torch", "torchvision")
        else:
            run_pip_install("torch", "torchvision", "--index-url", "https://download.pytorch.org/whl/cpu")

    dependencies = [
        "pandas",
        "matplotlib",
        "PyYAML",
        "Pillow",
        "imagehash",
        "fiftyone",
    ]
    for package in dependencies:
        run_pip_install(package)

    # FiftyOne can pull the default Polars wheel, which requires newer CPU
    # features than some of our deployment hosts provide.
    run_pip_install("--upgrade", "polars[rtcompat]")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
