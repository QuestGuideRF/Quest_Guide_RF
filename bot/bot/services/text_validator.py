from difflib import SequenceMatcher
from typing import Tuple, List
import re
class TextValidator:
    @staticmethod
    def normalize_text(text: str) -> str:
        text = text.lower().strip()
        text = re.sub(r'\s+', ' ', text)
        text = re.sub(r'[^\w\s]', '', text)
        return text
    @classmethod
    def check_answer(
        cls,
        user_answer: str,
        correct_answer: str,
        accept_partial: bool = False,
        similarity_threshold: float = 0.8
    ) -> Tuple[bool, float]:
        user_normalized = cls.normalize_text(user_answer)
        correct_normalized = cls.normalize_text(correct_answer)
        if user_normalized == correct_normalized:
            return True, 1.0
        if accept_partial and correct_normalized in user_normalized:
            return True, 0.9
        similarity = SequenceMatcher(None, user_normalized, correct_normalized).ratio()
        if similarity >= similarity_threshold:
            return True, similarity
        return False, similarity
    @classmethod
    def check_multiple_answers(
        cls,
        user_answer: str,
        correct_answers: List[str],
        accept_partial: bool = False,
        similarity_threshold: float = 0.8
    ) -> Tuple[bool, float, str]:
        best_match = (False, 0.0, "")
        for correct_answer in correct_answers:
            is_correct, similarity = cls.check_answer(
                user_answer,
                correct_answer,
                accept_partial,
                similarity_threshold
            )
            if is_correct:
                return True, similarity, correct_answer
            if similarity > best_match[1]:
                best_match = (is_correct, similarity, correct_answer)
        return best_match