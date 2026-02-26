<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Calendar;
use App\Models\Course;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleVersion;
use App\Models\SoftWeight;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ───────────────────────────────────────
        // UNIVERSITY 1: КПІ — Факультет інформатики та обчислювальної техніки
        // Реалістичний набір: 12 груп, 16 викладачів, ~70 activities
        // ───────────────────────────────────────
        $kpi = $this->createUniversity(
            name: 'КПІ ім. Ігоря Сікорського',
            slug: 'kpi',
            ownerName: 'Адмін КПІ',
            ownerEmail: 'admin@kpi.ua',
            teachers: [
                // Кафедра вищої математики
                ['name' => 'Іванов Петро Сергійович', 'email' => 'ivanov@kpi.ua', 'phone' => '+380671234567'],
                ['name' => 'Петренко Олена Миколаївна', 'email' => 'petrenko@kpi.ua', 'phone' => '+380672345678'],
                // Кафедра фізики
                ['name' => 'Сидоренко Андрій Вікторович', 'email' => 'sydorenko@kpi.ua', 'phone' => '+380673456789'],
                ['name' => 'Коваленко Марія Іванівна', 'email' => 'kovalenko@kpi.ua', 'phone' => '+380674567890'],
                // Кафедра програмування
                ['name' => 'Шевченко Дмитро Олегович', 'email' => 'shevchenko@kpi.ua', 'phone' => '+380675678901'],
                ['name' => 'Бондаренко Наталія Петрівна', 'email' => 'bondarenko@kpi.ua', 'phone' => '+380676789012'],
                ['name' => 'Мороз Віктор Анатолійович', 'email' => 'moroz@kpi.ua', 'phone' => '+380677890123'],
                // Кафедра системного аналізу
                ['name' => 'Ткаченко Ірина Юріївна', 'email' => 'tkachenko@kpi.ua', 'phone' => '+380678901234'],
                ['name' => 'Левченко Сергій Павлович', 'email' => 'levchenko@kpi.ua', 'phone' => '+380679012345'],
                // Кафедра комп'ютерних мереж
                ['name' => 'Кравченко Олександр Ігорович', 'email' => 'kravchenko@kpi.ua', 'phone' => '+380670123456'],
                ['name' => 'Мельник Юрій Олександрович', 'email' => 'melnyk@kpi.ua', 'phone' => '+380671122334'],
                // Кафедра ШІ та БД
                ['name' => 'Козлов Артем Дмитрович', 'email' => 'kozlov@kpi.ua', 'phone' => '+380672233445'],
                ['name' => 'Пономаренко Тетяна Сергіївна', 'email' => 'ponomarenko@kpi.ua', 'phone' => '+380673344556'],
                // Кафедра гуманітарних
                ['name' => 'Олійник Ганна Володимирівна', 'email' => 'oliynyk@kpi.ua', 'phone' => '+380674455667'],
                ['name' => 'Романенко Ігор Михайлович', 'email' => 'romanenko@kpi.ua', 'phone' => '+380675566778'],
                // Кафедра фізвиховання
                ['name' => 'Савченко Вадим Борисович', 'email' => 'savchenko@kpi.ua', 'phone' => '+380676677889'],
            ],
            courses: [
                ['name' => '1 курс', 'number' => 1],
                ['name' => '2 курс', 'number' => 2],
                ['name' => '3 курс', 'number' => 3],
                ['name' => '4 курс', 'number' => 4],
            ],
            groups: [
                // 1 курс — 3 групи
                ['name' => 'КН-11', 'course_number' => 1, 'code' => 'КН-11', 'size' => 25],
                ['name' => 'КН-12', 'course_number' => 1, 'code' => 'КН-12', 'size' => 28],
                ['name' => 'КН-13', 'course_number' => 1, 'code' => 'КН-13', 'size' => 26],
                // 2 курс — 3 групи
                ['name' => 'КН-21', 'course_number' => 2, 'code' => 'КН-21', 'size' => 22],
                ['name' => 'КН-22', 'course_number' => 2, 'code' => 'КН-22', 'size' => 24],
                ['name' => 'КН-23', 'course_number' => 2, 'code' => 'КН-23', 'size' => 20],
                // 3 курс — 3 групи
                ['name' => 'КН-31', 'course_number' => 3, 'code' => 'КН-31', 'size' => 20],
                ['name' => 'КН-32', 'course_number' => 3, 'code' => 'КН-32', 'size' => 18],
                ['name' => 'КН-33', 'course_number' => 3, 'code' => 'КН-33', 'size' => 22],
                // 4 курс — 3 групи
                ['name' => 'КН-41', 'course_number' => 4, 'code' => 'КН-41', 'size' => 15],
                ['name' => 'КН-42', 'course_number' => 4, 'code' => 'КН-42', 'size' => 16],
                ['name' => 'КН-43', 'course_number' => 4, 'code' => 'КН-43', 'size' => 14],
            ],
            rooms: [
                // Лекційні
                ['code' => '101', 'title' => 'Велика лекційна аудиторія', 'capacity' => 120, 'room_type' => 'lecture'],
                ['code' => '102', 'title' => 'Лекційна аудиторія 2', 'capacity' => 80, 'room_type' => 'lecture'],
                ['code' => '103', 'title' => 'Лекційна аудиторія 3', 'capacity' => 60, 'room_type' => 'lecture'],
                ['code' => '104', 'title' => 'Лекційна аудиторія 4', 'capacity' => 50, 'room_type' => 'lecture'],
                // Комп'ютерні лабораторії
                ['code' => '201', 'title' => 'Комп\'ютерна лабораторія 1', 'capacity' => 30, 'room_type' => 'pc'],
                ['code' => '202', 'title' => 'Комп\'ютерна лабораторія 2', 'capacity' => 25, 'room_type' => 'pc'],
                ['code' => '203', 'title' => 'Комп\'ютерна лабораторія 3', 'capacity' => 28, 'room_type' => 'pc'],
                // Семінарні
                ['code' => '301', 'title' => 'Семінарна аудиторія 1', 'capacity' => 35, 'room_type' => 'seminar'],
                ['code' => '302', 'title' => 'Семінарна аудиторія 2', 'capacity' => 30, 'room_type' => 'seminar'],
                ['code' => '303', 'title' => 'Семінарна аудиторія 3', 'capacity' => 28, 'room_type' => 'seminar'],
                ['code' => '304', 'title' => 'Семінарна аудиторія 4', 'capacity' => 25, 'room_type' => 'seminar'],
                // Лабораторії (фізика тощо)
                ['code' => '401', 'title' => 'Фізична лабораторія 1', 'capacity' => 20, 'room_type' => 'lab'],
                ['code' => '402', 'title' => 'Фізична лабораторія 2', 'capacity' => 18, 'room_type' => 'lab'],
                // Спортзал
                ['code' => 'СЗ-1', 'title' => 'Спортивний зал', 'capacity' => 50, 'room_type' => 'gym'],
            ],
            calendarName: 'Осінній семестр 2026/2027',
            calendarStart: '2026-09-01',
            calendarEnd: '2027-01-15',
        );

        // ───────────────────────────────────────
        // UNIVERSITY 2: ЛНУ (менший для порівняння)
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
        $this->command->info('   КПІ: admin@kpi.ua / password (12 груп, 16 викладачів, ~70 activities)');
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
        $groupsByCourse = [];
        foreach ($groups as $g) {
            $courseNumber = $g['course_number'];
            unset($g['course_number']);
            $group = Group::withoutGlobalScopes()->create(array_merge($g, [
                'tenant_id' => $tid,
                'course_id' => $createdCourses[$courseNumber]->id,
                'active' => true,
            ]));
            $createdGroups[] = $group;
            $groupsByCourse[$courseNumber][] = $group;
        }

        // 6. Create Rooms
        $createdRooms = [];
        foreach ($rooms as $r) {
            $createdRooms[] = Room::withoutGlobalScopes()->create(array_merge($r, [
                'tenant_id' => $tid,
                'active' => true,
            ]));
        }

        // 7. Create Calendar + TimeSlots (with parity)
        $calendar = Calendar::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'name' => $calendarName,
            'start_date' => $calendarStart,
            'end_date' => $calendarEnd,
            'weeks' => 16,
            'parity_enabled' => true,
            'days_per_week' => 6,
            'slots_per_day' => 6,
            'slot_duration_minutes' => 90,
            'break_duration_minutes' => 10,
        ]);

        // Create time slots (6 days × 6 slots × 3 parities)
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
                foreach (['both', 'num', 'den'] as $parity) {
                    TimeSlot::withoutGlobalScopes()->create([
                        'tenant_id' => $tid,
                        'calendar_id' => $calendar->id,
                        'day_of_week' => $day,
                        'slot_index' => $slotIdx,
                        'start_time' => $start,
                        'end_time' => $end,
                        'parity' => $parity,
                        'enabled' => true,
                    ]);
                }
            }
        }

        // 8. Create Subjects & Activities
        // Realistic curriculum per course
        $curriculum = $this->getCurriculum();

        $createdSubjects = [];
        $createdActivities = [];
        $teacherIdx = 0;

        foreach ($curriculum as $courseNum => $courseSubjects) {
            if (!isset($groupsByCourse[$courseNum])) {
                continue;
            }
            $courseGroups = $groupsByCourse[$courseNum];

            foreach ($courseSubjects as $subjectData) {
                // Create subject
                $subject = Subject::withoutGlobalScopes()->create([
                    'name' => $subjectData['name'],
                    'type' => $subjectData['types'][0] ?? 'lecture',
                    'tenant_id' => $tid,
                    'teacher_id' => $createdTeachers[$teacherIdx % count($createdTeachers)]->id,
                ]);
                $createdSubjects[] = $subject;

                // Assign lecture teacher
                $lectureTeacher = $createdTeachers[$teacherIdx % count($createdTeachers)];
                $practiceTeacher = $createdTeachers[($teacherIdx + 1) % count($createdTeachers)];
                $teacherIdx++;

                // Create lecture activity (shared by all course groups)
                if (in_array('lecture', $subjectData['types'])) {
                    $activity = Activity::withoutGlobalScopes()->create([
                        'tenant_id' => $tid,
                        'subject_id' => $subject->id,
                        'calendar_id' => $calendar->id,
                        'title' => $subject->name . ' (Лекція)',
                        'activity_type' => 'lecture',
                        'duration_slots' => 1,
                        'required_slots_per_period' => $subjectData['lecture_slots'] ?? 1,
                    ]);

                    // All groups in the course attend the lecture
                    foreach ($courseGroups as $group) {
                        DB::table('activity_groups')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'group_id' => $group->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('activity_teachers')->insert([
                        'tenant_id' => $tid,
                        'activity_id' => $activity->id,
                        'teacher_id' => $lectureTeacher->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Assign room type
                    DB::table('activity_room_types')->insert([
                        'tenant_id' => $tid,
                        'activity_id' => $activity->id,
                        'room_type' => 'lecture',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $createdActivities[] = $activity;
                }

                // Create practice/lab per group
                foreach ($courseGroups as $group) {
                    if (in_array('practice', $subjectData['types'])) {
                        $activity = Activity::withoutGlobalScopes()->create([
                            'tenant_id' => $tid,
                            'subject_id' => $subject->id,
                            'calendar_id' => $calendar->id,
                            'title' => $subject->name . ' (Практика, ' . $group->code . ')',
                            'activity_type' => 'practice',
                            'duration_slots' => 1,
                            'required_slots_per_period' => $subjectData['practice_slots'] ?? 1,
                        ]);

                        DB::table('activity_groups')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'group_id' => $group->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('activity_teachers')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'teacher_id' => $practiceTeacher->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('activity_room_types')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'room_type' => 'seminar',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $createdActivities[] = $activity;
                    }

                    if (in_array('lab', $subjectData['types'])) {
                        $roomType = $subjectData['lab_room'] ?? 'pc';
                        $activity = Activity::withoutGlobalScopes()->create([
                            'tenant_id' => $tid,
                            'subject_id' => $subject->id,
                            'calendar_id' => $calendar->id,
                            'title' => $subject->name . ' (Лабораторна, ' . $group->code . ')',
                            'activity_type' => 'lab',
                            'duration_slots' => 1,
                            'required_slots_per_period' => $subjectData['lab_slots'] ?? 1,
                        ]);

                        DB::table('activity_groups')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'group_id' => $group->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('activity_teachers')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'teacher_id' => $practiceTeacher->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('activity_room_types')->insert([
                            'tenant_id' => $tid,
                            'activity_id' => $activity->id,
                            'room_type' => $roomType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $createdActivities[] = $activity;
                    }
                }
            }
        }

        // 9. Create SoftWeights
        SoftWeight::withoutGlobalScopes()->create([
            'tenant_id' => $tid,
            'w_windows' => 10,
            'w_prefs' => 5,
            'w_balance' => 2,
        ]);

        // 10. Create Teacher Unavailabilities (some teachers not available on certain days/slots)
        $this->createUnavailabilities($tid, $calendar->id, $createdTeachers);

        // 11. Create Teacher Preferences
        $this->createPreferences($tid, $createdTeachers);

        // 12. Create Preference Rules
        $this->createPreferenceRules($tid, $createdTeachers);

        return compact('tenant', 'owner', 'createdTeachers', 'createdCourses', 'createdGroups', 'calendar');
    }

    /**
     * Realistic curriculum: subjects with lecture, practice, and lab components per course.
     */
    private function getCurriculum(): array
    {
        return [
            // 1 course — fundamental subjects
            1 => [
                ['name' => 'Вища математика', 'types' => ['lecture', 'practice'], 'lecture_slots' => 2, 'practice_slots' => 1],
                ['name' => 'Програмування (С++)', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 2, 'lab_room' => 'pc'],
                ['name' => 'Фізика', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'lab'],
                ['name' => 'Дискретна математика', 'types' => ['lecture', 'practice'], 'lecture_slots' => 1, 'practice_slots' => 1],
                ['name' => 'Англійська мова', 'types' => ['practice'], 'practice_slots' => 2],
                ['name' => 'Фізичне виховання', 'types' => ['practice'], 'practice_slots' => 1],
                ['name' => 'Історія України', 'types' => ['lecture'], 'lecture_slots' => 1],
            ],
            // 2 course — core CS subjects
            2 => [
                ['name' => 'Алгоритми та структури даних', 'types' => ['lecture', 'practice', 'lab'], 'lecture_slots' => 1, 'practice_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'ООП (Java)', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 2, 'lab_room' => 'pc'],
                ['name' => 'Бази даних', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Теорія ймовірностей', 'types' => ['lecture', 'practice'], 'lecture_slots' => 1, 'practice_slots' => 1],
                ['name' => 'Операційні системи', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Англійська мова (проф.)', 'types' => ['practice'], 'practice_slots' => 1],
            ],
            // 3 course — advanced subjects
            3 => [
                ['name' => 'Комп\'ютерні мережі', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Штучний інтелект', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Веб-розробка', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 2, 'lab_room' => 'pc'],
                ['name' => 'Чисельні методи', 'types' => ['lecture', 'practice'], 'lecture_slots' => 1, 'practice_slots' => 1],
                ['name' => 'Системне програмування', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Проєктування ПЗ', 'types' => ['lecture', 'practice'], 'lecture_slots' => 1, 'practice_slots' => 1],
            ],
            // 4 course — specialization
            4 => [
                ['name' => 'Розподілені системи', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Машинне навчання', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 2, 'lab_room' => 'pc'],
                ['name' => 'Кібербезпека', 'types' => ['lecture', 'practice'], 'lecture_slots' => 1, 'practice_slots' => 1],
                ['name' => 'DevOps', 'types' => ['lecture', 'lab'], 'lecture_slots' => 1, 'lab_slots' => 1, 'lab_room' => 'pc'],
                ['name' => 'Дипломний проєкт', 'types' => ['practice'], 'practice_slots' => 2],
            ],
        ];
    }

    /**
     * Create teacher unavailabilities — certain days/slots blocked.
     */
    private function createUnavailabilities(string $tid, int $calendarId, array $teachers): void
    {
        $unavailabilities = [
            // Teacher 0: not available on Saturdays
            [0, 6, 1], [0, 6, 2], [0, 6, 3], [0, 6, 4], [0, 6, 5], [0, 6, 6],
            // Teacher 1: not available Monday mornings (slots 1-2)
            [1, 1, 1], [1, 1, 2],
            // Teacher 2: not available Wednesday afternoons (slots 4-6)
            [2, 3, 4], [2, 3, 5], [2, 3, 6],
            // Teacher 3: not available Fridays
            [3, 5, 1], [3, 5, 2], [3, 5, 3], [3, 5, 4], [3, 5, 5], [3, 5, 6],
            // Teacher 5: not available evenings (slot 6 every day)
            [5, 1, 6], [5, 2, 6], [5, 3, 6], [5, 4, 6], [5, 5, 6],
            // Teacher 7: not available Thursday mornings
            [7, 4, 1], [7, 4, 2],
            // Teacher 9: not available Tuesday
            [9, 2, 1], [9, 2, 2], [9, 2, 3], [9, 2, 4], [9, 2, 5], [9, 2, 6],
        ];

        foreach ($unavailabilities as [$teacherIdx, $day, $slot]) {
            if (!isset($teachers[$teacherIdx])) continue;
            DB::table('teacher_unavailability')->insert([
                'tenant_id' => $tid,
                'teacher_id' => $teachers[$teacherIdx]->id,
                'calendar_id' => $calendarId,
                'day_of_week' => $day,
                'slot_index' => $slot,
                'parity' => 'both',
                'reason' => 'Недоступний',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create teacher preferences — preferred/avoided time slots.
     */
    private function createPreferences(string $tid, array $teachers): void
    {
        $prefs = [
            // Teacher 0 prefers morning (positive weight for slots 1-2)
            [0, 1, 1, 5], [0, 1, 2, 5], [0, 2, 1, 5], [0, 2, 2, 5],
            // Teacher 0 avoids evening (negative weight for slots 5-6)
            [0, 1, 5, -8], [0, 1, 6, -8], [0, 2, 5, -8], [0, 2, 6, -8],
            // Teacher 2 prefers afternoon
            [2, 1, 3, 3], [2, 1, 4, 3], [2, 2, 3, 3], [2, 2, 4, 3],
            // Teacher 4 avoids first slot
            [4, 1, 1, -10], [4, 2, 1, -10], [4, 3, 1, -10], [4, 4, 1, -10], [4, 5, 1, -10],
            // Teacher 6 prefers Tuesday and Thursday
            [6, 2, 2, 4], [6, 2, 3, 4], [6, 4, 2, 4], [6, 4, 3, 4],
        ];

        foreach ($prefs as [$teacherIdx, $day, $slot, $weight]) {
            if (!isset($teachers[$teacherIdx])) continue;
            DB::table('teacher_preferences')->insert([
                'tenant_id' => $tid,
                'teacher_id' => $teachers[$teacherIdx]->id,
                'day_of_week' => $day,
                'slot_index' => $slot,
                'parity' => 'both',
                'weight' => $weight,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create preference rules (new system) for teachers.
     */
    private function createPreferenceRules(string $tid, array $teachers): void
    {
        $rules = [
            // Teacher 0: max 4 hours per day
            [0, 'max_hours_per_day', ['max_hours' => 4], 8],
            // Teacher 1: prefers starting no earlier than slot 2
            [1, 'min_start_slot', ['min_slot' => 2], 6],
            // Teacher 3: max 3 hours per day
            [3, 'max_hours_per_day', ['max_hours' => 3], 10],
            // Teacher 5: prefers to finish by slot 4
            [5, 'max_end_slot', ['max_slot' => 4], 7],
            // Teacher 7: prefers Monday slot 3
            [7, 'preferred_slot', ['day_of_week' => 1, 'slot_index' => 3], 5],
            // Teacher 8: prefers Wednesday slot 2
            [8, 'preferred_slot', ['day_of_week' => 3, 'slot_index' => 2], 5],
            // Teacher 10: max 5 hours per day
            [10, 'max_hours_per_day', ['max_hours' => 5], 4],
            // Teacher 11: Saturday unavailable (via rule)
            [11, 'unavailable_day', ['day_of_week' => 6], 10],
            // Teacher 12: prefers starting from slot 2
            [12, 'min_start_slot', ['min_slot' => 2], 5],
            // Teacher 14: max end slot 5
            [14, 'max_end_slot', ['max_slot' => 5], 6],
        ];

        foreach ($rules as [$teacherIdx, $ruleType, $params, $weight]) {
            if (!isset($teachers[$teacherIdx])) continue;
            DB::table('teacher_preference_rules')->insert([
                'tenant_id' => $tid,
                'teacher_id' => $teachers[$teacherIdx]->id,
                'rule_type' => $ruleType,
                'params' => json_encode($params),
                'weight' => $weight,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
