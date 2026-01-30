import cv2
import numpy as np
import logging
import hashlib
import time
import gc
from typing import Tuple, Optional
from collections import defaultdict
from pathlib import Path
import urllib.request
import urllib.error
try:
    import onnxruntime as ort
    ONNXRUNTIME_AVAILABLE = True
except ImportError:
    ONNXRUNTIME_AVAILABLE = False
logger = logging.getLogger(__name__)
cv2.setNumThreads(1)
cv2.ocl.setUseOpenCL(False)
_photo_hashes = defaultdict(list)
_last_cleanup = time.time()
_yolo_session = None
_yolo_error: Optional[str] = None
_yolo_onnx_path: Optional[Path] = None
def _malloc_trim() -> None:
    try:
        import ctypes
        libc = ctypes.CDLL("libc.so.6")
        if hasattr(libc, "malloc_trim"):
            libc.malloc_trim(0)
    except Exception:
        pass
def _unload_yolo_session() -> None:
    global _yolo_session, _yolo_error
    if _yolo_session is not None:
        try:
            del _yolo_session
        except Exception:
            pass
        _yolo_session = None
        _yolo_error = None
        gc.collect()
        gc.collect()
        _malloc_trim()
        logger.info("[YOLO] Сессия выгружена, память освобождена")
def _ensure_yolov8n_onnx() -> Path:
    global _yolo_onnx_path
    if _yolo_onnx_path is not None and _yolo_onnx_path.exists():
        return _yolo_onnx_path
    models_dir = Path(__file__).resolve().parent / "models"
    models_dir.mkdir(parents=True, exist_ok=True)
    onnx_path = models_dir / "yolov8n.onnx"
    if onnx_path.exists():
        _yolo_onnx_path = onnx_path
        return onnx_path
    urls = [
        "https://huggingface.co/SpotLab/YOLOv8Detection/resolve/3005c6751fb19cdeb6b10c066185908faf66a097/yolov8n.onnx?download=true",
        "https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.onnx",
    ]
    tmp_path = models_dir / "yolov8n.onnx.part"
    last_err: Optional[Exception] = None
    for url in urls:
        logger.info(f"[YOLO] Downloading yolov8n.onnx from {url}")
        try:
            urllib.request.urlretrieve(url, tmp_path.as_posix())
            tmp_path.replace(onnx_path)
            logger.info("[YOLO] yolov8n.onnx downloaded")
            _yolo_onnx_path = onnx_path
            return onnx_path
        except Exception as e:
            last_err = e
            try:
                if tmp_path.exists():
                    tmp_path.unlink()
            except Exception:
                pass
            logger.warning(f"[YOLO] Download failed from {url}: {e}")
            continue
    raise RuntimeError(f"Failed to download yolov8n.onnx: {last_err}")
def _get_yolo_session():
    global _yolo_session, _yolo_error
    if not ONNXRUNTIME_AVAILABLE:
        raise RuntimeError("onnxruntime not available. Install: pip install onnxruntime")
    if _yolo_session is not None:
        return _yolo_session
    if _yolo_error is not None:
        raise RuntimeError(_yolo_error)
    try:
        onnx_path = _ensure_yolov8n_onnx()
        sess_options = ort.SessionOptions()
        sess_options.intra_op_num_threads = 1
        sess_options.inter_op_num_threads = 1
        sess_options.execution_mode = ort.ExecutionMode.ORT_SEQUENTIAL
        sess_options.graph_optimization_level = ort.GraphOptimizationLevel.ORT_DISABLE_ALL
        providers = ['CPUExecutionProvider']
        session = ort.InferenceSession(
            onnx_path.as_posix(),
            sess_options=sess_options,
            providers=providers
        )
        _yolo_session = session
        logger.info("[YOLO] ONNX Runtime session created")
        return _yolo_session
    except Exception as e:
        _yolo_error = f"Failed to init YOLOv8 ONNX Runtime: {e}"
        logger.error(f"[YOLO] {_yolo_error}", exc_info=True)
        raise RuntimeError(_yolo_error)
def _letterbox_bgr(img_bgr: np.ndarray, new_shape: int = 640, color=(114, 114, 114)):
    h, w = img_bgr.shape[:2]
    r = min(new_shape / h, new_shape / w)
    new_unpad = (int(round(w * r)), int(round(h * r)))
    dw = new_shape - new_unpad[0]
    dh = new_shape - new_unpad[1]
    dw /= 2
    dh /= 2
    if (w, h) != new_unpad:
        img_bgr = cv2.resize(img_bgr, new_unpad, interpolation=cv2.INTER_LINEAR)
    top, bottom = int(round(dh - 0.1)), int(round(dh + 0.1))
    left, right = int(round(dw - 0.1)), int(round(dw + 0.1))
    img_bgr = cv2.copyMakeBorder(img_bgr, top, bottom, left, right, cv2.BORDER_CONSTANT, value=color)
    return img_bgr, r, (left, top)
def _xywh_to_xyxy(xywh: np.ndarray) -> np.ndarray:
    x, y, w, h = xywh.T
    x1 = x - w / 2
    y1 = y - h / 2
    x2 = x + w / 2
    y2 = y + h / 2
    return np.stack([x1, y1, x2, y2], axis=1)
def _nms_xyxy(boxes: np.ndarray, scores: np.ndarray, iou_thres: float = 0.45) -> np.ndarray:
    if boxes.size == 0:
        return np.array([], dtype=np.int64)
    x1 = boxes[:, 0]
    y1 = boxes[:, 1]
    x2 = boxes[:, 2]
    y2 = boxes[:, 3]
    areas = (x2 - x1 + 1) * (y2 - y1 + 1)
    order = scores.argsort()[::-1]
    keep = []
    while order.size > 0:
        i = order[0]
        keep.append(i)
        xx1 = np.maximum(x1[i], x1[order[1:]])
        yy1 = np.maximum(y1[i], y1[order[1:]])
        xx2 = np.minimum(x2[i], x2[order[1:]])
        yy2 = np.minimum(y2[i], y2[order[1:]])
        w = np.maximum(0.0, xx2 - xx1 + 1)
        h = np.maximum(0.0, yy2 - yy1 + 1)
        inter = w * h
        ovr = inter / (areas[i] + areas[order[1:]] - inter + 1e-9)
        inds = np.where(ovr <= iou_thres)[0]
        order = order[inds + 1]
    return np.array(keep, dtype=np.int64)
class PoseService:
    def __init__(self, config):
        self.config = config
        self._face_cascade = None
        self.min_unique_colors = 15
        self.hash_timeout = 3600
    def get_cascade(self, name: str = 'default'):
        cascades = {
            'default': 'haarcascade_frontalface_default.xml',
            'alt': 'haarcascade_frontalface_alt.xml',
            'alt2': 'haarcascade_frontalface_alt2.xml',
            'profile': 'haarcascade_profileface.xml',
        }
        cascade_path = cv2.data.haarcascades + cascades.get(name, cascades['default'])
        cascade = cv2.CascadeClassifier(cascade_path)
        if cascade.empty():
            logger.error(f"Не удалось загрузить каскад: {cascade_path}")
        return cascade
    @property
    def face_cascade(self):
        if self._face_cascade is None:
            self._face_cascade = self.get_cascade('alt2')
        return self._face_cascade
    def _load_and_resize_image(self, photo_path: str, max_size: int = 480):
        img = cv2.imread(photo_path)
        if img is None:
            return None
        h, w = img.shape[:2]
        if max(h, w) > max_size:
            scale = max_size / max(h, w)
            new_w = int(w * scale)
            new_h = int(h * scale)
            img = cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)
        return img
    def _compute_image_hash(self, img) -> str:
        small = cv2.resize(img, (16, 16), interpolation=cv2.INTER_AREA)
        gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
        avg = gray.mean()
        binary = (gray > avg).flatten()
        hash_str = ''.join(['1' if b else '0' for b in binary])
        return hashlib.md5(hash_str.encode()).hexdigest()
    def _is_duplicate_photo(self, img, user_id: int) -> bool:
        global _photo_hashes, _last_cleanup
        if time.time() - _last_cleanup > 1800:
            current_time = time.time()
            for uid in list(_photo_hashes.keys()):
                _photo_hashes[uid] = [
                    (h, t) for h, t in _photo_hashes[uid]
                    if current_time - t < self.hash_timeout
                ]
                if not _photo_hashes[uid]:
                    del _photo_hashes[uid]
            _last_cleanup = current_time
        current_hash = self._compute_image_hash(img)
        current_time = time.time()
        for saved_hash, saved_time in _photo_hashes.get(user_id, []):
            if saved_hash == current_hash and current_time - saved_time < self.hash_timeout:
                logger.warning(f"Дубликат фото от пользователя {user_id}")
                return True
        _photo_hashes[user_id].append((current_hash, current_time))
        return False
    def _check_color_diversity(self, img) -> Tuple[bool, int]:
        hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
        hist = cv2.calcHist([hsv], [0], None, [180], [0, 180])
        unique_hues = np.count_nonzero(hist > 10)
        return unique_hues >= self.min_unique_colors, unique_hues
    def _detect_face(self, gray, w, h) -> Optional[Tuple[int, int, int, int]]:
        faces = self.face_cascade.detectMultiScale(
            gray,
            scaleFactor=1.15,
            minNeighbors=3,
            minSize=(25, 25),
            maxSize=(int(w * 0.85), int(h * 0.85))
        )
        if len(faces) > 0:
            return tuple(faces[0])
        return None
    def _check_pose_soft(self, gray, face, required_pose: str) -> Tuple[bool, str]:
        h, w = gray.shape[:2]
        face_x, face_y, face_w, face_h = face
        face_center_x = face_x + face_w // 2
        upper_region = gray[0:face_y, :] if face_y > 20 else None
        left_region = gray[face_y:face_y+face_h, 0:face_x] if face_x > 20 else None
        right_region = gray[face_y:face_y+face_h, face_x+face_w:w] if face_x + face_w < w - 20 else None
        has_upper_activity = False
        has_side_activity = False
        if upper_region is not None and upper_region.size > 100:
            edges = cv2.Canny(upper_region, 30, 80)
            contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
            significant = [c for c in contours if cv2.contourArea(c) > 150]
            has_upper_activity = len(significant) >= 1
            del edges
        if left_region is not None and left_region.size > 100:
            edges = cv2.Canny(left_region, 30, 80)
            contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
            if any(cv2.contourArea(c) > 200 for c in contours):
                has_side_activity = True
            del edges
        if right_region is not None and right_region.size > 100:
            edges = cv2.Canny(right_region, 30, 80)
            contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
            if any(cv2.contourArea(c) > 200 for c in contours):
                has_side_activity = True
            del edges
        if required_pose == "hands_up":
            if has_upper_activity:
                return True, "✅ Руки подняты! Отлично!"
            else:
                return True, "✅ Фото принято!"
        elif required_pose == "heart":
            if has_upper_activity:
                return True, "✅ Сердечко! ❤️"
            else:
                return True, "✅ Фото принято!"
        elif required_pose == "point":
            if has_side_activity or has_upper_activity:
                return True, "✅ Указываете пальцем! 👉"
            else:
                return True, "✅ Фото принято!"
        return True, "✅ Фото принято!"
    async def check_pose(self, photo_path: str, required_pose: str, language: str = 'ru', user_id: int = 0) -> Tuple[bool, str]:
        try:
            from bot.utils.i18n import i18n
            img = self._load_and_resize_image(photo_path, max_size=480)
            if img is None:
                return False, i18n.get("photo_read_error", language, default="❌ Could not read photo")
            if user_id and self._is_duplicate_photo(img, user_id):
                del img
                gc.collect()
                return False, i18n.get("photo_duplicate", language, default="❌ Это фото уже отправлялось. Сделайте новое фото!")
            color_ok, unique_colors = self._check_color_diversity(img)
            if not color_ok:
                del img
                gc.collect()
                logger.warning(f"Мало цветов на фото: {unique_colors}")
                return False, i18n.get("photo_too_simple", language, default="❌ Фото слишком однотонное. Сделайте фото на улице с объектом!")
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            h, w = gray.shape[:2]
            face = self._detect_face(gray, w, h)
            if face is None:
                del img, gray
                gc.collect()
                return False, i18n.get("pose_face_not_visible", language, default="❌ Не вижу лицо на фото. Встаньте лицом к камере!")
            pose_ok, pose_msg = self._check_pose_soft(gray, face, required_pose)
            del img, gray
            gc.collect()
            if language == 'en':
                pose_msg = pose_msg.replace("Руки подняты! Отлично!", "Hands raised! Great!")
                pose_msg = pose_msg.replace("Сердечко!", "Heart shape!")
                pose_msg = pose_msg.replace("Указываете пальцем!", "Pointing!")
                pose_msg = pose_msg.replace("Фото принято!", "Photo accepted!")
            return pose_ok, pose_msg
        except Exception as e:
            gc.collect()
            from bot.utils.i18n import i18n
            logger.error(f"Ошибка при проверке фото: {e}", exc_info=True)
            return True, i18n.get("pose_accepted", language, default="✅ Фото принято!")
    async def check_people_count(self, photo_path: str) -> Tuple[bool, str, int]:
        if not ONNXRUNTIME_AVAILABLE:
            logger.error("[PEOPLE] onnxruntime не установлен")
            return False, "❌ Система проверки людей недоступна", 0
        gc.collect()
        _malloc_trim()
        try:
            img = cv2.imread(photo_path)
            if img is None:
                logger.error(f"Не удалось загрузить фото: {photo_path}")
                return False, "❌ Не удалось загрузить фото", 0
            h, w = img.shape[:2]
            logger.info(f"[PEOPLE] Размер фото: {w}x{h}, ищу людей через YOLOv8 (ONNX Runtime)")
            max_size = 640
            if max(h, w) > max_size:
                scale = max_size / max(h, w)
                new_w, new_h = int(w * scale), int(h * scale)
                logger.info(f"[PEOPLE] Уменьшаю фото до {new_w}x{new_h} для экономии памяти")
                img = cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)
            session = _get_yolo_session()
            img0_h, img0_w = img.shape[:2]
            input_size = 640
            lb, r, (dw, dh) = _letterbox_bgr(img, new_shape=input_size)
            del img
            gc.collect()
            try:
                input_tensor = np.empty((1, 3, input_size, input_size), dtype=np.float32)
                lb_f = lb.astype(np.float32) / 255.0
                input_tensor[0, 0] = lb_f[:, :, 0]
                input_tensor[0, 1] = lb_f[:, :, 1]
                input_tensor[0, 2] = lb_f[:, :, 2]
                del lb_f
            except Exception as alloc_err:
                if "allocate" in str(alloc_err).lower() or "memory" in str(alloc_err).lower():
                    del lb
                    gc.collect()
                    logger.error(f"[PEOPLE] Недостаточно памяти для тензора: {alloc_err}")
                    return False, "❌ Недостаточно памяти. Попробуйте ещё раз.", 0
                raise
            del lb
            gc.collect()
            input_name = session.get_inputs()[0].name
            try:
                outputs = session.run(None, {input_name: input_tensor})
                out = outputs[0]
            except Exception as e:
                if "memory" in str(e).lower() or "alloc" in str(e).lower():
                    logger.error(f"[PEOPLE] Недостаточно памяти для YOLO inference: {e}")
                    del input_tensor
                    gc.collect()
                    return False, "❌ Недостаточно памяти. Попробуйте ещё раз.", 0
                raise
            y = out[0]
            if y.ndim == 3:
                y = y[0]
            if y.ndim != 2:
                raise RuntimeError(f"Unexpected YOLO output shape: {out.shape} -> {y.shape}")
            if y.shape[0] == 84 and y.shape[1] > 84:
                y = y.T
            elif y.shape[1] == 84:
                pass
            else:
                raise RuntimeError(f"Unexpected YOLO output inner shape: {y.shape}")
            boxes_xywh = y[:, 0:4]
            cls_scores = y[:, 4:]
            person_scores = cls_scores[:, 0]
            conf_mask = person_scores >= 0.50
            if not np.any(conf_mask):
                del input_tensor, out, y
                gc.collect()
                return False, "❌ 0 людей найдено на фото (YOLOv8 conf<50%)", 0
            boxes_xywh = boxes_xywh[conf_mask]
            scores = person_scores[conf_mask]
            boxes_xyxy = _xywh_to_xyxy(boxes_xywh)
            boxes_xyxy[:, [0, 2]] -= dw
            boxes_xyxy[:, [1, 3]] -= dh
            boxes_xyxy /= r
            boxes_xyxy[:, 0] = np.clip(boxes_xyxy[:, 0], 0, img0_w - 1)
            boxes_xyxy[:, 1] = np.clip(boxes_xyxy[:, 1], 0, img0_h - 1)
            boxes_xyxy[:, 2] = np.clip(boxes_xyxy[:, 2], 0, img0_w - 1)
            boxes_xyxy[:, 3] = np.clip(boxes_xyxy[:, 3], 0, img0_h - 1)
            keep = _nms_xyxy(boxes_xyxy, scores, iou_thres=0.45)
            count = int(len(keep))
            max_conf = float(scores.max()) if scores.size else 0.0
            logger.info(f"[PEOPLE] YOLOv8 ONNX Runtime: найдено людей={count}, max_conf={max_conf:.2f}")
            del input_tensor, out, y, boxes_xywh, cls_scores, person_scores, boxes_xyxy, scores, keep
            gc.collect()
            if count == 0:
                return False, "❌ 0 людей найдено на фото (YOLOv8 conf<50%)", 0
            return True, f"✅ На фото найден человек (YOLOv8 {max_conf:.0%})", count
        except cv2.error as e:
            if "Insufficient memory" in str(e):
                logger.error(f"Недостаточно памяти для обработки фото: {e}")
                gc.collect()
                return False, "❌ Недостаточно памяти для обработки фото", 0
            raise
        except Exception as e:
            gc.collect()
            logger.error(f"Ошибка при подсчёте людей: {e}", exc_info=True)
            if "allocate" in str(e).lower() or "memory" in str(e).lower():
                return False, "❌ Недостаточно памяти. Попробуйте ещё раз.", 0
            return False, f"❌ {str(e)[:80]}", 0
        finally:
            _unload_yolo_session()
    def __del__(self):
        self._face_cascade = None
        gc.collect()
        logger.info("PoseService очищен")