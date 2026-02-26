package db

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5/pgxpool"

	"scheduler/pkg/types"
)

type DB interface {
	Close() error
	GetScheduleInput(ctx context.Context, tenantID string, calendarID, scheduleID int64, scope types.Scope) (*types.ScheduleInput, error)
	SaveAssignments(ctx context.Context, tenantID string, scheduleID int64, assignments []types.Assignment) error
	SaveViolations(ctx context.Context, tenantID string, scheduleID int64, violations []types.Violation) error
	GetUnavailabilities(ctx context.Context, tenantID string, calendarID int64) ([]types.Unavailability, error)
	GetPreferences(ctx context.Context, tenantID string) ([]types.Preference, error)
}

type PostgresDB struct {
	pool *pgxpool.Pool
}

func NewPostgresDB(connStr string) (*PostgresDB, error) {
	config, err := pgxpool.ParseConfig(connStr)
	if err != nil {
		return nil, fmt.Errorf("failed to parse config: %w", err)
	}

	pool, err := pgxpool.NewWithConfig(context.Background(), config)
	if err != nil {
		return nil, fmt.Errorf("failed to create pool: %w", err)
	}

	if err := pool.Ping(context.Background()); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	return &PostgresDB{pool: pool}, nil
}

func (db *PostgresDB) Close() {
	db.pool.Close()
}

func (db *PostgresDB) GetScheduleInput(ctx context.Context, tenantID string, calendarID, scheduleID int64, scope types.Scope) (*types.ScheduleInput, error) {
	input := &types.ScheduleInput{
		TenantID:   tenantID,
		CalendarID: calendarID,
		ScheduleID: scheduleID,
		Scope:      scope,
	}

	activities, err := db.getActivities(ctx, tenantID, calendarID)
	if err != nil {
		return nil, fmt.Errorf("failed to get activities: %w", err)
	}
	input.Activities = activities

	timeSlots, err := db.getTimeSlots(ctx, tenantID, calendarID)
	if err != nil {
		return nil, fmt.Errorf("failed to get time slots: %w", err)
	}
	input.TimeSlots = timeSlots

	rooms, err := db.getRooms(ctx, tenantID)
	if err != nil {
		return nil, fmt.Errorf("failed to get rooms: %w", err)
	}
	input.Rooms = rooms

	groups, err := db.getGroups(ctx, tenantID)
	if err != nil {
		return nil, fmt.Errorf("failed to get groups: %w", err)
	}
	input.Groups = groups

	teachers, err := db.getTeachers(ctx, tenantID)
	if err != nil {
		return nil, fmt.Errorf("failed to get teachers: %w", err)
	}
	input.Teachers = teachers

	unavailabilities, err := db.GetUnavailabilities(ctx, tenantID, calendarID)
	if err != nil {
		return nil, fmt.Errorf("failed to get unavailabilities: %w", err)
	}
	input.Unavailabilities = unavailabilities

	preferences, err := db.GetPreferences(ctx, tenantID)
	if err != nil {
		return nil, fmt.Errorf("failed to get preferences: %w", err)
	}
	input.Preferences = preferences

	preferenceRules, err := db.GetPreferenceRules(ctx, tenantID)
	if err != nil {
		return nil, fmt.Errorf("failed to get preference rules: %w", err)
	}
	input.PreferenceRules = preferenceRules

	return input, nil
}

func (db *PostgresDB) getActivities(ctx context.Context, tenantID string, calendarID int64) ([]types.Activity, error) {
	query := `
		SELECT 
			a.id, a.subject_id, s.name, a.activity_type, a.duration_slots, a.required_slots_per_period,
			COALESCE(array_agg(DISTINCT ag.group_id) FILTER (WHERE ag.group_id IS NOT NULL), '{}'),
			COALESCE(array_agg(DISTINCT at.teacher_id) FILTER (WHERE at.teacher_id IS NOT NULL), '{}'),
			COALESCE(array_agg(DISTINCT art.room_type) FILTER (WHERE art.room_type IS NOT NULL), '{}'),
			MAX(g.size)
		FROM activities a
		JOIN subjects s ON s.id = a.subject_id AND s.tenant_id = a.tenant_id
		LEFT JOIN activity_groups ag ON ag.activity_id = a.id AND ag.tenant_id = a.tenant_id
		LEFT JOIN activity_teachers at ON at.activity_id = a.id AND at.tenant_id = a.tenant_id
		LEFT JOIN activity_room_types art ON art.activity_id = a.id AND art.tenant_id = a.tenant_id
		LEFT JOIN groups g ON g.id = ag.group_id AND g.tenant_id = a.tenant_id
		WHERE a.tenant_id = $1 AND a.calendar_id = $2
		GROUP BY a.id, s.name
	`

	rows, err := db.pool.Query(ctx, query, tenantID, calendarID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var activities []types.Activity
	for rows.Next() {
		var a types.Activity
		if err := rows.Scan(
			&a.ID, &a.SubjectID, &a.SubjectName, &a.ActivityType, &a.DurationSlots, &a.RequiredSlotsPerPeriod,
			&a.GroupIDs, &a.TeacherIDs, &a.RoomTypes, &a.GroupSize,
		); err != nil {
			return nil, err
		}
		activities = append(activities, a)
	}

	return activities, nil
}

func (db *PostgresDB) getTimeSlots(ctx context.Context, tenantID string, calendarID int64) ([]types.TimeSlot, error) {
	query := `
		SELECT id, day_of_week, slot_index, start_time::text, end_time::text, parity, calendar_id
		FROM time_slots
		WHERE tenant_id = $1 AND calendar_id = $2 AND enabled = true
		ORDER BY day_of_week, slot_index
	`

	rows, err := db.pool.Query(ctx, query, tenantID, calendarID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var slots []types.TimeSlot
	for rows.Next() {
		var s types.TimeSlot
		if err := rows.Scan(&s.ID, &s.DayOfWeek, &s.SlotIndex, &s.StartTime, &s.EndTime, &s.Parity, &s.CalendarID); err != nil {
			return nil, err
		}
		slots = append(slots, s)
	}

	return slots, nil
}

func (db *PostgresDB) getRooms(ctx context.Context, tenantID string) ([]types.Room, error) {
	query := `
		SELECT id, code, title, capacity, room_type, '[]'::jsonb, active
		FROM rooms
		WHERE tenant_id = $1 AND active = true
		ORDER BY code
	`

	rows, err := db.pool.Query(ctx, query, tenantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var rooms []types.Room
	for rows.Next() {
		var r types.Room
		if err := rows.Scan(&r.ID, &r.Code, &r.Title, &r.Capacity, &r.RoomType, &r.Features, &r.Active); err != nil {
			return nil, err
		}
		rooms = append(rooms, r)
	}

	return rooms, nil
}

func (db *PostgresDB) getGroups(ctx context.Context, tenantID string) ([]types.Group, error) {
	query := `
		SELECT id, COALESCE(code, name), name, COALESCE(size, 0), COALESCE(active, true)
		FROM groups
		WHERE tenant_id = $1 AND COALESCE(active, true) = true
		ORDER BY name
	`

	rows, err := db.pool.Query(ctx, query, tenantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var groups []types.Group
	for rows.Next() {
		var g types.Group
		if err := rows.Scan(&g.ID, &g.Code, &g.Title, &g.Size, &g.Active); err != nil {
			return nil, err
		}
		groups = append(groups, g)
	}

	return groups, nil
}

func (db *PostgresDB) getTeachers(ctx context.Context, tenantID string) ([]types.Teacher, error) {
	query := `
		SELECT id, name, email, name
		FROM teachers
		WHERE tenant_id = $1
		ORDER BY name
	`

	rows, err := db.pool.Query(ctx, query, tenantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var teachers []types.Teacher
	for rows.Next() {
		var t types.Teacher
		if err := rows.Scan(&t.ID, &t.Name, &t.Email, &t.FullName); err != nil {
			return nil, err
		}
		teachers = append(teachers, t)
	}

	return teachers, nil
}

func (db *PostgresDB) GetUnavailabilities(ctx context.Context, tenantID string, calendarID int64) ([]types.Unavailability, error) {
	query := `
		SELECT id, 'teacher', teacher_id, day_of_week, slot_index, parity, reason
		FROM teacher_unavailability
		WHERE tenant_id = $1 AND calendar_id = $2
		UNION ALL
		SELECT id, 'room', room_id, day_of_week, slot_index, parity, reason
		FROM room_unavailability
		WHERE tenant_id = $1 AND calendar_id = $2
		UNION ALL
		SELECT id, 'group', group_id, day_of_week, slot_index, parity, reason
		FROM group_unavailability
		WHERE tenant_id = $1 AND calendar_id = $2
	`

	rows, err := db.pool.Query(ctx, query, tenantID, calendarID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var unavails []types.Unavailability
	for rows.Next() {
		var u types.Unavailability
		if err := rows.Scan(&u.ID, &u.EntityType, &u.EntityID, &u.DayOfWeek, &u.SlotIndex, &u.Parity, &u.Reason); err != nil {
			return nil, err
		}
		unavails = append(unavails, u)
	}

	return unavails, nil
}

func (db *PostgresDB) GetPreferences(ctx context.Context, tenantID string) ([]types.Preference, error) {
	query := `
		SELECT id, teacher_id, day_of_week, slot_index, parity, weight
		FROM teacher_preferences
		WHERE tenant_id = $1
	`

	rows, err := db.pool.Query(ctx, query, tenantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var prefs []types.Preference
	for rows.Next() {
		var p types.Preference
		if err := rows.Scan(&p.ID, &p.TeacherID, &p.DayOfWeek, &p.SlotIndex, &p.Parity, &p.Weight); err != nil {
			return nil, err
		}
		prefs = append(prefs, p)
	}

	return prefs, nil
}

func (db *PostgresDB) GetPreferenceRules(ctx context.Context, tenantID string) ([]types.PreferenceRule, error) {
	query := `
		SELECT teacher_id, rule_type, params, weight, is_active
		FROM teacher_preference_rules
		WHERE tenant_id = $1 AND is_active = true
	`

	rows, err := db.pool.Query(ctx, query, tenantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var rules []types.PreferenceRule
	for rows.Next() {
		var r types.PreferenceRule
		if err := rows.Scan(&r.TeacherID, &r.RuleType, &r.Params, &r.Weight, &r.IsActive); err != nil {
			return nil, err
		}
		rules = append(rules, r)
	}

	return rules, nil
}

func (db *PostgresDB) SaveAssignments(ctx context.Context, tenantID string, scheduleID int64, assignments []types.Assignment) error {
	if len(assignments) == 0 {
		return nil
	}

	tx, err := db.pool.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer tx.Rollback(ctx)

	_, err = tx.Exec(ctx, `
		DELETE FROM schedule_assignments 
		WHERE tenant_id = $1 AND schedule_version_id = $2 AND locked = false
	`, tenantID, scheduleID)
	if err != nil {
		return fmt.Errorf("failed to delete existing assignments: %w", err)
	}

	for _, a := range assignments {
		_, err = tx.Exec(ctx, `
			INSERT INTO schedule_assignments (tenant_id, schedule_version_id, activity_id, day_of_week, slot_index, parity, room_id, locked, source)
			VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
		`, tenantID, scheduleID, a.ActivityID, a.DayOfWeek, a.SlotIndex, a.Parity, a.RoomID, a.Locked, a.Source)
		if err != nil {
			return fmt.Errorf("failed to insert assignment: %w", err)
		}
	}

	return tx.Commit(ctx)
}

func (db *PostgresDB) SaveViolations(ctx context.Context, tenantID string, scheduleID int64, violations []types.Violation) error {
	if len(violations) == 0 {
		return nil
	}

	tx, err := db.pool.Begin(ctx)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer tx.Rollback(ctx)

	_, err = tx.Exec(ctx, `
		DELETE FROM violations 
		WHERE tenant_id = $1 AND schedule_version_id = $2
	`, tenantID, scheduleID)
	if err != nil {
		return fmt.Errorf("failed to delete existing violations: %w", err)
	}

	for _, v := range violations {
		_, err = tx.Exec(ctx, `
			INSERT INTO violations (tenant_id, schedule_version_id, activity_id, code, severity, meta)
			VALUES ($1, $2, $3, $4, $5, $6)
		`, tenantID, scheduleID, v.ActivityID, v.Code, v.Severity, v.Meta)
		if err != nil {
			return fmt.Errorf("failed to insert violation: %w", err)
		}
	}

	return tx.Commit(ctx)
}
