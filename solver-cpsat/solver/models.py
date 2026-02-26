from pydantic import BaseModel


class Activity(BaseModel):
    id: int
    subject_id: int
    subject_name: str
    activity_type: str
    duration_slots: int
    required_slots_per_period: int
    group_ids: list[int]
    teacher_ids: list[int]
    room_types: list[str]
    group_size: int


class TimeSlot(BaseModel):
    id: int
    day_of_week: int
    slot_index: int
    start_time: str = ""
    end_time: str = ""
    parity: str = "both"
    calendar_id: int = 0


class Room(BaseModel):
    id: int
    code: str
    title: str = ""
    capacity: int
    room_type: str
    features: list[str] = []
    active: bool = True


class Unavailability(BaseModel):
    id: int = 0
    entity_type: str  # teacher, room, group
    entity_id: int
    day_of_week: int
    slot_index: int
    parity: str = "both"
    reason: str = ""


class Preference(BaseModel):
    id: int = 0
    teacher_id: int
    day_of_week: int
    slot_index: int
    parity: str = "both"
    weight: int = 0


class PreferenceRule(BaseModel):
    teacher_id: int
    rule_type: str
    params: dict
    weight: int = 10
    is_active: bool = True


class LockedAssignment(BaseModel):
    activity_id: int
    day_of_week: int
    slot_index: int
    parity: str
    room_id: int


class Weights(BaseModel):
    w_windows: int = 10
    w_prefs: int = 5
    w_balance: int = 2


class SolveRequest(BaseModel):
    activities: list[Activity]
    time_slots: list[TimeSlot]
    rooms: list[Room]
    unavailabilities: list[Unavailability] = []
    preferences: list[Preference] = []
    preference_rules: list[PreferenceRule] = []
    weights: Weights = Weights()
    timeout_seconds: int = 420
    locked_assignments: list[LockedAssignment] = []


class AssignmentResult(BaseModel):
    activity_id: int
    day_of_week: int
    slot_index: int
    parity: str
    room_id: int


class Violation(BaseModel):
    activity_id: int | None = None
    code: str
    severity: str
    meta: dict[str, str] = {}


class SolveResponse(BaseModel):
    status: str  # feasible, optimal, infeasible
    assignments: list[AssignmentResult]
    violations: list[Violation] = []
    objective_value: float = 0.0
    solve_time_ms: int = 0
