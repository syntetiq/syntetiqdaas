import base64
import hashlib
import io
import os
import threading
from typing import Optional

import numpy as np
import torch
from fastapi import FastAPI, HTTPException
from PIL import Image
from pydantic import BaseModel, Field
from sam2.build_sam import build_sam2
from sam2.sam2_image_predictor import SAM2ImagePredictor


class PointPayload(BaseModel):
    x: float = Field(ge=0, le=100)
    y: float = Field(ge=0, le=100)


class SegmentClickRequest(BaseModel):
    image_id: Optional[str] = None
    image_base64: str
    image_mime_type: Optional[str] = None
    point: PointPayload


class BoundingBox(BaseModel):
    x: float
    y: float
    width: float
    height: float


class SegmentClickResponse(BaseModel):
    bbox: BoundingBox
    score: float


class Sam2Segmenter:
    def __init__(self) -> None:
        self._predictor: Optional[SAM2ImagePredictor] = None
        self._lock = threading.Lock()
        self._cached_image_key: Optional[str] = None
        self._bbox_padding_px = max(0, int(os.getenv("SAM2_BBOX_PADDING_PX", "2")))
        self._device = os.getenv("SAM2_DEVICE", "cuda" if torch.cuda.is_available() else "cpu")
        self._model_cfg = os.getenv("SAM2_MODEL_CFG", "configs/sam2.1/sam2.1_hiera_s.yaml")
        self._checkpoint = os.getenv("SAM2_CHECKPOINT", "/models/checkpoints/sam2.1_hiera_small.pt")

    def _ensure_predictor(self) -> SAM2ImagePredictor:
        if self._predictor is not None:
            return self._predictor

        if not os.path.exists(self._checkpoint):
            raise RuntimeError(
                f"SAM2 checkpoint not found at '{self._checkpoint}'. "
                "Set SAM2_CHECKPOINT to a valid model file inside the container."
            )

        model = build_sam2(self._model_cfg, self._checkpoint, device=self._device)
        self._predictor = SAM2ImagePredictor(model)
        return self._predictor

    def segment_click(self, payload: SegmentClickRequest) -> SegmentClickResponse:
        try:
            image_bytes = base64.b64decode(payload.image_base64)
        except Exception as exc:  # noqa: BLE001
            raise RuntimeError("Request image_base64 could not be decoded.") from exc

        try:
            pil_image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
        except Exception as exc:  # noqa: BLE001
            raise RuntimeError("Decoded image content is not a supported image.") from exc

        image_np = np.array(pil_image)
        image_height, image_width = image_np.shape[:2]
        if image_width == 0 or image_height == 0:
            raise RuntimeError("Decoded image has invalid dimensions.")

        point_x = (payload.point.x / 100.0) * image_width
        point_y = (payload.point.y / 100.0) * image_height
        image_hash = hashlib.sha1(image_bytes).hexdigest()
        image_key = f"{payload.image_id or 'anonymous'}:{image_hash}"

        with self._lock:
            predictor = self._ensure_predictor()

            if self._cached_image_key != image_key:
                predictor.set_image(image_np)
                self._cached_image_key = image_key

            masks, scores, _ = predictor.predict(
                point_coords=np.array([[point_x, point_y]], dtype=np.float32),
                point_labels=np.array([1], dtype=np.int32),
                multimask_output=True,
            )

        if masks is None or len(masks) == 0:
            raise RuntimeError("SAM2 did not return any masks for the clicked point.")

        best_index = int(np.argmax(scores))
        best_mask = masks[best_index]
        best_score = float(scores[best_index])
        mask_indices = np.argwhere(best_mask > 0)
        if mask_indices.size == 0:
            raise RuntimeError("SAM2 returned an empty mask for the clicked point.")

        min_y, min_x = mask_indices.min(axis=0)
        max_y, max_x = mask_indices.max(axis=0)
        padding = self._bbox_padding_px

        min_x = max(0, int(min_x) - padding)
        min_y = max(0, int(min_y) - padding)
        max_x = min(image_width - 1, int(max_x) + padding)
        max_y = min(image_height - 1, int(max_y) + padding)

        bbox = BoundingBox(
            x=(float(min_x) / image_width) * 100.0,
            y=(float(min_y) / image_height) * 100.0,
            width=((float(max_x) - float(min_x) + 1.0) / image_width) * 100.0,
            height=((float(max_y) - float(min_y) + 1.0) / image_height) * 100.0,
        )

        return SegmentClickResponse(bbox=bbox, score=best_score)


app = FastAPI(title="SyntetiQ SAM2 API")
segmenter = Sam2Segmenter()


@app.get("/health")
def healthcheck() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/segment/click", response_model=SegmentClickResponse)
def segment_click(payload: SegmentClickRequest) -> SegmentClickResponse:
    try:
        return segmenter.segment_click(payload)
    except RuntimeError as exc:
        raise HTTPException(status_code=503, detail=str(exc)) from exc
