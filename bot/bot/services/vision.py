import cv2
import numpy as np
import logging
from typing import Optional, List, Tuple
from pathlib import Path
from bot.config import VisionConfig
from bot.services.photo_verifier import PhotoVerifier, CLIP_AVAILABLE
logger = logging.getLogger(__name__)
class VisionService:
    def __init__(self, config: VisionConfig):
        self.config = config
        self.photo_verifier = None
        self.clip_available = False
        if CLIP_AVAILABLE:
            try:
                self.photo_verifier = PhotoVerifier(
                    similarity_threshold=config.similarity_threshold
                )
                if self.photo_verifier.memory_ok:
                    self.clip_available = True
                    logger.info("VisionService: CLIP PhotoVerifier initialized")
                else:
                    logger.warning("VisionService: CLIP PhotoVerifier created but memory check failed")
            except (MemoryError, ImportError) as e:
                logger.warning(f"VisionService: CLIP not available: {e}")
            except Exception as e:
                logger.warning(f"VisionService: Failed to initialize CLIP: {e}")
        self.akaze = cv2.AKAZE_create()
        self.orb = cv2.ORB_create(nfeatures=2000)
        self.bf_matcher = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=True)
    async def check_location(
        self,
        user_photo_path: str,
        reference_photo_paths: List[str] = None,
        object_description: Optional[str] = None,
        point_name: Optional[str] = None,
    ) -> Tuple[bool, float]:
        if self.clip_available and self.photo_verifier and object_description:
            try:
                logger.info(f"[VISION] Проверка локации через CLIP: '{object_description}'")
                context = f"at {point_name}" if point_name else None
                is_valid, score = await self.photo_verifier.verify_photo(
                    user_photo_path,
                    object_description,
                    context
                )
                if score > 0:
                    score_percent = score * 100
                    logger.info(
                        f"[VISION] CLIP результат: score={score_percent:.1f}%, "
                        f"порог={self.config.similarity_threshold * 100:.1f}%, "
                        f"результат: {'✅ ПРИНЯТО' if is_valid else '❌ ОТКЛОНЕНО'}"
                    )
                    return is_valid, score_percent
                else:
                    logger.warning("[VISION] CLIP вернул score=0, используем fallback")
            except (MemoryError, OSError, RuntimeError) as e:
                logger.warning(f"[VISION] CLIP недоступен (память/ошибка): {e}, используем fallback")
                self.clip_available = False
            except Exception as e:
                logger.error(f"[VISION] Ошибка CLIP: {e}, используем fallback", exc_info=True)
                self.clip_available = False
        if reference_photo_paths:
            logger.info(f"[VISION] Проверка локации через эталоны (legacy): {len(reference_photo_paths)}")
            return await self._check_location_legacy(user_photo_path, reference_photo_paths)
        if object_description:
            logger.warning(f"[VISION] CLIP недоступен, но есть описание '{object_description}' - пропускаем проверку")
            return True, 50.0
        logger.warning("[VISION] Нет описания объекта и нет эталонных фото")
        return False, 0.0
    async def _check_location_legacy(
        self,
        user_photo_path: str,
        reference_photo_paths: List[str],
    ) -> Tuple[bool, float]:
        user_img_color = cv2.imread(user_photo_path)
        if user_img_color is None:
            logger.error(f"[VISION] Не удалось загрузить фото: {user_photo_path}")
            return False, 0.0
        user_img_gray = cv2.cvtColor(user_img_color, cv2.COLOR_BGR2GRAY)
        h, w = user_img_gray.shape[:2]
        max_size = 1024
        if max(h, w) > max_size:
            scale = max_size / max(h, w)
            user_img_gray = cv2.resize(user_img_gray, (int(w * scale), int(h * scale)))
            user_img_color = cv2.resize(user_img_color, (int(w * scale), int(h * scale)))
        try:
            user_kp, user_desc = self.akaze.detectAndCompute(user_img_gray, None)
        except Exception as e:
            logger.warning("[VISION] AKAZE failed (%s), using ORB", e)
            user_kp, user_desc = self.orb.detectAndCompute(user_img_gray, None)
        if user_desc is None or len(user_kp) == 0:
            logger.warning("[VISION] Не найдено ключевых точек на фото пользователя")
            return False, 0.0
        logger.info(f"[VISION] Найдено {len(user_kp)} ключевых точек на фото пользователя")
        max_combined_score = 0.0
        best_ref_path = None
        for ref_path in reference_photo_paths:
            ref_img_color = cv2.imread(ref_path)
            if ref_img_color is None:
                logger.warning(f"[VISION] Не удалось загрузить эталон: {ref_path}")
                continue
            ref_img_gray = cv2.cvtColor(ref_img_color, cv2.COLOR_BGR2GRAY)
            ref_img_gray = cv2.resize(ref_img_gray, (user_img_gray.shape[1], user_img_gray.shape[0]))
            ref_img_color = cv2.resize(ref_img_color, (user_img_color.shape[1], user_img_color.shape[0]))
            keypoint_score = await self._compare_keypoints(user_kp, user_desc, ref_img_gray)
            color_score = await self._compare_histograms(user_img_color, ref_img_color)
            ssim_score = await self._compare_ssim(user_img_gray, ref_img_gray)
            combined_score = (
                keypoint_score * 0.60 +
                color_score * 0.25 +
                ssim_score * 0.15
            )
            logger.info(
                f"[VISION] Эталон {Path(ref_path).name}: "
                f"keypoints={keypoint_score:.2%}, colors={color_score:.2%}, "
                f"ssim={ssim_score:.2%}, combined={combined_score:.2%}"
            )
            if combined_score > max_combined_score:
                max_combined_score = combined_score
                best_ref_path = ref_path
        score_percent = max_combined_score * 100
        is_valid = max_combined_score >= self.config.similarity_threshold
        if best_ref_path:
            logger.info(
                f"[VISION] Лучшее совпадение: {Path(best_ref_path).name} "
                f"({score_percent:.1f}%), порог: {self.config.similarity_threshold * 100:.1f}%, "
                f"результат: {'✅ ПРИНЯТО' if is_valid else '❌ ОТКЛОНЕНО'}"
            )
        else:
            logger.warning("[VISION] Не удалось сравнить ни с одним эталоном")
        return is_valid, score_percent
    async def _compare_keypoints(self, user_kp, user_desc, ref_img_gray) -> float:
        try:
            ref_kp, ref_desc = self.akaze.detectAndCompute(ref_img_gray, None)
        except Exception as e:
            logger.debug("[VISION] AKAZE fallback on ref: %s", e)
            ref_kp, ref_desc = self.orb.detectAndCompute(ref_img_gray, None)
        if ref_desc is None or len(ref_kp) == 0:
            return 0.0
        matches = self.bf_matcher.match(user_desc, ref_desc)
        good_matches = [m for m in matches if m.distance < 70]
        if len(good_matches) == 0:
            return 0.0
        avg_keypoints = (len(user_kp) + len(ref_kp)) / 2
        score = min(len(good_matches) / avg_keypoints, 1.0)
        return score
    async def _compare_histograms(self, user_img_color, ref_img_color) -> float:
        try:
            user_hsv = cv2.cvtColor(user_img_color, cv2.COLOR_BGR2HSV)
            ref_hsv = cv2.cvtColor(ref_img_color, cv2.COLOR_BGR2HSV)
            hist_user_h = cv2.calcHist([user_hsv], [0], None, [180], [0, 180])
            hist_user_s = cv2.calcHist([user_hsv], [1], None, [256], [0, 256])
            hist_ref_h = cv2.calcHist([ref_hsv], [0], None, [180], [0, 180])
            hist_ref_s = cv2.calcHist([ref_hsv], [1], None, [256], [0, 256])
            cv2.normalize(hist_user_h, hist_user_h, 0, 1, cv2.NORM_MINMAX)
            cv2.normalize(hist_user_s, hist_user_s, 0, 1, cv2.NORM_MINMAX)
            cv2.normalize(hist_ref_h, hist_ref_h, 0, 1, cv2.NORM_MINMAX)
            cv2.normalize(hist_ref_s, hist_ref_s, 0, 1, cv2.NORM_MINMAX)
            score_h = cv2.compareHist(hist_user_h, hist_ref_h, cv2.HISTCMP_CORREL)
            score_s = cv2.compareHist(hist_user_s, hist_ref_s, cv2.HISTCMP_CORREL)
            score = (score_h + score_s) / 2
            score = (score + 1) / 2
            return max(0.0, min(1.0, score))
        except Exception as e:
            logger.warning(f"[VISION] Ошибка сравнения гистограмм: {e}")
            return 0.0
    async def _compare_ssim(self, user_img_gray, ref_img_gray) -> float:
        try:
            from skimage.metrics import structural_similarity as ssim
            score = ssim(user_img_gray, ref_img_gray)
            return max(0.0, min(1.0, score))
        except ImportError:
            logger.warning("[VISION] scikit-image not installed, using simplified SSIM")
            return await self._compare_mse(user_img_gray, ref_img_gray)
        except Exception as e:
            logger.warning(f"[VISION] Ошибка SSIM: {e}")
            return 0.0
    async def _compare_mse(self, user_img_gray, ref_img_gray) -> float:
        try:
            mse = np.mean((user_img_gray.astype(float) - ref_img_gray.astype(float)) ** 2)
            score = 1.0 / (1.0 + mse / 1000)
            return score
        except Exception as e:
            logger.warning(f"[VISION] Ошибка MSE: {e}")
            return 0.0
    async def use_google_vision(self, photo_path: str) -> Optional[str]:
        return None
    def extract_embedding(self, photo_path: str) -> Optional[np.ndarray]:
        return None