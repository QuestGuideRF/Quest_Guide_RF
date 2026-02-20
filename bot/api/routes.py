from aiohttp import web
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
import json
from bot.loader import SessionLocal, config
from bot.repositories.user import UserRepository
from bot.repositories.route import RouteRepository
from bot.repositories.progress import ProgressRepository
from bot.repositories.payment import PaymentRepository
from bot.models.achievement import UserAchievement
from bot.models.user_session import UserSession
async def verify_token(request: web.Request) -> int | None:
    token = request.headers.get('Authorization', '').replace('Bearer ', '')
    if not token:
        return None
    async with SessionLocal() as session:
        result = await session.execute(
            f"SELECT telegram_id FROM user_sessions WHERE token = '{token}' AND is_used = FALSE AND expires_at > NOW()"
        )
        row = result.first()
        return row[0] if row else None
@web.middleware
async def auth_middleware(request: web.Request, handler):
    api_secret = request.headers.get('X-API-Secret')
    if api_secret == config.web.api_secret:
        request['telegram_id'] = None
        return await handler(request)
    telegram_id = await verify_token(request)
    if not telegram_id:
        return web.json_response({'error': 'Unauthorized'}, status=401)
    request['telegram_id'] = telegram_id
    return await handler(request)
async def api_me(request: web.Request):
    telegram_id = request['telegram_id']
    async with SessionLocal() as session:
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(telegram_id)
        if not user:
            return web.json_response({'error': 'User not found'}, status=404)
        return web.json_response({
            'id': user.id,
            'telegram_id': user.telegram_id,
            'username': user.username,
            'first_name': user.first_name,
            'last_name': user.last_name,
            'photo_url': user.photo_url,
            'role': user.role.value,
            'created_at': user.created_at.isoformat() if user.created_at else None,
        })
async def api_routes(request: web.Request):
    telegram_id = request['telegram_id']
    async with SessionLocal() as session:
        route_repo = RouteRepository(session)
        routes = await route_repo.get_active()
        return web.json_response({
            'routes': [
                {
                    'id': r.id,
                    'name': r.name,
                    'description': r.description,
                    'type': r.route_type.value,
                    'price': r.price,
                    'duration': r.estimated_duration,
                    'distance': float(r.distance) if r.distance else None,
                    'is_active': r.is_active,
                }
                for r in routes
            ]
        })
async def api_progress(request: web.Request):
    telegram_id = request['telegram_id']
    async with SessionLocal() as session:
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(telegram_id)
        if not user:
            return web.json_response({'error': 'User not found'}, status=404)
        progress_repo = ProgressRepository(session)
        progresses = await session.execute(
            f"SELECT * FROM user_progress WHERE user_id = {telegram_id}"
        )
        progresses = progresses.fetchall()
        return web.json_response({
            'progress': [
                {
                    'id': p.id,
                    'route_id': p.route_id,
                    'status': p.status,
                    'points_completed': p.points_completed,
                    'started_at': p.started_at.isoformat() if p.started_at else None,
                    'completed_at': p.completed_at.isoformat() if p.completed_at else None,
                }
                for p in progresses
            ]
        })
async def api_payments(request: web.Request):
    telegram_id = request['telegram_id']
    async with SessionLocal() as session:
        payments = await session.execute(
            f"SELECT * FROM payments WHERE user_id = {telegram_id} ORDER BY created_at DESC"
        )
        payments = payments.fetchall()
        return web.json_response({
            'payments': [
                {
                    'id': p.id,
                    'route_id': p.route_id,
                    'amount': p.amount,
                    'status': p.status,
                    'created_at': p.created_at.isoformat() if p.created_at else None,
                }
                for p in payments
            ]
        })
async def api_achievements(request: web.Request):
    telegram_id = request['telegram_id']
    async with SessionLocal() as session:
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(telegram_id)
        if not user:
            return web.json_response({'error': 'User not found'}, status=404)
        all_achievements = await session.execute("SELECT * FROM achievements ORDER BY category, `order`")
        all_achievements = all_achievements.fetchall()
        user_achievements = await session.execute(
            text("SELECT achievement_id, earned_at FROM user_achievements WHERE user_id = :user_id"),
            {"user_id": user.id}
        )
        user_achievements = {ua.achievement_id: ua.earned_at for ua in user_achievements.fetchall()}
        return web.json_response({
            'achievements': [
                {
                    'id': a.id,
                    'name': a.name,
                    'description': a.description,
                    'icon': a.icon,
                    'category': a.category,
                    'is_hidden': a.is_hidden,
                    'earned': a.id in user_achievements,
                    'earned_at': user_achievements[a.id].isoformat() if a.id in user_achievements else None,
                }
                for a in all_achievements
            ]
        })
async def api_photos(request: web.Request):
    telegram_id = request['telegram_id']
    route_id = request.query.get('route_id')
    async with SessionLocal() as session:
        user_repo = UserRepository(session)
        user = await user_repo.get_by_telegram_id(telegram_id)
        if not user:
            return web.json_response({'error': 'User not found'}, status=404)
        params = {"user_id": user.id}
        query = "SELECT * FROM user_photos WHERE user_id = :user_id"
        if route_id:
            query += " AND point_id IN (SELECT id FROM points WHERE route_id = :route_id)"
            params["route_id"] = route_id
        query += " ORDER BY created_at DESC LIMIT 100"
        photos = await session.execute(text(query), params)
        photos = photos.fetchall()
        return web.json_response({
            'photos': [
                {
                    'id': p.id,
                    'point_id': p.point_id,
                    'file_path': p.file_path,
                    'created_at': p.created_at.isoformat() if p.created_at else None,
                }
                for p in photos
            ]
        })
def setup_api_routes(app: web.Application):
    app.middlewares.append(auth_middleware)
    app.router.add_get('/api/me', api_me)
    app.router.add_get('/api/routes', api_routes)
    app.router.add_get('/api/progress', api_progress)
    app.router.add_get('/api/payments', api_payments)
    app.router.add_get('/api/achievements', api_achievements)