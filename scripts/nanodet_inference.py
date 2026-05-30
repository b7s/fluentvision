#!/usr/bin/env python3
"""NanoDet-Plus inference script for FluentVision."""

import argparse
import json
import sys
import time
from pathlib import Path


def parse_args():
    parser = argparse.ArgumentParser(description="NanoDet inference")
    parser.add_argument("--image", type=str, help="Path to input image")
    parser.add_argument("--video", type=str, help="Path to input video")
    parser.add_argument("--model", type=str, default="nanodet-plus-m-416", help="Model name")
    parser.add_argument("--config", type=str, required=True, help="Path to config YAML")
    parser.add_argument("--checkpoint", type=str, required=True, help="Path to checkpoint file")
    parser.add_argument("--device", type=str, default="cpu", help="Device (cpu or cuda:0)")
    parser.add_argument("--conf", type=float, default=0.25, help="Confidence threshold")
    parser.add_argument("--imgsz", type=int, default=416, help="Input image size")
    parser.add_argument("--nanodet-path", type=str, default="", help="Path to nanodet repo")
    parser.add_argument("--vid-stride", type=int, default=1, help="Video frame stride")
    parser.add_argument("--save", action="store_true", help="Save annotated image/video")
    return parser.parse_args()


def setup_nanodet(nanodet_path):
    if nanodet_path and nanodet_path not in sys.path:
        sys.path.insert(0, nanodet_path)


def run_image_inference(args):
    from nanodet.demo.demo import Predictor
    import cv2
    import torch
    import yaml

    with open(args.config, "r") as f:
        cfg = yaml.safe_load(f)

    predictor = Predictor(cfg, args.checkpoint, args.device, args.conf)

    start = time.time()
    img = cv2.imread(args.image)
    if img is None:
        return {"error": f"Cannot read image: {args.image}"}

    result = predictor.run(img)
    inference_time = time.time() - start

    detections = []
    for det in result:
        detections.append({
            "class": det.get("class_name", str(det.get("class_id", ""))),
            "confidence": float(det.get("score", 0)),
            "box": {
                "x1": float(det.get("bbox", [0, 0, 0, 0])[0]),
                "y1": float(det.get("bbox", [0, 0, 0, 0])[1]),
                "x2": float(det.get("bbox", [0, 0, 0, 0])[2]),
                "y2": float(det.get("bbox", [0, 0, 0, 0])[3]),
            },
        })

    output = {
        "image_path": str(args.image),
        "provider": "nanodet",
        "model": args.model,
        "inference_time": round(inference_time, 4),
        "detection_count": len(detections),
        "detections": detections,
    }

    if args.save:
        annotated = img.copy()
        for det in result:
            bbox = det.get("bbox", [0, 0, 0, 0])
            score = det.get("score", 0)
            class_name = det.get("class_name", "")
            x1, y1, x2, y2 = [int(v) for v in bbox]
            cv2.rectangle(annotated, (x1, y1), (x2, y2), (0, 255, 0), 2)
            cv2.putText(annotated, f"{class_name} {score:.2f}", (x1, y1 - 10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

        annotated_path = str(Path(args.image).with_stem(Path(args.image).stem + "_annotated"))
        cv2.imwrite(annotated_path, annotated)
        output["annotated_path"] = annotated_path

    return output


def run_video_inference(args):
    import cv2

    start = time.time()
    cap = cv2.VideoCapture(args.video)
    if not cap.isOpened():
        return {"error": f"Cannot open video: {args.video}"}

    from nanodet.demo.demo import Predictor
    import yaml

    with open(args.config, "r") as f:
        cfg = yaml.safe_load(f)

    predictor = Predictor(cfg, args.checkpoint, args.device, args.conf)

    frames = []
    frame_idx = 0

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            break

        if frame_idx % args.vid_stride != 0:
            frame_idx += 1
            continue

        result = predictor.run(frame)
        detections = []
        for det in result:
            detections.append({
                "class": det.get("class_name", str(det.get("class_id", ""))),
                "confidence": float(det.get("score", 0)),
                "box": {
                    "x1": float(det.get("bbox", [0, 0, 0, 0])[0]),
                    "y1": float(det.get("bbox", [0, 0, 0, 0])[1]),
                    "x2": float(det.get("bbox", [0, 0, 0, 0])[2]),
                    "y2": float(det.get("bbox", [0, 0, 0, 0])[3]),
                },
            })

        frames.append({
            "image_path": f"frame_{frame_idx}",
            "detections": detections,
            "inference_time": 0,
        })

        frame_idx += 1

    cap.release()
    total_time = time.time() - start

    return {
        "video_path": str(args.video),
        "provider": "nanodet",
        "model": args.model,
        "total_inference_time": round(total_time, 4),
        "frames": frames,
    }


def main():
    args = parse_args()

    setup_nanodet(args.nanodet_path)

    try:
        if args.image:
            output = run_image_inference(args)
        elif args.video:
            output = run_video_inference(args)
        else:
            output = {"error": "Either --image or --video is required"}
    except Exception as e:
        output = {"error": str(e)}

    if "error" in output:
        print(json.dumps(output))
        sys.exit(1)

    print(json.dumps(output))


if __name__ == "__main__":
    main()
