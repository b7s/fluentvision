#!/usr/bin/env python3
"""Ultralytics YOLO26 inference script for FluentVision."""

import argparse
import json
import sys
import time
from pathlib import Path


def parse_args():
    parser = argparse.ArgumentParser(description="Ultralytics YOLO inference")
    parser.add_argument("--image", type=str, help="Path to input image")
    parser.add_argument("--video", type=str, help="Path to input video")
    parser.add_argument("--model", type=str, default="yolo26s.pt", help="Model filename")
    parser.add_argument("--task", type=str, default="detect", help="Task type")
    parser.add_argument("--device", type=str, default="cpu", help="Device (cpu or 0)")
    parser.add_argument("--conf", type=float, default=0.25, help="Confidence threshold")
    parser.add_argument("--iou", type=float, default=0.7, help="IoU threshold")
    parser.add_argument("--imgsz", type=int, default=640, help="Image size")
    parser.add_argument("--max-det", type=int, default=300, help="Max detections")
    parser.add_argument("--classes", type=str, default=None, help="Comma-separated class IDs")
    parser.add_argument("--augment", action="store_true", help="Test-time augmentation")
    parser.add_argument("--agnostic-nms", action="store_true", help="Class-agnostic NMS")
    parser.add_argument("--half", action="store_true", help="Half precision (FP16)")
    parser.add_argument("--end2end", action="store_true", help="End-to-end inference")
    parser.add_argument("--vid-stride", type=int, default=1, help="Video frame stride")
    parser.add_argument("--save", action="store_true", help="Save annotated image/video")
    return parser.parse_args()


def run_image_inference(model, args):
    start = time.time()
    predict_kwargs = {
        "source": args.image,
        "conf": args.conf,
        "iou": args.iou,
        "imgsz": args.imgsz,
        "device": args.device,
        "max_det": args.max_det,
        "augment": args.augment,
        "agnostic_nms": args.agnostic_nms,
        "half": args.half,
        "end2end": args.end2end,
        "save": args.save,
        "verbose": False,
    }
    if args.classes is not None:
        predict_kwargs["classes"] = [int(c) for c in args.classes.split(",")]

    results = model.predict(**predict_kwargs)
    inference_time = time.time() - start

    detections = []
    result = results[0]
    for i, box in enumerate(result.boxes):
        xyxy = box.xyxy[0].tolist()
        detections.append({
            "class": result.names[int(box.cls[0])],
            "confidence": float(box.conf[0]),
            "box": {
                "x1": xyxy[0],
                "y1": xyxy[1],
                "x2": xyxy[2],
                "y2": xyxy[3],
            },
        })

    output = {
        "image_path": str(args.image),
        "provider": "ultralytics",
        "model": args.model,
        "inference_time": round(inference_time, 4),
        "detection_count": len(detections),
        "detections": detections,
    }

    if args.save and result.save_dir:
        save_dir = Path(result.save_dir)
        saved_files = list(save_dir.glob("*")) if save_dir.exists() else []
        if saved_files:
            output["annotated_path"] = str(saved_files[-1])

    return output


def run_video_inference(model, args):
    start = time.time()
    predict_kwargs = {
        "source": args.video,
        "conf": args.conf,
        "iou": args.iou,
        "imgsz": args.imgsz,
        "device": args.device,
        "vid_stride": args.vid_stride,
        "save": args.save,
        "verbose": False,
        "stream": True,
    }
    if args.classes is not None:
        predict_kwargs["classes"] = [int(c) for c in args.classes.split(",")]

    frames = []
    for result in model.predict(**predict_kwargs):
        detections = []
        for box in result.boxes:
            xyxy = box.xyxy[0].tolist()
            detections.append({
                "class": result.names[int(box.cls[0])],
                "confidence": float(box.conf[0]),
                "box": {
                    "x1": xyxy[0],
                    "y1": xyxy[1],
                    "x2": xyxy[2],
                    "y2": xyxy[3],
                },
            })
        frames.append({
            "image_path": str(result.path) if result.path else "",
            "detections": detections,
            "inference_time": 0,
        })

    total_time = time.time() - start

    return {
        "video_path": str(args.video),
        "provider": "ultralytics",
        "model": args.model,
        "total_inference_time": round(total_time, 4),
        "frames": frames,
    }


def main():
    args = parse_args()

    try:
        from ultralytics import YOLO
    except ImportError:
        print(json.dumps({"error": "ultralytics package not installed. Run: pip install ultralytics"}))
        sys.exit(1)

    model = YOLO(args.model)

    if args.image:
        output = run_image_inference(model, args)
    elif args.video:
        output = run_video_inference(model, args)
    else:
        print(json.dumps({"error": "Either --image or --video is required"}))
        sys.exit(1)

    print(json.dumps(output))


if __name__ == "__main__":
    main()
