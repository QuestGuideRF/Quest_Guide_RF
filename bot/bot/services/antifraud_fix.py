import time
from typing import Dict, Tuple
class SimpleCache:
    def __init__(self):
        self.data: Dict[str, Tuple[str, float]] = {}
    async def exists(self, key: str) -> bool:
        if key in self.data:
            _, expire_time = self.data[key]
            if time.time() < expire_time:
                return True
            else:
                del self.data[key]
        return False
    async def setex(self, key: str, seconds: int, value: str):
        self.data[key] = (value, time.time() + seconds)
    async def get(self, key: str):
        if await self.exists(key):
            return self.data[key][0]
        return None
    async def incr(self, key: str) -> int:
        if key in self.data:
            val, expire = self.data[key]
            new_val = int(val) + 1
            self.data[key] = (str(new_val), expire)
            return new_val
        else:
            self.data[key] = ("1", time.time() + 3600)
            return 1
    async def expire(self, key: str, seconds: int):
        if key in self.data:
            val, _ = self.data[key]
            self.data[key] = (val, time.time() + seconds)
simple_cache = SimpleCache()