package types

import "time"

type TenantID string

type ScheduleRequest struct {
	TenantID       string  `json:"tenant_id"`
	CalendarID     int64   `json:"calendar_id"`
	ScheduleID     int64   `json:"schedule_id"`
	GroupIDs       []int64 `json:"group_ids,omitempty"`
	TeacherIDs     []int64 `json:"teacher_ids,omitempty"`
	Days           []int32 `json:"days,omitempty"`
	Scope          Scope   `json:"scope"`
	Weights        Weights `json:"weights"`
	TimeoutSeconds int32   `json:"timeout_seconds"`
}

type Weights struct {
	WWindows int32 `json:"w_windows"`
	WPrefs   int32 `json:"w_prefs"`
	WBalance int32 `json:"w_balance"`
}

type Scope struct {
	GroupIDs   []int32 `json:"group_ids"`
	TeacherIDs []int32 `json:"teacher_ids"`
	Days       []int32 `json:"days"`
}

type ProgressEvent struct {
	Percent int32  `json:"percent"`
	Stage   string `json:"stage"`
	Note    string `json:"note"`
}

type ScheduleResult struct {
	Status          ResultStatus `json:"status"`
	AssignmentIDs   []int64      `json:"assignment_ids"`
	Violations      []Violation  `json:"violations"`
	TotalViolations int32        `json:"total_violations"`
	ObjectiveValue  float64      `json:"objective_value"`
	SolveTimeMs     int64        `json:"solve_time_ms"`
}

type ResultStatus int32

const (
	ResultStatus_FEASIBLE   ResultStatus = 0
	ResultStatus_OPTIMAL    ResultStatus = 1
	ResultStatus_INFEASIBLE ResultStatus = 2
)

type Violation struct {
	ActivityID int64             `json:"activity_id"`
	Code       string            `json:"code"`
	Severity   string            `json:"severity"`
	Meta       map[string]string `json:"meta"`
}

type Activity struct {
	ID                     int64    `json:"id"`
	SubjectID              int64    `json:"subject_id"`
	SubjectName            string   `json:"subject_name"`
	ActivityType           string   `json:"activity_type"`
	DurationSlots          int32    `json:"duration_slots"`
	RequiredSlotsPerPeriod int32    `json:"required_slots_per_period"`
	GroupIDs               []int64  `json:"group_ids"`
	TeacherIDs             []int64  `json:"teacher_ids"`
	RoomTypes              []string `json:"room_types"`
	GroupSize              int32    `json:"group_size"`
}

type TimeSlot struct {
	ID         int64  `json:"id"`
	DayOfWeek  int32  `json:"day_of_week"`
	SlotIndex  int32  `json:"slot_index"`
	StartTime  string `json:"start_time"`
	EndTime    string `json:"end_time"`
	Parity     string `json:"parity"`
	CalendarID int64  `json:"calendar_id"`
}

type Room struct {
	ID       int64    `json:"id"`
	Code     string   `json:"code"`
	Title    string   `json:"title"`
	Capacity int32    `json:"capacity"`
	RoomType string   `json:"room_type"`
	Features []string `json:"features"`
	Active   bool     `json:"active"`
}

type Unavailability struct {
	ID         int64  `json:"id"`
	EntityType string `json:"entity_type"`
	EntityID   int64  `json:"entity_id"`
	DayOfWeek  int32  `json:"day_of_week"`
	SlotIndex  int32  `json:"slot_index"`
	Parity     string `json:"parity"`
	Reason     string `json:"reason"`
}

type Preference struct {
	ID        int64  `json:"id"`
	TeacherID int64  `json:"teacher_id"`
	DayOfWeek int32  `json:"day_of_week"`
	SlotIndex int32  `json:"slot_index"`
	Parity    string `json:"parity"`
	Weight    int32  `json:"weight"`
}

type Group struct {
	ID     int64  `json:"id"`
	Code   string `json:"code"`
	Title  string `json:"title"`
	Size   int32  `json:"size"`
	Active bool   `json:"active"`
}

type Teacher struct {
	ID       int64  `json:"id"`
	Name     string `json:"name"`
	Email    string `json:"email"`
	FullName string `json:"full_name"`
}

type ScheduleInput struct {
	TenantID         string
	CalendarID       int64
	ScheduleID       int64
	Scope            Scope
	Activities       []Activity
	TimeSlots        []TimeSlot
	Rooms            []Room
	Groups           []Group
	Teachers         []Teacher
	Unavailabilities []Unavailability
	Preferences      []Preference
	Weights          Weights
	ExistingSolution map[string]int64
}

type Assignment struct {
	ID         int64     `json:"id"`
	ScheduleID int64     `json:"schedule_id"`
	ActivityID int64     `json:"activity_id"`
	DayOfWeek  int32     `json:"day_of_week"`
	SlotIndex  int32     `json:"slot_index"`
	Parity     string    `json:"parity"`
	RoomID     int64     `json:"room_id"`
	Locked     bool      `json:"locked"`
	Source     string    `json:"source"`
	CreatedAt  time.Time `json:"created_at"`
}

type SolverConfig struct {
	TimeoutSeconds int32
	NumWorkers     int32
	Seed           int64
}

func NewSolverConfig() *SolverConfig {
	return &SolverConfig{
		TimeoutSeconds: 420,
		NumWorkers:     4,
		Seed:           time.Now().Unix(),
	}
}
