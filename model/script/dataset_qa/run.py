import argparse
import hashlib
import json
import math
import os
from datetime import datetime, timezone
from itertools import combinations
from pathlib import Path
from typing import Optional

os.environ.setdefault("MPLBACKEND", "Agg")

import matplotlib.pyplot as plt
import pandas as pd
import yaml
from PIL import Image


SPLIT_NAMES = ("train", "val", "test")
BORDER_MARGIN = 0.001
EXTREME_ASPECT_RATIO = 8.0
DUPLICATE_BOX_DECIMALS = 6
PREDICTIONS_FIELD = "qa_predictions"
LOCALIZATION_MODEL = "faster-rcnn-resnet50-fpn-coco-torch"
IOU_MATCH_THRESHOLD = 0.5
AREA_BUCKETS = ["tiny", "small", "medium", "large"]
PLOT_TITLE_SIZE = 32
PLOT_LABEL_SIZE = 24
PLOT_TICK_SIZE = 24
PLOT_TEXT_SIZE = 24


class ProgressReporter:
    def __init__(self, path: Optional[Path]):
        self.path = path

    def update(self, progress: float, message: str) -> None:
        if self.path is None:
            return

        payload = {
            "progress": max(0.0, min(1.0, float(progress))),
            "message": message,
            "updated_at": utc_now(),
        }
        ensure_dir(self.path.parent)
        tmp_path = self.path.with_suffix(self.path.suffix + ".tmp")
        tmp_path.write_text(json.dumps(payload, indent=2), encoding="utf-8")
        tmp_path.replace(self.path)


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat()


def json_safe(value):
    if isinstance(value, dict):
        return {str(k): json_safe(v) for k, v in value.items()}
    if isinstance(value, (list, tuple, set)):
        return [json_safe(v) for v in value]
    if isinstance(value, Path):
        return str(value)
    if hasattr(value, "item"):
        try:
            return value.item()
        except Exception:
            pass
    try:
        if pd.isna(value):
            return None
    except Exception:
        pass
    return value


def section_state(status: str, message: Optional[str] = None) -> dict:
    payload = {"status": status}
    if message:
        payload["message"] = message
    return payload


def read_yaml(path: Path) -> dict:
    with path.open("r", encoding="utf-8") as handle:
        data = yaml.safe_load(handle) or {}
    if not isinstance(data, dict):
        raise ValueError(f"Expected YAML mapping in {path}")
    return data


def normalize_names(names_value) -> dict[int, str]:
    if isinstance(names_value, dict):
        return {int(key): str(value) for key, value in names_value.items()}
    if isinstance(names_value, list):
        return {index: str(value) for index, value in enumerate(names_value)}
    return {}


def resolve_split_dirs(dataset_path: Path, config: dict) -> dict[str, Path]:
    result = {}
    for split in SPLIT_NAMES:
        split_value = config.get(split)
        if not split_value:
            continue

        split_path = Path(split_value)
        if not split_path.is_absolute():
            split_path = (dataset_path / split_path).resolve()
        result[split] = split_path
    if not result:
        raise ValueError(f"Could not infer split directories from {dataset_path / 'data.yaml'}")
    return result


def image_size(image_path: Path) -> tuple[int, int]:
    with Image.open(image_path) as image:
        return image.width, image.height


def parse_label_file(label_path: Path, labels_map: dict[int, str]) -> list[dict]:
    if not label_path.exists():
        return []

    content = label_path.read_text(encoding="utf-8").strip()
    if content == "":
        return []

    records = []
    for index, raw_line in enumerate(content.splitlines()):
        line = raw_line.strip()
        if line == "":
            continue
        parts = line.split()
        if len(parts) != 5:
            continue
        class_id = int(float(parts[0]))
        x_center, y_center, width, height = [float(value) for value in parts[1:]]
        x = x_center - (width / 2)
        y = y_center - (height / 2)
        records.append(
            {
                "label_id": f"{label_path.stem}-{index}",
                "class_id": class_id,
                "label": labels_map.get(class_id, str(class_id)),
                "rel_x": x,
                "rel_y": y,
                "rel_w": width,
                "rel_h": height,
            }
        )
    return records


def load_dataset(dataset_path: Path) -> tuple[list[dict], pd.DataFrame, pd.DataFrame, dict]:
    data_yaml_path = dataset_path / "data.yaml"
    if not data_yaml_path.exists():
        raise ValueError(f"{data_yaml_path} does not exist")

    config = read_yaml(data_yaml_path)
    labels_map = normalize_names(config.get("names", {}))
    split_dirs = resolve_split_dirs(dataset_path, config)

    samples = []
    detection_rows = []
    sample_rows = []

    for split, split_images_dir in split_dirs.items():
        if not split_images_dir.exists():
            continue

        labels_dir = split_images_dir.parent / "labels"
        for image_path in sorted([p for p in split_images_dir.rglob("*") if p.is_file()]):
            if image_path.suffix.lower() not in {".jpg", ".jpeg", ".png", ".webp", ".bmp"}:
                continue

            width, height = image_size(image_path)
            label_path = labels_dir / f"{image_path.stem}.txt"
            annotations = parse_label_file(label_path, labels_map)
            sample_id = str(image_path.relative_to(dataset_path))

            samples.append(
                {
                    "sample_id": sample_id,
                    "filepath": str(image_path),
                    "relative_filepath": sample_id,
                    "split": split,
                    "width": width,
                    "height": height,
                    "annotations": annotations,
                }
            )
            sample_rows.append(
                {
                    "sample_id": sample_id,
                    "filepath": str(image_path),
                    "relative_filepath": sample_id,
                    "split": split,
                    "img_width": width,
                    "img_height": height,
                    "annotation_count": len(annotations),
                }
            )

            for annotation in annotations:
                detection_rows.append(
                    {
                        "sample_id": sample_id,
                        "filepath": str(image_path),
                        "relative_filepath": sample_id,
                        "split": split,
                        "label_id": annotation["label_id"],
                        "label": annotation["label"],
                        "class_id": annotation["class_id"],
                        "rel_x": annotation["rel_x"],
                        "rel_y": annotation["rel_y"],
                        "rel_w": annotation["rel_w"],
                        "rel_h": annotation["rel_h"],
                        "rel_area": annotation["rel_w"] * annotation["rel_h"],
                        "aspect_ratio": (annotation["rel_w"] / annotation["rel_h"]) if annotation["rel_h"] else None,
                        "center_x": annotation["rel_x"] + (annotation["rel_w"] / 2),
                        "center_y": annotation["rel_y"] + (annotation["rel_h"] / 2),
                        "img_width": width,
                        "img_height": height,
                    }
                )

    sample_meta_df = pd.DataFrame(sample_rows)
    gt_df = pd.DataFrame(detection_rows)
    return samples, sample_meta_df, gt_df, {"names": labels_map, "split_dirs": split_dirs}


def bucket_area(area):
    if pd.isna(area):
        return "unknown"
    if area < 0.001:
        return "tiny"
    if area < 0.01:
        return "small"
    if area < 0.1:
        return "medium"
    return "large"


def add_bbox_validation_columns(df: pd.DataFrame):
    df = df.copy()
    if df.empty:
        for column in [
            "has_negative_size",
            "has_zero_area",
            "out_of_bounds",
            "border_touching",
            "extreme_aspect_ratio",
            "duplicate_box",
            "issue_count",
            "any_bbox_issue",
            "issue_types",
        ]:
            df[column] = []
        return df, [
            "has_negative_size",
            "has_zero_area",
            "out_of_bounds",
            "border_touching",
            "extreme_aspect_ratio",
            "duplicate_box",
        ]

    df["has_negative_size"] = (df["rel_w"] < 0) | (df["rel_h"] < 0)
    df["has_zero_area"] = (df["rel_w"] <= 0) | (df["rel_h"] <= 0) | (df["rel_area"] <= 0)
    df["out_of_bounds"] = (
        (df["rel_x"] < 0)
        | (df["rel_y"] < 0)
        | ((df["rel_x"] + df["rel_w"]) > 1)
        | ((df["rel_y"] + df["rel_h"]) > 1)
    )
    df["border_touching"] = (
        (df["rel_x"] <= BORDER_MARGIN)
        | (df["rel_y"] <= BORDER_MARGIN)
        | ((df["rel_x"] + df["rel_w"]) >= 1 - BORDER_MARGIN)
        | ((df["rel_y"] + df["rel_h"]) >= 1 - BORDER_MARGIN)
    )
    aspect_ratio = df["aspect_ratio"].replace([float("inf")], pd.NA)
    df["extreme_aspect_ratio"] = (
        (aspect_ratio >= EXTREME_ASPECT_RATIO)
        | (aspect_ratio <= (1 / EXTREME_ASPECT_RATIO))
    ).fillna(False)
    rounded = df[["rel_x", "rel_y", "rel_w", "rel_h"]].round(DUPLICATE_BOX_DECIMALS)
    df["duplicate_box"] = pd.DataFrame(
        {
            "sample_id": df["sample_id"],
            "label": df["label"],
            "rel_x": rounded["rel_x"],
            "rel_y": rounded["rel_y"],
            "rel_w": rounded["rel_w"],
            "rel_h": rounded["rel_h"],
        }
    ).duplicated(keep=False)
    validation_columns = [
        "has_negative_size",
        "has_zero_area",
        "out_of_bounds",
        "border_touching",
        "extreme_aspect_ratio",
        "duplicate_box",
    ]
    df["issue_count"] = df[validation_columns].sum(axis=1)
    df["any_bbox_issue"] = df["issue_count"] > 0
    df["issue_types"] = df[validation_columns].apply(
        lambda row: [column for column, value in row.items() if value], axis=1
    )
    return df, validation_columns


def add_custom_heuristics(df: pd.DataFrame, min_samples: int = 10) -> pd.DataFrame:
    df = df.copy()
    df["class_size_outlier"] = False
    df["class_aspect_outlier"] = False

    for label, group in df.groupby("label"):
        if len(group) < min_samples:
            continue
        for source_column, target_column in (("rel_area", "class_size_outlier"), ("aspect_ratio", "class_aspect_outlier")):
            series = group[source_column].replace([float("inf")], pd.NA).dropna()
            if len(series) < min_samples:
                continue
            q1 = float(series.quantile(0.25))
            q3 = float(series.quantile(0.75))
            iqr = q3 - q1
            lower = q1 - (1.5 * iqr)
            upper = q3 + (1.5 * iqr)
            mask = (df["label"] == label) & ((df[source_column] < lower) | (df[source_column] > upper))
            df.loc[mask, target_column] = True

    df["tight_or_loose_box"] = df["class_size_outlier"] | df["class_aspect_outlier"] | df["border_touching"]
    return df


def bbox_iou(box_a, box_b):
    ax1, ay1, aw, ah = box_a
    bx1, by1, bw, bh = box_b
    ax2, ay2 = ax1 + aw, ay1 + ah
    bx2, by2 = bx1 + bw, by1 + bh
    inter_x1 = max(ax1, bx1)
    inter_y1 = max(ay1, by1)
    inter_x2 = min(ax2, bx2)
    inter_y2 = min(ay2, by2)
    inter_w = max(0.0, inter_x2 - inter_x1)
    inter_h = max(0.0, inter_y2 - inter_y1)
    inter_area = inter_w * inter_h
    union = (aw * ah) + (bw * bh) - inter_area
    return 0.0 if union <= 0 else inter_area / union


def find_overlap_pairs(df: pd.DataFrame, iou_threshold: float = 0.9) -> pd.DataFrame:
    rows = []
    columns = ["sample_id", "filepath", "split", "label", "label_id_a", "label_id_b", "iou"]
    if df.empty:
        return pd.DataFrame(columns=columns)
    for (_, label), group in df.groupby(["sample_id", "label"]):
        if len(group) < 2:
            continue
        records = group[
            ["sample_id", "filepath", "split", "label", "label_id", "rel_x", "rel_y", "rel_w", "rel_h"]
        ].to_dict("records")
        for record_a, record_b in combinations(records, 2):
            iou = bbox_iou(
                (record_a["rel_x"], record_a["rel_y"], record_a["rel_w"], record_a["rel_h"]),
                (record_b["rel_x"], record_b["rel_y"], record_b["rel_w"], record_b["rel_h"]),
            )
            if iou >= iou_threshold:
                rows.append(
                    {
                        "sample_id": record_a["sample_id"],
                        "filepath": record_a["filepath"],
                        "split": record_a["split"],
                        "label": record_a["label"],
                        "label_id_a": record_a["label_id"],
                        "label_id_b": record_b["label_id"],
                        "iou": iou,
                    }
                )
    if not rows:
        return pd.DataFrame(columns=columns)
    return pd.DataFrame(rows).sort_values(["iou", "filepath"], ascending=[False, True]).reset_index(drop=True)


def build_image_difficulty(df: pd.DataFrame, sample_meta_df: pd.DataFrame) -> pd.DataFrame:
    if df.empty:
        difficulty_df = sample_meta_df.copy()
        difficulty_df["object_count"] = 0
        difficulty_df["class_diversity"] = 0
        difficulty_df["avg_object_size"] = 0
        difficulty_df["total_object_area"] = 0
        difficulty_df["median_object_size"] = 0
        difficulty_df["difficulty_score"] = 0
        return difficulty_df

    grouped = df.groupby("sample_id").agg(
        object_count=("label_id", "count"),
        class_diversity=("label", "nunique"),
        avg_object_size=("rel_area", "mean"),
        total_object_area=("rel_area", "sum"),
        median_object_size=("rel_area", "median"),
    )
    difficulty_df = sample_meta_df.merge(grouped, on="sample_id", how="left")
    fill_zero_columns = ["object_count", "class_diversity", "avg_object_size", "total_object_area", "median_object_size"]
    difficulty_df[fill_zero_columns] = difficulty_df[fill_zero_columns].fillna(0)
    difficulty_df["count_pressure"] = difficulty_df["object_count"].rank(pct=True, method="average")
    difficulty_df["density_pressure"] = difficulty_df["total_object_area"].rank(pct=True, method="average")
    difficulty_df["diversity_pressure"] = difficulty_df["class_diversity"].rank(pct=True, method="average")
    difficulty_df["small_object_pressure"] = 0.0
    annotated_mask = difficulty_df["object_count"] > 0
    if annotated_mask.any():
        avg_size_rank = difficulty_df.loc[annotated_mask, "avg_object_size"].rank(pct=True, method="average")
        difficulty_df.loc[annotated_mask, "small_object_pressure"] = 1 - avg_size_rank
    difficulty_df["difficulty_score"] = 100 * (
        (0.35 * difficulty_df["count_pressure"])
        + (0.25 * difficulty_df["density_pressure"])
        + (0.25 * difficulty_df["small_object_pressure"])
        + (0.15 * difficulty_df["diversity_pressure"])
    )
    return difficulty_df.sort_values("difficulty_score", ascending=False).reset_index(drop=True)


def build_quality_report(
    df: pd.DataFrame,
    total_images: int,
    class_counts: pd.Series,
    leaks_df: pd.DataFrame,
    exact_review_paths: list[str],
    near_review_paths: list[str],
    size_bucket_counts: pd.Series,
    overlap_df: pd.DataFrame,
    suspicious_sample_summary: pd.DataFrame,
):
    total_images = max(int(total_images), 1)
    total_boxes = max(int(len(df)), 1)

    if len(class_counts) <= 1:
        class_balance_score = 100.0
    else:
        balance_ratio = float(class_counts.min()) / float(class_counts.max()) if float(class_counts.max()) else 1.0
        class_balance_score = 100 * (balance_ratio ** 0.5)

    bbox_validity_score = 100 * max(0.0, 1 - float(df["any_bbox_issue"].mean() if not df.empty else 0.0))
    duplicates_rate = min(1.0, (len(exact_review_paths) + (0.5 * len(near_review_paths))) / total_images)
    duplicates_score = 100 * (1 - duplicates_rate)

    eval_splits = [split for split in ("val", "test") if split in set(df["split"]) and not df.empty]
    if eval_splits:
        all_classes = set(class_counts.index)
        coverage_scores = []
        for split in eval_splits:
            split_classes = set(df.loc[df["split"] == split, "label"])
            coverage_scores.append(len(split_classes) / max(len(all_classes), 1))
        class_coverage = sum(coverage_scores) / len(coverage_scores)
    else:
        class_coverage = 1.0

    leak_rate = min(1.0, len(leaks_df) / total_images) if total_images else 0.0
    split_health_score = 100 * ((0.7 * class_coverage) + (0.3 * (1 - leak_rate)))
    nonzero_buckets = int((size_bucket_counts > 0).sum()) if not size_bucket_counts.empty else 0
    size_coverage_score = 100 * (nonzero_buckets / 4)
    overlap_rate = min(1.0, len(overlap_df) / total_boxes)
    outlier_rate = min(1.0, float(df["tight_or_loose_box"].mean() if not df.empty else 0.0))
    suspicious_rate = min(1.0, suspicious_sample_summary.shape[0] / total_images)
    consistency_proxy_score = 100 * max(0.0, 1 - ((0.5 * overlap_rate) + (0.3 * outlier_rate) + (0.2 * suspicious_rate)))

    scores = {
        "class_balance": round(class_balance_score, 2),
        "bbox_validity": round(bbox_validity_score, 2),
        "consistency_proxy": round(consistency_proxy_score, 2),
        "duplicates": round(duplicates_score, 2),
        "split_health": round(split_health_score, 2),
        "size_coverage": round(size_coverage_score, 2),
    }
    weights = {
        "class_balance": 0.20,
        "bbox_validity": 0.20,
        "consistency_proxy": 0.15,
        "duplicates": 0.15,
        "split_health": 0.15,
        "size_coverage": 0.15,
    }
    total_score = sum(scores[name] * weight for name, weight in weights.items())
    return {
        "total_score": round(total_score, 2),
        "sub_scores": scores,
        "notes": {
            "consistency_proxy": "Uses same-class high-IoU overlaps and class-specific size/aspect outliers even when model-assisted localization consistency is unavailable.",
        },
    }


def save_current_figure(path: Path):
    plt.tight_layout()
    plt.savefig(path, dpi=300, bbox_inches="tight")
    plt.close()


def style_axes(ax, title: str, xlabel: Optional[str] = None, ylabel: Optional[str] = None, rotate_xticks: int = 0):
    ax.set_title(title, fontsize=PLOT_TITLE_SIZE, pad=18)
    if xlabel is not None:
        ax.set_xlabel(xlabel, fontsize=PLOT_LABEL_SIZE, labelpad=12)
    if ylabel is not None:
        ax.set_ylabel(ylabel, fontsize=PLOT_LABEL_SIZE, labelpad=12)
    ax.tick_params(axis="both", labelsize=PLOT_TICK_SIZE)
    for label in ax.get_xticklabels():
        label.set_rotation(rotate_xticks)
        label.set_horizontalalignment("right" if rotate_xticks else "center")
    return ax


def ensure_dir(path: Path):
    path.mkdir(parents=True, exist_ok=True)


def add_file_entry(entries: list[dict], label: str, path: Path, description: Optional[str] = None):
    entries.append(
        {
            "label": label,
            "path": str(path.as_posix()),
            "description": description,
        }
    )


def plot_placeholder(title: str, path: Path, message: str):
    plt.figure(figsize=(16, 8))
    plt.title(title, fontsize=PLOT_TITLE_SIZE, pad=18)
    plt.text(0.5, 0.5, message, ha="center", va="center", fontsize=PLOT_TEXT_SIZE)
    plt.axis("off")
    save_current_figure(path)


def export_core_artifacts(
    gt_df: pd.DataFrame,
    objects_per_class: pd.Series,
    objects_per_image: pd.Series,
    bbox_validation_summary: pd.DataFrame,
    size_bucket_counts: pd.Series,
    difficulty_df: pd.DataFrame,
    artifacts_dir: Path,
) -> list[dict]:
    artifacts = []

    path = artifacts_dir / "class_histogram.png"
    if objects_per_class.empty:
        plot_placeholder("Objects Per Class", path, "No annotations available")
    else:
        plt.figure(figsize=(20, 8))
        ax = objects_per_class.plot(kind="bar")
        style_axes(ax, "Objects Per Class", xlabel="Label", ylabel="Objects", rotate_xticks=90)
        plt.grid(alpha=0.2)
        save_current_figure(path)
    add_file_entry(artifacts, "Class Histogram", Path("artifacts/class_histogram.png"), "Object count by class.")

    path = artifacts_dir / "bbox_area_histogram.png"
    if gt_df.empty:
        plot_placeholder("BBox Relative Area", path, "No annotations available")
    else:
        plt.figure(figsize=(16, 8))
        num_bins = min(30, gt_df["rel_area"].nunique() or 1)
        ax = gt_df["rel_area"].plot(kind="hist", bins=num_bins)
        style_axes(ax, "BBox Relative Area", xlabel="Relative Area", ylabel="Boxes")
        plt.grid(alpha=0.2)
        save_current_figure(path)
    add_file_entry(artifacts, "BBox Area Histogram", Path("artifacts/bbox_area_histogram.png"), "Relative bbox size distribution.")

    path = artifacts_dir / "bbox_aspect_ratio_histogram.png"
    aspect_ratio_series = gt_df["aspect_ratio"].replace([float("inf")], pd.NA).dropna() if not gt_df.empty else pd.Series(dtype=float)
    if aspect_ratio_series.empty:
        plot_placeholder("BBox Aspect Ratio", path, "No annotations available")
    else:
        plt.figure(figsize=(16, 8))
        num_bins = min(30, aspect_ratio_series.nunique() or 1)
        ax = aspect_ratio_series.plot(kind="hist", bins=num_bins)
        style_axes(ax, "BBox Aspect Ratio", xlabel="Aspect Ratio", ylabel="Boxes")
        plt.grid(alpha=0.2)
        save_current_figure(path)
    add_file_entry(artifacts, "BBox Aspect Ratio Histogram", Path("artifacts/bbox_aspect_ratio_histogram.png"), "BBox shape distribution.")

    if not objects_per_image.empty:
        plt.figure(figsize=(16, 8))
        num_bins = min(20, objects_per_image.nunique() or 1)
        ax = objects_per_image.plot(kind="hist", bins=num_bins)
        style_axes(ax, "Objects Per Image", xlabel="Objects Per Image", ylabel="Images")
        plt.grid(alpha=0.2)
        path = artifacts_dir / "objects_per_image_histogram.png"
        save_current_figure(path)
        add_file_entry(artifacts, "Objects Per Image", Path("artifacts/objects_per_image_histogram.png"), "Object count distribution per image.")
    else:
        path = artifacts_dir / "objects_per_image_histogram.png"
        plot_placeholder("Objects Per Image", path, "No images available")
        add_file_entry(artifacts, "Objects Per Image", Path("artifacts/objects_per_image_histogram.png"), "Object count distribution per image.")

    plt.figure(figsize=(16, 8))
    ax = size_bucket_counts.reindex(AREA_BUCKETS, fill_value=0).plot(kind="bar")
    style_axes(ax, "Object Size Buckets", xlabel="Size Bucket", ylabel="Objects", rotate_xticks=0)
    plt.grid(alpha=0.2)
    path = artifacts_dir / "size_buckets.png"
    save_current_figure(path)
    add_file_entry(artifacts, "Size Buckets", Path("artifacts/size_buckets.png"), "Coverage across tiny/small/medium/large buckets.")

    plt.figure(figsize=(20, 8))
    ax = bbox_validation_summary.set_index("issue")["boxes"].plot(kind="bar")
    style_axes(ax, "Bounding-Box Validation Flags", xlabel="Issue", ylabel="Boxes", rotate_xticks=45)
    plt.grid(alpha=0.2)
    path = artifacts_dir / "bbox_validation_flags.png"
    save_current_figure(path)
    add_file_entry(artifacts, "Validation Flags", Path("artifacts/bbox_validation_flags.png"), "Counts of invalid or suspicious bbox flags.")

    path = artifacts_dir / "difficulty_distribution.png"
    if difficulty_df.empty:
        plot_placeholder("Image Difficulty Score Distribution", path, "No images available")
    else:
        plt.figure(figsize=(16, 8))
        num_bins = min(20, difficulty_df["difficulty_score"].nunique() or 1)
        ax = difficulty_df["difficulty_score"].plot(kind="hist", bins=num_bins)
        style_axes(ax, "Image Difficulty Score Distribution", xlabel="Difficulty Score", ylabel="Images")
        plt.grid(alpha=0.2)
        save_current_figure(path)
    add_file_entry(artifacts, "Difficulty", Path("artifacts/difficulty_distribution.png"), "Distribution of heuristic image difficulty scores.")

    return artifacts


def try_import_fiftyone():
    try:
        import fiftyone as fo
        import fiftyone.brain as fob
        import fiftyone.zoo as foz
    except Exception as exc:
        return None, None, None, exc
    return fo, fob, foz, None


def create_fiftyone_dataset(samples: list[dict]):
    fo, _, _, error = try_import_fiftyone()
    if error:
        raise error

    dataset_name = "dataset_qa_" + hashlib.sha1((samples[0]["filepath"] if samples else utc_now()).encode("utf-8")).hexdigest()[:10]
    dataset = fo.Dataset(dataset_name)
    fo_samples = []
    for sample in samples:
        detections = []
        for annotation in sample["annotations"]:
            detections.append(
                fo.Detection(
                    label=annotation["label"],
                    bounding_box=[annotation["rel_x"], annotation["rel_y"], annotation["rel_w"], annotation["rel_h"]],
                )
            )
        fo_sample = fo.Sample(filepath=sample["filepath"])
        fo_sample.tags = [sample["split"]]
        fo_sample["ground_truth"] = fo.Detections(detections=detections)
        fo_samples.append(fo_sample)
    dataset.add_samples(fo_samples)
    dataset.compute_metadata()
    return dataset


def build_duplicates_section(
    samples: list[dict],
    artifacts_dir: Path,
    section_statuses: dict,
    reporter: Optional[ProgressReporter] = None,
) -> tuple[dict, pd.DataFrame, list[str], list[str], pd.DataFrame]:
    fo, fob, _, error = try_import_fiftyone()
    if error:
        section_statuses["duplicates"] = section_state("failed", str(error))
        empty = pd.DataFrame(columns=["filepath"])
        return {
            "exact_duplicate_groups": 0,
            "near_duplicate_pairs": 0,
            "split_leak_samples": 0,
            "lowest_uniqueness": [],
        }, empty, [], [], empty

    dataset = create_fiftyone_dataset(samples)
    try:
        filepath_map = dict(zip(dataset.values("id"), dataset.values("filepath")))

        if reporter:
            reporter.update(0.58, "Computing exact duplicate groups.")
        exact_duplicates = fob.compute_exact_duplicates(dataset, num_workers=0)
        exact_rows = []
        exact_review_paths = set()
        for anchor_id, duplicate_ids in exact_duplicates.items():
            for duplicate_id in duplicate_ids:
                exact_rows.append(
                    {
                        "anchor_path": filepath_map.get(anchor_id),
                        "duplicate_path": filepath_map.get(duplicate_id),
                    }
                )
                exact_review_paths.update([filepath_map.get(anchor_id), filepath_map.get(duplicate_id)])
        exact_df = pd.DataFrame(exact_rows)

        if reporter:
            reporter.update(0.64, "Computing near-duplicate pairs.")
        near_duplicates = fob.compute_near_duplicates(dataset, threshold=0.15, num_workers=0)
        near_rows = []
        near_review_paths = set()
        for anchor_id, neighbors in (near_duplicates.neighbors_map or {}).items():
            for neighbor_id, distance in neighbors:
                near_rows.append(
                    {
                        "anchor_path": filepath_map.get(anchor_id),
                        "neighbor_path": filepath_map.get(neighbor_id),
                        "distance": distance,
                    }
                )
                near_review_paths.update([filepath_map.get(anchor_id), filepath_map.get(neighbor_id)])
        near_df = pd.DataFrame(near_rows)

        available_splits = [split for split in SPLIT_NAMES if dataset.match_tags(split).count() > 0]
        if len(available_splits) >= 2:
            if reporter:
                reporter.update(0.68, "Checking for split leakage.")
            leaks_index = fob.compute_leaky_splits(dataset, splits=available_splits, threshold=0.15, num_workers=0)
            leaks_index.tag_leaks("leak")
            leak_view = leaks_index.leaks_view()
            leaks_df = pd.DataFrame({"filepath": leak_view.values("filepath")})
        else:
            leaks_df = pd.DataFrame(columns=["filepath"])

        if "uniqueness" not in dataset.get_field_schema() or dataset.exists("uniqueness").count() != dataset.count():
            if reporter:
                reporter.update(0.72, "Computing uniqueness scores.")
            fob.compute_uniqueness(dataset, uniqueness_field="uniqueness", num_workers=0)

        uniqueness_df = pd.DataFrame(
            {
                "filepath": dataset.values("filepath"),
                "uniqueness": dataset.values("uniqueness"),
            }
        ).sort_values("uniqueness")

        plt.figure(figsize=(16, 8))
        num_bins = min(20, uniqueness_df["uniqueness"].nunique() or 1)
        ax = uniqueness_df["uniqueness"].plot(kind="hist", bins=num_bins)
        style_axes(ax, "Uniqueness Distribution", xlabel="Uniqueness", ylabel="Images")
        plt.grid(alpha=0.2)
        save_current_figure(artifacts_dir / "uniqueness_distribution.png")

        section_statuses["duplicates"] = section_state("succeeded")
        return (
            {
                "exact_duplicate_groups": len(exact_duplicates),
                "near_duplicate_pairs": int(len(near_df)),
                "split_leak_samples": int(len(leaks_df)),
                "lowest_uniqueness": uniqueness_df.head(20).to_dict("records"),
            },
            leaks_df,
            sorted(path for path in exact_review_paths if path),
            sorted(path for path in near_review_paths if path),
            uniqueness_df,
        )
    finally:
        dataset.delete()


def greedy_match_detections(gt_records, pred_records, iou_threshold=IOU_MATCH_THRESHOLD):
    candidates = []
    for gt_idx, gt_record in enumerate(gt_records):
        gt_box = (gt_record["rel_x"], gt_record["rel_y"], gt_record["rel_w"], gt_record["rel_h"])
        for pred_idx, pred_record in enumerate(pred_records):
            pred_box = (pred_record["rel_x"], pred_record["rel_y"], pred_record["rel_w"], pred_record["rel_h"])
            iou = bbox_iou(gt_box, pred_box)
            if iou >= iou_threshold:
                candidates.append((iou, gt_idx, pred_idx))
    matches = []
    used_gt = set()
    used_pred = set()
    for iou, gt_idx, pred_idx in sorted(candidates, reverse=True):
        if gt_idx in used_gt or pred_idx in used_pred:
            continue
        used_gt.add(gt_idx)
        used_pred.add(pred_idx)
        matches.append((gt_idx, pred_idx, iou))
    missing_gt = [gt_records[i] for i in range(len(gt_records)) if i not in used_gt]
    extra_pred = [pred_records[i] for i in range(len(pred_records)) if i not in used_pred]
    return matches, missing_gt, extra_pred


def evaluate_localization_consistency(gt_df: pd.DataFrame, pred_df: pd.DataFrame, iou_threshold=IOU_MATCH_THRESHOLD):
    match_rows = []
    sample_rows = []
    missing_rows = []
    extra_rows = []
    gt_groups = {sample_id: grp.to_dict("records") for sample_id, grp in gt_df.groupby("sample_id")}
    pred_groups = {sample_id: grp.to_dict("records") for sample_id, grp in pred_df.groupby("sample_id")}
    all_sample_ids = sorted(set(gt_groups) | set(pred_groups))

    for sample_id in all_sample_ids:
        gt_records = gt_groups.get(sample_id, [])
        pred_records = pred_groups.get(sample_id, [])
        matches, missing_gt, extra_pred = greedy_match_detections(gt_records, pred_records, iou_threshold=iou_threshold)
        filepath = (gt_records or pred_records or [{"filepath": None}])[0]["filepath"]
        split = (gt_records or pred_records or [{"split": "unspecified"}])[0]["split"]
        for gt_idx, pred_idx, iou in matches:
            gt_record = gt_records[gt_idx]
            pred_record = pred_records[pred_idx]
            match_rows.append(
                {
                    "sample_id": sample_id,
                    "filepath": filepath,
                    "split": split,
                    "gt_label": gt_record["label"],
                    "pred_label": pred_record["label"],
                    "gt_label_id": gt_record["label_id"],
                    "pred_label_id": pred_record["label_id"],
                    "iou": iou,
                }
            )
        for record in missing_gt:
            missing_rows.append(
                {
                    "sample_id": sample_id,
                    "filepath": filepath,
                    "split": split,
                    "gt_label": record["label"],
                    "gt_label_id": record["label_id"],
                }
            )
        for record in extra_pred:
            extra_rows.append(
                {
                    "sample_id": sample_id,
                    "filepath": filepath,
                    "split": split,
                    "pred_label": record["label"],
                    "pred_label_id": record["label_id"],
                }
            )
        sample_rows.append(
            {
                "sample_id": sample_id,
                "filepath": filepath,
                "split": split,
                "gt_count": len(gt_records),
                "pred_count": len(pred_records),
                "matched_count": len(matches),
                "missing_count": len(missing_gt),
                "extra_count": len(extra_pred),
                "mean_iou": sum(match[2] for match in matches) / len(matches) if matches else None,
            }
        )

    matches_df = pd.DataFrame(match_rows)
    samples_df = pd.DataFrame(sample_rows)
    missing_df = pd.DataFrame(missing_rows)
    extra_df = pd.DataFrame(extra_rows)

    per_class_iou = pd.DataFrame(columns=["gt_label", "match_count", "mean_iou", "median_iou"])
    if not matches_df.empty:
        per_class_iou = (
            matches_df.groupby("gt_label")["iou"]
            .agg([("match_count", "count"), ("mean_iou", "mean"), ("median_iou", "median")])
            .reset_index()
            .sort_values(["match_count", "gt_label"], ascending=[False, True])
        )
    summary = {
        "match_iou_threshold": iou_threshold,
        "matched_pairs": int(len(matches_df)),
        "mean_iou": round(float(matches_df["iou"].mean()), 4) if not matches_df.empty else None,
        "median_iou": round(float(matches_df["iou"].median()), 4) if not matches_df.empty else None,
        "missing_objects": int(len(missing_df)),
        "extra_objects": int(len(extra_df)),
    }
    return {
        "summary": summary,
        "matches_df": matches_df,
        "samples_df": samples_df,
        "missing_df": missing_df,
        "extra_df": extra_df,
        "per_class_iou_df": per_class_iou,
    }


def build_consistency_section(
    samples: list[dict],
    gt_df: pd.DataFrame,
    artifacts_dir: Path,
    section_statuses: dict,
    reporter: Optional[ProgressReporter] = None,
):
    fo, _, foz, error = try_import_fiftyone()
    if error:
        section_statuses["consistency"] = section_state("failed", str(error))
        return {
            "summary": {"match_iou_threshold": IOU_MATCH_THRESHOLD},
            "per_class_iou": [],
            "samples": [],
            "missing": [],
            "extra": [],
        }

    dataset = create_fiftyone_dataset(samples)
    try:
        if reporter:
            reporter.update(0.78, "Loading consistency-check model.")
        model = foz.load_zoo_model(LOCALIZATION_MODEL)
        if reporter:
            reporter.update(0.82, "Running model-based consistency checks.")
        dataset.apply_model(model, label_field=PREDICTIONS_FIELD, num_workers=0)

        pred_rows = []
        for sample in dataset.select_fields([PREDICTIONS_FIELD]).iter_samples(progress=False):
            detections = getattr(getattr(sample, PREDICTIONS_FIELD, None), "detections", None) or []
            for index, det in enumerate(detections):
                x, y, w, h = det.bounding_box
                pred_rows.append(
                    {
                        "sample_id": str(Path(sample.filepath)),
                        "filepath": sample.filepath,
                        "split": sample.tags[0] if sample.tags else "unspecified",
                        "label_id": f"{Path(sample.filepath).stem}-pred-{index}",
                        "label": det.label,
                        "rel_x": x,
                        "rel_y": y,
                        "rel_w": w,
                        "rel_h": h,
                    }
                )
        pred_df = pd.DataFrame(pred_rows)
        gt_eval_df = gt_df.copy()
        gt_eval_df["sample_id"] = gt_eval_df["filepath"]
        if reporter:
            reporter.update(0.88, "Comparing predictions against annotations.")
        consistency_result = evaluate_localization_consistency(gt_eval_df, pred_df, iou_threshold=IOU_MATCH_THRESHOLD)

        if not consistency_result["matches_df"].empty:
            plt.figure(figsize=(16, 8))
            num_bins = min(20, consistency_result["matches_df"]["iou"].nunique() or 1)
            ax = consistency_result["matches_df"]["iou"].plot(kind="hist", bins=num_bins)
            style_axes(ax, "Localization IoU Distribution", xlabel="IoU", ylabel="Matched Boxes")
            plt.grid(alpha=0.2)
            save_current_figure(artifacts_dir / "localization_iou_histogram.png")
        else:
            plt.figure(figsize=(16, 8))
            plt.title("Localization IoU Distribution", fontsize=PLOT_TITLE_SIZE, pad=18)
            plt.text(0.5, 0.5, "No matched predictions", ha="center", va="center", fontsize=PLOT_TEXT_SIZE)
            plt.axis("off")
            save_current_figure(artifacts_dir / "localization_iou_histogram.png")

        section_statuses["consistency"] = section_state("succeeded")
        return {
            "summary": consistency_result["summary"],
            "per_class_iou": consistency_result["per_class_iou_df"].to_dict("records"),
            "samples": consistency_result["samples_df"].head(100).to_dict("records"),
            "missing": consistency_result["missing_df"].head(100).to_dict("records"),
            "extra": consistency_result["extra_df"].head(100).to_dict("records"),
        }
    except Exception as exc:
        section_statuses["consistency"] = section_state("failed", str(exc))
        return {
            "summary": {"match_iou_threshold": IOU_MATCH_THRESHOLD},
            "per_class_iou": [],
            "samples": [],
            "missing": [],
            "extra": [],
        }
    finally:
        dataset.delete()


def save_dataframe_csv(df: pd.DataFrame, path: Path, review_files: list[dict], label: str, description: str):
    df.to_csv(path, index=False)
    add_file_entry(review_files, label, Path("reviews") / path.name, description)


def build_empty_dataset_report(dataset_path: Path, scope: str, source_id: str, input_format: str) -> dict:
    empty_message = "Dataset does not contain any images to analyze."

    return {
        "dataset_info": {
            "dataset_name": dataset_path.name,
            "dataset_path": str(dataset_path),
            "generated_at": utc_now(),
            "scope": scope,
            "source_id": source_id,
            "input_format": input_format,
            "total_images": 0,
            "annotated_images": 0,
            "unannotated_images": 0,
            "total_objects": 0,
            "avg_objects_per_image": 0.0,
            "median_objects_per_image": 0.0,
            "class_count": 0,
        },
        "class_distribution": [],
        "bbox_statistics": {
            "size_buckets": {bucket: 0 for bucket in AREA_BUCKETS},
            "bbox_validation": [],
            "custom_heuristics": [],
        },
        "suspicious_annotations": [],
        "duplicates": {
            "exact_duplicate_groups": 0,
            "near_duplicate_pairs": 0,
            "split_leak_samples": 0,
            "lowest_uniqueness": [],
        },
        "split_quality": {
            "class_by_split": {},
            "size_by_split": {},
            "missing_classes_in_eval": {},
        },
        "image_difficulty": [],
        "consistency": {
            "summary": {},
            "per_class_iou": [],
            "samples": [],
            "missing": [],
            "extra": [],
        },
        "review_exports": {
            "suspicious_samples": [],
            "overlap_issues": [],
            "difficult_images": [],
        },
        "quality_score": {
            "total_score": None,
            "sub_scores": {},
            "notes": {
                "empty_dataset": empty_message,
            },
        },
        "section_statuses": {
            "dataset_info": section_state("succeeded"),
            "class_distribution": section_state("skipped", empty_message),
            "bbox_statistics": section_state("skipped", empty_message),
            "suspicious_annotations": section_state("skipped", empty_message),
            "duplicates": section_state("skipped", empty_message),
            "split_quality": section_state("skipped", empty_message),
            "image_difficulty": section_state("skipped", empty_message),
            "consistency": section_state("skipped", empty_message),
            "quality_score": section_state("skipped", empty_message),
        },
        "artifacts": [],
        "review_exports_files": [],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Evaluate dataset quality and export a JSON report.")
    parser.add_argument("--dataset-path", required=True)
    parser.add_argument("--output-dir", required=True)
    parser.add_argument("--progress-path", default="")
    parser.add_argument("--scope", default="build")
    parser.add_argument("--source-id", default="")
    parser.add_argument("--input-format", default="yolo")
    args = parser.parse_args()

    dataset_path = Path(args.dataset_path).expanduser().resolve()
    output_dir = Path(args.output_dir).expanduser().resolve()
    progress_path = Path(args.progress_path).expanduser().resolve() if args.progress_path else None
    reporter = ProgressReporter(progress_path)
    artifacts_dir = output_dir / "artifacts"
    reviews_dir = output_dir / "reviews"
    ensure_dir(artifacts_dir)
    ensure_dir(reviews_dir)
    reporter.update(0.03, "Initializing dataset QA workspace.")

    section_statuses = {}
    review_files = []
    artifacts = []

    reporter.update(0.10, "Loading dataset metadata and annotations.")
    samples, sample_meta_df, gt_df, config = load_dataset(dataset_path)
    if sample_meta_df.empty:
        reporter.update(0.92, "No dataset images found. Writing an empty QA report.")
        report = build_empty_dataset_report(
            dataset_path,
            args.scope,
            args.source_id,
            args.input_format,
        )
        report_path = output_dir / "report.json"
        reporter.update(0.97, "Writing dataset QA report.")
        report_path.write_text(json.dumps(json_safe(report), indent=2), encoding="utf-8")
        reporter.update(1.0, "Dataset QA report ready.")
        return 0

    if gt_df.empty:
        gt_df = pd.DataFrame(
            columns=[
                "sample_id",
                "filepath",
                "relative_filepath",
                "split",
                "label_id",
                "label",
                "class_id",
                "rel_x",
                "rel_y",
                "rel_w",
                "rel_h",
                "rel_area",
                "aspect_ratio",
                "center_x",
                "center_y",
                "img_width",
                "img_height",
            ]
        )

    gt_df["size_bucket"] = gt_df["rel_area"].map(bucket_area) if not gt_df.empty else pd.Series(dtype=str)
    gt_df, validation_flag_columns = add_bbox_validation_columns(gt_df)
    gt_df = add_custom_heuristics(gt_df)
    reporter.update(0.24, "Computing dataset statistics.")

    objects_per_image = gt_df.groupby("sample_id").size().reindex(sample_meta_df["sample_id"], fill_value=0)
    objects_per_class = gt_df["label"].value_counts().sort_values(ascending=False)
    images_per_class = gt_df.groupby("label")["sample_id"].nunique().sort_values(ascending=False)
    section_statuses["dataset_info"] = section_state("succeeded")
    section_statuses["class_distribution"] = section_state("succeeded")
    section_statuses["bbox_statistics"] = section_state("succeeded")
    section_statuses["suspicious_annotations"] = section_state("succeeded")
    section_statuses["split_quality"] = section_state("succeeded")
    section_statuses["image_difficulty"] = section_state("succeeded")

    overview_dict = {
        "dataset_name": dataset_path.name,
        "dataset_path": str(dataset_path),
        "generated_at": utc_now(),
        "scope": args.scope,
        "source_id": args.source_id,
        "input_format": args.input_format,
        "total_images": int(sample_meta_df.shape[0]),
        "annotated_images": int((objects_per_image > 0).sum()),
        "unannotated_images": int((objects_per_image == 0).sum()),
        "total_objects": int(len(gt_df)),
        "avg_objects_per_image": round(float(objects_per_image.mean()), 3),
        "median_objects_per_image": round(float(objects_per_image.median()), 3),
        "class_count": int(objects_per_class.shape[0]),
    }

    class_table = (
        objects_per_class.rename("objects")
        .to_frame()
        .join(images_per_class.rename("images"), how="outer")
        .fillna(0)
        .astype(int)
        .reset_index()
        .rename(columns={"index": "label"})
    )

    bbox_validation_summary = pd.DataFrame(
        [
            {
                "issue": column,
                "boxes": int(gt_df[column].sum()) if column in gt_df else 0,
                "share_of_boxes": round(float(gt_df[column].mean()), 4) if column in gt_df and not gt_df.empty else 0.0,
            }
            for column in validation_flag_columns
        ]
    ).sort_values(["boxes", "issue"], ascending=[False, True]).reset_index(drop=True)

    suspicious_annotations_df = (
        gt_df.loc[
            gt_df["any_bbox_issue"],
            ["sample_id", "filepath", "split", "label", "rel_x", "rel_y", "rel_w", "rel_h", "issue_count", "issue_types"],
        ]
        .sort_values(["issue_count", "filepath", "label"], ascending=[False, True, True])
        .reset_index(drop=True)
        if not gt_df.empty
        else pd.DataFrame(columns=["sample_id", "filepath", "split", "label", "rel_x", "rel_y", "rel_w", "rel_h", "issue_count", "issue_types"])
    )

    suspicious_sample_summary = (
        gt_df.groupby(["sample_id", "filepath", "split"], dropna=False)
        .agg(total_boxes=("label_id", "count"), flagged_boxes=("any_bbox_issue", "sum"), total_issue_flags=("issue_count", "sum"))
        .query("flagged_boxes > 0")
        .sort_values(["flagged_boxes", "total_issue_flags", "filepath"], ascending=[False, False, True])
        .reset_index()
        if not gt_df.empty
        else pd.DataFrame(columns=["sample_id", "filepath", "split", "total_boxes", "flagged_boxes", "total_issue_flags"])
    )

    split_class_table = pd.crosstab(gt_df["split"], gt_df["label"]).sort_index() if not gt_df.empty else pd.DataFrame()
    split_size_table = pd.crosstab(gt_df["split"], gt_df["size_bucket"]).sort_index() if not gt_df.empty else pd.DataFrame()
    missing_eval_classes = {
        split: sorted(set(objects_per_class.index) - set(gt_df.loc[gt_df["split"] == split, "label"]))
        for split in ("val", "test")
        if split in set(gt_df["split"])
    }

    custom_heuristics_summary = pd.DataFrame(
        [
            {"heuristic": "class_size_outlier", "boxes": int(gt_df["class_size_outlier"].sum()) if "class_size_outlier" in gt_df else 0},
            {"heuristic": "class_aspect_outlier", "boxes": int(gt_df["class_aspect_outlier"].sum()) if "class_aspect_outlier" in gt_df else 0},
            {"heuristic": "tight_or_loose_box", "boxes": int(gt_df["tight_or_loose_box"].sum()) if "tight_or_loose_box" in gt_df else 0},
        ]
    ).sort_values(["boxes", "heuristic"], ascending=[False, True]).reset_index(drop=True)

    overlap_issues_df = find_overlap_pairs(gt_df, iou_threshold=0.9)
    difficulty_df = build_image_difficulty(gt_df, sample_meta_df)
    difficult_paths = difficulty_df.head(min(50, len(difficulty_df)))["filepath"].tolist() if not difficulty_df.empty else []

    size_bucket_counts = gt_df["size_bucket"].value_counts().reindex(AREA_BUCKETS, fill_value=0) if not gt_df.empty else pd.Series([0, 0, 0, 0], index=AREA_BUCKETS)
    reporter.update(0.40, "Generating QA artifact charts.")
    artifacts.extend(
        export_core_artifacts(
            gt_df if not gt_df.empty else pd.DataFrame({"rel_area": [], "aspect_ratio": []}),
            objects_per_class if not objects_per_class.empty else pd.Series(dtype=int),
            objects_per_image,
            bbox_validation_summary,
            size_bucket_counts,
            difficulty_df if not difficulty_df.empty else pd.DataFrame({"difficulty_score": [0]}),
            artifacts_dir,
        )
    )

    reporter.update(0.54, "Starting duplicate and leakage analysis.")
    duplicates_report, leaks_df, exact_review_paths, near_review_paths, uniqueness_df = build_duplicates_section(
        samples,
        artifacts_dir,
        section_statuses,
        reporter,
    )
    if not uniqueness_df.empty:
        add_file_entry(artifacts, "Uniqueness Distribution", Path("artifacts/uniqueness_distribution.png"), "Distribution of low-vs-high uniqueness images.")
    reporter.update(0.76, "Starting consistency analysis.")
    consistency_report = build_consistency_section(samples, gt_df, artifacts_dir, section_statuses, reporter)
    if (artifacts_dir / "localization_iou_histogram.png").exists():
        add_file_entry(artifacts, "Localization IoU", Path("artifacts/localization_iou_histogram.png"), "Model-vs-GT localization IoU distribution.")

    reporter.update(0.92, "Building quality summary and review exports.")
    quality_report = build_quality_report(
        gt_df,
        sample_meta_df.shape[0],
        objects_per_class,
        leaks_df,
        exact_review_paths,
        near_review_paths,
        size_bucket_counts,
        overlap_issues_df,
        suspicious_sample_summary,
    )
    section_statuses["quality_score"] = section_state("succeeded")

    save_dataframe_csv(suspicious_annotations_df, reviews_dir / "suspicious_annotations.csv", review_files, "Suspicious Annotations", "Top suspicious annotation rows.")
    save_dataframe_csv(suspicious_sample_summary, reviews_dir / "suspicious_samples.csv", review_files, "Suspicious Samples", "Samples with the most validation flags.")
    save_dataframe_csv(overlap_issues_df, reviews_dir / "overlap_issues.csv", review_files, "Overlap Issues", "Same-class high-IoU overlaps.")
    save_dataframe_csv(difficulty_df, reviews_dir / "difficult_images.csv", review_files, "Difficult Images", "Images ranked by heuristic difficulty.")
    save_dataframe_csv(pd.DataFrame(duplicates_report.get("lowest_uniqueness", [])), reviews_dir / "lowest_uniqueness.csv", review_files, "Lowest Uniqueness", "Most redundant-looking images.")

    review_manifest = {
        "suspicious_samples": suspicious_sample_summary["filepath"].tolist() if not suspicious_sample_summary.empty else [],
        "overlap_issues": overlap_issues_df["filepath"].drop_duplicates().tolist() if not overlap_issues_df.empty else [],
        "difficult_images": difficult_paths,
        "exact_duplicates": exact_review_paths,
        "near_duplicates": near_review_paths,
        "split_leaks": leaks_df["filepath"].tolist() if not leaks_df.empty else [],
    }
    (reviews_dir / "review_manifest.json").write_text(json.dumps(json_safe(review_manifest), indent=2), encoding="utf-8")
    add_file_entry(review_files, "Review Manifest", Path("reviews/review_manifest.json"), "Grouped review queues for manual inspection.")

    report = {
        "dataset_info": overview_dict,
        "class_distribution": class_table.to_dict("records"),
        "bbox_statistics": {
            "size_buckets": size_bucket_counts.to_dict(),
            "bbox_validation": bbox_validation_summary.to_dict("records"),
            "custom_heuristics": custom_heuristics_summary.to_dict("records"),
        },
        "suspicious_annotations": suspicious_annotations_df.head(50).to_dict("records"),
        "duplicates": duplicates_report,
        "split_quality": {
            "class_by_split": split_class_table.to_dict() if not split_class_table.empty else {},
            "size_by_split": split_size_table.to_dict() if not split_size_table.empty else {},
            "missing_classes_in_eval": missing_eval_classes,
        },
        "image_difficulty": difficulty_df.head(50).to_dict("records"),
        "consistency": consistency_report,
        "review_exports": {
            "suspicious_samples": suspicious_sample_summary.head(100).to_dict("records"),
            "overlap_issues": overlap_issues_df.head(100).to_dict("records"),
            "difficult_images": difficulty_df.head(100).to_dict("records"),
        },
        "quality_score": quality_report,
        "section_statuses": section_statuses,
        "artifacts": artifacts,
        "review_exports_files": review_files,
    }

    report_path = output_dir / "report.json"
    reporter.update(0.97, "Writing dataset QA report.")
    report_path.write_text(json.dumps(json_safe(report), indent=2), encoding="utf-8")
    reporter.update(1.0, "Dataset QA report ready.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
