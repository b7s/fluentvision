#!/usr/bin/env python3
"""Ultralytics Solutions script for FluentVision.

Runs one of the Ultralytics built-in solutions (counting, heatmap, speed, etc.)
on a video or image source and returns structured JSON results.
"""

import argparse
import json
import sys
import time

import cv2


SOLUTION_MAP = {
    "count": "ObjectCounter",
    "crop": "ObjectCropper",
    "blur": "ObjectBlurrer",
    "workout": "AIGym",
    "heatmap": "Heatmap",
    "isegment": "InstanceSegmentation",
    "visioneye": "VisionEye",
    "speed": "SpeedEstimator",
    "queue": "QueueManager",
    "analytics": "Analytics",
    "trackzone": "TrackZone",
    "distance": "DistanceCalculation",
}


def parse_args():
    parser = argparse.ArgumentParser(description="Ultralytics Solutions runner")
    parser.add_argument("--solution", type=str, required=True, choices=list(SOLUTION_MAP.keys()), help="Solution name")
    parser.add_argument("--source", type=str, required=True, help="Path to image or video file, or stream URL")
    parser.add_argument("--model", type=str, default="yolo26s.pt", help="Model filename")
    parser.add_argument("--device", type=str, default="cpu", help="Device (cpu or 0)")
    parser.add_argument("--conf", type=float, default=0.25, help="Confidence threshold")
    parser.add_argument("--iou", type=float, default=0.7, help="IoU threshold")
    parser.add_argument("--imgsz", type=int, default=640, help="Image size")
    parser.add_argument("--classes", type=str, default=None, help="Comma-separated class IDs")
    parser.add_argument("--save", action="store_true", help="Save output video")
    parser.add_argument("--save-path", type=str, default=None, help="Directory to save output")
    parser.add_argument("--max-frames", type=int, default=0, help="Max frames to process (0 = all)")
    parser.add_argument("--region", type=str, default=None, help="Region points as JSON array e.g. '[[20,400],[1080,400]]'")
    parser.add_argument("--colormap", type=int, default=None, help="OpenCV colormap constant for heatmap")
    parser.add_argument("--blur-ratio", type=float, default=None, help="Blur ratio 0.1-1.0 for ObjectBlurrer")
    parser.add_argument("--crop-dir", type=str, default=None, help="Directory for cropped objects")
    parser.add_argument("--vision-point", type=str, default=None, help="Vision point as JSON array e.g. '[20,20]'")
    parser.add_argument("--kpts", type=str, default=None, help="Keypoint indices for workout e.g. '6,8,10'")
    parser.add_argument("--up-angle", type=float, default=None, help="Up angle for workout")
    parser.add_argument("--down-angle", type=float, default=None, help="Down angle for workout")
    parser.add_argument("--fps", type=float, default=None, help="FPS for speed estimation")
    parser.add_argument("--max-hist", type=int, default=None, help="Max history points for speed estimation")
    parser.add_argument("--meter-per-pixel", type=float, default=None, help="Meter per pixel for speed estimation")
    parser.add_argument("--max-speed", type=int, default=None, help="Max speed for speed estimation")
    parser.add_argument("--analytics-type", type=str, default=None, help="Analytics chart type: line, bar, pie, area")
    parser.add_argument("--json-file", type=str, default=None, help="JSON file path for parking management")
    parser.add_argument("--records", type=int, default=None, help="Detection count threshold for security alarm")
    parser.add_argument("--tracker", type=str, default=None, help="Tracker config e.g. botsort.yaml")
    return parser.parse_args()


def parse_region(region_str):
    import json as _json
    if region_str is None:
        return None
    points = _json.loads(region_str)
    return [tuple(p) for p in points]


def parse_vision_point(vp_str):
    import json as _json
    if vp_str is None:
        return None
    vp = _json.loads(vp_str)
    return tuple(vp)


def parse_kpts(kpts_str):
    if kpts_str is None:
        return None
    return [int(k) for k in kpts_str.split(",")]


def build_solution_kwargs(args):
    kwargs = {
        "model": args.model,
        "conf": args.conf,
        "iou": args.iou,
        "imgsz": args.imgsz,
        "device": args.device,
    }

    if args.classes is not None:
        kwargs["classes"] = [int(c) for c in args.classes.split(",")]

    if args.tracker is not None:
        kwargs["tracker"] = args.tracker

    region = parse_region(args.region)
    if region is not None:
        kwargs["region"] = region

    if args.colormap is not None:
        kwargs["colormap"] = args.colormap

    if args.blur_ratio is not None:
        kwargs["blur_ratio"] = args.blur_ratio

    if args.crop_dir is not None:
        kwargs["crop_dir"] = args.crop_dir

    vp = parse_vision_point(args.vision_point)
    if vp is not None:
        kwargs["vision_point"] = vp

    kpts = parse_kpts(args.kpts)
    if kpts is not None:
        kwargs["kpts"] = kpts

    if args.up_angle is not None:
        kwargs["up_angle"] = args.up_angle

    if args.down_angle is not None:
        kwargs["down_angle"] = args.down_angle

    if args.fps is not None:
        kwargs["fps"] = args.fps

    if args.max_hist is not None:
        kwargs["max_hist"] = args.max_hist

    if args.meter_per_pixel is not None:
        kwargs["meter_per_pixel"] = args.meter_per_pixel

    if args.max_speed is not None:
        kwargs["max_speed"] = args.max_speed

    if args.analytics_type is not None:
        kwargs["analytics_type"] = args.analytics_type

    if args.json_file is not None:
        kwargs["json_file"] = args.json_file

    if args.records is not None:
        kwargs["records"] = args.records

    return kwargs


def extract_results(solution_obj, results, solution_name):
    data = {
        "solution": solution_name,
        "model": solution_obj.CFG.get("model", ""),
        "provider": "ultralytics",
    }

    if hasattr(results, "total_tracks"):
        data["total_tracks"] = results.total_tracks

    if hasattr(results, "in_count"):
        data["in_count"] = results.in_count

    if hasattr(results, "out_count"):
        data["out_count"] = results.out_count

    if hasattr(results, "classwise_count"):
        cc = results.classwise_count
        if isinstance(cc, dict):
            data["classwise_count"] = {str(k): v for k, v in cc.items()}

    if hasattr(results, "queue_count"):
        data["queue_count"] = results.queue_count

    if hasattr(results, "total_crop_objects"):
        data["total_crop_objects"] = results.total_crop_objects

    if hasattr(results, "pixels_distance"):
        data["pixels_distance"] = results.pixels_distance

    if hasattr(results, "workout_count"):
        data["workout_count"] = results.workout_count

    if hasattr(results, "workout_angle"):
        data["workout_angle"] = results.workout_angle

    if hasattr(results, "workout_stage"):
        data["workout_stage"] = results.workout_stage

    if hasattr(results, "filled_slots"):
        data["filled_slots"] = results.filled_slots

    if hasattr(results, "available_slots"):
        data["available_slots"] = results.available_slots

    if hasattr(results, "speed_dict"):
        sd = results.speed_dict
        if isinstance(sd, dict):
            data["speed_dict"] = {str(k): v for k, v in sd.items()}

    if hasattr(results, "email_sent"):
        data["email_sent"] = results.email_sent

    if hasattr(results, "region_counts"):
        rc = results.region_counts
        if isinstance(rc, dict):
            data["region_counts"] = {str(k): v for k, v in rc.items()}

    return data


def is_video_source(source):
    video_exts = (".mp4", ".avi", ".mov", ".mkv", ".wmv", ".flv", ".webm", ".m4v")
    if source.lower().startswith(("rtsp://", "rtmp://", "http://", "https://", "tcp://")):
        return True
    return source.lower().endswith(video_exts)


def main():
    args = parse_args()

    real_stdout = sys.stdout
    sys.stdout = sys.stderr

    try:
        from ultralytics import solutions
    except ImportError:
        sys.stdout = real_stdout
        print(json.dumps({"error": "ultralytics package not installed. Run: pip install ultralytics"}))
        sys.exit(1)

    solution_class_name = SOLUTION_MAP[args.solution]
    solution_class = getattr(solutions, solution_class_name, None)

    if solution_class is None:
        sys.stdout = real_stdout
        print(json.dumps({"error": f"Solution class '{solution_class_name}' not found in ultralytics.solutions"}))
        sys.exit(1)

    kwargs = build_solution_kwargs(args)

    try:
        solution_obj = solution_class(**kwargs)
    except Exception as e:
        sys.stdout = real_stdout
        print(json.dumps({"error": f"Failed to initialize solution: {str(e)}"}))
        sys.exit(1)

    source = args.source
    is_video = is_video_source(source)

    start = time.time()
    frame_count = 0

    if not is_video:
        im0 = cv2.imread(source)
        if im0 is None:
            sys.stdout = real_stdout
            print(json.dumps({"error": f"Cannot read image: {source}"}))
            sys.exit(1)

        results = solution_obj(im0)
        elapsed = time.time() - start
        data = extract_results(solution_obj, results, args.solution)
        data["source"] = source
        data["inference_time"] = round(elapsed, 4)
        data["frame_count"] = 1

        if args.save and args.save_path:
            import os
            os.makedirs(args.save_path, exist_ok=True)
            out_path = os.path.join(args.save_path, os.path.basename(source))
            cv2.imwrite(out_path, results.plot_im)
            data["annotated_path"] = out_path

        sys.stdout = real_stdout
        print(json.dumps(data))
        return

    cap = cv2.VideoCapture(source)
    if not cap.isOpened():
        sys.stdout = real_stdout
        print(json.dumps({"error": f"Cannot open video: {source}"}))
        sys.exit(1)

    w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    fps = cap.get(cv2.CAP_PROP_FPS) or 30.0

    video_writer = None
    out_path = None
    if args.save:
        import os
        save_dir = args.save_path or "."
        os.makedirs(save_dir, exist_ok=True)
        out_name = os.path.splitext(os.path.basename(source))[0] + "_solution.avi"
        out_path = os.path.join(save_dir, out_name)
        fourcc = cv2.VideoWriter_fourcc(*"mp4v")
        video_writer = cv2.VideoWriter(out_path, fourcc, fps, (w, h))

    per_frame_data = []

    try:
        while cap.isOpened():
            success, frame = cap.read()
            if not success:
                break

            frame_count += 1

            if args.solution == "analytics":
                results = solution_obj(frame, frame_count)
            else:
                results = solution_obj(frame)

            frame_data = extract_results(solution_obj, results, args.solution)
            per_frame_data.append(frame_data)

            if video_writer is not None and hasattr(results, "plot_im"):
                video_writer.write(results.plot_im)

            if args.max_frames > 0 and frame_count >= args.max_frames:
                break
    finally:
        cap.release()
        if video_writer is not None:
            video_writer.release()

    elapsed = time.time() - start

    data = {
        "solution": args.solution,
        "source": source,
        "model": solution_obj.CFG.get("model", ""),
        "provider": "ultralytics",
        "frame_count": frame_count,
        "total_time": round(elapsed, 4),
    }

    if per_frame_data:
        last = per_frame_data[-1]
        for key in ("total_tracks", "in_count", "out_count", "classwise_count",
                     "queue_count", "total_crop_objects", "pixels_distance",
                     "workout_count", "workout_angle", "workout_stage",
                     "filled_slots", "available_slots", "speed_dict",
                     "email_sent", "region_counts"):
            if key in last:
                data[key] = last[key]

    data["frames"] = per_frame_data

    if video_writer is not None:
        data["annotated_path"] = out_path

    sys.stdout = real_stdout
    print(json.dumps(data))


if __name__ == "__main__":
    main()
