import cv2
import mediapipe as mp
from typing import Optional, Tuple
from bot.config import VisionConfig
class PoseService:
    def __init__(self, config: VisionConfig):
        self.config = config
        self.mp_pose = mp.solutions.pose
        self.mp_face = mp.solutions.face_detection
        self.pose_detector = self.mp_pose.Pose(
            static_image_mode=True,
            min_detection_confidence=config.pose_confidence,
        )
        self.face_detector = self.mp_face.FaceDetection(
            min_detection_confidence=config.pose_confidence,
        )
    async def check_pose(
        self,
        photo_path: str,
        required_pose: str,
    ) -> Tuple[bool, str]:
        image = cv2.imread(photo_path)
        if image is None:
            return False, "Не удалось загрузить изображение"
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = self.pose_detector.process(image_rgb)
        if not results.pose_landmarks:
            return False, "Человек не обнаружен на фото"
        landmarks = results.pose_landmarks.landmark
        if required_pose == "hands_up":
            return self._check_hands_up(landmarks)
        elif required_pose == "heart":
            return self._check_heart(landmarks)
        elif required_pose == "point":
            return self._check_pointing(landmarks)
        else:
            return True, "Проверка позы не требуется"
    def _check_hands_up(self, landmarks) -> Tuple[bool, str]:
        left_wrist = landmarks[self.mp_pose.PoseLandmark.LEFT_WRIST]
        right_wrist = landmarks[self.mp_pose.PoseLandmark.RIGHT_WRIST]
        nose = landmarks[self.mp_pose.PoseLandmark.NOSE]
        if left_wrist.y < nose.y and right_wrist.y < nose.y:
            return True, "Руки подняты вверх ✓"
        return False, "Поднимите обе руки вверх"
    def _check_heart(self, landmarks) -> Tuple[bool, str]:
        left_wrist = landmarks[self.mp_pose.PoseLandmark.LEFT_WRIST]
        right_wrist = landmarks[self.mp_pose.PoseLandmark.RIGHT_WRIST]
        nose = landmarks[self.mp_pose.PoseLandmark.NOSE]
        distance = abs(left_wrist.x - right_wrist.x)
        avg_y = (left_wrist.y + right_wrist.y) / 2
        if distance < 0.2 and avg_y < nose.y:
            return True, "Сердечко руками ✓"
        return False, "Сложите руки сердечком над головой"
    def _check_pointing(self, landmarks) -> Tuple[bool, str]:
        left_wrist = landmarks[self.mp_pose.PoseLandmark.LEFT_WRIST]
        right_wrist = landmarks[self.mp_pose.PoseLandmark.RIGHT_WRIST]
        left_shoulder = landmarks[self.mp_pose.PoseLandmark.LEFT_SHOULDER]
        right_shoulder = landmarks[self.mp_pose.PoseLandmark.RIGHT_SHOULDER]
        left_extended = abs(left_wrist.x - left_shoulder.x) > 0.3
        right_extended = abs(right_wrist.x - right_shoulder.x) > 0.3
        if left_extended or right_extended:
            return True, "Указание пальцем ✓"
        return False, "Укажите пальцем на объект"
    async def count_people(self, photo_path: str) -> int:
        image = cv2.imread(photo_path)
        if image is None:
            return 0
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = self.face_detector.process(image_rgb)
        if not results.detections:
            return 0
        return len(results.detections)
    async def check_people_count(
        self,
        photo_path: str,
        min_people: int,
    ) -> Tuple[bool, str]:
        count = await self.count_people(photo_path)
        if count >= min_people:
            return True, f"Обнаружено людей: {count} ✓"
        return False, f"Нужно минимум {min_people} человек(а), обнаружено: {count}"
    def __del__(self):
        self.pose_detector.close()
        self.face_detector.close()