#!/usr/bin/env python3
"""Ultralytics YOLO26/YOLOE inference script for FluentVision."""

import argparse
import base64
import json
import sys
import threading
import time
from http.server import HTTPServer, BaseHTTPRequestHandler
from pathlib import Path

import cv2


def parse_args():
    parser = argparse.ArgumentParser(description="Ultralytics YOLO inference")
    parser.add_argument("--image", type=str, help="Path to input image")
    parser.add_argument("--video", type=str, help="Path to input video")
    parser.add_argument("--stream", type=str, help="Stream source (rtsp://, rtmp://, tcp://, webcam index)")
    parser.add_argument("--max-frames", type=int, default=0, help="Max frames for streaming (0 = unlimited)")
    parser.add_argument("--annotate", action="store_true", help="Enable annotation (required for MJPEG server or base64 output)")
    parser.add_argument("--annotate-frames", action="store_true", help="Include base64-encoded annotated frame in stream NDJSON output")
    parser.add_argument("--mjpeg-port", type=int, default=0, help="Start MJPEG HTTP server on this port (0 = disabled)")
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
    parser.add_argument("--save-path", type=str, default=None, help="Directory to save annotated output")
    parser.add_argument("--prompts", type=str, default=None, help="Comma-separated text prompts for YOLOE open-vocabulary detection")
    return parser.parse_args()


def encode_frame_b64(annotated_img):
    _, buf = cv2.imencode(".jpg", annotated_img, [cv2.IMWRITE_JPEG_QUALITY, 80])
    return base64.b64encode(buf.tobytes()).decode("utf-8")


class MjpegHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path not in ("/", "/stream", "/mjpeg"):
            self.send_response(404)
            self.end_headers()
            return

        self.send_response(200)
        self.send_header("Content-Type", "multipart/x-mixed-replace; boundary=frame")
        self.send_header("Cache-Control", "no-cache, no-store, must-revalidate")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()

        while True:
            with mjpeg_lock:
                if mjpeg_frame is None:
                    time.sleep(0.01)
                    continue
                frame_bytes = mjpeg_frame

            try:
                self.wfile.write(b"--frame\r\n")
                self.wfile.write(b"Content-Type: image/jpeg\r\n")
                self.wfile.write(f"Content-Length: {len(frame_bytes)}\r\n\r\n".encode())
                self.wfile.write(frame_bytes)
                self.wfile.write(b"\r\n")
                self.wfile.flush()
            except BrokenPipeError:
                break
            except Exception:
                break

            time.sleep(0.01)

    def log_message(self, format, *args):
        pass


mjpeg_frame = None
mjpeg_lock = threading.Lock()


def start_mjpeg_server(port):
    server = HTTPServer(("0.0.0.0", port), MjpegHandler)
    server.daemon_threads = True
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()
    return server


def update_mjpeg_frame(annotated_img):
    global mjpeg_frame
    _, buf = cv2.imencode(".jpg", annotated_img, [cv2.IMWRITE_JPEG_QUALITY, 80])
    with mjpeg_lock:
        mjpeg_frame = buf.tobytes()


def is_yoloe_model(model):
    return type(model).__name__ == "YOLOE"


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
        "half": args.half,
        "save": args.save,
        "verbose": False,
    }

    if args.save_path and args.save:
        predict_kwargs["project"] = args.save_path
        predict_kwargs["name"] = ""

    if not is_yoloe_model(model):
        predict_kwargs["agnostic_nms"] = args.agnostic_nms
        predict_kwargs["end2end"] = args.end2end

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
            output["annotated_path"] = str(saved_files[-1].resolve())

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

    if args.save_path and args.save:
        predict_kwargs["project"] = args.save_path
        predict_kwargs["name"] = ""
    if args.classes is not None:
        predict_kwargs["classes"] = [int(c) for c in args.classes.split(",")]

    total_detections = 0
    frames = []
    last_result = None
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
        total_detections += len(detections)
        frames.append({
            "image_path": str(result.path) if result.path else "",
            "detections": detections,
            "inference_time": 0,
        })
        last_result = result

    total_time = time.time() - start

    output = {
        "video_path": str(args.video),
        "provider": "ultralytics",
        "model": args.model,
        "total_inference_time": round(total_time, 4),
        "detection_count": total_detections,
        "frames": frames,
    }

    if args.save and last_result is not None and last_result.save_dir:
        save_dir = Path(last_result.save_dir)
        saved_files = list(save_dir.glob("*")) if save_dir.exists() else []
        if saved_files:
            output["annotated_path"] = str(saved_files[-1].resolve())

    return output


def run_stream_inference(model, args, real_stdout):
    mjpeg_server = None
    if args.mjpeg_port > 0:
        mjpeg_server = start_mjpeg_server(args.mjpeg_port)

    predict_kwargs = {
        "source": args.stream,
        "conf": args.conf,
        "iou": args.iou,
        "imgsz": args.imgsz,
        "device": args.device,
        "max_det": args.max_det,
        "augment": args.augment,
        "half": args.half,
        "stream": True,
        "verbose": False,
    }

    if not is_yoloe_model(model):
        predict_kwargs["agnostic_nms"] = args.agnostic_nms
        predict_kwargs["end2end"] = args.end2end

    if args.classes is not None:
        predict_kwargs["classes"] = [int(c) for c in args.classes.split(",")]

    frame_count = 0
    total_detections = 0
    start = time.time()

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

        frame_count += 1
        total_detections += len(detections)

        annotated_b64 = None
        needs_render = args.annotate_frames or args.mjpeg_port > 0
        if needs_render:
            annotated_img = result.plot()
            if args.annotate_frames:
                annotated_b64 = encode_frame_b64(annotated_img)
            if args.mjpeg_port > 0:
                update_mjpeg_frame(annotated_img)

        frame_output = {
            "frame": frame_count,
            "source": str(args.stream),
            "provider": "ultralytics",
            "model": args.model,
            "detection_count": len(detections),
            "detections": detections,
            "image_path": str(result.path) if result.path else "",
            "inference_time": 0,
            "type": "frame",
        }

        if annotated_b64 is not None:
            frame_output["annotated_frame"] = annotated_b64

        if args.mjpeg_port > 0 and frame_count == 1:
            frame_output["stream_url"] = f"http://localhost:{args.mjpeg_port}/stream"

        sys.stdout = real_stdout
        print(json.dumps(frame_output), flush=True)
        sys.stdout = sys.stderr

        if args.max_frames > 0 and frame_count >= args.max_frames:
            break

    total_time = time.time() - start

    summary_output = {
        "source": str(args.stream),
        "provider": "ultralytics",
        "model": args.model,
        "frame_count": frame_count,
        "total_detections": total_detections,
        "total_time": round(total_time, 4),
        "stopped": args.max_frames > 0,
        "type": "summary",
    }

    if mjpeg_server is not None:
        mjpeg_server.shutdown()

    return summary_output


def main():
    args = parse_args()

    real_stdout = sys.stdout
    sys.stdout = sys.stderr

    try:
        from ultralytics import YOLO
    except ImportError:
        sys.stdout = real_stdout
        print(json.dumps({"error": "ultralytics package not installed. Run: pip install ultralytics"}))
        sys.exit(1)

    model = YOLO(args.model)

    if args.prompts is not None and hasattr(model, "set_classes"):
        prompts = [p.strip() for p in args.prompts.split(",") if p.strip()]
        if prompts:
            try:
                model.set_classes(prompts)
            except AssertionError:
                pass

    if args.image:
        output = run_image_inference(model, args)
    elif args.video:
        output = run_video_inference(model, args)
    elif args.stream:
        output = run_stream_inference(model, args, real_stdout)
    else:
        sys.stdout = real_stdout
        print(json.dumps({"error": "Either --image, --video, or --stream is required"}))
        sys.exit(1)

    if args.stream:
        sys.stdout = real_stdout
        print(json.dumps(output))
    else:
        sys.stdout = real_stdout
        print(json.dumps(output))


if __name__ == "__main__":
    main()
