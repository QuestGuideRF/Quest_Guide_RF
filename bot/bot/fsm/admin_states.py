from aiogram.fsm.state import State, StatesGroup
class AdminCityStates(StatesGroup):
    name = State()
    description = State()
    photo = State()
class AdminRouteStates(StatesGroup):
    city = State()
    name = State()
    description = State()
    route_type = State()
    distance = State()
    estimated_duration = State()
    price = State()
class AdminPointStates(StatesGroup):
    name = State()
    task_text = State()
    fact_text = State()
    order = State()
<<<<<<< HEAD
=======
    require_pose = State()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    min_people = State()
    reference_photos = State()
    edit_menu = State()
    edit_name = State()
    edit_task = State()
    edit_fact = State()
<<<<<<< HEAD
=======
    edit_pose = State()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    edit_people = State()
class AdminUserStates(StatesGroup):
    search = State()
    message = State()
class AdminSettingsStates(StatesGroup):
    similarity_threshold = State()
    max_photos_per_hour = State()
<<<<<<< HEAD
    channel_stats_time = State()
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
class AdminHintStates(StatesGroup):
    level = State()
    text = State()
    has_map = State()
    map_photo = State()
class AdminPromoCodeStates(StatesGroup):
    code = State()
    discount_type = State()
    discount_value = State()
    route_id = State()
    max_uses = State()
    valid_from = State()
    valid_until = State()
<<<<<<< HEAD
    is_active = State()
class AdminModeratorStates(StatesGroup):
    reply_message = State()
    reject_reason = State()
class AdminReferralStates(StatesGroup):
    reward_amount = State()
=======
    is_active = State()
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
