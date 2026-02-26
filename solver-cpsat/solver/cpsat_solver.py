import time
from collections import defaultdict

from ortools.sat.python import cp_model

from .models import (
    AssignmentResult,
    SolveRequest,
    SolveResponse,
    Violation,
)


def solve(request: SolveRequest) -> SolveResponse:
    start_time = time.time()
    model = cp_model.CpModel()

    activities = request.activities
    time_slots = request.time_slots
    rooms = request.rooms
    raw_weights = request.weights

    # Normalize weights: CP-SAT requires integers, scale floats by ×10
    class IntWeights:
        def __init__(self, w):
            self.w_windows = int(w.w_windows * 10)
            self.w_prefs = int(w.w_prefs * 10)
            self.w_balance = int(w.w_balance * 10)

    weights = IntWeights(raw_weights)

    if not activities or not time_slots or not rooms:
        return SolveResponse(status="infeasible", assignments=[], solve_time_ms=0)

    # Build lookup structures
    unavail_set = _build_unavailability_set(request.unavailabilities)
    locked_set = _build_locked_set(request.locked_assignments)

    # Build teacher -> activities mapping
    teacher_activities: dict[int, list[int]] = defaultdict(list)
    group_activities: dict[int, list[int]] = defaultdict(list)
    for ai, act in enumerate(activities):
        for tid in act.teacher_ids:
            teacher_activities[tid].append(ai)
        for gid in act.group_ids:
            group_activities[gid].append(ai)

    # Build preference rules by teacher
    rules_by_teacher: dict[int, list] = defaultdict(list)
    for rule in request.preference_rules:
        if rule.is_active:
            rules_by_teacher[rule.teacher_id].append(rule)

    # ---- DECISION VARIABLES ----
    # x[ai][si][ri] = 1 if activity ai is assigned to slot si in room ri
    x: dict[tuple[int, int, int], cp_model.IntVar] = {}
    valid_combos: dict[int, list[tuple[int, int]]] = defaultdict(list)  # ai -> [(si, ri)]

    for ai, act in enumerate(activities):
        compatible_rooms = _get_compatible_rooms(rooms, act)
        for si, slot in enumerate(time_slots):
            # Check unavailabilities
            if _is_unavailable(act, slot, unavail_set):
                continue

            for ri, room in enumerate(compatible_rooms):
                # Check room unavailability
                room_key = ("room", room.id, slot.day_of_week, slot.slot_index, slot.parity)
                if room_key in unavail_set:
                    continue

                var = model.new_bool_var(f"x_{ai}_{si}_{ri}")
                x[(ai, si, ri)] = var
                valid_combos[ai].append((si, ri))

                # Fix locked assignments
                lock_key = (act.id, slot.day_of_week, slot.slot_index, slot.parity, room.id)
                if lock_key in locked_set:
                    model.add(var == 1)

    # Map from activity index to its list of compatible rooms
    act_compatible_rooms: dict[int, list] = {}
    for ai, act in enumerate(activities):
        act_compatible_rooms[ai] = _get_compatible_rooms(rooms, act)

    # ---- HARD CONSTRAINTS ----

    # 1. Each activity placed exactly required_slots_per_period times
    for ai, act in enumerate(activities):
        vars_for_activity = [x[(ai, si, ri)] for si, ri in valid_combos[ai] if (ai, si, ri) in x]
        if vars_for_activity:
            model.add(sum(vars_for_activity) == act.required_slots_per_period)
        # If no valid vars, it's infeasible for this activity

    # 2. No room double-booking per time slot
    slot_room_vars: dict[tuple[int, int], list] = defaultdict(list)
    for (ai, si, ri), var in x.items():
        global_room = act_compatible_rooms[ai][ri]
        slot_room_vars[(si, global_room.id)].append(var)
    for key, vars_list in slot_room_vars.items():
        if len(vars_list) > 1:
            model.add(sum(vars_list) <= 1)

    # 3. No teacher double-booking per time slot
    slot_teacher_vars: dict[tuple[int, int], list] = defaultdict(list)
    for (ai, si, ri), var in x.items():
        act = activities[ai]
        for tid in act.teacher_ids:
            slot_teacher_vars[(si, tid)].append(var)
    for key, vars_list in slot_teacher_vars.items():
        if len(vars_list) > 1:
            model.add(sum(vars_list) <= 1)

    # 4. No group double-booking per time slot
    slot_group_vars: dict[tuple[int, int], list] = defaultdict(list)
    for (ai, si, ri), var in x.items():
        act = activities[ai]
        for gid in act.group_ids:
            slot_group_vars[(si, gid)].append(var)
    for key, vars_list in slot_group_vars.items():
        if len(vars_list) > 1:
            model.add(sum(vars_list) <= 1)

    # 5. Preference rules as hard constraints (unavailable_day, unavailable_slot)
    for tid, rules in rules_by_teacher.items():
        for rule in rules:
            if rule.rule_type == "unavailable_day":
                day = rule.params.get("day_of_week")
                if day is not None:
                    for ai in teacher_activities.get(tid, []):
                        for si, ri in valid_combos[ai]:
                            if time_slots[si].day_of_week == int(day) and (ai, si, ri) in x:
                                model.add(x[(ai, si, ri)] == 0)

            elif rule.rule_type == "unavailable_slot":
                day = rule.params.get("day_of_week")
                slot_idx = rule.params.get("slot_index")
                if day is not None and slot_idx is not None:
                    for ai in teacher_activities.get(tid, []):
                        for si, ri in valid_combos[ai]:
                            s = time_slots[si]
                            if s.day_of_week == int(day) and s.slot_index == int(slot_idx) and (ai, si, ri) in x:
                                model.add(x[(ai, si, ri)] == 0)

    # ---- SOFT OBJECTIVES ----
    penalties = []

    # --- Window penalties (w_windows) ---
    if weights.w_windows > 0:
        all_days = sorted(set(s.day_of_week for s in time_slots))
        all_slot_indices = sorted(set(s.slot_index for s in time_slots))
        all_parities = sorted(set(s.parity for s in time_slots))

        effective_parities = ['both']
        if 'num' in all_parities or 'den' in all_parities:
            effective_parities = ['num', 'den']

        def _add_gap_penalties(entity_activities, prefix):
            """Add window gap penalties for a set of entity (group/teacher) activities."""
            for eid, act_indices in entity_activities.items():
                for day in all_days:
                    for eff_parity in effective_parities:
                        # Collect decision vars per slot_index
                        day_vars: dict[int, list] = defaultdict(list)
                        for ai in act_indices:
                            for si, ri in valid_combos[ai]:
                                s = time_slots[si]
                                if s.day_of_week != day or (ai, si, ri) not in x:
                                    continue
                                if s.parity == eff_parity or s.parity == 'both':
                                    day_vars[s.slot_index].append(x[(ai, si, ri)])

                        if len(day_vars) < 2:
                            continue

                        # has_class[slot_idx] = bool var, 1 if any class assigned at this slot
                        has_class = {}
                        for slot_idx in all_slot_indices:
                            if slot_idx in day_vars:
                                hc = model.new_bool_var(f"{prefix}c_{eid}_{day}_{eff_parity}_{slot_idx}")
                                model.add_max_equality(hc, day_vars[slot_idx])
                                has_class[slot_idx] = hc

                        # Detect gaps: an empty slot between two occupied slots
                        # Must handle BOTH cases:
                        #   a) slot NOT in has_class (no valid combos = always empty)
                        #   b) slot IN has_class but has_class=0 (solver chose not to place here)
                        for i, slot_idx in enumerate(all_slot_indices):
                            # Collect potential occupied slots before and after
                            before_vars = [has_class[s] for s in all_slot_indices[:i] if s in has_class]
                            after_vars = [has_class[s] for s in all_slot_indices[i + 1:] if s in has_class]

                            if not before_vars or not after_vars:
                                continue

                            has_before = model.new_bool_var(f"{prefix}b_{eid}_{day}_{eff_parity}_{slot_idx}")
                            has_after = model.new_bool_var(f"{prefix}a_{eid}_{day}_{eff_parity}_{slot_idx}")
                            model.add_max_equality(has_before, before_vars)
                            model.add_max_equality(has_after, after_vars)

                            gap = model.new_bool_var(f"gap_{prefix}_{eid}_{day}_{eff_parity}_{slot_idx}")

                            if slot_idx not in has_class:
                                # Case a: always empty, gap iff occupied before AND after
                                model.add_bool_and([has_before, has_after]).only_enforce_if(gap)
                                model.add_bool_or([has_before.negated(), has_after.negated()]).only_enforce_if(gap.negated())
                            else:
                                # Case b: gap iff NOT occupied here AND occupied before AND after
                                not_here = has_class[slot_idx].negated()
                                model.add_bool_and([not_here, has_before, has_after]).only_enforce_if(gap)
                                model.add_bool_or([has_class[slot_idx], has_before.negated(), has_after.negated()]).only_enforce_if(gap.negated())

                            penalties.append(gap * weights.w_windows)

        _add_gap_penalties(group_activities, "g")
        _add_gap_penalties(teacher_activities, "t")

    # --- Preference penalties (w_prefs) ---
    if weights.w_prefs > 0:
        # Legacy preferences (teacher_preferences table)
        for pref in request.preferences:
            if pref.weight == 0:
                continue
            for ai in teacher_activities.get(pref.teacher_id, []):
                for si, ri in valid_combos[ai]:
                    s = time_slots[si]
                    if s.day_of_week == pref.day_of_week and s.slot_index == pref.slot_index:
                        if _parity_matches(pref.parity, s.parity) and (ai, si, ri) in x:
                            if pref.weight < 0:
                                # Negative = avoid: penalize if assigned
                                penalties.append(x[(ai, si, ri)] * abs(pref.weight) * weights.w_prefs)

        # Preference rules (new system)
        for tid, rules in rules_by_teacher.items():
            for rule in rules:
                if rule.rule_type == "preferred_slot":
                    # Bonus for using preferred slot (penalize NOT using it)
                    day = rule.params.get("day_of_week")
                    slot_idx = rule.params.get("slot_index")
                    if day is None or slot_idx is None:
                        continue
                    for ai in teacher_activities.get(tid, []):
                        pref_vars = []
                        for si, ri in valid_combos[ai]:
                            s = time_slots[si]
                            if s.day_of_week == int(day) and s.slot_index == int(slot_idx) and (ai, si, ri) in x:
                                pref_vars.append(x[(ai, si, ri)])
                        # No penalty needed — CP-SAT will naturally use preferred slots
                        # But we can add a small bonus (negative penalty)
                        bonus = -(rule.weight * weights.w_prefs) // 10
                        if bonus != 0:
                            for v in pref_vars:
                                penalties.append(v * bonus)

                elif rule.rule_type == "min_start_slot":
                    min_slot = rule.params.get("min_slot")
                    day = rule.params.get("day_of_week")
                    if min_slot is None:
                        continue
                    for ai in teacher_activities.get(tid, []):
                        for si, ri in valid_combos[ai]:
                            s = time_slots[si]
                            if s.slot_index < int(min_slot) and (ai, si, ri) in x:
                                if day is None or s.day_of_week == int(day):
                                    penalties.append(x[(ai, si, ri)] * rule.weight * weights.w_prefs)

                elif rule.rule_type == "max_end_slot":
                    max_slot = rule.params.get("max_slot")
                    day = rule.params.get("day_of_week")
                    if max_slot is None:
                        continue
                    for ai in teacher_activities.get(tid, []):
                        for si, ri in valid_combos[ai]:
                            s = time_slots[si]
                            if s.slot_index > int(max_slot) and (ai, si, ri) in x:
                                if day is None or s.day_of_week == int(day):
                                    penalties.append(x[(ai, si, ri)] * rule.weight * weights.w_prefs)

                elif rule.rule_type == "max_hours_per_day":
                    max_hours = rule.params.get("max_hours")
                    if max_hours is None:
                        continue
                    all_days_set = sorted(set(s.day_of_week for s in time_slots))
                    for day in all_days_set:
                        day_load = []
                        for ai in teacher_activities.get(tid, []):
                            for si, ri in valid_combos[ai]:
                                if time_slots[si].day_of_week == day and (ai, si, ri) in x:
                                    day_load.append(x[(ai, si, ri)])
                        if day_load:
                            excess = model.new_int_var(0, 20, f"excess_{tid}_{day}")
                            load_sum = model.new_int_var(0, 20, f"load_{tid}_{day}")
                            model.add(load_sum == sum(day_load))
                            model.add(excess >= load_sum - int(max_hours))
                            model.add(excess >= 0)
                            penalties.append(excess * rule.weight * weights.w_prefs)

    # --- Balance penalties (w_balance) ---
    if weights.w_balance > 0:
        all_days = sorted(set(s.day_of_week for s in time_slots))

        # Group balance
        for gid, act_indices in group_activities.items():
            day_loads = []
            for day in all_days:
                load_vars = []
                for ai in act_indices:
                    for si, ri in valid_combos[ai]:
                        if time_slots[si].day_of_week == day and (ai, si, ri) in x:
                            load_vars.append(x[(ai, si, ri)])
                if load_vars:
                    day_load = model.new_int_var(0, 20, f"gl_{gid}_{day}")
                    model.add(day_load == sum(load_vars))
                    day_loads.append(day_load)

            if len(day_loads) >= 2:
                max_load = model.new_int_var(0, 20, f"gmax_{gid}")
                min_load = model.new_int_var(0, 20, f"gmin_{gid}")
                model.add_max_equality(max_load, day_loads)
                model.add_min_equality(min_load, day_loads)
                diff = model.new_int_var(0, 20, f"gdiff_{gid}")
                model.add(diff == max_load - min_load)
                penalties.append(diff * weights.w_balance)

        # Teacher balance
        for tid, act_indices in teacher_activities.items():
            day_loads = []
            for day in all_days:
                load_vars = []
                for ai in act_indices:
                    for si, ri in valid_combos[ai]:
                        if time_slots[si].day_of_week == day and (ai, si, ri) in x:
                            load_vars.append(x[(ai, si, ri)])
                if load_vars:
                    day_load = model.new_int_var(0, 20, f"tl_{tid}_{day}")
                    model.add(day_load == sum(load_vars))
                    day_loads.append(day_load)

            if len(day_loads) >= 2:
                max_load = model.new_int_var(0, 20, f"tmax_{tid}")
                min_load = model.new_int_var(0, 20, f"tmin_{tid}")
                model.add_max_equality(max_load, day_loads)
                model.add_min_equality(min_load, day_loads)
                diff = model.new_int_var(0, 20, f"tdiff_{tid}")
                model.add(diff == max_load - min_load)
                penalties.append(diff * weights.w_balance)

    # ---- OBJECTIVE ----
    if penalties:
        model.minimize(sum(penalties))

    # ---- SOLVE ----
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = request.timeout_seconds
    solver.parameters.num_workers = 8

    status = solver.solve(model)

    # ---- EXTRACT RESULTS ----
    assignments = []
    violations = []

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        for (ai, si, ri), var in x.items():
            if solver.value(var) == 1:
                act = activities[ai]
                slot = time_slots[si]
                room = act_compatible_rooms[ai][ri]
                assignments.append(
                    AssignmentResult(
                        activity_id=act.id,
                        day_of_week=slot.day_of_week,
                        slot_index=slot.slot_index,
                        parity=slot.parity,
                        room_id=room.id,
                    )
                )

        # Check for unplaced activities
        placed_counts: dict[int, int] = defaultdict(int)
        for a in assignments:
            placed_counts[a.activity_id] += 1

        for act in activities:
            placed = placed_counts.get(act.id, 0)
            if placed < act.required_slots_per_period:
                violations.append(
                    Violation(
                        activity_id=act.id,
                        code="UNPLACED_ACTIVITY",
                        severity="hard",
                        meta={"placed": str(placed), "required": str(act.required_slots_per_period)},
                    )
                )

        result_status = "optimal" if status == cp_model.OPTIMAL else "feasible"
    else:
        result_status = "infeasible"

    elapsed_ms = int((time.time() - start_time) * 1000)

    return SolveResponse(
        status=result_status,
        assignments=assignments,
        violations=violations,
        objective_value=solver.objective_value if status in (cp_model.OPTIMAL, cp_model.FEASIBLE) else 0,
        solve_time_ms=elapsed_ms,
    )


def _get_compatible_rooms(rooms, activity) -> list:
    compatible = []
    for room in rooms:
        if room.capacity < activity.group_size:
            continue
        if not activity.room_types:
            compatible.append(room)
            continue
        for rt in activity.room_types:
            if rt == room.room_type or (rt == "lab" and room.room_type == "pc"):
                compatible.append(room)
                break
    return compatible


def _build_unavailability_set(unavailabilities) -> set:
    result = set()
    for u in unavailabilities:
        result.add((u.entity_type, u.entity_id, u.day_of_week, u.slot_index, u.parity))
        if u.parity == "both":
            result.add((u.entity_type, u.entity_id, u.day_of_week, u.slot_index, "num"))
            result.add((u.entity_type, u.entity_id, u.day_of_week, u.slot_index, "den"))
    return result


def _build_locked_set(locked_assignments) -> set:
    return {
        (la.activity_id, la.day_of_week, la.slot_index, la.parity, la.room_id)
        for la in locked_assignments
    }


def _is_unavailable(activity, slot, unavail_set) -> bool:
    for tid in activity.teacher_ids:
        key = ("teacher", tid, slot.day_of_week, slot.slot_index, slot.parity)
        if key in unavail_set:
            return True
    for gid in activity.group_ids:
        key = ("group", gid, slot.day_of_week, slot.slot_index, slot.parity)
        if key in unavail_set:
            return True
    return False


def _parity_matches(p1: str, p2: str) -> bool:
    if p1 == "both" or p2 == "both":
        return True
    return p1 == p2
