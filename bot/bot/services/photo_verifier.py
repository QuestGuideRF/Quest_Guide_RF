import os
import logging
import warnings
from typing import Optional, Tuple, List
from pathlib import Path
import torch
from PIL import Image
logger = logging.getLogger(__name__)
warnings.filterwarnings("ignore", message=".*TypedStorage is deprecated.*", category=UserWarning, module="torch")
CLIP_AVAILABLE = False
_clip_load_failed_this_process = False
try:
    from transformers import CLIPProcessor, CLIPModel
    CLIP_AVAILABLE = True
except ImportError:
    logger.warning("CLIP not available: transformers not installed")
def _check_memory_available(min_mb: int = 500) -> bool:
    try:
        import psutil
        mem = psutil.virtual_memory()
        available_mb = mem.available / (1024 * 1024)
        total_mb = mem.total / (1024 * 1024)
        logger.info(f"Memory check: available={available_mb:.1f}MB, total={total_mb:.1f}MB, required={min_mb}MB")
        if available_mb < min_mb:
            logger.warning(f"Insufficient memory: {available_mb:.1f}MB < {min_mb}MB")
            return False
        return True
    except ImportError:
        logger.warning("psutil not available, cannot check memory - will try anyway")
        return True
    except Exception as e:
        logger.warning(f"Error checking memory: {e}, will try anyway")
        return True
class PhotoVerifier:
    def __init__(self, similarity_threshold: float = 0.30, model_name: str = "openai/clip-vit-base-patch32"):
        if not CLIP_AVAILABLE:
            raise ImportError("CLIP not available. Install: pip install transformers torch")
        self.similarity_threshold = similarity_threshold
        self.model_name = model_name
        self.model = None
        self.processor = None
        self.device = "cuda" if torch.cuda.is_available() else "cpu"
        self.model_loaded = False
        self.memory_ok = _check_memory_available(500)
        logger.info(f"PhotoVerifier: device={self.device}, model={model_name}, threshold={similarity_threshold}, memory_ok={self.memory_ok}")
    def _load_model(self):
        global _clip_load_failed_this_process
        if self.model is not None:
            return
        if _clip_load_failed_this_process:
            logger.info("CLIP skip: already failed in this process, using fallback")
            raise MemoryError("CLIP already failed to load in this process, skipping")
        if not self.memory_ok:
            logger.warning("Not enough memory to load CLIP model (need ~500MB)")
            raise MemoryError("Not enough memory for CLIP model")
        try:
            logger.info(f"Loading CLIP model: {self.model_name} (this may take a moment...)")
            import gc
            gc.collect()
            try:
                self.model = CLIPModel.from_pretrained(
                    self.model_name,
                    torch_dtype=torch.float32,
                    local_files_only=False,
                    use_safetensors=True,
                )
            except Exception as e1:
                if "safetensors" in str(e1).lower() or "not found" in str(e1).lower():
                    try:
                        self.model = CLIPModel.from_pretrained(
                            self.model_name,
                            torch_dtype=torch.float32,
                            local_files_only=False,
                        )
                    except Exception as e2:
                        logger.error(f"Error loading CLIP model weights: {e2}")
                        raise MemoryError(f"Failed to load CLIP model: {e2}")
                else:
                    logger.error(f"Error loading CLIP model weights: {e1}")
                    raise MemoryError(f"Failed to load CLIP model: {e1}")
            try:
                self.processor = CLIPProcessor.from_pretrained(self.model_name)
            except Exception as e:
                logger.error(f"Error loading CLIP processor: {e}")
                if self.model:
                    del self.model
                raise MemoryError(f"Failed to load CLIP processor: {e}")
            try:
                self.model.to(self.device)
                self.model.eval()
            except Exception as e:
                logger.error(f"Error moving model to device: {e}")
                if self.model:
                    del self.model
                if self.processor:
                    del self.processor
                raise MemoryError(f"Failed to initialize CLIP model: {e}")
            self.model_loaded = True
            gc.collect()
            logger.info("CLIP model loaded successfully")
        except (MemoryError, OSError, RuntimeError) as e:
            _clip_load_failed_this_process = True
            logger.error(f"Failed to load CLIP model (memory/OS/runtime error): {e}")
            self.model = None
            self.processor = None
            self.memory_ok = False
            raise MemoryError(f"Not enough memory for CLIP: {e}")
        except Exception as e:
            logger.error(f"Failed to load CLIP model: {e}", exc_info=True)
            self.model = None
            self.processor = None
            try:
                if "memory" in str(e).lower() or "allocate" in str(e).lower():
                    _clip_load_failed_this_process = True
            except Exception:
                pass
            raise
    async def verify_photo(
        self,
        photo_path: str,
        object_description: str,
        additional_context: Optional[str] = None
    ) -> Tuple[bool, float]:
        if not CLIP_AVAILABLE:
            logger.error("CLIP not available")
            return False, 0.0
        if not self.memory_ok:
            logger.warning("Not enough memory for CLIP, skipping verification")
            return False, 0.0
        if not os.path.exists(photo_path):
            logger.error(f"Photo not found: {photo_path}")
            return False, 0.0
        try:
            self._load_model()
            if self.model is None:
                logger.error("CLIP model failed to load")
                return False, 0.0
            image = Image.open(photo_path).convert("RGB")
            text_prompt = self._build_prompt(object_description, additional_context)
            inputs = self.processor(
                text=[text_prompt],
                images=image,
                return_tensors="pt",
                padding=True
            )
            inputs = {k: v.to(self.device) for k, v in inputs.items()}
            with torch.no_grad():
                outputs = self.model(**inputs)
                logits_per_image = outputs.logits_per_image
                similarity_score = torch.sigmoid(logits_per_image[0, 0]).item()
            is_valid = similarity_score >= self.similarity_threshold
            logger.info(
                f"Photo verification: score={similarity_score:.3f}, "
                f"threshold={self.similarity_threshold}, "
                f"result={'✅ PASS' if is_valid else '❌ FAIL'}, "
                f"prompt='{text_prompt[:50]}...'"
            )
            return is_valid, similarity_score
        except Exception as e:
            if "already failed" in str(e):
                logger.debug(f"CLIP skip: {e}")
            else:
                logger.error(f"Error verifying photo: {e}", exc_info=True)
            return False, 0.0
    async def verify_photo_multiple(
        self,
        photo_path: str,
        descriptions: List[str]
    ) -> Tuple[bool, float, str]:
        if not CLIP_AVAILABLE:
            return False, 0.0, ""
        if not os.path.exists(photo_path):
            return False, 0.0, ""
        try:
            self._load_model()
            image = Image.open(photo_path).convert("RGB")
            inputs = self.processor(
                text=descriptions,
                images=image,
                return_tensors="pt",
                padding=True
            )
            inputs = {k: v.to(self.device) for k, v in inputs.items()}
            with torch.no_grad():
                outputs = self.model(**inputs)
                logits_per_image = outputs.logits_per_image[0]
                scores = torch.softmax(logits_per_image, dim=0).cpu().numpy()
            best_idx = scores.argmax()
            best_score = float(scores[best_idx])
            best_description = descriptions[best_idx]
            is_valid = best_score >= self.similarity_threshold
            logger.info(
                f"Photo verification (multiple): best_score={best_score:.3f}, "
                f"best_match='{best_description[:50]}...', "
                f"result={'✅ PASS' if is_valid else '❌ FAIL'}"
            )
            return is_valid, best_score, best_description
        except Exception as e:
            logger.error(f"Error verifying photo (multiple): {e}", exc_info=True)
            return False, 0.0, ""
    def _build_prompt(self, object_description: str, additional_context: Optional[str] = None) -> str:
        prompt = f"a photo of {object_description}"
        if additional_context:
            prompt += f" {additional_context}"
        return prompt
    def cleanup(self):
        if self.model is not None:
            del self.model
            del self.processor
            self.model = None
            self.processor = None
            if torch.cuda.is_available():
                torch.cuda.empty_cache()
            logger.info("PhotoVerifier cleaned up")