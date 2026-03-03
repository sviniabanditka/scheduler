# Документація проекту "University Scheduler SaaS"

## Повна технічна документація дипломного проекту

---

## Зміст

1. [Загальний огляд проекту](#1-загальний-огляд-проекту)
2. [Архітектура системи](#2-архітектура-системи)
3. [Технологічний стек](#3-технологічний-стек)
4. [Docker-інфраструктура](#4-docker-інфраструктура)
5. [База даних](#5-база-даних)
6. [Мультитенантність](#6-мультитенантність)
7. [Система автентифікації та ролей](#7-система-автентифікації-та-ролей)
8. [Laravel-додаток: Моделі](#8-laravel-додаток-моделі)
9. [Laravel-додаток: Сервіси](#9-laravel-додаток-сервіси)
10. [Laravel-додаток: Контролери](#10-laravel-додаток-контролери)
11. [Laravel-додаток: Middleware](#11-laravel-додаток-middleware)
12. [Laravel-додаток: Провайдери](#12-laravel-додаток-провайдери)
13. [Laravel-додаток: Маршрутизація](#13-laravel-додаток-маршрутизація)
14. [Laravel-додаток: Jobs та Commands](#14-laravel-додаток-jobs-та-commands)
15. [Laravel-додаток: Policies](#15-laravel-додаток-policies)
16. [Filament адмін-панель: Конфігурація](#16-filament-адмін-панель-конфігурація)
17. [Filament адмін-панель: Ресурси](#17-filament-адмін-панель-ресурси)
18. [Filament адмін-панель: Сторінки](#18-filament-адмін-панель-сторінки)
19. [Filament адмін-панель: Віджети](#19-filament-адмін-панель-віджети)
20. [Go Solver сервіс: Архітектура](#20-go-solver-сервіс-архітектура)
21. [Go Solver сервіс: API](#21-go-solver-сервіс-api)
22. [Go Solver сервіс: Типи даних](#22-go-solver-сервіс-типи-даних)
23. [Go Solver сервіс: Рівень бази даних](#23-go-solver-сервіс-рівень-бази-даних)
24. [Go Solver сервіс: Жадібний алгоритм](#24-go-solver-сервіс-жадібний-алгоритм)
25. [Go Solver сервіс: Управління станом](#25-go-solver-сервіс-управління-станом)
26. [Go Solver сервіс: Об'єктивна функція](#26-go-solver-сервіс-обєктивна-функція)
27. [Go Solver сервіс: Імітація відпалу](#27-go-solver-сервіс-імітація-відпалу)
28. [Go Solver сервіс: Табу-пошук](#28-go-solver-сервіс-табу-пошук)
29. [Фронтенд: Blade-шаблони](#29-фронтенд-blade-шаблони)
30. [Потік генерації розкладу](#30-потік-генерації-розкладу)
31. [Публічний доступ до розкладу](#31-публічний-доступ-до-розкладу)
32. [Система перенесення занять](#32-система-перенесення-занять)
33. [Структура файлів проекту](#33-структура-файлів-проекту)
34. [Конфігурація середовища](#34-конфігурація-середовища)

---

## 1. Загальний огляд проекту

### 1.1. Призначення

"University Scheduler SaaS" — це багатокористувацька SaaS-платформа для автоматичного створення та управління розкладом навчальних занять у вищих навчальних закладах. Система дозволяє:

- Автоматично генерувати оптимальний розклад занять з урахуванням жорстких та м'яких обмежень
- Управляти навчальними даними: групами, викладачами, предметами, аудиторіями, курсами
- Підтримувати роботу кількох університетів (тенантів) в одній інсталяції
- Надавати публічний доступ до розкладу для студентів без необхідності авторизації
- Обробляти заявки викладачів на перенесення занять
- Версіонувати розклади з можливістю публікації та архівування

### 1.2. Основні функціональні можливості

1. **Мультитенантність** — кожен університет працює в ізольованому середовищі з власним піддоменом та публічним slug-посиланням
2. **Генерація розкладу** — три алгоритми оптимізації (жадібний, імітація відпалу, табу-пошук) в окремому Go-сервісі
3. **Адміністративна панель** — повнофункціональний інтерфейс управління на базі Filament 3
4. **Публічний розклад** — доступ до розкладу за унікальним посиланням без авторизації
5. **Кабінет викладача** — перегляд персонального розкладу, створення заявок на перенесення
6. **Система ролей** — п'ять рівнів доступу: owner, admin, planner, teacher, viewer
7. **Підтримка парності тижнів** — чисельник/знаменник для розкладів з чергуванням тижнів
8. **Версіонування розкладів** — чернетки, публікація, архівування з історією змін

### 1.3. Цільова аудиторія

- **Адміністратори ВНЗ** — повне управління системою та даними
- **Планувальники** — створення та редагування розкладу
- **Викладачі** — перегляд персонального розкладу, подача заявок на перенесення
- **Студенти** — перегляд публічного розкладу (без авторизації)

---

## 2. Архітектура системи

### 2.1. Загальна архітектура

Система побудована на мікросервісній архітектурі з двома основними компонентами:

1. **Laravel-додаток (PHP 8.2)** — веб-інтерфейс, бізнес-логіка, управління даними
2. **Go Solver сервіс** — алгоритми оптимізації розкладу

Обидва компоненти з'єднані через HTTP API та мають спільний доступ до бази даних PostgreSQL.

### 2.2. Схема взаємодії компонентів

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│                 │     │                  │     │                 │
│   Браузер       │────▶│    Nginx         │────▶│  Laravel App    │
│   (Користувач)  │     │    (порт 8080)   │     │  (PHP-FPM 9000) │
│                 │     │                  │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          │ HTTP POST
                                                          ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│                 │     │                  │     │                 │
│   Redis         │◀───▶│  Queue Worker    │     │   Go Solver     │
│   (порт 6379)   │     │  (Laravel)       │     │   (порт 8081)   │
│                 │     │                  │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          │ SQL
                                                          ▼
                                                 ┌─────────────────┐
                                                 │                 │
                                                 │   PostgreSQL    │
                                                 │   (порт 5432)   │
                                                 │                 │
                                                 └─────────────────┘
```

### 2.3. Потік даних

1. Користувач взаємодіє з системою через веб-браузер
2. Nginx виступає реверс-проксі та перенаправляє запити на PHP-FPM
3. Laravel-додаток обробляє бізнес-логіку та зберігає дані в PostgreSQL
4. При генерації розкладу Laravel створює Job, який відправляється у чергу Redis
5. Queue Worker обробляє Job та надсилає HTTP-запит до Go Solver
6. Go Solver читає навчальні дані безпосередньо з PostgreSQL
7. Після завершення оптимізації Solver записує результати (assignments, violations) назад у PostgreSQL
8. Laravel оновлює статус версії розкладу

### 2.4. Патерни проектування

- **Multi-Tenancy** — ізоляція даних через глобальний scope та автоматичне присвоєння tenant_id
- **Repository Pattern** — інкапсуляція SQL-запитів в окремий рівень бази даних (Go Solver)
- **Service Layer** — бізнес-логіка виділена в окремі сервіси (TenantManager, ScheduleGenerationService, RescheduleService)
- **Job Queue** — асинхронна генерація розкладу через чергу Redis
- **Version Control** — версіонування розкладів з підтримкою parent/child зв'язків
- **Policy-Based Authorization** — авторизація через Laravel Policies

---

## 3. Технологічний стек

### 3.1. Серверна частина (Backend)

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| PHP | 8.2 | Основна мова серверної частини |
| Laravel | 10.10+ | PHP-фреймворк для веб-додатків |
| Filament | 3.0+ | Адмін-панель для Laravel |
| Laravel Sanctum | 3.2+ | API-автентифікація |
| GuzzleHTTP | 7.2+ | HTTP-клієнт для зв'язку з Solver |

### 3.2. Сервіс оптимізації (Solver)

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| Go | 1.21 | Мова програмування Solver |
| pgx/v5 | 5.5.1 | PostgreSQL-драйвер для Go |
| puddle/v2 | — | Пул з'єднань до бази даних |

### 3.3. База даних та кешування

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| PostgreSQL | 16 (Alpine) | Реляційна база даних |
| Redis | 7 (Alpine) | Кешування, черги, сесії |

### 3.4. Інфраструктура

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| Docker | — | Контейнеризація |
| Docker Compose | — | Оркестрація сервісів |
| Nginx | Alpine | Реверс-проксі, веб-сервер |
| Make | — | Автоматизація команд |

### 3.5. Фронтенд

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| Tailwind CSS | CDN | CSS-фреймворк |
| Alpine.js | 3.x | Реактивність JavaScript |
| Blade | Laravel 10 | Шаблонізатор |
| Chart.js | Filament | Графіки та діаграми |

### 3.6. Інструменти розробки

| Інструмент | Версія | Призначення |
|------------|--------|-------------|
| Composer | latest | Менеджер PHP-пакетів |
| Laravel Pint | 1.0+ | Лінтер коду PHP |
| PHPUnit | 10.1+ | Фреймворк тестування |
| Xdebug | — | Дебагер PHP |
| Faker | 1.9.1+ | Генерація тестових даних |

### 3.7. Залежності PHP (composer.json)

**Основні залежності:**
```json
{
  "php": "^8.1",
  "filament/filament": "^3.0",
  "guzzlehttp/guzzle": "^7.2",
  "laravel/framework": "^10.10",
  "laravel/sanctum": "^3.2",
  "laravel/tinker": "^2.8"
}
```

**Залежності для розробки:**
```json
{
  "fakerphp/faker": "^1.9.1",
  "laravel/pint": "^1.0",
  "laravel/sail": "^1.18",
  "mockery/mockery": "^1.4.4",
  "nunomaduro/collision": "^7.0",
  "phpunit/phpunit": "^10.1",
  "spatie/laravel-ignition": "^2.0"
}
```

### 3.8. Залежності Go (go.mod)

```
github.com/jackc/pgx/v5 v5.5.1     — PostgreSQL-драйвер
github.com/jackc/pgpassfile          — Підтримка pgpass
github.com/jackc/pgservicefile       — Підтримка pg_service
github.com/jackc/puddle/v2           — Пул з'єднань
golang.org/x/crypto v0.16.0          — Криптографічні утиліти
golang.org/x/sync v0.4.0             — Примітиви синхронізації
golang.org/x/text v0.14.0            — Обробка тексту/Unicode
```

---

## 4. Docker-інфраструктура

### 4.1. Загальний огляд

Проект повністю контейнеризований за допомогою Docker та Docker Compose. Складається з 6 сервісів, об'єднаних в мережу `scheduler`.

### 4.2. Сервіси Docker Compose

#### 4.2.1. Сервіс `app` (PHP-додаток)

- **Образ:** Власний, збирається з `./docker/php/Dockerfile`
- **Контейнер:** `scheduler_app`
- **Робоча директорія:** `/var/www`
- **Залежності:** postgres, redis
- **Томи:** Монтування всього проекту в `/var/www`
- **Мережа:** `scheduler` (bridge)

Dockerfile для PHP-додатку базується на `php:8.2-fpm` та включає:

- **Системні залежності:** git, curl, libpng-dev, libonig-dev, libxml2-dev, zip, unzip, libzip-dev, libfreetype6-dev, libjpeg62-turbo-dev, libgd-dev, libicu-dev, libpq-dev
- **PHP-розширення:** pdo_mysql, pdo_pgsql, mbstring, exif, pcntl, bcmath, gd, zip, intl, redis (PECL), xdebug (PECL)
- **Конфігурація PHP:** upload_max_filesize=40M, post_max_size=40M, memory_limit=512M, max_execution_time=300
- **Xdebug:** mode=develop,debug, start_with_request=yes, client_host=host.docker.internal, port=9003
- **Користувач:** www (uid 1000, gid 1000) — не root
- **Порт:** 9000 (PHP-FPM)

#### 4.2.2. Сервіс `nginx` (Веб-сервер)

- **Образ:** `nginx:alpine`
- **Контейнер:** `scheduler_nginx`
- **Порт:** 8080:80 (доступний ззовні на порту 8080)
- **Залежності:** app
- **Конфігурація:** `./docker/nginx/default.conf`

Nginx-конфігурація:
- Document root: `/var/www/public`
- FastCGI proxy до `app:9000` для PHP-файлів
- Rewrite для Laravel: `try_files $uri $uri/ /index.php?$query_string`
- Підтримка gzip-стиснення

#### 4.2.3. Сервіс `postgres` (База даних)

- **Образ:** `postgres:16-alpine`
- **Контейнер:** `scheduler_postgres`
- **Порт:** 5432:5432
- **Змінні середовища:** DB_DATABASE, DB_USERNAME, DB_PASSWORD (з .env)
- **Томи:** `postgres_data:/var/lib/postgresql/data` (персистентне сховище)

#### 4.2.4. Сервіс `redis` (Кешування)

- **Образ:** `redis:7-alpine`
- **Контейнер:** `scheduler_redis`
- **Порт:** 6379:6379
- **Томи:** `redis_data:/data` (персистентне сховище)

#### 4.2.5. Сервіс `queue-worker` (Обробник черги)

- **Образ:** Власний (той самий Dockerfile, що й app)
- **Контейнер:** `scheduler_queue_worker`
- **Команда:** `php artisan queue:work redis --tries=1 --timeout=660 --sleep=3`
- **Призначення:** Обробка фонових завдань (генерація розкладу)

Параметри обробника черги:
- `--tries=1` — одна спроба виконання завдання
- `--timeout=660` — таймаут 11 хвилин (для тривалої генерації розкладу)
- `--sleep=3` — інтервал опитування черги 3 секунди

#### 4.2.6. Сервіс `solver` (Go-сервіс)

- **Образ:** Власний, збирається з `./solver/Dockerfile`
- **Контейнер:** `scheduler_solver`
- **Порти:** 50051:50051 (gRPC, зарезервований), 8081:8081 (HTTP)
- **Залежності:** postgres
- **Змінна середовища:** DATABASE_URL для прямого підключення до PostgreSQL

Dockerfile для Go Solver використовує multi-stage build:
- **Етап 1 (Build):** golang:1.21-alpine — збірка статичного бінарника (CGO_ENABLED=0, GOOS=linux)
- **Етап 2 (Runtime):** alpine:3.19 — мінімальний образ з ca-certificates та tzdata

### 4.3. Makefile — команди розробки

| Команда | Опис |
|---------|------|
| `make help` | Показати довідку |
| `make build` | Збірка контейнерів (без кешу) |
| `make up` | Запуск усіх сервісів у фоновому режимі |
| `make down` | Зупинка усіх сервісів |
| `make restart` | Перезапуск сервісів |
| `make install` | Composer install, копіювання .env, генерація app key |
| `make migrate` | Запуск міграцій Laravel |
| `make seed` | Запуск сідерів бази даних |
| `make fresh` | Повне перестворення БД з сідами |
| `make logs` | Перегляд логів усіх сервісів |
| `make logs-app` | Логи додатку |
| `make logs-nginx` | Логи Nginx |
| `make logs-db` | Логи бази даних |
| `make shell` | Вхід у bash контейнера app |
| `make shell-db` | Вхід у bash контейнера PostgreSQL |
| `make clean` | Видалення контейнерів та томів |
| `make test` | Запуск PHPUnit-тестів |
| `make cache-clear` | Очищення всіх кешів Laravel |

### 4.4. Мережева інфраструктура

Усі контейнери об'єднані в bridge-мережу `scheduler`. Внутрішня комунікація відбувається за іменами сервісів:

- `app` → `postgres:5432` (Laravel до БД)
- `app` → `redis:6379` (кеш, черги, сесії)
- `app` → `solver:8081` (генерація розкладу)
- `solver` → `postgres:5432` (прямий доступ до БД)
- `nginx` → `app:9000` (FastCGI)
- `queue-worker` → `redis:6379` (обробка черги)
- `queue-worker` → `postgres:5432` (доступ до БД)

---

## 5. База даних

### 5.1. Загальний огляд

База даних PostgreSQL 16 містить 26 таблиць, організованих за функціональним призначенням. Усі основні таблиці містять поле `tenant_id` (UUID) для забезпечення мультитенантності.

### 5.2. Схема бази даних

#### 5.2.1. Управління тенантами

**Таблиця `tenants`** — основна таблиця мультитенантності

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | UUID | PK | Унікальний ідентифікатор тенанта |
| name | string | NOT NULL | Назва університету |
| subdomain | string | UNIQUE | Піддомен для доступу |
| domain | string | UNIQUE | Повний домен |
| public_slug | string(64) | UNIQUE, NULLABLE | Публічний slug для розкладу |
| is_active | boolean | DEFAULT true | Статус активності |
| settings | jsonb | NULLABLE | JSON-об'єкт налаштувань |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Поле `settings` може містити:
```json
{
  "days_per_week": 6,
  "slots_per_day": 6,
  "slot_duration": 90,
  "language": "uk"
}
```

#### 5.2.2. Управління користувачами

**Таблиця `users`**

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор користувача |
| tenant_id | UUID | FK → tenants, NULLABLE, INDEX | Прив'язка до тенанта |
| name | string | NOT NULL | Повне ім'я |
| email | string | UNIQUE | Електронна пошта |
| email_verified_at | timestamp | NULLABLE | Дата верифікації email |
| password | string | NOT NULL | Хешований пароль |
| role | enum | DEFAULT 'viewer' | Роль користувача |
| teacher_id | bigInteger | FK → teachers, NULLABLE, INDEX | Прив'язка до викладача |
| remember_token | string | NULLABLE | Токен "запам'ятати мене" |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Допустимі значення ролі: `owner`, `admin`, `planner`, `teacher`, `viewer`.

**Таблиця `password_reset_tokens`**

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| email | string | PK | Email користувача |
| token | string | NOT NULL | Токен скидання |
| created_at | timestamp | NULLABLE | Дата створення |

**Таблиця `personal_access_tokens`**

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор токена |
| tokenable_type | string | NOT NULL | Поліморфний тип |
| tokenable_id | bigInteger | NOT NULL | Поліморфний ID |
| name | string | NOT NULL | Назва токена |
| token | string(64) | UNIQUE | Хешований токен |
| abilities | text | NULLABLE | JSON-масив дозволів |
| last_used_at | timestamp | NULLABLE | Останнє використання |
| expires_at | timestamp | NULLABLE | Термін дії |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

#### 5.2.3. Організаційна структура

**Таблиця `teachers`** — викладачі

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, NULLABLE, INDEX | Тенант |
| name | string | NOT NULL | Повне ім'я викладача |
| email | string | UNIQUE | Електронна пошта |
| phone | string | NULLABLE | Телефон |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `courses`** — курси навчання

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, NULLABLE, INDEX | Тенант |
| name | string | NOT NULL | Назва курсу |
| number | integer | NOT NULL | Номер курсу (1-4) |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `groups`** — навчальні групи

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, NULLABLE, INDEX | Тенант |
| course_id | bigInteger | FK → courses (CASCADE DELETE) | Курс |
| name | string | NOT NULL | Назва групи |
| code | string(20) | NULLABLE | Код групи |
| size | integer | DEFAULT 0 | Кількість студентів |
| semester | integer | NULLABLE | Семестр |
| program | string | NULLABLE | Навчальна програма |
| active | boolean | DEFAULT true | Статус активності |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `subjects`** — навчальні предмети

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, NULLABLE, INDEX | Тенант |
| name | string | NOT NULL | Назва предмету |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| type | enum | NOT NULL | Тип: lecture, practice |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

#### 5.2.4. Приміщення та календар

**Таблиця `rooms`** — аудиторії

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| code | string(20) | NOT NULL | Код аудиторії |
| title | string(100) | NOT NULL | Назва аудиторії |
| capacity | integer | DEFAULT 0 | Місткість |
| room_type | enum | DEFAULT 'lecture' | Тип аудиторії |
| features | jsonb | NULLABLE | Додаткове обладнання |
| active | boolean | DEFAULT true | Статус активності |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Допустимі типи аудиторій: `lecture`, `lab`, `seminar`, `pc`, `gym`, `other`.

**Таблиця `calendars`** — навчальні календарі

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| name | string(120) | NOT NULL | Назва календаря |
| start_date | date | NOT NULL | Дата початку |
| end_date | date | NOT NULL | Дата закінчення |
| weeks | integer | DEFAULT 16 | Кількість тижнів |
| parity_enabled | boolean | DEFAULT false | Увімкнення парності |
| first_week_parity | string(3) | DEFAULT 'num' | Парність першого тижня |
| days_per_week | integer | DEFAULT 6 | Днів на тиждень |
| slots_per_day | integer | DEFAULT 6 | Пар на день |
| slot_duration_minutes | integer | DEFAULT 90 | Тривалість пари (хв) |
| break_duration_minutes | integer | DEFAULT 10 | Тривалість перерви (хв) |
| slot_times | json | NULLABLE | Налаштування часу пар |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Значення `first_week_parity`: `num` (чисельник) або `den` (знаменник).

**Таблиця `time_slots`** — часові слоти

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| day_of_week | smallInteger | NOT NULL | День тижня (1-7) |
| slot_index | smallInteger | NOT NULL | Номер пари (1-N) |
| start_time | time | NOT NULL | Час початку |
| end_time | time | NOT NULL | Час завершення |
| parity | enum | DEFAULT 'both' | Парність слоту |
| enabled | boolean | DEFAULT true | Активність слоту |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Значення `parity`: `both` (кожен тиждень), `num` (чисельник), `den` (знаменник).

#### 5.2.5. Навчальні активності

**Таблиця `activities`** — навчальні заняття

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| subject_id | bigInteger | FK → subjects (CASCADE DELETE) | Предмет |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| title | string(200) | NULLABLE | Назва заняття |
| activity_type | enum | DEFAULT 'lecture' | Тип заняття |
| duration_slots | smallInteger | DEFAULT 1 | Тривалість у слотах |
| required_slots_per_period | smallInteger | DEFAULT 1 | Кількість занять на тиждень |
| notes | text | NULLABLE | Примітки |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Допустимі типи занять: `lecture`, `lab`, `seminar`, `practice`, `pc`.

**Таблиця `activity_groups`** — зв'язок активностей з групами

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| activity_id | bigInteger | FK → activities (CASCADE DELETE) | Активність |
| group_id | bigInteger | FK → groups (CASCADE DELETE) | Група |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `activity_teachers`** — зв'язок активностей з викладачами

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| activity_id | bigInteger | FK → activities (CASCADE DELETE) | Активність |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `activity_room_types`** — вимоги до типів аудиторій

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| activity_id | bigInteger | FK → activities (CASCADE DELETE) | Активність |
| room_type | enum | NOT NULL | Необхідний тип аудиторії |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

#### 5.2.6. Обмеження доступності та побажання

**Таблиця `teacher_unavailability`** — недоступність викладачів

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| day_of_week | smallInteger | NOT NULL | День тижня (1-7) |
| slot_index | smallInteger | NOT NULL | Номер пари |
| parity | enum | DEFAULT 'both' | Парність |
| reason | text | NULLABLE | Причина недоступності |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `room_unavailability`** — недоступність аудиторій

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| room_id | bigInteger | FK → rooms (CASCADE DELETE) | Аудиторія |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| day_of_week | smallInteger | NOT NULL | День тижня |
| slot_index | smallInteger | NOT NULL | Номер пари |
| parity | enum | DEFAULT 'both' | Парність |
| reason | text | NULLABLE | Причина недоступності |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `group_unavailability`** — недоступність груп

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| group_id | bigInteger | FK → groups (CASCADE DELETE) | Група |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| day_of_week | smallInteger | NOT NULL | День тижня |
| slot_index | smallInteger | NOT NULL | Номер пари |
| parity | enum | DEFAULT 'both' | Парність |
| reason | text | NULLABLE | Причина недоступності |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `teacher_preferences`** — побажання викладачів щодо часових слотів

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| day_of_week | smallInteger | NOT NULL | День тижня |
| slot_index | smallInteger | NOT NULL | Номер пари |
| parity | enum | DEFAULT 'both' | Парність |
| weight | smallInteger | DEFAULT 0 | Вага побажання |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Від'ємне значення `weight` означає уникнення слоту, додатне — перевагу.

**Таблиця `teacher_preference_rules`** — правила побажань викладачів

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| rule_type | string(50) | NOT NULL | Тип правила |
| params | jsonb | NOT NULL | Параметри правила |
| priority | smallInteger | DEFAULT 0 | Пріоритет |
| weight | smallInteger | DEFAULT 10 | Вага правила |
| is_active | boolean | DEFAULT true | Статус активності |
| comment | text | NULLABLE | Коментар |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Типи правил:
- `max_hours_per_day` — максимум годин на день (params: `{"max_hours": N}`)
- `min_start_slot` — мінімальний початковий слот (params: `{"min_slot": N, "day_of_week": D}`)
- `max_end_slot` — максимальний кінцевий слот (params: `{"max_slot": N, "day_of_week": D}`)
- `preferred_slot` — бажаний слот (params: `{"day_of_week": D, "slot_index": S}`)
- `unavailable_day` — недоступний день (params: `{"day_of_week": D}`)
- `unavailable_slot` — недоступний слот (params: `{"day_of_week": D, "slot_index": S}`)

**Таблиця `reschedule_requests`** — заявки на перенесення занять

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| teacher_id | bigInteger | FK → teachers (CASCADE DELETE) | Викладач |
| assignment_id | bigInteger | FK → schedule_assignments (CASCADE DELETE) | Призначення |
| proposed_day_of_week | smallInteger | NOT NULL | Запропонований день |
| proposed_slot_index | smallInteger | NOT NULL | Запропонований слот |
| proposed_parity | enum | DEFAULT 'both' | Запропонована парність |
| proposed_room_id | bigInteger | FK → rooms (SET NULL), NULLABLE | Запропонована аудиторія |
| status | enum | DEFAULT 'pending' | Статус заявки |
| teacher_comment | text | NULLABLE | Коментар викладача |
| admin_comment | text | NULLABLE | Коментар адміністратора |
| reviewed_by | bigInteger | FK → users (SET NULL), NULLABLE | Ким розглянуто |
| reviewed_at | timestamp | NULLABLE | Дата розгляду |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Статуси: `pending`, `approved`, `rejected`.

#### 5.2.7. Оптимізація та обмеження

**Таблиця `soft_weights`** — ваги м'яких обмежень

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| tenant_id | UUID | PK, FK → tenants | Тенант (первинний ключ) |
| w_windows | integer | DEFAULT 10 | Вага для вікон у розкладі груп |
| w_teacher_windows | integer | DEFAULT 1 | Вага для вікон у розкладі викладачів |
| w_prefs | integer | DEFAULT 5 | Вага для побажань викладачів |
| w_balance | integer | DEFAULT 2 | Вага для балансування навантаження |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

#### 5.2.8. Управління розкладом

**Таблиця `schedule_versions`** — версії розкладу

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| calendar_id | bigInteger | FK → calendars (CASCADE DELETE) | Календар |
| name | string(120) | NOT NULL | Назва версії |
| status | enum | DEFAULT 'draft' | Статус версії |
| created_by | bigInteger | FK → users (RESTRICT DELETE) | Автор |
| parent_version_id | bigInteger | NULLABLE | Батьківська версія |
| version_number | integer | DEFAULT 1 | Номер версії |
| random_seed | integer | NULLABLE | Початкове значення для RNG |
| generation_params | jsonb | NULLABLE | Параметри генерації |
| generation_started_at | timestamp | NULLABLE | Початок генерації |
| generation_finished_at | timestamp | NULLABLE | Завершення генерації |
| published_at | timestamp | NULLABLE | Дата публікації |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Статуси: `draft`, `published`, `archived`, `generating`, `failed`.

**Таблиця `schedule_assignments`** — призначення у розкладі

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| schedule_version_id | bigInteger | FK → schedule_versions (CASCADE DELETE) | Версія розкладу |
| activity_id | bigInteger | FK → activities (CASCADE DELETE) | Активність |
| day_of_week | smallInteger | NOT NULL | День тижня (1-7) |
| slot_index | smallInteger | NOT NULL | Номер пари |
| parity | enum | DEFAULT 'both' | Парність |
| room_id | bigInteger | FK → rooms (RESTRICT DELETE) | Аудиторія |
| locked | boolean | DEFAULT false | Заблоковано для змін |
| source | enum | DEFAULT 'solver' | Джерело призначення |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Значення `source`: `solver` (створено алгоритмом) або `manual` (створено вручну).

**Таблиця `violations`** — порушення обмежень

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| schedule_version_id | bigInteger | FK → schedule_versions (CASCADE DELETE) | Версія розкладу |
| activity_id | bigInteger | FK → activities (SET NULL), NULLABLE | Активність |
| code | string(50) | NOT NULL | Код порушення |
| severity | enum | DEFAULT 'soft' | Серйозність |
| meta | jsonb | NULLABLE | Додаткова інформація |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

Значення `severity`: `hard` (жорстке обмеження) або `soft` (м'яке обмеження).

#### 5.2.9. Імпорт та аудит

**Таблиця `import_jobs`** — завдання імпорту

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| user_id | bigInteger | FK → users (RESTRICT DELETE) | Користувач |
| kind | enum | NOT NULL | Тип файлу: csv, xlsx, ics |
| status | enum | DEFAULT 'pending' | Статус імпорту |
| stats | jsonb | NULLABLE | Статистика імпорту |
| error_message | text | NULLABLE | Повідомлення про помилку |
| file_path | string(500) | NULLABLE | Шлях до файлу |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

**Таблиця `audit_logs`** — журнал аудиту

| Поле | Тип | Обмеження | Опис |
|------|-----|-----------|------|
| id | bigInteger | PK, AUTO | Ідентифікатор |
| tenant_id | UUID | FK → tenants, INDEX | Тенант |
| actor_user_id | bigInteger | FK → users (SET NULL), NULLABLE | Користувач-актор |
| action | string(50) | NOT NULL | Тип дії |
| entity | string(50) | NOT NULL | Назва сутності |
| entity_id | bigInteger | NULLABLE | ID сутності |
| meta | jsonb | NULLABLE | Додаткова інформація |
| created_at | timestamp | | Дата створення |
| updated_at | timestamp | | Дата оновлення |

### 5.3. Порядок виконання міграцій

1. Стандартні міграції Laravel (users, password_reset_tokens, failed_jobs, personal_access_tokens)
2. Успадковані таблиці (teachers, subjects, courses, groups, schedules)
3. Основна SaaS-міграція (`2026_02_26_000001_create_tenants_and_all_tables.php`) — створює tenants та всі пов'язані таблиці
4. Таблиці кабінету викладача (teacher_preference_rules, reschedule_requests)
5. Додаткові поля (generation_started_at, generation_finished_at для schedule_versions)
6. Додаткові ваги (w_teacher_windows для soft_weights)
7. Конфігурація календаря (slot_times, first_week_parity для calendars)

### 5.4. Сідери (Seeders)

#### 5.4.1. DatabaseSeeder

Головний сідер, що викликає `DemoSeeder`.

#### 5.4.2. DemoSeeder

Створює два демонстраційних університети:

**Університет "КПІ" (великий):**
- 16 викладачів з 6 кафедр
- 4 курси (1-4)
- 12 навчальних груп (3 на курс)
- 13 аудиторій: 4 лекційних, 3 комп'ютерних, 4 семінарських, 2 фізичних лабораторії, 1 спортивний зал
- Календар: 01.09.2026 — 15.01.2027 (16 тижнів)
- ~70 активностей (лекції, практичні, лабораторні)
- Обліковий запис: admin@kpi.ua / password

**Університет "ЛНУ" (менший):**
- 6 викладачів
- 3 курси
- 5 навчальних груп
- 5 аудиторій
- Календар: 01.09.2026 — 20.01.2027
- Обліковий запис: admin@lnu.ua / password

**Стандартний розклад пар:**

| Пара | Початок | Кінець | Тривалість |
|------|---------|--------|------------|
| 1 | 08:30 | 10:05 | 90 хв |
| 2 | 10:15 | 11:50 | 90 хв |
| 3 | 12:10 | 13:45 | 90 хв |
| 4 | 13:55 | 15:30 | 90 хв |
| 5 | 15:40 | 17:15 | 90 хв |
| 6 | 17:25 | 19:00 | 90 хв |

**Додаткові демо-дані:**
- Недоступність 7+ викладачів у конкретні слоти
- Побажання 5+ викладачів щодо часу занять
- 11 правил побажань (max_hours, min_start_slot, preferred_slot тощо)

### 5.5. Ключові патерни бази даних

1. **Мультитенантність** — усі таблиці з даними мають `tenant_id` (UUID) як зовнішній ключ до таблиці `tenants`
2. **Система парності** — підтримка чергування тижнів (`num`/`den`/`both`) для двотижневих розкладів
3. **Софт-ваги** — конфігуровані ваги оптимізатора для кожного тенанта
4. **Механізм блокування** — поле `locked` у schedule_assignments запобігає змінам від солвера
5. **Контроль версій** — schedule_versions підтримують чернетки, публікацію, архівування з батьківськими зв'язками
6. **Журнал аудиту** — audit_logs відстежує всі зміни з інформацією про актора та метаданими
7. **Гнучкі правила** — teacher_preference_rules використовують jsonb для розширюваних параметрів
8. **Пайплайн імпорту** — import_jobs відстежує імпорт CSV/XLSX/ICS зі статусом та статистикою
9. **Каскадне видалення** — більшість зовнішніх ключів використовують CASCADE DELETE для забезпечення цілісності

---

## 6. Мультитенантність

### 6.1. Концепція

Система реалізує мультитенантну архітектуру типу "shared database, shared schema" — всі тенанти використовують одну базу даних та одну схему, але дані ізолюються через поле `tenant_id` у кожній таблиці.

### 6.2. TenantScope Trait

**Файл:** `app/Models/Traits/TenantScope.php`

Трейт `TenantScope` є основним механізмом ізоляції даних. Він:

1. **Реєструє глобальний scope** — клас `TenantGlobalScope` автоматично додає `WHERE tenant_id = ?` до всіх запитів
2. **Автоматично встановлює tenant_id** — при створенні нового запису автоматично заповнює `tenant_id`
3. **Запобігає нескінченній рекурсії** — використовує статичну змінну `$applying` для уникнення циклів

Механізм визначення tenant_id:
```
1. app('tenant') → Tenant::find($id)
2. auth()->user()->tenant_id
```

Трейт надає:
- `scopeTenant($query, $tenantId)` — ручна фільтрація за тенантом
- `getTenantId()` — визначення поточного tenant_id
- `bootTenantScope()` — реєстрація глобального scope та автоматичне встановлення tenant_id

### 6.3. TenantManager Service

**Файл:** `app/Services/TenantManager.php`

Сервіс для управління контекстом тенанта. Зареєстрований як singleton в `TenantServiceProvider`.

Методи:
- `getTenant(): ?Tenant` — отримання поточного тенанта з кешуванням (Cache::remember, TTL 3600 сек)
- `setTenant(Tenant $tenant)` — встановлення активного тенанта
- `setTenantById(string $tenantId): ?Tenant` — пошук та встановлення тенанта за ID
- `resolveTenantId(): ?string` — визначення tenant_id з авторизованого користувача або домену/піддомену
- `extractSubdomain(string $host): ?string` — вилучення піддомену з хоста
- `clearTenant()` — очищення контексту тенанта
- `getTenantId(): ?string` — отримання поточного tenant_id

### 6.4. TenantMiddleware

**Файл:** `app/Http/Middleware/TenantMiddleware.php`

Middleware, що встановлює контекст тенанта для кожного запиту:
1. Використовує TenantManager для визначення тенанта
2. Якщо не знайдено — використовує `auth()->user()->tenant_id`
3. Встановлює контекст тенанта для запиту
4. Дозволяє публічні сторінки без тенанта

### 6.5. Slug-система

При створенні тенанта автоматично генерується унікальний `public_slug` з назви університету. Метод `generateUniqueSlug()` у моделі Tenant:
- Транслітерує назву
- Перевіряє унікальність
- Додає числовий суфікс при колізіях

---

## 7. Система автентифікації та ролей

### 7.1. Автентифікація

Система підтримує стандартну автентифікацію Laravel через сесії.

#### 7.1.1. Реєстрація

Процес реєстрації нового університету (`AuthController::register`):
1. Валідація даних: ім'я, email (унікальний), назва університету, пароль з підтвердженням
2. Створення тенанта у транзакції БД:
   - Створення запису `Tenant` з назвою університету
   - Автоматична генерація `public_slug`
3. Створення користувача з роллю `owner`
4. Автоматичний вхід у систему
5. Перенаправлення до адмін-панелі

#### 7.1.2. Вхід

Процес входу (`AuthController::login`):
1. Валідація email та паролю
2. Спроба автентифікації через `Auth::attempt()`
3. Встановлення контексту тенанта через TenantManager
4. Перенаправлення до адмін-панелі

#### 7.1.3. Вихід

Процес виходу (`AuthController::logout`):
1. Очищення контексту тенанта
2. Виклик `Auth::logout()`

### 7.2. Ролі та дозволи

Система підтримує 5 ролей з ієрархічним доступом:

| Роль | Рівень | Можливості |
|------|--------|------------|
| `owner` | Найвищий | Повний доступ, управління тенантом |
| `admin` | Високий | Управління користувачами, налаштуваннями |
| `planner` | Середній | Управління розкладом, генерація |
| `teacher` | Обмежений | Перегляд особистого розкладу, заявки |
| `viewer` | Мінімальний | Лише перегляд |

Модель `User` містить допоміжні методи для перевірки ролей:
- `isOwner()` — повертає true для ролей owner
- `isAdmin()` — повертає true для ролей owner та admin
- `isPlanner()` — повертає true для owner, admin та planner
- `isTeacher()` — повертає true для ролі teacher
- `isViewer()` — повертає true для ролі viewer

### 7.3. Контроль доступу в Filament

Кожен Filament-ресурс та сторінка реалізує метод `canAccess()` для перевірки доступу:
- Ресурси управління даними: `isPlanner()` (planner, admin, owner)
- Управління користувачами та тенантом: `isAdmin()` (admin, owner)
- Правила побажань: `isAdmin()` або `isTeacher()`
- Кабінет викладача: наявність `teacher_id` у профілі

---

## 8. Laravel-додаток: Моделі

### 8.1. Загальний огляд

Проект містить 17 моделей та 1 трейт (`TenantScope`). Усі моделі (крім `Tenant` та `User`) використовують трейт `TenantScope` для мультитенантної ізоляції.

### 8.2. Модель Tenant

**Файл:** `app/Models/Tenant.php`

Базова модель мультитенантності. Використовує UUID як первинний ключ.

**Трейти:** HasFactory, HasUuids

**Fillable:** name, subdomain, domain, public_slug, settings, is_active

**Кастування (Casts):**
- `settings` → array
- `is_active` → boolean

**Зв'язки (Relationships):**
- `users()` → HasMany User
- `teachers()` → HasMany Teacher
- `subjects()` → HasMany Subject
- `groups()` → HasMany Group
- `rooms()` → HasMany Room
- `calendars()` → HasMany Calendar
- `activities()` → HasMany Activity
- `scheduleVersions()` → HasMany ScheduleVersion
- `softWeights()` → HasOne SoftWeight

**Методи:**
- `generateUniqueSlug()` — створення унікального slug з назви
- `getPublicUrl()` — отримання URL публічного розкладу

**Подія booted:** Автоматична генерація `public_slug` при створенні тенанта.

### 8.3. Модель User

**Файл:** `app/Models/User.php`

Модель користувача системи. Розширює `Authenticatable`.

**Трейти:** HasApiTokens, HasFactory, Notifiable, TenantScope

**Fillable:** tenant_id, name, email, password, role, teacher_id

**Hidden:** password, remember_token

**Кастування:**
- `email_verified_at` → datetime
- `password` → hashed

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `teacher()` → BelongsTo Teacher

**Методи перевірки ролей:**
- `isOwner()` — role === 'owner'
- `isAdmin()` — role in ['owner', 'admin']
- `isPlanner()` — role in ['owner', 'admin', 'planner']
- `isTeacher()` — role === 'teacher'
- `isViewer()` — role === 'viewer'

### 8.4. Модель Activity

**Файл:** `app/Models/Activity.php`

Навчальна активність — центральна сутність для розкладу.

**Трейти:** HasFactory, TenantScope

**Fillable:** tenant_id, subject_id, title, activity_type, duration_slots, required_slots_per_period, calendar_id, notes

**Кастування:**
- `duration_slots` → integer
- `required_slots_per_period` → integer

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `subject()` → BelongsTo Subject
- `calendar()` → BelongsTo Calendar
- `groups()` → BelongsToMany Group (через activity_groups з pivot tenant_id)
- `teachers()` → BelongsToMany Teacher (через activity_teachers з pivot tenant_id)
- `assignments()` → HasMany ScheduleAssignment

### 8.5. Модель Calendar

**Файл:** `app/Models/Calendar.php`

Навчальний календар з налаштуваннями часових слотів.

**Трейти:** HasFactory, TenantScope

**Fillable:** tenant_id, name, start_date, end_date, weeks, parity_enabled, first_week_parity, days_per_week, slots_per_day, slot_duration_minutes, break_duration_minutes, slot_times

**Кастування:**
- `start_date` → date
- `end_date` → date
- `parity_enabled` → boolean
- `days_per_week` → integer
- `slots_per_day` → integer
- `slot_duration_minutes` → integer
- `break_duration_minutes` → integer
- `slot_times` → array

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `timeSlots()` → HasMany TimeSlot
- `activities()` → HasMany Activity
- `scheduleVersions()` → HasMany ScheduleVersion

**Методи:**
- `getParityForDate($date)` — визначення парності ('num' або 'den') для конкретної дати на основі тижня
- `generateDefaultSlotTimes()` — статичний метод для створення масиву часових слотів з duration та break
- `syncTimeSlots()` — синхронізація таблиці TimeSlot з JSON slot_times

### 8.6. Модель ScheduleVersion

**Файл:** `app/Models/ScheduleVersion.php`

Версія розкладу з підтримкою parent/child ієрархії.

**Трейти:** HasFactory, TenantScope

**Fillable:** tenant_id, calendar_id, name, status, created_by, parent_version_id, version_number, random_seed, generation_params, published_at, generation_started_at, generation_finished_at

**Кастування:**
- `generation_params` → array
- `published_at` → datetime
- `generation_started_at` → datetime
- `generation_finished_at` → datetime
- `version_number` → integer
- `random_seed` → integer

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `calendar()` → BelongsTo Calendar
- `creator()` → BelongsTo User (через created_by)
- `parentVersion()` → BelongsTo ScheduleVersion (self-referential)
- `childVersions()` → HasMany ScheduleVersion
- `assignments()` → HasMany ScheduleAssignment
- `violations()` → HasMany Violation

**Статуси:** draft, generating, published, archived, failed

### 8.7. Модель ScheduleAssignment

**Файл:** `app/Models/ScheduleAssignment.php`

Окреме призначення (одне заняття у конкретному слоті).

**Трейти:** HasFactory, TenantScope

**Fillable:** tenant_id, schedule_version_id, activity_id, day_of_week, slot_index, parity, room_id, locked, source

**Кастування:**
- `day_of_week` → integer
- `slot_index` → integer
- `locked` → boolean

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `scheduleVersion()` → BelongsTo ScheduleVersion
- `activity()` → BelongsTo Activity
- `room()` → BelongsTo Room

### 8.8. Модель Subject

**Файл:** `app/Models/Subject.php`

Навчальний предмет.

**Трейти:** HasFactory, TenantScope

**Константи:**
- `TYPE_LECTURE` = 'lecture'
- `TYPE_PRACTICE` = 'practice'
- `TYPE_LAB` = 'lab'
- `TYPE_SEMINAR` = 'seminar'
- `TYPE_PC` = 'pc'
- `TYPES` — масив з українськими назвами типів

**Fillable:** tenant_id, name, teacher_id, type

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `teacher()` → BelongsTo Teacher
- `activities()` → HasMany Activity

**Аксесори:**
- `getTypeLabelAttribute()` — повертає українську назву типу предмету

### 8.9. Модель Teacher

**Файл:** `app/Models/Teacher.php`

Викладач.

**Трейти:** HasFactory, TenantScope

**Fillable:** tenant_id, name, email, phone

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `subjects()` → HasMany Subject
- `activities()` → BelongsToMany Activity
- `unavailabilities()` → HasMany TeacherUnavailability
- `user()` → HasOne User
- `preferenceRules()` → HasMany TeacherPreferenceRule
- `rescheduleRequests()` → HasMany RescheduleRequest

### 8.10. Модель TeacherPreferenceRule

**Файл:** `app/Models/TeacherPreferenceRule.php`

Правило побажання викладача щодо розкладу.

**Трейти:** HasFactory, TenantScope

**Константи:**
- `RULE_UNAVAILABLE_DAY` = 'unavailable_day'
- `RULE_UNAVAILABLE_SLOT` = 'unavailable_slot'
- `RULE_PREFERRED_SLOT` = 'preferred_slot'
- `RULE_MIN_START_SLOT` = 'min_start_slot'
- `RULE_MAX_END_SLOT` = 'max_end_slot'
- `RULE_MAX_HOURS_PER_DAY` = 'max_hours_per_day'
- `RULE_TYPES` — масив з українськими назвами типів правил
- `DAY_NAMES` — масив українських назв днів тижня

**Fillable:** tenant_id, teacher_id, rule_type, params, priority, weight, is_active, comment

**Кастування:**
- `params` → array
- `priority` → integer
- `weight` → integer
- `is_active` → boolean

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `teacher()` → BelongsTo Teacher

**Аксесори:**
- `getDescriptionAttribute()` — генерує людиночитабельний опис правила українською мовою

### 8.11. Модель RescheduleRequest

**Файл:** `app/Models/RescheduleRequest.php`

Заявка на перенесення заняття від викладача.

**Константи:**
- `STATUS_PENDING` = 'pending'
- `STATUS_APPROVED` = 'approved'
- `STATUS_REJECTED` = 'rejected'

**Fillable:** tenant_id, teacher_id, assignment_id, proposed_day_of_week, proposed_slot_index, proposed_parity, proposed_room_id, status, teacher_comment, admin_comment, reviewed_by, reviewed_at

**Зв'язки:**
- `tenant()` → BelongsTo Tenant
- `teacher()` → BelongsTo Teacher
- `assignment()` → BelongsTo ScheduleAssignment
- `proposedRoom()` → BelongsTo Room
- `reviewer()` → BelongsTo User

**Методи:**
- `isPending()` — перевірка чи заявка в стані очікування

### 8.12. Інші моделі

- **Course** — курс навчання (name, number), зв'язок з Groups
- **Group** — навчальна група (name, code, size, semester, program, active), зв'язок з Course та Activities
- **Room** — аудиторія (code, title, capacity, room_type, features, active)
- **SoftWeight** — ваги оптимізації (w_windows, w_teacher_windows, w_prefs, w_balance), первинний ключ = tenant_id
- **TeacherUnavailability** — недоступність викладача (teacher_id, calendar_id, day_of_week, slot_index, parity, reason)
- **TimeSlot** — часовий слот (calendar_id, day_of_week, slot_index, start_time, end_time, parity, enabled)
- **Violation** — порушення обмеження (schedule_version_id, activity_id, code, severity, meta)

---

## 9. Laravel-додаток: Сервіси

### 9.1. TenantManager

**Файл:** `app/Services/TenantManager.php`

Відповідає за управління контекстом тенанта протягом запиту.

Детальний опис наведено в розділі [6.3. TenantManager Service](#63-tenantmanager-service).

### 9.2. ScheduleGenerationService

**Файл:** `app/Services/ScheduleGenerationService.php`

Сервіс оркестрації генерації розкладу. Зв'язує Laravel-додаток з Go Solver.

**Конструктор:** Встановлює URL Solver з `env('SOLVER_URL', 'http://solver:8081')`

**Методи:**

#### `generate(Calendar $calendar, User $user, array $params): ScheduleVersion`
Головний метод для створення нової версії розкладу:
1. Отримує конфігурацію SoftWeight для тенанта
2. Створює новий запис ScheduleVersion зі статусом 'draft'
3. Повертає оновлену версію після генерації

#### `generateForVersion(ScheduleVersion $version, string $algorithm, int $timeout, array $weights): void`
HTTP-виклик до Go Solver:
1. Формує JSON-запит з параметрами (tenant_id, calendar_id, schedule_id, weights, timeout, algorithm)
2. Відправляє POST-запит до `{SOLVER_URL}/api/v1/generate`
3. Оновлює версію метаданими результату (статус, порушення, час вирішення)
4. Логує успішні та помилкові результати

#### `publish(ScheduleVersion $version): void`
Публікація версії розкладу:
1. Архівує поточну опубліковану версію (якщо є)
2. Змінює статус на 'published'
3. Встановлює `published_at`

#### `archive(ScheduleVersion $version): void`
Архівування версії — зміна статусу на 'archived'.

### 9.3. RescheduleService

**Файл:** `app/Services/RescheduleService.php`

Сервіс обробки заявок на перенесення занять.

**Залежності:** ScheduleGenerationService

**Методи:**

#### `validateProposal(RescheduleRequest $request): array`
Валідація запропонованого перенесення:
1. Перевірка конфліктів аудиторій (room conflicts)
2. Перевірка конфліктів викладачів (teacher conflicts)
3. Перевірка конфліктів груп (group conflicts)
4. Врахування парності (both/num/den)
5. Повертає масив описів конфліктів

#### `approve(RescheduleRequest $request, User $reviewer, ?string $comment): ScheduleVersion`
Схвалення заявки (у транзакції БД):
1. Створення нової дочірньої ScheduleVersion
2. Копіювання всіх призначень з поточної версії
3. Переміщення цільового призначення на запропонований слот
4. Оновлення статусу заявки на APPROVED
5. Повернення нової версії розкладу

#### `reject(RescheduleRequest $request, User $reviewer, ?string $comment): void`
Відхилення заявки — оновлення статусу на REJECTED.

---

## 10. Laravel-додаток: Контролери

### 10.1. AuthController

**Файл:** `app/Http/Controllers/AuthController.php`

Контролер автентифікації (реєстрація, вхід, вихід).

**Методи:**
- `showRegisterForm()` — відображення форми реєстрації (view: auth.register)
- `register()` — створення тенанта та користувача-власника у транзакції БД
- `showLoginForm()` — відображення форми входу (view: auth.login)
- `login()` — автентифікація з встановленням контексту тенанта
- `logout()` — очищення сесії та контексту тенанта

### 10.2. PublicScheduleController

**Файл:** `app/Http/Controllers/PublicScheduleController.php`

Контролер публічного розкладу. Не вимагає автентифікації.

**Методи:**

#### `show(string $slug)`
Відображення сторінки публічного розкладу:
- Шукає тенант за `public_slug`
- Завантажує курси та календар
- Повертає view `public-schedule`

#### `getGroups(string $slug, int $courseId)`
API-ендпоінт для отримання груп курсу (JSON).

#### `getScheduleData(string $slug, int $groupId, string $startDate, string $endDate)`
API-ендпоінт для отримання матриці розкладу:
- Обмежує діапазон дат межами календаря
- Враховує парність тижнів (num/den/both)
- Фільтрує за опублікованою версією
- Повертає: матрицю розкладу, часові слоти, діапазон дат, інформацію про версію, межі календаря

### 10.3. ScheduleController

**Файл:** `app/Http/Controllers/ScheduleController.php`

Контролер розкладу для автентифікованих користувачів.

**Методи:**
- `index()` — сторінка розкладу з курсами
- `getCourseGroups(int $courseId)` — JSON: групи курсу
- `getSchedule(int $groupId, string $startDate, string $endDate)` — JSON: матриця розкладу
- `getCurrentWeekRange()` — JSON: початок та кінець поточного тижня
- `getWeeks()` — JSON: всі 52 тижні року
- `getCourses()` — JSON: всі курси
- `getSubjects()` — JSON: всі предмети з викладачами
- `getTeachers()` — JSON: всі викладачі

### 10.4. HomeController

**Файл:** `app/Http/Controllers/HomeController.php`

- `index()` — домашня сторінка (view: home)

---

## 11. Laravel-додаток: Middleware

### 11.1. TenantMiddleware

**Файл:** `app/Http/Middleware/TenantMiddleware.php`

Основний middleware для мультитенантності. Детальний опис у розділі [6.4](#64-tenantmiddleware).

### 11.2. Стандартний middleware Laravel

| Middleware | Файл | Опис |
|-----------|------|------|
| Authenticate | `Authenticate.php` | Перенаправлення неавтентифікованих на 'login' |
| EncryptCookies | `EncryptCookies.php` | Шифрування cookies |
| PreventRequestsDuringMaintenance | `PreventRequestsDuringMaintenance.php` | Блокування під час обслуговування |
| RedirectIfAuthenticated | `RedirectIfAuthenticated.php` | Перенаправлення авторизованих |
| TrimStrings | `TrimStrings.php` | Обрізання пробілів |
| TrustProxies | `TrustProxies.php` | Довіра проксі-серверам |
| ValidateSignature | `ValidateSignature.php` | Перевірка підпису URL |
| VerifyCsrfToken | `VerifyCsrfToken.php` | Захист від CSRF |

### 11.3. HTTP Kernel

**Файл:** `app/Http/Kernel.php`

**Глобальний middleware:**
- TrustProxies, HandleCors, PreventRequestsDuringMaintenance, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull

**Група 'web':**
- EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, VerifyCsrfToken, SubstituteBindings

**Група 'api':**
- ThrottleRequests:api, SubstituteBindings

---

## 12. Laravel-додаток: Провайдери

### 12.1. AppServiceProvider

**Файл:** `app/Providers/AppServiceProvider.php`

Порожній провайдер (без користувацьких сервісів).

### 12.2. AuthServiceProvider

**Файл:** `app/Providers/AuthServiceProvider.php`

Реєстрація політик авторизації:
- `User::class` → `UserPolicy::class`
- `TeacherPreferenceRule::class` → `TeacherPreferenceRulePolicy::class`
- `RescheduleRequest::class` → `RescheduleRequestPolicy::class`

### 12.3. EventServiceProvider

**Файл:** `app/Providers/EventServiceProvider.php`

Реєстрація обробників подій:
- `Registered::class` → `SendEmailVerificationNotification::class`

### 12.4. FilamentServiceProvider

**Файл:** `app/Providers/FilamentServiceProvider.php`

Реєстрація Filament-ресурсів та навігаційних груп:
- Ресурси: TenantResource, RoomResource, CalendarResource, ActivityResource
- Навігаційні групи: 'SaaS', 'Розклад', 'Система'

### 12.5. AdminPanelProvider

**Файл:** `app/Providers/Filament/AdminPanelProvider.php`

Конфігурація Filament-панелі (детально у розділі [16](#16-filament-адмін-панель-конфігурація)).

### 12.6. RouteServiceProvider

**Файл:** `app/Providers/RouteServiceProvider.php`

- HOME = '/home'
- Реєстрація API та web маршрутів
- Обмеження частоти запитів: 60 запитів/хвилину на користувача

### 12.7. TenantServiceProvider

**Файл:** `app/Providers/TenantServiceProvider.php`

Реєстрація TenantManager як singleton з початковим значенням null.

---

## 13. Laravel-додаток: Маршрутизація

### 13.1. Веб-маршрути (routes/web.php)

#### Маршрути автентифікації:
```
GET  /register              → AuthController::showRegisterForm
POST /register              → AuthController::register
GET  /login                 → AuthController::showLoginForm
POST /login                 → AuthController::login
POST /logout                → AuthController::logout
```

#### Головна сторінка:
```
GET  /                      → view: welcome
```

#### Публічний розклад (без автентифікації):
```
GET  /s/{slug}                                      → PublicScheduleController::show
GET  /s/{slug}/api/groups/{courseId}                 → PublicScheduleController::getGroups
GET  /s/{slug}/api/schedule/{groupId}/{start}/{end}  → PublicScheduleController::getScheduleData
```

#### API-ендпоінти (автентифіковані):
```
GET  /api/courses/{courseId}/groups                  → ScheduleController::getCourseGroups
GET  /api/groups/{groupId}/schedule/{start}/{end}    → ScheduleController::getSchedule
GET  /api/weeks                                      → ScheduleController::getWeeks
GET  /api/current-week                               → ScheduleController::getCurrentWeekRange
GET  /api/courses                                    → ScheduleController::getCourses
GET  /api/subjects                                   → ScheduleController::getSubjects
GET  /api/teachers                                   → ScheduleController::getTeachers
```

### 13.2. API-маршрути (routes/api.php)

```
GET  /api/user                                       → Повертає поточного користувача (Sanctum)
```

### 13.3. Filament-маршрути (автоматичні)

Filament автоматично генерує маршрути для:
- `/admin` — Dashboard
- `/admin/schedule-generation` — Генерація розкладу
- `/admin/schedule-management` — Управління розкладом
- `/admin/teacher-schedule` — Розклад викладача
- `/admin/{resource}` — CRUD для кожного ресурсу
- `/admin/{resource}/create` — Створення
- `/admin/{resource}/{record}/edit` — Редагування

---

## 14. Laravel-додаток: Jobs та Commands

### 14.1. GenerateScheduleJob

**Файл:** `app/Jobs/GenerateScheduleJob.php`

Фонове завдання для генерації розкладу.

**Інтерфейс:** ShouldQueue

**Параметри:**
- `scheduleVersionId` (int) — ID версії розкладу
- `algorithm` (string, default: 'greedy') — алгоритм оптимізації

**Конфігурація:**
- **Timeout:** 660 секунд (11 хвилин)
- **Retries:** 1 спроба
- **Queue:** Redis

**Логіка виконання:**
1. Знаходить ScheduleVersion за ID
2. Встановлює статус 'generating'
3. Записує `generation_started_at`
4. Викликає `ScheduleGenerationService::generateForVersion()`
5. У разі успіху — статус 'draft', записує `generation_finished_at`
6. У разі помилки — статус 'failed', логує помилку
7. Записує параметри генерації в `generation_params` (JSONB)

### 14.2. GenerateScheduleCommand

**Файл:** `app/Console/Commands/GenerateScheduleCommand.php`

Artisan-команда для генерації розкладу з командного рядка.

---

## 15. Laravel-додаток: Policies

### 15.1. UserPolicy

**Файл:** `app/Policies/UserPolicy.php`

- `viewAny()` — дозвіл: isAdmin()
- `view()` — дозвіл: isAdmin()
- `create()` — дозвіл: isAdmin()
- `update()` — дозвіл: isAdmin()
- `delete()` — дозвіл: isAdmin() AND user_id ≠ model_id (не можна видалити себе)

### 15.2. TeacherPreferenceRulePolicy

**Файл:** `app/Policies/TeacherPreferenceRulePolicy.php`

Контроль доступу до правил побажань викладачів. Викладачі бачать тільки свої правила.

### 15.3. RescheduleRequestPolicy

**Файл:** `app/Policies/RescheduleRequestPolicy.php`

Контроль доступу до заявок на перенесення.

---

## 16. Filament адмін-панель: Конфігурація

### 16.1. Загальні налаштування панелі

**Файл:** `app/Providers/Filament/AdminPanelProvider.php`

| Параметр | Значення |
|----------|----------|
| ID панелі | `admin` |
| URL-шлях | `/admin` |
| Бренд | "Scheduler SaaS" |
| Основний колір | Indigo |
| Автентифікація | Login увімкнено, реєстрація вимкнена |
| Сторінка за замовчуванням | Dashboard |

### 16.2. Middleware стек Filament

1. EncryptCookies
2. AddQueuedCookiesToResponse
3. StartSession
4. AuthenticateSession
5. ShareErrorsFromSession
6. VerifyCsrfToken
7. SubstituteBindings
8. DisableBladeIconComponents
9. DispatchServingFilamentEvent
10. **TenantMiddleware** — мультитенантний middleware

### 16.3. Навігація

Навігаційні групи:
- **Управління даними** — Викладачі, Предмети, Курси, Групи
- **Розклад** — Заняття, Календарі, Аудиторії, Генерація розкладу, Управління розкладом, Заявки на перенос
- **Система** — Користувачі (згорнута за замовчуванням)
- **Налаштування** — Мій університет
- **Мій кабінет** — Мій розклад, Правила переваг (для викладачів)

Додатковий навігаційний елемент: "Публічна сторінка" — посилання на публічний розклад тенанта (відкривається в новій вкладці, видиме лише для авторизованих користувачів з тенантом).

### 16.4. Зареєстровані віджети

1. QuickAccessWidget — швидкий доступ
2. StatsOverviewWidget — статистика
3. ScheduleChartWidget — графік розподілу занять
4. RecentSchedulesWidget — останні версії розкладу
5. SubjectTypeChartWidget — розподіл типів активностей
6. AccountWidget — інформація про обліковий запис

---

## 17. Filament адмін-панель: Ресурси

### 17.1. TenantResource — "Мій університет"

**Файл:** `app/Filament/Resources/TenantResource.php`

**Навігація:** Іконка: building-office, Група: "Налаштування", Label: "Мій університет"
**Доступ:** Тільки admin (isAdmin())
**Обмеження:** Створення вимкнено, запит фільтрується до тенанта поточного користувача

**Форма:**
- `name` — текстове поле (обов'язкове, макс. 255)
- `subdomain` — текстове поле (вимкнене)
- `public_slug` — текстове поле (вимкнене)
- `is_active` — перемикач (за замовчуванням: true)
- `settings` — компонент KeyValue

**Таблиця:**
- `name` — пошук
- `public_slug` — форматований як URL, копіювання
- `is_active` — іконки (boolean)
- `created_at` — DateTime

### 17.2. UserResource — "Користувачі"

**Файл:** `app/Filament/Resources/UserResource.php`

**Навігація:** Іконка: users, Група: "Система", Label: "Користувачі"
**Доступ:** Тільки admin

**Форма:**
- `name` — текстове поле (обов'язкове, макс. 255)
- `email` — email поле (обов'язкове, унікальне)
- `password` — поле паролю (обов'язкове при створенні, мін. 8 символів, хешується)
- `role` — вибір: owner, admin, planner, teacher, viewer (за замовч.: viewer, live-оновлення)
- `teacher_id` — вибір викладача (видимий коли role === 'teacher')

**Таблиця:**
- `name` — пошук, сортування
- `email` — пошук, сортування, копіювання
- `role` — бейдж з кольоровим кодуванням (danger, warning, primary, success, gray)
- `teacher.name` — перемикач видимості (приховано за замовч.)
- `email_verified_at` — іконки (галочка/хрестик)
- `created_at` — DateTime, сортування

**Фільтри:** Verified / Unverified email

### 17.3. CalendarResource — "Календарі"

**Файл:** `app/Filament/Resources/CalendarResource.php`

**Навігація:** Іконка: calendar, Група: "Розклад", Label: "Календарі"
**Доступ:** Planner та вище

**Форма (3 секції):**

Секція "Основні параметри" (3 колонки):
- `name`, `start_date`, `end_date`, `weeks`, `days_per_week`
- `parity_enabled` — перемикач (для чергування тижнів)
- `first_week_parity` — вибір (num/den)

Секція "Налаштування пар" (3 колонки):
- `slots_per_day` (за замовч.: 6, live з авто-генерацією)
- `slot_duration_minutes` (за замовч.: 90, live)
- `break_duration_minutes` (за замовч.: 10, live)

Секція "Розклад пар" — Repeater:
- `slot_number` — вимкнене
- `start_time` — текстове поле з маскою часу (99:99)
- `end_time` — текстове поле з маскою часу

### 17.4. ActivityResource — "Заняття"

**Файл:** `app/Filament/Resources/ActivityResource.php`

**Навігація:** Іконка: book-open, Група: "Розклад", Label: "Заняття"
**Доступ:** Planner та вище

**Форма:**
- `subject_id` — вибір предмету (обов'язковий)
- `calendar_id` — вибір календаря (обов'язковий)
- `title` — назва (макс. 200)
- `activity_type` — вибір: lecture, lab, seminar, practice, pc
- `duration_slots` — ціле число (за замовч.: 1)
- `required_slots_per_period` — ціле число (за замовч.: 1)
- `groups` — мульти-вибір груп
- `teachers` — мульти-вибір викладачів
- `notes` — текстова область

### 17.5. RoomResource — "Аудиторії"

**Файл:** `app/Filament/Resources/RoomResource.php`

**Навігація:** Іконка: building-library, Група: "Розклад", Label: "Аудиторії"
**Доступ:** Planner та вище

**Форма:**
- `code` — код аудиторії (обов'язковий, макс. 20)
- `title` — назва (обов'язкова, макс. 100)
- `capacity` — місткість (за замовч.: 0)
- `room_type` — тип: lecture, lab, seminar, pc, gym, other
- `active` — перемикач (за замовч.: true)

**Фільтри:** room_type (Select), active (Ternary)

### 17.6. TeacherResource — "Викладачі"

**Файл:** `app/Filament/Resources/TeacherResource.php`

**Навігація:** Іконка: academic-cap, Група: "Управління даними", Label: "Викладачі"
**Доступ:** Planner та вище

**Форма:**
- `name` — ім'я (обов'язкове, макс. 255, повна ширина)
- `email` — email (обов'язковий, унікальний)
- `phone` — телефон (макс. 20)

**Таблиця:**
- `name`, `email` — пошук, сортування
- `phone` — пошук, перемикач видимості
- `subjects_count` — бейдж кількості (success)
- `activities_count` — бейдж кількості (info)

### 17.7. GroupResource — "Групи"

**Файл:** `app/Filament/Resources/GroupResource.php`

**Навігація:** Іконка: user-group, Група: "Управління даними", Label: "Групи"
**Доступ:** Planner та вище

**Форма:**
- `name` — назва (обов'язкова, макс. 255, повна ширина)
- `course_id` — вибір курсу (обов'язковий, пошук, preloaded)

**Фільтри:** course (Select), course_number (1-4 курс), has_activities

### 17.8. SubjectResource — "Предмети"

**Файл:** `app/Filament/Resources/SubjectResource.php`

**Навігація:** Іконка: book-open, Група: "Управління даними", Label: "Предмети"
**Доступ:** Planner та вище

**Форма:**
- `name` — назва (обов'язкова, макс. 255, повна ширина)
- `teacher_id` — вибір викладача (обов'язковий, пошук, preloaded)
- `type` — вибір типу з Subject::TYPES

### 17.9. CourseResource — "Курси"

**Файл:** `app/Filament/Resources/CourseResource.php`

**Навігація:** Іконка: academic-cap, Група: "Управління даними", Label: "Курси"
**Доступ:** Planner та вище

**Форма:**
- `name` — назва (обов'язкова, макс. 255, повна ширина)
- `number` — вибір номера курсу (1-4)

### 17.10. TeacherPreferenceRuleResource — "Правила переваг"

**Файл:** `app/Filament/Resources/TeacherPreferenceRuleResource.php`

**Навігація:** Іконка: adjustments-horizontal, Label: "Правила переваг"
**Доступ:** Admin або Teacher

**Динамічна навігаційна група:**
- Для викладачів: "Мій кабінет"
- Для адміністраторів: "Управління даними"

**Фільтрація запитів:** Викладачі бачать тільки свої правила.

**Форма:**
- `teacher_id` — вибір (видимий тільки для адмінів; для викладачів — автоматичне заповнення)
- `rule_type` — вибір з RULE_TYPES (обов'язковий, live, повна ширина)
- `params.day_of_week` — вибір дня (видимий для: unavailable_day, unavailable_slot, preferred_slot, min_start_slot, max_end_slot)
- `params.slot_index` — номер пари 1-8 (видимий для: unavailable_slot, preferred_slot)
- `params.min_slot` — мін. пара 1-8 (видимий для: min_start_slot)
- `params.max_slot` — макс. пара 1-8 (видимий для: max_end_slot)
- `params.max_hours` — макс. годин 1-8 (видимий для: max_hours_per_day)
- `weight` — вага 1-100 (за замовч.: 10)
- `is_active` — перемикач (за замовч.: true)
- `comment` — текстова область

**Таблиця:**
- `teacher.name` — видимий тільки для адмінів
- `rule_type` — бейдж з кольоровим кодуванням
- `description` — обгорнутий текст
- `weight` — сортування
- `is_active` — іконка
- `comment` — обрізано до 30 символів

### 17.11. RescheduleRequestResource — "Заявки на перенос"

**Файл:** `app/Filament/Resources/RescheduleRequestResource.php`

**Навігація:** Іконка: arrow-path, Група: "Розклад", Label: "Заявки на перенос"
**Доступ:** Planner та вище
**Бейдж навігації:** Кількість заявок в очікуванні (колір: warning)

**Таблиця:**
- `teacher.name` — пошук, сортування
- `assignment.activity.title` — обгорнутий текст
- `assignment` — форматований як "Пн, пара 1"
- `proposed_day_of_week` — форматований з назвами днів
- `status` — бейдж (pending=warning, approved=success, rejected=danger)
- `teacher_comment` — обрізано до 40 символів
- `created_at` — DateTime, сортування

**Дії (inline):**

1. **Approve (Затвердити):**
   - Іконка: check, Колір: success
   - Поле форми: admin_comment (текстова область)
   - Валідація конфліктів через RescheduleService
   - Модальне вікно підтвердження
   - Сповіщення

2. **Reject (Відхилити):**
   - Іконка: x-mark, Колір: danger
   - Поле форми: admin_comment (текстова область, мітка: "Причина відмови")
   - Модальне вікно підтвердження
   - Сповіщення

---

## 18. Filament адмін-панель: Сторінки

### 18.1. Dashboard — "Панель керування"

**Файл:** `app/Filament/Pages/Dashboard.php`

**Навігація:** Іконка: home, Sort: 1
**Маршрут:** `/admin`

**Поведінка:**
- При відкритті перевіряє роль користувача
- Якщо роль `teacher` — автоматичне перенаправлення на сторінку TeacherSchedule
- Для інших ролей — відображення Dashboard з віджетами

### 18.2. ScheduleGenerationPage — "Генерація розкладу"

**Файл:** `app/Filament/Pages/ScheduleGenerationPage.php`

**Навігація:** Іконка: cpu-chip, Група: "Розклад", Sort: 5
**Маршрут:** `/admin/schedule-generation`
**View:** `filament.pages.schedule-generation`
**Доступ:** Planner та вище

**Публічні властивості:**
- `calendar_id` — обраний календар
- `algorithm` — алгоритм (greedy, annealing, tabu)
- `w_windows` — вага вікон груп
- `w_teacher_windows` — вага вікон викладачів
- `w_prefs` — вага побажань
- `w_balance` — вага балансу
- `timeout` — таймаут у секундах
- `version_name` — назва версії
- `isGenerating` — статус генерації

**Секції форми:**

1. "Параметри генерації":
   - `calendar_id` — вибір календаря (обов'язковий)
   - `version_name` — назва версії (опціонально)
   - `timeout` — таймаут 30-1800 секунд (за замовч.: 420 = 7 хвилин)
   - `algorithm` — вибір алгоритму: Жадібний (greedy), Імітація відпалу (annealing), Табу-пошук (tabu)

2. "Ваги м'яких обмежень":
   - `w_windows` — штраф за вікна у групах (за замовч.: 10)
   - `w_teacher_windows` — штраф за вікна у викладачів (за замовч.: 1)
   - `w_prefs` — вага побажань викладачів (за замовч.: 5)
   - `w_balance` — вага балансу навантаження (за замовч.: 2)

**Методи:**
- `mount()` — завантаження значень ваг за замовчуванням з SoftWeights
- `generate()` — створення ScheduleVersion та відправка GenerateScheduleJob у чергу
- `publishVersion($id)` — публікація версії
- `archiveVersion($id)` — архівування версії

**View відображає:**
- Форму генерації з кнопкою "Згенерувати розклад"
- Таблицю останніх версій з колонками: Назва, Календар, Статус, Алгоритм, Призначення, Порушення, Дата
- Бейджі статусів: draft, published, archived, generating, failed
- Для версій зі статусом 'generating' — автоматичне оновлення кожні 5 секунд (polling)
- Кнопки дій: Опублікувати / Архівувати

### 18.3. ScheduleManagement — "Управління розкладом"

**Файл:** `app/Filament/Pages/ScheduleManagement.php`

**Навігація:** Іконка: calendar-days, Група: "Розклад", Sort: 6
**Маршрут:** `/admin/schedule-management`
**View:** `filament.pages.schedule-management`
**Доступ:** Planner та вище

Це найскладніша сторінка системи з інтерактивним редактором розкладу.

**Властивості стану:**

Фільтри:
- `selectedVersion` — обрана версія розкладу
- `selectedGroup` — фільтр за групою (опціонально)
- `startDate`, `endDate` — діапазон дат
- `weekLabel` — форматована мітка тижня

Модальне вікно редагування:
- `showEditModal`, `editingAssignmentId`
- `modalActivityId`, `modalRoomId`, `modalDayOfWeek`, `modalSlotIndex`, `modalParity`

Модальне вікно створення:
- `showCreateModal`
- `createActivityId`, `createRoomId`, `createDayOfWeek`, `createSlotIndex`, `createParity`

Панель статистики:
- Модальні вікна для додавання/видалення пропущених/зайвих призначень

**Основні функції:**

1. **Навігація по тижнях:**
   - Попередній/наступний тиждень, поточний тиждень
   - Мітка парності (Чисельник/Знаменник)
   - Автоматичне обмеження дат діапазоном календаря

2. **Матриця розкладу:**
   - Сітка: дати × часові слоти
   - Призначення згруповані за активністю, викладачами, групами, аудиторіями
   - Фільтрація за парністю
   - Кольорове кодування за типом предмету
   - Клік для редагування

3. **Редагування призначення:**
   - Зміна активності, аудиторії, дня, слоту, парності
   - Виявлення конфліктів (аудиторія, викладач, група)
   - Перемикач блокування (locked)

4. **Створення призначення:**
   - Вибір з доступних активностей
   - Вибір аудиторії, дня, слоту, парності
   - Виявлення конфліктів

5. **Статистика:**
   - Перегляд необхідних vs призначених слотів для кожної активності
   - Додавання пропущених призначень
   - Видалення зайвих призначень
   - Видалення можливе тільки для незаблокованих призначень

**Методи:**
- `prevWeek()`, `nextWeek()`, `currentWeek()` — навігація по тижнях
- `openEditModal($id)`, `closeEditModal()`, `saveAssignment()`, `deleteAssignment()` — управління модальними вікнами
- `toggleStats()`, `getSubjectStatsProperty()` — статистика
- `checkConflicts()` — перевірка конфліктів (аудиторія, викладач, група)

### 18.4. TeacherSchedule — "Мій розклад"

**Файл:** `app/Filament/Pages/TeacherSchedule.php`

**Навігація:** Іконка: calendar-days, Група: "Мій кабінет", Sort: 1
**Маршрут:** `/admin/teacher-schedule`
**View:** `filament.pages.teacher-schedule`
**Доступ:** Тільки викладачі з присвоєним teacher_id

**Властивості стану:**
- `startDate`, `endDate`, `weekLabel` — діапазон дат
- `showRescheduleModal`, `rescheduleAssignmentId` — модальне вікно
- `proposedDayOfWeek`, `proposedSlotIndex`, `proposedParity`, `proposedRoomId` — запропонований слот
- `teacherComment` — коментар викладача

**Функції:**

1. **Тижневий вигляд:**
   - Відображення опублікованого розкладу для викладача
   - Навігація по тижнях (попередній/наступний/поточний)
   - Мітка парності

2. **Заявка на перенесення:**
   - Модальне вікно з формою
   - Поля: запропонований день, слот, парність, аудиторія, коментар
   - Валідація конфліктів
   - Створення запису RescheduleRequest

3. **Обчислювані дані:**
   - `publishedVersion` — остання опублікована версія
   - `calendar` — календар версії
   - `timeSlots` — часові слоти календаря
   - `scheduleData` — матриця призначень викладача
   - `myRequests` — останні заявки на перенесення

**View відображає:**
- Тижневу таблицю розкладу (read-only)
- Кнопки навігації по тижнях з міткою парності
- Модальне вікно для подачі заявки на перенесення
- Таблицю заявок з колонками: Предмет, Запропонований час, Статус, Коментар адміна, Дата

---

## 19. Filament адмін-панель: Віджети

### 19.1. StatsOverviewWidget — Статистика

**Файл:** `app/Filament/Widgets/StatsOverviewWidget.php`

**Sort:** 1

Відображає 4 картки статистики:
1. **Групи** — загальна кількість груп (іконка: users, колір: info)
2. **Викладачі** — загальна кількість викладачів (іконка: user-group, колір: primary)
3. **Активності** — загальна кількість активностей (іконка: book-open, колір: warning)
4. **Аудиторії** — кількість активних аудиторій (іконка: building-office, колір: success)

### 19.2. QuickAccessWidget — Швидкий доступ

**Файл:** `app/Filament/Widgets/QuickAccessWidget.php`

**Sort:** 2, **Span:** повна ширина

Відображає 2 картки навігації:
- "Головна сторінка розкладу" — посилання на перегляд розкладу
- "Управління розкладом" — посилання на сторінку управління

### 19.3. ScheduleChartWidget — Графік розподілу занять

**Файл:** `app/Filament/Widgets/ScheduleChartWidget.php`

**Sort:** 3, **Span:** повна ширина
**Заголовок:** "Розподіл занять по днях тижня"

**Тип графіку:** Bar chart (стовпчаста діаграма)
**Дані:** Кількість призначень на кожен день тижня (з останньої опублікованої версії)
**Кольори:** 7 різних кольорів для кожного дня

### 19.4. RecentSchedulesWidget — Останні версії розкладу

**Файл:** `app/Filament/Widgets/RecentSchedulesWidget.php`

**Sort:** 4, **Span:** повна ширина
**Заголовок:** "Останні версії розкладу"

**Таблиця:**
- `name` — пошук, сортування
- `calendar.name` — сортування
- `status` — бейдж (draft=warning, published=success, archived=gray)
- `creator.name` — сортування
- `assignments_count` — кількість, сортування
- `created_at` — DateTime (формат: d.m.Y H:i), сортування

Відображає останні 10 версій, відсортованих за датою створення (DESC).

### 19.5. SubjectTypeChartWidget — Розподіл типів активностей

**Файл:** `app/Filament/Widgets/SubjectTypeChartWidget.php`

**Sort:** 5, **Span:** повна ширина
**Заголовок:** "Розподіл активностей по типах"

**Тип графіку:** Doughnut chart (кільцева діаграма)
**Дані:** Кількість активностей за типом:
- Лекції (lecture) — синій
- Практичні (practice) — зелений
- Лабораторні (lab) — бурштиновий
- Семінари (seminar) — червоний
- Комп'ютерні (pc) — фіолетовий

---

## 20. Go Solver сервіс: Архітектура

### 20.1. Структура проекту

```
solver/
├── cmd/server/main.go              — Точка входу HTTP-сервера
├── internal/
│   ├── db/postgres.go              — Рівень бази даних (pgx/v5)
│   ├── solver/
│   │   ├── scheduler.go            — Жадібний алгоритм та HTTP-обробник
│   │   ├── state.go                — Управління станом для метаевристик
│   │   ├── objective.go            — Об'єктивна функція (скоринг)
│   │   ├── simulated_annealing.go  — Алгоритм імітації відпалу
│   │   └── tabu_search.go          — Алгоритм табу-пошуку
│   ├── api/                        — (порожній, обробники в scheduler.go)
│   └── models/                     — (порожній, типи в pkg/types)
├── pkg/types/types.go              — Усі доменні типи та структури
├── go.mod & go.sum                 — Залежності Go
└── Dockerfile                      — Multi-stage збірка
```

### 20.2. Конфігурація сервера

**Файл:** `solver/cmd/server/main.go`

| Параметр | Змінна середовища | За замовчуванням |
|----------|-------------------|------------------|
| HTTP порт | `HTTP_PORT` | 8081 |
| URL бази даних | `DATABASE_URL` | `host=localhost user=postgres password=secret dbname=scheduler port=5432 sslmode=disable` |

**Функціональність:**
- Коректне завершення при SIGINT/SIGTERM з таймаутом 5 секунд
- Два ендпоінти: `/health` та `/api/v1/generate`

### 20.3. Маршрутизація алгоритмів

```go
switch req.Algorithm {
case "annealing", "cpsat":
    return s.solveAnnealing(ctx, input, req, startTime)
case "tabu":
    return s.solveTabuSearch(ctx, input, req, startTime)
default:
    return s.optimize(input, req)  // Жадібний (за замовчуванням)
}
```

- `"greedy"` або порожній рядок → Жадібний алгоритм
- `"annealing"` або `"cpsat"` → Імітація відпалу
- `"tabu"` → Табу-пошук

### 20.4. Обробка обмежень

**Жорсткі обмеження** (порушення неприпустимі):
- Заборона подвійного бронювання аудиторії (той самий слот, день, парність)
- Заборона подвійного бронювання викладача
- Заборона подвійного бронювання групи
- Недоступність (викладач, аудиторія, група)
- Місткість аудиторії ≥ розмір групи
- Тривалість заняття ≤ часовий слот
- Відповідність типу аудиторії

**М'які обмеження** (мінімізація порушень):
- Вікна в розкладі груп/викладачів (мінімізація фрагментації)
- Побажання викладачів (уникнення небажаних слотів)
- Баланс навантаження по днях
- Правила побажань (початок/кінець занять, максимум годин на день)

### 20.5. Обробка парності

Система підтримує три значення парності:
- `"both"` — заняття кожен тиждень
- `"num"` — заняття тільки на чисельнику (парних тижнях)
- `"den"` — заняття тільки на знаменнику (непарних тижнях)

Логіка конфліктів:
- Призначення `"both"` конфліктує з `"num"` та `"den"`
- Призначення `"num"` конфліктує з `"both"` та іншим `"num"`
- Призначення `"den"` конфліктує з `"both"` та іншим `"den"`
- Але `"num"` та `"den"` НЕ конфліктують між собою

---

## 21. Go Solver сервіс: API

### 21.1. GET /health

Ендпоінт перевірки стану здоров'я сервісу.

**Запит:** Без параметрів

**Відповідь:**
```json
{
  "status": "ok"
}
```

### 21.2. POST /api/v1/generate

Основний ендпоінт генерації розкладу.

**Тіло запиту (JSON):**
```json
{
  "tenant_id": "uuid-string",
  "calendar_id": 1,
  "schedule_id": 1,
  "group_ids": [1, 2, 3],
  "teacher_ids": [1, 2],
  "days": [1, 2, 3, 4, 5],
  "scope": {
    "group_ids": [1, 2],
    "teacher_ids": [1],
    "days": [1, 2, 3]
  },
  "weights": {
    "w_windows": 10.0,
    "w_teacher_windows": 1.0,
    "w_prefs": 5.0,
    "w_balance": 2.0
  },
  "timeout_seconds": 420,
  "algorithm": "greedy"
}
```

**Тіло відповіді (JSON):**
```json
{
  "status": "FEASIBLE",
  "assignment_ids": [1, 2, 3, ...],
  "violations": [
    {
      "activity_id": 5,
      "code": "GROUP_WINDOW",
      "severity": "soft",
      "meta": {"group_id": "3", "day": "1", "gap": "2"}
    }
  ],
  "total_violations": 3,
  "objective_value": 15.5,
  "solve_time_ms": 5230
}
```

**Управління таймаутом:**
- Якщо timeout < 30 секунд — використовується повний таймаут для вирішення
- Інакше — резервується 15 секунд для збереження результатів

**Коди порушень:**
- `UNPLACED_ACTIVITY` — активність не розміщена (severity: hard)
- `GROUP_WINDOW` — вікно в розкладі групи (severity: soft)
- `PREFERENCE_VIOLATION` — порушення побажання викладача (severity: soft)

**Статуси результату:**
- `FEASIBLE` — знайдено допустимий розклад
- `OPTIMAL` — знайдено оптимальний розклад
- `INFEASIBLE` — розклад неможливо скласти

---

## 22. Go Solver сервіс: Типи даних

### 22.1. Типи запитів

**ScheduleRequest** — запит на генерацію:

| Поле | Тип | Опис |
|------|-----|------|
| TenantID | string | ID тенанта |
| CalendarID | int64 | ID календаря |
| ScheduleID | int64 | ID версії розкладу |
| GroupIDs | []int64 | Фільтр за групами (опціонально) |
| TeacherIDs | []int64 | Фільтр за викладачами (опціонально) |
| Days | []int32 | Фільтр за днями (опціонально) |
| Scope | Scope | Явна структура фільтрації |
| Weights | Weights | Ваги об'єктивної функції |
| TimeoutSeconds | int32 | Таймаут вирішення |
| Algorithm | string | Алгоритм: greedy/annealing/cpsat/tabu |

**Weights** — коефіцієнти об'єктивної функції:

| Поле | Тип | Опис |
|------|-----|------|
| WWindows | float64 | Вага штрафу за вікна в розкладі груп/викладачів |
| WTeacherWindows | float64 | Вага штрафу за вікна у викладачів |
| WPrefs | float64 | Вага штрафу за порушення побажань |
| WBalance | float64 | Вага штрафу за дисбаланс навантаження |

### 22.2. Типи відповідей

**ScheduleResult** — результат генерації:

| Поле | Тип | Опис |
|------|-----|------|
| Status | ResultStatus | FEASIBLE/OPTIMAL/INFEASIBLE |
| AssignmentIDs | []int64 | ID створених призначень |
| Violations | []Violation | Знайдені порушення |
| TotalViolations | int32 | Кількість порушень |
| ObjectiveValue | float64 | Значення об'єктивної функції |
| SolveTimeMs | int64 | Час вирішення (мс) |

**Violation** — порушення обмеження:

| Поле | Тип | Опис |
|------|-----|------|
| ActivityID | int64 | ID активності |
| Code | string | Код порушення |
| Severity | string | hard або soft |
| Meta | map[string]string | Додаткові дані |

### 22.3. Доменні типи

**Activity** — навчальна активність:

| Поле | Тип | Опис |
|------|-----|------|
| ID | int64 | Ідентифікатор |
| SubjectID | int64 | ID предмету |
| SubjectName | string | Назва предмету |
| ActivityType | string | Тип заняття |
| DurationSlots | int32 | Тривалість у слотах |
| RequiredSlotsPerPeriod | int32 | Кількість занять на тиждень |
| GroupIDs | []int64 | ID прив'язаних груп |
| TeacherIDs | []int64 | ID прив'язаних викладачів |
| RoomTypes | []string | Необхідні типи аудиторій |
| GroupSize | int32 | Розмір групи |

**TimeSlot** — часовий слот:

| Поле | Тип | Опис |
|------|-----|------|
| ID | int64 | Ідентифікатор |
| DayOfWeek | int32 | День тижня (1-7) |
| SlotIndex | int32 | Номер пари |
| StartTime | string | Час початку |
| EndTime | string | Час закінчення |
| Parity | string | Парність (both/num/den) |
| CalendarID | int64 | ID календаря |

**Room** — аудиторія:

| Поле | Тип | Опис |
|------|-----|------|
| ID | int64 | Ідентифікатор |
| Code | string | Код |
| Title | string | Назва |
| Capacity | int32 | Місткість |
| RoomType | string | Тип аудиторії |
| Features | map[string]interface{} | Обладнання |
| Active | bool | Статус |

**Preference** — побажання викладача:

| Поле | Тип | Опис |
|------|-----|------|
| ID | int64 | Ідентифікатор |
| TeacherID | int64 | ID викладача |
| DayOfWeek | int32 | День тижня |
| SlotIndex | int32 | Номер пари |
| Parity | string | Парність |
| Weight | int32 | Вага (від'ємне = уникати) |

**PreferenceRule** — правило побажання:

| Поле | Тип | Опис |
|------|-----|------|
| TeacherID | int64 | ID викладача |
| RuleType | string | Тип правила |
| Params | map[string]interface{} | Параметри правила |
| Weight | int32 | Вага штрафу |
| IsActive | bool | Статус активності |

**Assignment** — призначення:

| Поле | Тип | Опис |
|------|-----|------|
| ID | int64 | Ідентифікатор |
| ScheduleID | int64 | ID версії розкладу |
| ActivityID | int64 | ID активності |
| DayOfWeek | int32 | День тижня |
| SlotIndex | int32 | Номер пари |
| Parity | string | Парність |
| RoomID | int64 | ID аудиторії |
| Locked | bool | Заблоковано |
| Source | string | Джерело: solver/manual |

**ScheduleInput** — внутрішня структура, що об'єднує всі дані для вирішення задачі.

---

## 23. Go Solver сервіс: Рівень бази даних

### 23.1. Загальний огляд

**Файл:** `solver/internal/db/postgres.go`

Рівень доступу до бази даних використовує pgx/v5 з пулом з'єднань.

### 23.2. Методи інтерфейсу DB

#### `GetScheduleInput(ctx, tenantID, calendarID, scheduleID) → ScheduleInput`

Завантажує повну задачу для вирішення:

1. **Активності** — SQL-запит з JOIN на subjects та агрегацією через array_agg для:
   - group_ids (DISTINCT)
   - teacher_ids (DISTINCT)
   - room_types (DISTINCT)
   - max(group.size) — максимальний розмір групи

2. **Часові слоти** — відфільтровані за `enabled = true`, відсортовані за day_of_week та slot_index

3. **Аудиторії** — відфільтровані за `active = true`

4. **Групи** — відфільтровані за `active = true`

5. **Викладачі** — всі викладачі тенанта

6. **Недоступності** — UNION з трьох таблиць:
   - teacher_unavailability (entity_type = 'teacher')
   - room_unavailability (entity_type = 'room')
   - group_unavailability (entity_type = 'group')

7. **Побажання** — з таблиці teacher_preferences

8. **Правила побажань** — з таблиці teacher_preference_rules (тільки is_active = true)

#### `SaveAssignments(ctx, tenantID, scheduleID, assignments) → []int64`

Збереження розв'язку у транзакції:
1. Видалення існуючих незаблокованих призначень
2. Вставка нових призначень
3. Повернення масиву ID створених записів

Колонки: tenant_id, schedule_version_id, activity_id, day_of_week, slot_index, parity, room_id, locked, source

#### `SaveViolations(ctx, tenantID, scheduleID, violations)`

Збереження порушень у транзакції:
1. Видалення існуючих порушень
2. Вставка нових записів

Колонки: tenant_id, schedule_version_id, activity_id, code, severity, meta (JSONB)

#### `GetUnavailabilities(ctx, tenantID, calendarID) → []Unavailability`

UNION-запит з трьох таблиць недоступності.

#### `GetPreferences(ctx, tenantID) → []Preference`

Побажання викладачів.

#### `GetPreferenceRules(ctx, tenantID) → []PreferenceRule`

Активні правила побажань.

---

## 24. Go Solver сервіс: Жадібний алгоритм

### 24.1. Загальний огляд

**Файл:** `solver/internal/solver/scheduler.go`

Жадібний алгоритм є базовим та найшвидшим алгоритмом оптимізації. Він послідовно розміщує активності у найкращі доступні слоти.

### 24.2. Потік виконання

1. **Ініціалізація:**
   - Створення структур зайнятості (busy maps) для аудиторій, викладачів та груп
   - Формат ключа: `"день_слот_парність"`
   - Сортування активностей за `RequiredSlotsPerPeriod` (спадання) — складніші обмеження першими
   - Визначення ефективних значень парності ("both" або ["num", "den"])

2. **Основний цикл** — для кожної активності:
   - Для кожного необхідного слоту (required_slots_per_period):
     - Перебір усіх часових слотів та аудиторій
     - Перевірка доступності (група, викладач, аудиторія)
     - Перевірка недоступностей
     - Обчислення оцінки кандидата
     - Вибір найкращого кандидата
     - Позначення слоту як зайнятого
     - Оновлення відстеження день-слот для груп

3. **Якщо активність не розміщена:**
   - Створення порушення `UNPLACED_ACTIVITY` (severity: hard)

### 24.3. Перевірка конфліктів

Метод `busyConflict()` перевіряє чи слот конфліктує з існуючими призначеннями:
- Призначення `"both"` конфліктує з `"num"` та `"den"`
- Призначення `"num"`/`"den"` конфліктують з `"both"` та одне з одним (якщо та сама парність)

### 24.4. Функція оцінки кандидата

Метод `scoreCandidate()` обчислює зважений штраф за розміщення активності у конкретному слоті:

**Компонент "Вікна" (Gap Penalty):**
- Для кожної групи на цьому дні/парності — обчислення квадратичного штрафу за вікна
- Формула: `sum(gap²)`, де gap = кількість порожніх слотів між призначеннями
- Вага: `WWindows`
- Квадратичний штраф більше карає великі вікна

**Компонент "Баланс" (Balance Penalty):**
- Для кожної групи — підрахунок поточних призначень на цільовому дні
- Штраф: `dayLoad × weight × 0.5`
- Вага: `WBalance`
- Заохочує рівномірний розподіл по днях

**Компонент "Побажання" (Preference Penalty):**
- Для кожного від'ємного побажання, що збігається з викладачем та слотом
- Штраф: `abs(weight) × wPrefs × 0.1`
- Вага: `WPrefs`
- Заохочує уникнення небажаних слотів

### 24.5. Обчислення вікон

Метод `computeGapsQuadratic()`:
1. Отримує відсортований масив унікальних зайнятих слотів
2. Для кожної пари сусідніх слотів обчислює gap = slot[i+1] - slot[i] - 1
3. Повертає `sum(gap²)` — квадратична функція штрафує великі вікна значно більше

### 24.6. Виявлення м'яких порушень

**checkWindows():**
- Знаходить порушення `GROUP_WINDOW` — вікна в розкладі груп
- Для кожної групи, дня та парності перевіряє наявність прогалин
- Повертає масив порушень з метаданими (group_id, day, gap)

**checkPreferences():**
- Знаходить порушення `PREFERENCE_VIOLATION`
- Для кожного від'ємного побажання перевіряє наявність призначення у цьому слоті
- Повертає масив порушень

### 24.7. Складність алгоритму

- **Часова:** O(activities × timeSlots × rooms) на кожне розміщення
- **Просторова:** O(rooms + teachers + groups) для структур зайнятості
- **Швидкість:** Обробляє 100+ активностей за секунди

---

## 25. Go Solver сервіс: Управління станом

### 25.1. Загальний огляд

**Файл:** `solver/internal/solver/state.go`

Управління станом використовується алгоритмами імітації відпалу та табу-пошуку.

### 25.2. Структура SAState

```go
type SAState struct {
    Input       *types.ScheduleInput
    Assignments []types.Assignment
    Score       float64
    Weights     types.Weights

    RoomBusy    map[int64]map[string]bool    // entityID → "day_slot_parity" → bool
    TeacherBusy map[int64]map[string]bool
    GroupBusy   map[int64]map[string]bool

    ActivityAssignments map[int64][]int       // actID → індекси в Assignments
}
```

### 25.3. Побудова стану

Метод `buildState()`:
1. Копіює призначення з жадібного розв'язку
2. Будує структури зайнятості (busy maps) з призначень
3. Обчислює початкову оцінку
4. Створює відображення активностей на індекси призначень

### 25.4. Перевірка допустимості

Метод `isFeasible()` перевіряє чи переміщення допустиме:
1. Відсутність порушень жорстких обмежень
2. Перевірка конфліктів аудиторії/викладача/групи з урахуванням парності
3. Перевірка недоступностей
4. Виключення оригінального слоту при оцінці переміщень

### 25.5. Операції зі структурами зайнятості

- `removeFromBusy(assignment)` — очищення слоту з усіх busy maps
- `addToBusy(assignment)` — встановлення слоту в усіх busy maps

### 25.6. Застосування та скасування переміщень

- `applyMove(move)` — застосування переміщення до стану
- `undoMove(move)` — скасування переміщення
- Підтримуються типи: SwapSlot, SwapRoom, SwapTwo, Compact

### 25.7. Типи переміщень

```go
type MoveType int
const (
    MoveSwapSlot    // Зміна дня/слоту одного призначення
    MoveSwapRoom    // Зміна аудиторії одного призначення
    MoveSwapTwo     // Обмін слотами між двома призначеннями
    MoveUnplace     // Видалення призначення (не використовується)
    MoveReplace     // Видалення та повторне розміщення (не використовується)
    MoveCompact     // Переміщення для зменшення вікон
)
```

### 25.8. Генерація випадкових переміщень

Метод `randomMove()` генерує переміщення з ймовірностями:
- **25% SwapSlot** — переміщення призначення у випадковий допустимий слот
- **20% SwapRoom** — зміна аудиторії (якщо є сумісні альтернативи)
- **25% SwapTwo** — обмін слотами між двома призначеннями
- **30% Compact** — переміщення для зменшення вікон

Робиться до 50 спроб знайти допустиме переміщення.

### 25.9. Логіка Compact-переміщення

Метод `generateCompactMove()`:
1. Обрати випадкове призначення з групи
2. Знайти інші зайняті слоти на тому ж дні/парності
3. Генерувати цільові слоти, суміжні з зайнятими (min-1, min+1, max-1, max+1)
4. Спробувати перемістити призначення для зменшення вікон

---

## 26. Go Solver сервіс: Об'єктивна функція

### 26.1. Загальний огляд

**Файл:** `solver/internal/solver/objective.go`

Об'єктивна функція обчислює загальний штраф розв'язку (менше = краще).

### 26.2. Головна функція calculateScore()

Повертає загальний штраф. Компоненти:

#### Штраф за нерозміщені активності (множник: 1000.0)

Для кожної активності з меншою кількістю розміщень ніж потрібно:
```
penalty += (required - placed) × 1000
```
Це жорсткий штраф, що пріоритизує допустимість розв'язку.

#### Штраф за вікна (ваги: WWindows, WTeacherWindows)

Метод `countWindowGapsSeparate()`:
1. Для кожної сутності (група або викладач), дня та парності:
   - Дедуплікація та сортування зайнятих слотів
   - Обчислення: `sum(gap²)` для кожного вікна між слотами
2. Окремий підрахунок для груп (WWindows) та викладачів (WTeacherWindows)
3. Якщо WTeacherWindows не вказано — за замовчуванням 1.0

#### Штраф за порушення побажань (вага: WPrefs)

Метод `evalPreferenceRules()`:

**Спадкові побажання (legacy):**
- Від'ємна вага = уникнення
- Штраф: `abs(weight) × count_violations`

**Правила побажань (4 типи):**

1. **preferred_slot (бажаний слот):**
   - Параметри: day_of_week, slot_index
   - Бонус (від'ємний штраф) за використання бажаного слоту
   - Зміна: `-weight / 10.0`

2. **min_start_slot (мінімальний початковий слот):**
   - Параметри: min_slot, (опціонально) day_of_week
   - Штраф: `weight` за кожне призначення перед min_slot
   - Опціонально: фільтрація за конкретним днем

3. **max_end_slot (максимальний кінцевий слот):**
   - Параметри: max_slot, (опціонально) day_of_week
   - Штраф: `weight` за кожне призначення після max_slot
   - Опціонально: фільтрація за конкретним днем

4. **max_hours_per_day (максимум годин на день):**
   - Параметри: max_hours
   - Штраф: `(excess_load - max_hours) × weight` за кожен день
   - Карає перевантажені дні

#### Штраф за дисбаланс (вага: WBalance)

Метод `evalBalance()`:
1. Для кожної сутності (група та викладач):
   - Підрахунок призначень за день
   - Обчислення: max_load - min_load
   - Штраф: `(max - min)`
2. Заохочує рівномірний розподіл по днях

### 26.3. Допоміжні функції

- `countGapsInMap()` — обчислення квадратичних штрафів за вікна
- `collectParities()` — вилучення унікальних значень парності з часових слотів
- `collectDays()` — вилучення унікальних значень днів
- `parityMatches()` — перевірка сумісності двох значень парності
- `parityConflicts()` — аліас для перевірки сумісності
- `getIntParam()` — безпечне вилучення int-параметрів з map Params
- `slotKey()` — генерація ключа "день_слот_парність"
- `abs32()` — абсолютне значення для int32

---

## 27. Go Solver сервіс: Імітація відпалу

### 27.1. Загальний огляд

**Файл:** `solver/internal/solver/simulated_annealing.go`

Імітація відпалу (Simulated Annealing, SA) — метаевристичний алгоритм, що виходить з локальних оптимумів через ймовірнісне прийняття гірших розв'язків.

### 27.2. Ініціалізація

1. Запуск жадібного алгоритму для отримання початкового розв'язку
2. Побудова SAState з жадібного розв'язку
3. Обчислення початкової оцінки

### 27.3. Адаптивне управління температурою

```
Початкова температура:
  - 5% від початкової оцінки
  - Обмеження: [1.0, 50.0]
  - Забезпечує ~37% прийняття на 5% гірших переміщень на початку

Швидкість охолодження:
  - Базується на таймауті для досягнення мінімальної температури
  - Формула: coolingRate = (minTemp / initialTemp) ^ (1.0 / estimatedTotalSteps)
  - Обмеження: [0.99, 0.9999]
  - Повільніше охолодження = більше дослідження
```

### 27.4. Стратегія ітерацій

- `iterPerTemp = max(кількість_активностей × 3, 20)` ітерацій на рівень температури
- Оцінка ~500 кроків температури за секунду
- Продовження до temp < saMinTemp (0.01)

### 27.5. Прийняття переміщень

Для кожного переміщення:
1. Обчислення delta = newScore - oldScore
2. Прийняття якщо:
   - delta < 0 (покращення), АБО
   - random() < exp(-delta / temp) (ймовірнісне прийняття)
3. Якщо прийнято та краще за найкраще — оновлення найкращого розв'язку

### 27.6. Інтенсифікація та диверсифікація

**Логіка reseed:** Якщо немає покращення протягом `iterPerTemp × 200` ітерацій:
- Скидання стану до найкращого розв'язку
- Зменшення температури на 50% (фокусування пошуку)
- Очищення лічильника покращень

### 27.7. Статистика та логування

- Відстеження: totalIter, accepted, improved, reseedCount
- Логування кожні `iterPerTemp × 100` ітерацій
- Вивід: температура, поточна оцінка, найкраща оцінка

### 27.8. Завершення

- Коли температура падає нижче 0.01, АБО
- Таймаут контексту (повертає найкращий знайдений)

### 27.9. Вивід

- Найкращі призначення за весь прогін
- Порушення обчислені з найкращого розв'язку
- Значення об'єктивної функції найкращого розв'язку

### 27.10. Характеристики продуктивності

- Час: визначається таймаутом (420 секунд за замовчуванням)
- Ітерації: ~500 кроків температури × iterPerTemp
- Покращення жадібного на 5-15%

---

## 28. Go Solver сервіс: Табу-пошук

### 28.1. Загальний огляд

**Файл:** `solver/internal/solver/tabu_search.go`

Табу-пошук (Tabu Search) — детерміністичний алгоритм локального пошуку, що підтримує список заборонених (табу) переміщень для уникнення циклів.

### 28.2. Ініціалізація

1. Запуск жадібного алгоритму для початкового розв'язку
2. Побудова SAState
3. Ініціалізація порожнього табу-списку

### 28.3. Управління табу-списком

- Кільцевий буфер розміром 50
- Зберігає `tabuMoveKey` = {AssignmentIndex, NewDay, NewSlot}
- Запобігає повторному відвідуванню нещодавніх переміщень

### 28.4. Стратегія ітерацій

```
Для кожної ітерації:
  1. Генерація tabuNeighborhood (30) випадкових сусідів
  2. Оцінка кожного переміщення
  3. Вибір найкращого сусіда якщо:
     - Не табу та краще за поточний, АБО
     - Табу але задовольняє аспірацію (краще за глобальний найкращий)
  4. Додавання переміщення до табу-списку
  5. Оновлення найкращого якщо покращення
  6. Продовження до 50,000 ітерацій або відсутності допустимих переміщень
```

### 28.5. Критерій аспірації

- Дозволяє табу-переміщення, якщо вони перевищують глобальний найкращий результат
- Дозволяє алгоритму вийти з табульованих регіонів для значних покращень

### 28.6. Завершення

- Досягнуто 50,000 ітерацій, АБО
- Таймаут контексту, АБО
- Не знайдено допустимих переміщень (після перевірки 10,000+ ітерацій)

### 28.7. Характеристики продуктивності

- Час: до 50,000 ітерацій (зазвичай швидше за SA)
- Оцінює 30 сусідів на ітерацію
- Детермінований, повторюваний результат

---

## 29. Фронтенд: Blade-шаблони

### 29.1. Загальний огляд

Фронтенд побудований на Blade-шаблонах з використанням Tailwind CSS (CDN) та Alpine.js (CDN). Загалом 13 файлів, ~2,759 рядків коду.

### 29.2. Головний макет

**Файл:** `resources/views/layouts/app.blade.php` (173 рядки)

- Навігаційна панель з логотипом та посиланнями
- Підтримка темної теми (dark mode) через Alpine.js + localStorage
- Адаптивне мобільне меню (hamburger)
- Футер з копірайтом
- CDN-завантаження: Tailwind CSS та Alpine.js v3

### 29.3. Публічні сторінки

#### Welcome (Лендінг)

**Файл:** `resources/views/welcome.blade.php` (174 рядки)

- Hero-секція з описом системи (українською мовою)
- Секція "Можливості" з 3 картками:
  - Автоматична генерація розкладу
  - Побажання викладачів
  - Публічні посилання
- Секція "Як це працює" — 4 кроки: реєстрація → дані → генерація → публікація
- CTA-секція з кнопками реєстрації/входу
- Адаптивний дизайн з градієнтами Tailwind

#### Публічний розклад

**Файл:** `resources/views/public-schedule.blade.php` (490 рядків)

Найскладніша публічна сторінка — переглядач розкладу без авторизації:
- Фільтри: Курс → Група → Тиждень
- Навігація по тижнях з підтримкою парності (чисельник/знаменник)
- Обмеження навігації межами календаря
- Кольорове кодування типів занять:
  - Лекції — синій
  - Практичні — зелений
  - Лабораторні — бурштиновий
  - Семінари — червоний
  - Комп'ютерні — фіолетовий
- Легенда та порожні стани
- Alpine.js для інтерактивності
- API-виклики для динамічного завантаження даних

### 29.4. Сторінки автентифікації

#### Login

**Файл:** `resources/views/auth/login.blade.php` (88 рядків)

- Форма входу з підтримкою темної теми
- Поля: email, пароль
- Чекбокс "Запам'ятати мене"
- Відображення помилок валідації
- Посилання на реєстрацію
- Градієнтний заголовок з іконкою календаря

#### Register

**Файл:** `resources/views/auth/register.blade.php` (103 рядки)

- Форма реєстрації з темною темою
- Поля: ім'я, email, назва університету, пароль, підтвердження паролю
- Відображення помилок валідації
- Посилання на вхід

### 29.5. Filament-шаблони

#### Генерація розкладу

**Файл:** `resources/views/filament/pages/schedule-generation.blade.php` (132 рядки)

- Форма генерації (wire:submit="generate")
- Кнопка "Згенерувати розклад" та посилання на публічну сторінку
- Таблиця останніх версій:
  - Назва, Календар, Статус, Алгоритм, Призначення, Порушення, Дата
  - Бейджі статусів з кольорами
  - Алгоритм + значення об'єктивної функції
  - Дії: публікація/архівування
  - Polling (5с) для версій зі статусом 'generating'

#### Управління розкладом

**Файл:** `resources/views/filament/pages/schedule-management.blade.php` (629 рядків)

Найскладніший шаблон системи:
- Фільтри: вибір версії, групи, навігація по тижнях
- Панель статистики покриття предметів
- Інтерактивна таблиця розкладу:
  - Кольорове кодування типів
  - Клік для редагування
  - Hover-дії: блокування/розблокування, видалення
  - Клік по порожньому слоту — створення
- Модальні вікна: редагування, створення, статистика
- CSS для стилізації типів занять (light/dark mode)

#### Розклад викладача

**Файл:** `resources/views/filament/pages/teacher-schedule.blade.php` (272 рядки)

- Тижневий вигляд розкладу (read-only)
- Навігація по тижнях з парністю
- Модальне вікно для заявок на перенесення
- Таблиця заявок (предмет, час, статус, коментар, дата)

#### Віджет швидкого доступу

**Файл:** `resources/views/filament/widgets/quick-access-widget.blade.php` (59 рядків)

- 2 картки навігації з іконками Heroicon

### 29.6. Компоненти

#### Таблиця розкладу

**Файл:** `resources/views/components/schedule-table.blade.php` (226 рядків)

Повторно використовуваний компонент з `@props(['editable' => false])`:
- Відображення часових слотів (рядки) × дат (стовпці)
- Кольорове кодування типів
- Якщо editable: модальні вікна для додавання/редагування/видалення
- Обробка парності
- Валідація форм

---

## 30. Потік генерації розкладу

### 30.1. Повна послідовність дій

```
Користувач (Planner)        Laravel App              Queue Worker         Go Solver           PostgreSQL
       │                        │                        │                   │                    │
       │ 1. Обирає параметри    │                        │                   │                    │
       │    та натискає          │                        │                   │                    │
       │    "Згенерувати"        │                        │                   │                    │
       │──────────────────────▶  │                        │                   │                    │
       │                        │ 2. Створює              │                   │                    │
       │                        │    ScheduleVersion      │                   │                    │
       │                        │    (status: draft)      │                   │                    │
       │                        │──────────────────────────────────────────────────────────────────▶│
       │                        │                        │                   │                    │
       │                        │ 3. Відправляє          │                   │                    │
       │                        │    GenerateScheduleJob  │                   │                    │
       │                        │    у чергу Redis       │                   │                    │
       │                        │──────────────────────▶  │                   │                    │
       │                        │                        │                   │                    │
       │ 4. Отримує відповідь   │                        │                   │                    │
       │    (status: generating) │                        │                   │                    │
       │◀──────────────────────  │                        │                   │                    │
       │                        │                        │                   │                    │
       │ 5. Polling (5с)        │                        │ 6. Обробляє Job   │                    │
       │                        │                        │    Встановлює     │                    │
       │                        │                        │    generating     │                    │
       │                        │                        │─────────────────────────────────────────▶│
       │                        │                        │                   │                    │
       │                        │                        │ 7. HTTP POST      │                    │
       │                        │                        │    /api/v1/generate│                   │
       │                        │                        │──────────────────▶│                    │
       │                        │                        │                   │                    │
       │                        │                        │                   │ 8. Читає дані      │
       │                        │                        │                   │    (activities,     │
       │                        │                        │                   │     rooms, teachers,│
       │                        │                        │                   │     constraints)    │
       │                        │                        │                   │──────────────────▶  │
       │                        │                        │                   │◀──────────────────  │
       │                        │                        │                   │                    │
       │                        │                        │                   │ 9. Виконує         │
       │                        │                        │                   │    алгоритм        │
       │                        │                        │                   │    оптимізації     │
       │                        │                        │                   │                    │
       │                        │                        │                   │ 10. Зберігає       │
       │                        │                        │                   │     assignments    │
       │                        │                        │                   │     та violations  │
       │                        │                        │                   │──────────────────▶  │
       │                        │                        │                   │◀──────────────────  │
       │                        │                        │                   │                    │
       │                        │                        │ 11. Отримує       │                    │
       │                        │                        │     результат    │                    │
       │                        │                        │◀──────────────────│                    │
       │                        │                        │                   │                    │
       │                        │                        │ 12. Оновлює      │                    │
       │                        │                        │     status=draft  │                    │
       │                        │                        │     та metadata   │                    │
       │                        │                        │─────────────────────────────────────────▶│
       │                        │                        │                   │                    │
       │ 13. Polling бачить     │                        │                   │                    │
       │     status=draft       │                        │                   │                    │
       │     та результати      │                        │                   │                    │
       │◀──────────────────────  │                        │                   │                    │
       │                        │                        │                   │                    │
       │ 14. Натискає           │                        │                   │                    │
       │     "Опублікувати"     │                        │                   │                    │
       │──────────────────────▶  │                        │                   │                    │
       │                        │ 15. Змінює status       │                   │                    │
       │                        │     на published       │                   │                    │
       │                        │──────────────────────────────────────────────────────────────────▶│
       │                        │                        │                   │                    │
```

### 30.2. Параметри генерації

| Параметр | Діапазон | За замовчуванням | Опис |
|----------|----------|------------------|------|
| calendar_id | — | обов'язковий | ID календаря |
| algorithm | greedy/annealing/tabu | greedy | Алгоритм оптимізації |
| timeout | 30-1800 с | 420 с (7 хв) | Таймаут вирішення |
| w_windows | 0+ | 10 | Штраф за вікна груп |
| w_teacher_windows | 0+ | 1 | Штраф за вікна викладачів |
| w_prefs | 0+ | 5 | Вага побажань |
| w_balance | 0+ | 2 | Вага балансу |
| version_name | текст | автоматична назва | Назва версії |

### 30.3. Порівняння алгоритмів

| Характеристика | Жадібний | Імітація відпалу | Табу-пошук |
|---------------|----------|-----------------|------------|
| Швидкість | Дуже швидко | Повільно (залежить від timeout) | Середньо |
| Якість | Базова | Найкраща | Хороша |
| Детермінованість | Ні (залежить від порядку) | Ні (стохастичний) | Так |
| Вихід з лок. оптимуму | Ні | Так | Так |
| Рекомендація | Для швидкого прев'ю | Для фінального розкладу | Для відтворюваних результатів |

---

## 31. Публічний доступ до розкладу

### 31.1. URL-структура

Публічний розклад доступний за адресою: `/s/{public_slug}`

Наприклад: `http://localhost:8080/s/kpi`

### 31.2. API-ендпоінти публічного розкладу

```
GET /s/{slug}                                         → HTML-сторінка
GET /s/{slug}/api/groups/{courseId}                    → JSON: групи курсу
GET /s/{slug}/api/schedule/{groupId}/{start}/{end}     → JSON: матриця розкладу
```

### 31.3. Функціональність

- **Не вимагає автентифікації** — доступний для всіх
- **Фільтрація:** Курс → Група → Тиждень
- **Навігація по тижнях** з обмеженням межами календаря
- **Підтримка парності** — чисельник/знаменник
- **Кольорове кодування** типів занять
- **Тільки опублікована версія** — чернетки та архівні не видимі

### 31.4. Структура відповіді API

```json
{
  "schedule": {
    "2026-09-01": {
      "1": [{
        "activity_id": 1,
        "subject_name": "Математика",
        "activity_type": "lecture",
        "teacher_names": ["Іванов І.І."],
        "group_names": ["КП-11", "КП-12"],
        "room_code": "305",
        "parity": "both"
      }]
    }
  },
  "time_slots": [...],
  "date_range": {"start": "2026-09-01", "end": "2026-09-06"},
  "version": {"name": "Версія 1", "published_at": "..."},
  "calendar_bounds": {"start": "2026-09-01", "end": "2027-01-15"}
}
```

---

## 32. Система перенесення занять

### 32.1. Загальний огляд

Система дозволяє викладачам подавати заявки на перенесення своїх занять. Адміністратори можуть схвалювати або відхиляти заявки.

### 32.2. Процес перенесення

```
Викладач                          Адміністратор
    │                                  │
    │ 1. Відкриває "Мій розклад"       │
    │ 2. Обирає заняття                │
    │ 3. Вказує новий день/слот/пару   │
    │ 4. Додає коментар                │
    │ 5. Подає заявку                  │
    │ ───────── pending ─────────────▶ │
    │                                  │ 6. Переглядає заявку
    │                                  │ 7. Перевіряє конфлікти
    │                                  │ 8. Схвалює/відхиляє
    │ ◀──── approved/rejected ──────── │
    │                                  │
    │ 9. Бачить оновлений розклад      │
    │    (якщо схвалено)               │
```

### 32.3. Валідація заявки

`RescheduleService::validateProposal()` перевіряє:
1. **Конфлікт аудиторії** — чи вільна запропонована аудиторія
2. **Конфлікт викладача** — чи не зайнятий викладач у цей час
3. **Конфлікт групи** — чи не зайняті групи у цей час
4. **Парність** — коректна обробка both/num/den

### 32.4. Схвалення заявки

`RescheduleService::approve()` виконує у транзакції:
1. Створення нової дочірньої ScheduleVersion
2. Копіювання всіх призначень поточної версії
3. Зміна цільового призначення на запропонований слот
4. Оновлення статусу заявки на APPROVED
5. Повернення нової версії

### 32.5. Відхилення заявки

`RescheduleService::reject()` — просте оновлення статусу на REJECTED з коментарем адміністратора.

### 32.6. Навігаційний бейдж

У бічній навігації ресурсу RescheduleRequestResource відображається бейдж з кількістю заявок в стані 'pending' (колір: warning).

---

## 33. Структура файлів проекту

### 33.1. Кореневі файли

```
/scheduler
├── .env                          — Конфігурація середовища
├── .env.example                  — Шаблон конфігурації
├── docker.env.example            — Шаблон Docker-конфігурації
├── composer.json                 — PHP-залежності
├── composer.lock                 — Зафіксовані версії PHP-пакетів
├── docker-compose.yml            — Оркестрація Docker-сервісів
├── Makefile                      — Команди розробки
├── artisan                       — Точка входу Laravel CLI
├── CLAUDE.md                     — Інструкції для Claude Code
├── README.md                     — Документація проекту
└── DOC.md                        — Ця документація
```

### 33.2. Каталог app/ (102 файли)

```
app/
├── Console/
│   ├── Kernel.php                            — Конфігурація планувальника
│   └── Commands/
│       └── GenerateScheduleCommand.php       — Artisan-команда генерації
├── Exceptions/
│   └── Handler.php                           — Обробник винятків
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php                     — Панель керування
│   │   ├── ScheduleGenerationPage.php        — Генерація розкладу
│   │   ├── ScheduleManagement.php            — Управління розкладом
│   │   └── TeacherSchedule.php               — Розклад викладача
│   ├── Resources/
│   │   ├── ActivityResource.php              — Ресурс занять
│   │   ├── CalendarResource.php              — Ресурс календарів
│   │   ├── CourseResource.php                — Ресурс курсів
│   │   ├── GroupResource.php                 — Ресурс груп
│   │   ├── RescheduleRequestResource.php     — Ресурс заявок
│   │   ├── RoomResource.php                  — Ресурс аудиторій
│   │   ├── SubjectResource.php               — Ресурс предметів
│   │   ├── TeacherPreferenceRuleResource.php — Ресурс правил побажань
│   │   ├── TeacherResource.php               — Ресурс викладачів
│   │   ├── TenantResource.php                — Ресурс тенанта
│   │   ├── UserResource.php                  — Ресурс користувачів
│   │   └── */Pages/                          — Create/Edit/List для кожного
│   └── Widgets/
│       ├── QuickAccessWidget.php             — Швидкий доступ
│       ├── RecentSchedulesWidget.php         — Останні версії
│       ├── ScheduleChartWidget.php           — Графік розподілу
│       ├── StatsOverviewWidget.php           — Статистика
│       └── SubjectTypeChartWidget.php        — Графік типів
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php                — Автентифікація
│   │   ├── Controller.php                    — Базовий контролер
│   │   ├── HomeController.php                — Домашня сторінка
│   │   ├── PublicScheduleController.php      — Публічний розклад
│   │   └── ScheduleController.php            — API розкладу
│   ├── Kernel.php                            — HTTP-ядро
│   └── Middleware/
│       ├── Authenticate.php
│       ├── EncryptCookies.php
│       ├── PreventRequestsDuringMaintenance.php
│       ├── RedirectIfAuthenticated.php
│       ├── TenantMiddleware.php              — Мультитенантний middleware
│       ├── TrimStrings.php
│       ├── TrustProxies.php
│       ├── ValidateSignature.php
│       └── VerifyCsrfToken.php
├── Jobs/
│   └── GenerateScheduleJob.php               — Фонова генерація
├── Models/
│   ├── Activity.php
│   ├── Calendar.php
│   ├── Course.php
│   ├── Group.php
│   ├── RescheduleRequest.php
│   ├── Room.php
│   ├── ScheduleAssignment.php
│   ├── ScheduleVersion.php
│   ├── SoftWeight.php
│   ├── Subject.php
│   ├── Teacher.php
│   ├── TeacherPreferenceRule.php
│   ├── TeacherUnavailability.php
│   ├── Tenant.php
│   ├── TimeSlot.php
│   ├── User.php
│   ├── Violation.php
│   └── Traits/
│       └── TenantScope.php                   — Трейт мультитенантності
├── Policies/
│   ├── RescheduleRequestPolicy.php
│   ├── TeacherPreferenceRulePolicy.php
│   └── UserPolicy.php
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   ├── EventServiceProvider.php
│   ├── FilamentServiceProvider.php
│   ├── RouteServiceProvider.php
│   ├── TenantServiceProvider.php
│   └── Filament/
│       └── AdminPanelProvider.php
└── Services/
    ├── RescheduleService.php
    ├── ScheduleGenerationService.php
    └── TenantManager.php
```

### 33.3. Каталог solver/ (Go)

```
solver/
├── cmd/
│   └── server/
│       └── main.go                           — HTTP-сервер
├── internal/
│   ├── api/                                  — (порожній)
│   ├── db/
│   │   └── postgres.go                       — Рівень БД
│   ├── models/                               — (порожній)
│   └── solver/
│       ├── scheduler.go                      — Жадібний алгоритм
│       ├── state.go                          — Управління станом
│       ├── objective.go                      — Об'єктивна функція
│       ├── simulated_annealing.go            — Імітація відпалу
│       └── tabu_search.go                    — Табу-пошук
├── pkg/
│   └── types/
│       └── types.go                          — Доменні типи
├── go.mod
├── go.sum
└── Dockerfile
```

### 33.4. Каталог resources/

```
resources/
└── views/
    ├── layouts/
    │   └── app.blade.php                     — Головний макет
    ├── auth/
    │   ├── login.blade.php                   — Сторінка входу
    │   └── register.blade.php                — Сторінка реєстрації
    ├── components/
    │   └── schedule-table.blade.php          — Компонент таблиці
    ├── filament/
    │   ├── pages/
    │   │   ├── schedule-generation.blade.php — Генерація
    │   │   ├── schedule-management.blade.php — Управління
    │   │   ├── teacher-schedule.blade.php    — Розклад викладача
    │   │   └── public-schedule.blade.php     — Публічний (redirect)
    │   └── widgets/
    │       └── quick-access-widget.blade.php — Віджет доступу
    ├── welcome.blade.php                     — Лендінг
    ├── home.blade.php                        — Домашня сторінка
    ├── schedule.blade.php                    — Розклад (auth)
    └── public-schedule.blade.php             — Публічний розклад
```

### 33.5. Каталог docker/

```
docker/
├── php/
│   ├── Dockerfile                            — PHP 8.2-FPM образ
│   ├── local.ini                             — Конфігурація PHP
│   └── xdebug.ini                            — Конфігурація Xdebug
├── nginx/
│   └── default.conf                          — Конфігурація Nginx
└── mysql/
    └── my.cnf                                — Конфігурація MySQL (legacy)
```

### 33.6. Каталог database/

```
database/
├── migrations/
│   ├── 2014_10_12_000000_create_users_table.php
│   ├── 2014_10_12_100000_create_password_reset_tokens_table.php
│   ├── 2019_08_19_000000_create_failed_jobs_table.php
│   ├── 2019_12_14_000001_create_personal_access_tokens_table.php
│   ├── 2024_01_01_000001_create_teachers_table.php
│   ├── 2024_01_01_000002_create_subjects_table.php
│   ├── 2024_01_01_000003_create_courses_table.php
│   ├── 2024_01_01_000004_create_groups_table.php
│   ├── 2024_01_01_000005_create_schedules_table.php
│   ├── 2026_02_26_000001_create_tenants_and_all_tables.php
│   ├── 2026_03_01_000001_create_teacher_cabinet_tables.php
│   ├── 2026_03_01_000002_add_generation_timestamps.php
│   ├── 2026_03_01_000003_add_teacher_windows_weight.php
│   └── 2026_03_02_000001_add_calendar_config_fields.php
├── seeders/
│   ├── DatabaseSeeder.php
│   └── DemoSeeder.php
└── factories/                                — (порожній)
```

---

## 34. Конфігурація середовища

### 34.1. Змінні середовища (.env)

```env
# Додаток
APP_NAME=Scheduler
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=base64:...

# База даних
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=scheduler
DB_USERNAME=scheduler
DB_PASSWORD=scheduler_password

# Кешування та черги
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Пошта
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Логування
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Solver
SOLVER_URL=http://solver:8081
```

### 34.2. Конфігурація Laravel (config/)

| Файл | Ключові налаштування |
|------|---------------------|
| `app.php` | Locale: 'uk', Timezone: UTC |
| `auth.php` | Guard: web (session), Provider: Eloquent |
| `database.php` | Default: pgsql, UTF-8 |
| `cache.php` | Default: redis |
| `queue.php` | Default: redis |
| `session.php` | Driver: redis, Lifetime: 120 хв |
| `mail.php` | Default: smtp (mailpit для dev) |

### 34.3. Конфігурація Docker (docker-compose.yml)

Повна конфігурація описана у розділі [4. Docker-інфраструктура](#4-docker-інфраструктура).

---

## Додаток А. Глосарій термінів

| Термін | Визначення |
|--------|-----------|
| Тенант (Tenant) | Ізольоване середовище для одного університету |
| Активність (Activity) | Одне навчальне заняття (лекція, практика, лабораторна) |
| Призначення (Assignment) | Розміщення конкретного заняття у конкретному слоті |
| Версія розкладу (Schedule Version) | Один варіант розкладу з унікальним набором призначень |
| Парність (Parity) | Чергування тижнів: чисельник (num) / знаменник (den) / обидва (both) |
| Часовий слот (Time Slot) | Одна пара: конкретний день + номер пари + час |
| Вікно (Window/Gap) | Порожній слот між двома зайнятими слотами для тієї ж групи/викладача |
| М'яке обмеження (Soft Constraint) | Бажане обмеження, порушення якого мінімізується |
| Жорстке обмеження (Hard Constraint) | Обов'язкове обмеження, порушення якого неприпустиме |
| Солвер (Solver) | Go-сервіс для алгоритмічної оптимізації розкладу |
| Об'єктивна функція | Числова оцінка якості розкладу (менше = краще) |

## Додаток Б. Діаграма зв'язків сутностей (ER-діаграма)

```
                                    ┌──────────────┐
                                    │   Tenant     │
                                    │   (UUID)     │
                                    └──────┬───────┘
                                           │
                    ┌──────────────┬────────┼────────┬──────────────┐
                    │              │        │        │              │
              ┌─────▼──────┐ ┌────▼────┐ ┌─▼────┐ ┌─▼──────┐ ┌───▼────┐
              │   User     │ │ Teacher │ │ Room │ │Calendar│ │ Course │
              └─────┬──────┘ └────┬────┘ └──────┘ └───┬────┘ └───┬────┘
                    │             │                    │          │
                    │        ┌────▼────────┐     ┌────▼────┐ ┌───▼────┐
                    │        │  Subject    │     │TimeSlot │ │ Group  │
                    │        └────┬────────┘     └─────────┘ └───┬────┘
                    │             │                              │
                    │        ┌────▼─────────────────────────────▼┐
                    │        │           Activity                │
                    │        │  (activity_groups, activity_teachers,│
                    │        │   activity_room_types)             │
                    │        └────────────┬──────────────────────┘
                    │                     │
              ┌─────▼──────────┐  ┌───────▼──────────────────────┐
              │ScheduleVersion │  │    ScheduleAssignment        │
              │  (status:      │  │  (day, slot, parity, room)   │
              │  draft/pub/arc)│──│                               │
              └────────┬───────┘  └──────────────────────────────┘
                       │
              ┌────────▼───────┐
              │   Violation    │
              │  (code, sev)   │
              └────────────────┘
```

## Додаток В. Статистика проекту

| Показник | Значення |
|----------|----------|
| PHP-файли в app/ | ~102 |
| Моделі | 17 + 1 трейт |
| Filament-ресурси | 11 |
| Filament-сторінки | 4 |
| Filament-віджети | 5 |
| Blade-шаблони | 13 (~2,759 рядків) |
| Go-файли (solver) | 7 |
| Таблиці БД | 26 |
| Міграції | 14 |
| Docker-сервіси | 6 |
| Алгоритми оптимізації | 3 |
| Ролі користувачів | 5 |

## Додаток Г. Порівняння алгоритмів оптимізації

### Жадібний алгоритм (Greedy)

```
Вхід: Список активностей, часових слотів, аудиторій, обмежень
Вихід: Набір призначень

1. Сортувати активності за складністю (спадання)
2. Для кожної активності:
   a. Для кожного необхідного слоту:
      i.  Перебрати всі (день, слот, парність, аудиторія) комбінації
      ii. Відфільтрувати недопустимі (конфлікти, недоступність)
      iii. Обчислити оцінку кожного кандидата
      iv. Обрати кандидата з мінімальною оцінкою
      v.  Позначити слот як зайнятий
3. Повернути призначення та порушення
```

### Імітація відпалу (Simulated Annealing)

```
Вхід: Жадібний розв'язок, параметри (температура, охолодження)
Вихід: Покращений набір призначень

1. Побудувати початковий стан з жадібного розв'язку
2. T = початкова_температура
3. Поки T > мінімальна_температура:
   a. Для i = 1..ітерацій_на_температуру:
      i.   Згенерувати випадкове переміщення
      ii.  Обчислити delta = нова_оцінка - поточна_оцінка
      iii. Якщо delta < 0 або random() < exp(-delta/T):
           - Прийняти переміщення
           - Якщо краще за найкраще: оновити
      iv.  Інакше: скасувати переміщення
   b. T = T × coolingRate
   c. Якщо давно без покращень: reseed
4. Повернути найкращий розв'язок
```

### Табу-пошук (Tabu Search)

```
Вхід: Жадібний розв'язок, розмір табу-списку
Вихід: Покращений набір призначень

1. Побудувати початковий стан з жадібного розв'язку
2. Ініціалізувати порожній табу-список
3. Для i = 1..50000:
   a. Згенерувати 30 випадкових сусідів
   b. Для кожного сусіда:
      i.  Обчислити оцінку
      ii. Якщо не в табу-списку або задовольняє аспірацію:
          - Відстежити як кандидата
   c. Обрати найкращого кандидата
   d. Додати переміщення до табу-списку
   e. Якщо краще за найкраще: оновити
4. Повернути найкращий розв'язок
```
