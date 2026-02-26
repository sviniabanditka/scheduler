<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Course;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\SoftWeight;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ───────────────────────────────────────
        // UNIVERSITY 1: КПІ
        // ───────────────────────────────────────
        $kpi = $this->createUniversity(
            name: 'КПІ ім. Ігоря Сікорського',
            slug: 'kpi',
            ownerName: 'Адмін КПІ',
            ownerEmail: 'admin@kpi.ua',
            teachers: [
                ['name' => 'Іванов Петро Сергійович', 'email' => 'ivanov@kpi.ua', 'phone' => '+380671234567'],
                ['name' => 'Петренко Олена Миколаївна', 'email' => 'petrenko@kpi.ua', 'phone' => '+380672345678'],
                ['name' => 'Сидоренко Андрій Вікторович', 'email' => 'sydorenko@kpi.ua', 'phone' => '+380673456789'],
                ['name' => 'Коваленко Марія Іванівна', 'email' => 'kovalenko@kpi.ua', 'phone' => '+380674567890'],
                ['name' => 'Шевченко Дмитро Олегович', 'email' => 'shevchenko@kpi.ua', 'phone' => '+380675678901'],
                ['name' => 'Бондаренко Наталія Петрівна', 'email' => 'bondarenko@kpi.ua', 'phone' => '+380676789012'],
                ['name' => 'Мороз Віктор Анатолійович', 'email' => 'moroz@kpi.ua', 'phone' => '+380677890123'],
                ['name' => 'Ткаченко Ірина Юріївна', 'email' => 'tkachenko@kpi.ua', 'phone' => '+380678901234'],
            ],
            courses: [
                ['name' => '1 курс', 'number' => 1],
                ['name' => '2 курс', 'number' => 2],
                ['name' => '3 курс', 'number' => 3],
                ['name' => '4 курс', 'number' => 4],
            ],
            groups: [
                ['name' => 'КН-11', 'course_number' => 1, 'code' => 'КН-11', 'size' => 25],
                ['name' => 'КН-12', 'course_number' => 1, 'code' => 'КН-12', 'size' => 28],
                ['name' => 'КН-21', 'course_number' => 2, 'code' => 'КН-21', 'size' => 22],
                ['name' => 'КН-22', 'course_number' => 2, 'code' => 'КН-22', 'size' => 24],
                ['name' => 'КН-31', 'course_number' => 3, 'code' => 'КН-31', 'size' => 20],
                ['name' => 'КН-32', 'course_number' => 3, 'code' => 'КН-32', 'size' => 18],
                ['name' => 'КН-41', 'course_number' => 4, 'code' => 'КН-41', 'size' => 15],
            ],
            subjects: [
                ['name' => 'Вища математика', 'type' => 'lecture'],
                ['name' => 'Програмування', 'type' => 'practice'],
                ['name' => 'Фізика', 'type' => 'lecture'],
                ['name' => 'Алгоритми та структури даних', 'type' => 'practice'],
                ['name' => 'Бази даних', 'type' => 'practice'],
                ['name' => 'Операційні системи', 'type' => 'lecture'],
                ['name' => 'Комп\'ютерні мережі', 'type' => 'lecture'],
                ['name' => 'Штучний інтелект', 'type' => 'practice'],
                ['name' => 'Веб-розробка', 'type' => 'practice'],
                ['name' => 'Дискретна математика', 'type' => 'lecture'],
            ],
            rooms: [
                ['code' => '101', 'title' => 'Велика лекційна аудиторія', 'capacity' => 120, 'room_type' => 'lecture'],
                ['code' => '102', 'title' => 'Лекційна аудиторія 2', 'capacity' => 80, 'room_type' => 'lecture'],
                ['code' => '201', 'title' => 'Комп\'ютерна лабораторія 1', 'capacity' => 30, 'room_type' => 'pc'],
                ['code' => '202', 'title' => 'Комп\'ютерна лабораторія 2', 'capacity' => 25, 'room_type' => 'pc'],
                ['code' => '301', 'title' => 'Семінарна аудиторія 1', 'capacity' => 35, 'room_type' => 'seminar'],
                ['code' => '302', 'title' => 'Семінарна аудиторія 2', 'capacity' => 30, 'room_type' => 'seminar'],
                ['code' => '401', 'title' => 'Фізична лабораторія', 'capacity' => 20, 'room_type' => 'lab'],
            ],
            calendarName: 'Осінній семестр 2026/2027',
            calendarStart: '2026-09-01',
            calendarEnd: '2027-01-15',
        );

        // ───────────────────────────────────────
        // UNIVERSITY 2: ЛНУ
        // ───────────────────────────────────────
        $lnu = $this->createUniversity(
            name: 'ЛНУ ім. Івана Франка',
            slug: 'lnu',
            ownerName: 'Адмін ЛНУ',
            ownerEmail: 'admin@lnu.ua',
            teachers: [
                ['name' => 'Мельник Олександр Іванович', 'email' => 'melnyk@lnu.ua', 'phone' => '+380631234567'],
                ['name' => 'Грищенко Тетяна Сергіївна', 'email' => 'gryshchenko@lnu.ua', 'phone' => '+380632345678'],
                ['name' => 'Пономаренко Роман Ігорович', 'email' => 'ponomarenko@lnu.ua', 'phone' => '+380633456789'],
                ['name' => 'Литвиненко Оксана Михайлівна', 'email' => 'lytvynenko@lnu.ua', 'phone' => '+380634567890'],
                ['name' => 'Захарченко Богдан Олександрович', 'email' => 'zakharchenko@lnu.ua', 'phone' => '+380635678901'],
                ['name' => 'Довженко Юлія Вадимівна', 'email' => 'dovzhenko@lnu.ua', 'phone' => '+380636789012'],
            ],
            courses: [
                ['name' => '1 курс ІМ', 'number' => 1],
                ['name' => '2 курс ІМ', 'number' => 2],
                ['name' => '3 курс ІМ', 'number' => 3],
            ],
            groups: [
                ['name' => 'ПМІ-11', 'course_number' => 1, 'code' => 'ПМІ-11', 'size' => 30],
                ['name' => 'ПМІ-12', 'course_number' => 1, 'code' => 'ПМІ-12', 'size' => 28],
                ['name' => 'ПМІ-21', 'course_number' => 2, 'code' => 'ПМІ-21', 'size' => 26],
                ['name' => 'ПМІ-31', 'course_number' => 3, 'code' => 'ПМІ-31', 'size' => 22],
                ['name' => 'ПМІ-32', 'course_number' => 3, 'code' => 'ПМІ-32', 'size' => 20],
            ],
            subjects: [
                ['name' => 'Математичний аналіз', 'type' => 'lecture'],
                ['name' => 'Лінійна алгебра', 'type' => 'lecture'],
                ['name' => 'Основи програмування', 'type' => 'practice'],
                ['name' => 'Теорія ймовірностей', 'type' => 'lecture'],
                ['name' => 'Чисельні методи', 'type' => 'practice'],
                ['name' => 'Системний аналіз', 'type' => 'lecture'],
                ['name' => 'Моделювання процесів', 'type' => 'practice'],
                ['name' => 'Англійська мова', 'type' => 'practice'],
            ],
            rooms: [
                ['code' => 'A101', 'title' => 'Лекційний зал А', 'capacity' => 100, 'room_type' => 'lecture'],
                ['code' => 'A102', 'title' => 'Лекційний зал Б', 'capacity' => 60, 'room_type' => 'lecture'],
                ['code' => 'B201', 'title' => 'Комп\'ютерний клас 1', 'capacity' => 25, 'room_type' => 'pc'],
                ['code' => 'B202', 'title' => 'Комп\'ютерний клас 2', 'capacity' => 20, 'room_type' => 'pc'],
                ['code' => 'C301', 'title' => 'Аудиторія для семінарів', 'capacity' => 40, 'room_type' => 'seminar'],
            ],
            calendarName: 'Осінній семестр 2026',
            calendarStart: '2026-09-01',
            calendarEnd: '2027-01-20',
        );

        $this->command->info('✅ Демо-дата створена: 2 університети з повними наборами даних');
        $this->command->info('   КПІ: admin@kpi.ua / password');
        $this->command->info('   ЛНУ: admin@lnu.ua / password');
    }

    private function createUniversity(
        string $name,
        string $slug,
        string $ownerName,
        string $ownerEmail,
        array $teachers,
        array $courses,
        array $groups,
        array $subjects,
        array $rooms,
        string $calendarName,
        string $calendarStart,
        string $calendarEnd,
    ): array {
        // 1. Create Tenant
        $tenant = Tenant::create([
            'name' => $name,
            'subdomain' => $slug,
            'domain' => $slug . '.scheduler.local',
            'public_slug' => $slug,
            'is_active' => true,
            'settings' => [
                'days_per_week' => 6,
                'slots_per_day' => 6,
                'slot_duration' => 90,
                'language' => 'uk',
            ],
        ]);

        $tid = $tenant->id;

        // 2. Create Owner User
        $owner = User::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'name' => $ownerName,
            'email' => $ownerEmail,
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        // 3. Create Teachers
        $createdTeachers = [];
        foreach ($teachers as $t) {
            $createdTeachers[] = Teacher::withoutGlobalScopes()->create(array_merge($t, ['tenant_id' => $tid]));
        }

        // 4. Create Courses
        $createdCourses = [];
        foreach ($courses as $c) {
            $createdCourses[$c['number']] = Course::withoutGlobalScopes()->create(array_merge($c, ['tenant_id' => $tid]));
        }

        // 5. Create Groups
        $createdGroups = [];
        foreach ($groups as $g) {
            $courseNumber = $g['course_number'];
            unset($g['course_number']);
            $createdGroups[] = Group::withoutGlobalScopes()->create(array_merge($g, [
                'tenant_id' => $tid,
                'course_id' => $createdCourses[$courseNumber]->id,
                'active' => true,
            ]));
        }

        // 6. Create Rooms
        $createdRooms = [];
        foreach ($rooms as $r) {
            $createdRooms[] = Room::withoutGlobalScopes()->create(array_merge($r, [
                'tenant_id' => $tid,
                'active' => true,
            ]));
        }

        // 7. Create Calendar + TimeSlots
        $calendar = Calendar::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'name' => $calendarName,
            'start_date' => $calendarStart,
            'end_date' => $calendarEnd,
            'weeks' => 16,
            'parity_enabled' => false,
            'days_per_week' => 6,
            'slots_per_day' => 6,
            'slot_duration_minutes' => 90,
            'break_duration_minutes' => 10,
        ]);

        // Create time slots (6 days × 6 slots)
        $slotTimes = [
            1 => ['08:30', '10:05'],
            2 => ['10:15', '11:50'],
            3 => ['12:10', '13:45'],
            4 => ['13:55', '15:30'],
            5 => ['15:40', '17:15'],
            6 => ['17:25', '19:00'],
        ];

        foreach (range(1, 6) as $day) {
            foreach ($slotTimes as $slotIdx => [$start, $end]) {
                TimeSlot::withoutGlobalScopes()->create([
                    'tenant_id' => $tid,
                    'calendar_id' => $calendar->id,
                    'day_of_week' => $day,
                    'slot_index' => $slotIdx,
                    'start_time' => $start,
                    'end_time' => $end,
                    'parity' => 'both',
                    'enabled' => true,
                ]);
            }
        }

        // 8. Create Subjects (assign teachers round-robin)
        $createdSubjects = [];
        foreach ($subjects as $i => $s) {
            $teacher = $createdTeachers[$i % count($createdTeachers)];
            $createdSubjects[] = Subject::withoutGlobalScopes()->create(array_merge($s, [
                'tenant_id' => $tid,
                'teacher_id' => $teacher->id,
            ]));
        }

        // 9. Create Activities (each subject → 1-2 activities for first groups)
        $createdActivities = [];
        $activityTypes = ['lecture', 'practice', 'lab', 'seminar'];

        foreach ($createdSubjects as $si => $subject) {
            // Lecture activity
            $activity = Activity::withoutGlobalScopes()->create([
                'tenant_id' => $tid,
                'subject_id' => $subject->id,
                'calendar_id' => $calendar->id,
                'title' => $subject->name . ' (Лекція)',
                'activity_type' => 'lecture',
                'duration_slots' => 1,
                'required_slots_per_period' => 1,
            ]);

            // Assign groups (first 2 groups get this activity)
            $assignedGroups = array_slice($createdGroups, 0, min(2, count($createdGroups)));
            foreach ($assignedGroups as $group) {
                \DB::table('activity_groups')->insert([
                    'tenant_id' => $tid,
                    'activity_id' => $activity->id,
                    'group_id' => $group->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign teacher
            $teacher = $createdTeachers[$si % count($createdTeachers)];
            \DB::table('activity_teachers')->insert([
                'tenant_id' => $tid,
                'activity_id' => $activity->id,
                'teacher_id' => $teacher->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $createdActivities[] = $activity;

            // Practice activity (for every other subject)
            if ($si % 2 === 0) {
                $practiceActivity = Activity::withoutGlobalScopes()->create([
                    'tenant_id' => $tid,
                    'subject_id' => $subject->id,
                    'calendar_id' => $calendar->id,
                    'title' => $subject->name . ' (Практика)',
                    'activity_type' => 'practice',
                    'duration_slots' => 1,
                    'required_slots_per_period' => 1,
                ]);

                // Each group separately
                foreach (array_slice($createdGroups, 0, min(3, count($createdGroups))) as $group) {
                    \DB::table('activity_groups')->insert([
                        'tenant_id' => $tid,
                        'activity_id' => $practiceActivity->id,
                        'group_id' => $group->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                \DB::table('activity_teachers')->insert([
                    'tenant_id' => $tid,
                    'activity_id' => $practiceActivity->id,
                    'teacher_id' => $teacher->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdActivities[] = $practiceActivity;
            }
        }

        // 10. Create SoftWeights
        SoftWeight::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'w_windows' => 10,
            'w_prefs' => 5,
            'w_balance' => 2,
        ]);

        // 11. Create a ScheduleVersion + some assignments
        $version = ScheduleVersion::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'calendar_id' => $calendar->id,
            'name' => 'Розклад v1 (демо)',
            'status' => 'published',
            'created_by' => $owner->id,
            'version_number' => 1,
            'random_seed' => 42,
            'generation_params' => ['weights' => ['w_windows' => 10, 'w_prefs' => 5, 'w_balance' => 2]],
            'published_at' => now(),
        ]);

        // Create sample schedule assignments
        $roomsByType = collect($createdRooms)->groupBy('room_type');
        $lectureRooms = $roomsByType->get('lecture', collect())->values();
        $practiceRooms = $roomsByType->get('pc', $roomsByType->get('seminar', collect()))->values();
        $allRooms = collect($createdRooms)->values();

        $daySlot = 1; // start from Monday
        $slotIdx = 1;

        foreach ($createdActivities as $activity) {
            $room = $activity->activity_type === 'lecture'
                ? ($lectureRooms->isNotEmpty() ? $lectureRooms[$slotIdx % $lectureRooms->count()] : $allRooms[0])
                : ($practiceRooms->isNotEmpty() ? $practiceRooms[$slotIdx % $practiceRooms->count()] : $allRooms[0]);

            ScheduleAssignment::withoutGlobalScopes()->create([
                'tenant_id' => $tid,
                'schedule_version_id' => $version->id,
                'activity_id' => $activity->id,
                'day_of_week' => $daySlot,
                'slot_index' => $slotIdx,
                'parity' => 'both',
                'room_id' => $room->id,
                'locked' => false,
                'source' => 'manual',
            ]);

            $slotIdx++;
            if ($slotIdx > 5) {
                $slotIdx = 1;
                $daySlot++;
                if ($daySlot > 6) $daySlot = 1;
            }
        }

        return compact('tenant', 'owner', 'createdTeachers', 'createdCourses', 'createdGroups', 'calendar', 'version');
    }
}
