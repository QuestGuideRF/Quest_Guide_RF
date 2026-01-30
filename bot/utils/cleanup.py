import asyncio
from pathlib import Path
from datetime import datetime, timedelta
from bot.loader import config, SessionLocal
async def cleanup_temp_files(max_age_hours: int = 24):
    temp_dir = Path(config.paths.temp_dir)
    if not temp_dir.exists():
        print(f"Temporary directory not found: {temp_dir}")
        return
    cutoff_time = datetime.now() - timedelta(hours=max_age_hours)
    deleted_count = 0
    for file_path in temp_dir.glob('*'):
        if file_path.is_file():
            file_mtime = datetime.fromtimestamp(file_path.stat().st_mtime)
            if file_mtime < cutoff_time:
                file_path.unlink()
                deleted_count += 1
                print(f"Deleted: {file_path}")
    print(f"Cleanup completed. Deleted {deleted_count} files.")
async def cleanup_expired_sessions():
    async with SessionLocal() as session:
        result = await session.execute(
            "DELETE FROM user_sessions WHERE expires_at < NOW() OR (is_used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))"
        )
        await session.commit()
        print(f"Deleted {result.rowcount} expired sessions.")
async def main():
    print("Starting cleanup...")
    await cleanup_temp_files(max_age_hours=24)
    await cleanup_expired_sessions()
    print("Cleanup finished.")
if __name__ == "__main__":
    asyncio.run(main())