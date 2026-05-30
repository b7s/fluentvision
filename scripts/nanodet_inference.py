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


def parse_detections(dets, class_names, score_thres):
    detections = []
    if dets is None:
        return detections
    for class_id, class_dets in dets.items():
        if not isinstance(class_dets, list):
            continue
        for det in class_dets:
            score = det[-1]
            if score < score_thres:
                continue
            x1, y1, x2, y2 = det[:4]
            class_name = class_names[class_id] if class_id < len(class_names) else str(class_id)
            detections.append({
                "class": class_name,
                "confidence": float(score),
                "box": {
                    "x1": float(x1),
                    "y1": float(y1),
                    "x2": float(x2),
                    "y2": float(y2),
                },
            })
    return detections


def run_image_inference(args):
    import cv2
    import torch
    from nanodet.model.arch import build_model
    from nanodet.util import Logger, cfg, load_config, load_model_weight
    from nanodet.data.batch_process import stack_batch_img
    from nanodet.data.collate import naive_collate
    from nanodet.data.transform import Pipeline

    load_config(cfg, args.config)
    logger = Logger(-1, use_tensorboard=False)

    model = build_model(cfg.model)
    ckpt = torch.load(args.checkpoint, map_location=lambda storage, loc: storage)
    load_model_weight(model, ckpt, logger)

    if cfg.model.arch.backbone.name == "RepVGG":
        deploy_config = cfg.model
        deploy_config.arch.backbone.update({"deploy": True})
        deploy_model = build_model(deploy_config)
        from nanodet.model.backbone.repvgg import repvgg_det_model_convert
        model = repvgg_det_model_convert(model, deploy_model)

    model = model.to(args.device).eval()
    pipeline = Pipeline(cfg.data.val.pipeline, cfg.data.val.keep_ratio)

    start = time.time()
    img = cv2.imread(args.image)
    if img is None:
        return {"error": f"Cannot read image: {args.image}"}

    height, width = img.shape[:2]
    img_info = {"id": 0, "file_name": None, "height": height, "width": width}
    meta = dict(img_info=img_info, raw_img=img, img=img)
    meta = pipeline(None, meta, cfg.data.val.input_size)
    meta["img"] = torch.from_numpy(meta["img"].transpose(2, 0, 1)).to(args.device)
    meta = naive_collate([meta])
    meta["img"] = stack_batch_img(meta["img"], divisible=32)

    with torch.no_grad():
        results = model.inference(meta)

    inference_time = time.time() - start

    detections = parse_detections(results[0], cfg.class_names, args.conf)

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
        for det in detections:
            box = det["box"]
            x1, y1, x2, y2 = [int(v) for v in [box["x1"], box["y1"], box["x2"], box["y2"]]]
            cv2.rectangle(annotated, (x1, y1), (x2, y2), (0, 255, 0), 2)
            cv2.putText(
                annotated,
                f"{det['class']} {det['confidence']:.2f}",
                (x1, y1 - 10),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                (0, 255, 0),
                2,
            )

        annotated_path = str(Path(args.image).with_stem(Path(args.image).stem + "_annotated"))
        cv2.imwrite(annotated_path, annotated)
        output["annotated_path"] = annotated_path

    return output


def run_video_inference(args):
    import cv2
    import torch
    from nanodet.model.arch import build_model
    from nanodet.util import Logger, cfg, load_config, load_model_weight
    from nanodet.data.batch_process import stack_batch_img
    from nanodet.data.collate import naive_collate
    from nanodet.data.transform import Pipeline

    load_config(cfg, args.config)
    logger = Logger(-1, use_tensorboard=False)

    model = build_model(cfg.model)
    ckpt = torch.load(args.checkpoint, map_location=lambda storage, loc: storage)
    load_model_weight(model, ckpt, logger)

    if cfg.model.arch.backbone.name == "RepVGG":
        deploy_config = cfg.model
        deploy_config.arch.backbone.update({"deploy": True})
        deploy_model = build_model(deploy_config)
        from nanodet.model.backbone.repvgg import repvgg_det_model_convert
        model = repvgg_det_model_convert(model, deploy_model)

    model = model.to(args.device).eval()
    pipeline = Pipeline(cfg.data.val.pipeline, cfg.data.val.keep_ratio)

    start = time.time()
    cap = cv2.VideoCapture(args.video)
    if not cap.isOpened():
        return {"error": f"Cannot open video: {args.video}"}

    frames = []
    frame_idx = 0

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            break

        if frame_idx % args.vid_stride != 0:
            frame_idx += 1
            continue

        height, width = frame.shape[:2]
        img_info = {"id": 0, "file_name": f"frame_{frame_idx}", "height": height, "width": width}
        meta = dict(img_info=img_info, raw_img=frame, img=frame)
        meta = pipeline(None, meta, cfg.data.val.input_size)
        meta["img"] = torch.from_numpy(meta["img"].transpose(2, 0, 1)).to(args.device)
        meta = naive_collate([meta])
        meta["img"] = stack_batch_img(meta["img"], divisible=32)

        with torch.no_grad():
            results = model.inference(meta)

        detections = parse_detections(results[0], cfg.class_names, args.conf)
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

    real_stdout = sys.stdout
    sys.stdout = sys.stderr

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

    sys.stdout = real_stdout
    print(json.dumps(output))

    if "error" in output:
        sys.exit(1)


if __name__ == "__main__":
    main()
