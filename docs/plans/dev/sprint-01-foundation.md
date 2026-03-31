---
plan_type: dev
sprint: 1
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 1
---

# Sprint 1: Базовый каркас проекта

## Overview

Установить и настроить базовую инфраструктуру проекта: Laravel 12, Inertia v2, Vue 3, Tailwind 3, систему авторизации с ролями, базовые layouts и окружение разработки.

**Цель спринта:** Получить работающий скелет приложения с авторизацией, разделением ролей (athlete/coach/admin) и базовой навигацией для всех кабинетов.

**Источник требований:** Architecture spec (docs/plans/plan-as-is.md)

## Context

**Текущее состояние:**
- Чистый Laravel 12 с базовым Breeze + Inertia
- Только стандартная таблица users (name, email, password)
- Нет ролей, нет расширенных профилей
- Нет справочников (cities, sports)

**Целевое состояние:**
- Система ролей: athlete, coach, admin
- Расширенная таблица users с полями role, phone, first_name, last_name, city_id
- Справочники: cities, sports
- Раздельные layouts для каждой роли
- Базовая навигация по кабинетам
- Docker окружение для разработки (опционально)

**Технический стек:**
- Laravel 12 (PHP 8.3)
- Inertia v2
- Vue 3 + Composition API
- Tailwind CSS 3
- Pinia (state management)
- Vite

**Архитектурные решения:**
- Роли хранятся в поле `users.role` (enum), без отдельной таблицы
- Один аккаунт = одна роль (без переключения)
- Middleware для разделения доступа по ролям
- Layouts: PublicLayout, AthleteLayout, CoachLayout, AdminLayout

## Error Handling & Logging Strategy

**Error Boundaries:**
- Domain exceptions: `App\Exceptions\Auth\*` для валидации регистрации и авторизации
- HTTP layer: использовать Laravel exception handler для рендеринга ошибок
- Middleware exceptions: abort(403) для нарушений прав доступа

**Logging:**
- Использовать Laravel Log facade с каналами из `config/logging.php`
- Structured context: всегда включать `user_id`, `request_id`, relevant entity IDs
- Levels:
  - `error`: для recoverable failures (валидация регистрации, ошибки миграций)
  - `warning`: для degraded states (неожиданные роли пользователей)
  - `info`: для significant events (успешная регистрация, первый вход)
  - `debug`: только для local development
- **Никогда не логировать**: пароли, tokens, PII без маскирования

**Применение в спринте:**
- Task 3 (регистрация): логировать info при успешной регистрации с role
- Task 4 (middleware): логировать warning при попытке доступа с неправильной ролью
- Task 10 (tests): проверять, что sensitive data не попадает в логи

## Validation Commands

```bash
# После завершения всех задач выполнить:

# 1. Проверка миграций и данных
php artisan migrate:status
php artisan db:seed --class=CitiesSeeder
php artisan db:seed --class=SportsSeeder

# 2. Проверка тестов
php artisan test --filter=RoleMiddlewareTest
php artisan test --filter=RegistrationTest

# 3. Проверка фронтенда
npm run build
npm run dev

# 4. Проверка роутов
php artisan route:list | grep -E "athlete|coach|admin"

# 5. Ручная проверка в браузере
# - Зарегистрировать athlete → попасть в /athlete/bookings
# - Зарегистрировать coach → попасть в /coach/dashboard
# - Попробовать зайти coach на /athlete/* → 403 Forbidden
```

---

### Task 1: Расширить миграцию users и добавить роли

**Цель:** Добавить поля для ролей, телефона, ФИО, связь с городом.

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `app/Models/User.php`

**Steps:**
- [x] Открыть миграцию create_users_table
- [x] Добавить поля:
  - `role` enum('athlete', 'coach', 'admin') not null
  - `phone` string nullable unique
  - `first_name` string nullable
  - `last_name` string nullable
  - `middle_name` string nullable
  - `avatar_path` string nullable
  - `city_id` foreignId nullable
  - `phone_verified_at` timestamp nullable
  - `status` enum('active', 'blocked') default 'active'
- [x] Убрать поле `name` (заменено на first_name, last_name)
- [x] Обновить Model User:
  - Добавить поля в `$fillable`
  - Добавить casts для `phone_verified_at`, `role`, `status`
  - Добавить accessor `getFullNameAttribute()`
- [x] Запустить `php artisan migrate`
- [x] Mark completed

---

### Task 2: Создать справочники cities и sports

**Цель:** Создать таблицы и модели для справочников городов и видов спорта.

**Files:**
- Create: `database/migrations/xxxx_create_cities_table.php`
- Create: `database/migrations/xxxx_create_sports_table.php`
- Create: `app/Models/City.php`
- Create: `app/Models/Sport.php`
- Create: `database/seeders/CitiesSeeder.php`
- Create: `database/seeders/SportsSeeder.php`

**Steps:**
- [x] Создать миграцию cities:
  - id, name, slug, country_code, lat, lng, timestamps
- [x] Создать миграцию sports:
  - id, slug, name, icon nullable, is_active boolean default true
- [x] Создать модели City, Sport
- [x] Создать CitiesSeeder с основными городами РФ (Москва, СПб, Екатеринбург, Казань, Новосибирск)
- [x] Создать SportsSeeder (running, functional, yoga, cycling, boxing, crossfit)
- [x] Добавить relationship `User belongsTo City`
- [x] Запустить `php artisan migrate`
- [x] Запустить `php artisan db:seed --class=CitiesSeeder`
- [x] Запустить `php artisan db:seed --class=SportsSeeder`
- [x] Mark completed

---

### Task 3: Обновить регистрацию с выбором роли

**Цель:** Добавить поле выбора роли (athlete/coach) на экране регистрации.

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Create: `app/Http/Requests/Auth/RegisterRequest.php`

**Steps:**
- [x] Создать RegisterRequest с валидацией:
  - role required in:athlete,coach
  - email required unique
  - phone nullable unique
  - first_name required
  - last_name required
  - password required min:8
- [x] Обновить RegisteredUserController:
  - Использовать RegisterRequest
  - Сохранять role, first_name, last_name, phone
- [x] Обновить Register.vue:
  - Убрать поле name
  - Добавить first_name, last_name
  - Добавить radio buttons для выбора роли (Атлет / Тренер)
  - Добавить phone (опционально)
- [x] Проверить регистрацию в браузере для athlete и coach
- [x] Mark completed

---

### Task 4: Создать middleware для проверки ролей

**Цель:** Создать middleware для разделения доступа по ролям.

**Files:**
- Create: `app/Http/Middleware/EnsureUserHasRole.php`
- Modify: `bootstrap/app.php`

**Steps:**
- [x] Создать middleware EnsureUserHasRole с параметром $role
- [x] Проверять `auth()->user()->role === $role`, иначе abort(403)
- [x] Зарегистрировать middleware alias в bootstrap/app.php:
  - `role:athlete`
  - `role:coach`
  - `role:admin`
- [x] Mark completed

---

### Task 5: Создать базовые роуты для кабинетов

**Цель:** Создать защищённые роуты для athlete, coach, admin.

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Athlete/DashboardController.php`
- Create: `app/Http/Controllers/Coach/DashboardController.php`

**Steps:**
- [x] В routes/web.php создать группы:
  - `/athlete/*` с middleware ['auth', 'role:athlete']
  - `/coach/*` с middleware ['auth', 'role:coach']
  - `/admin/*` с middleware ['auth', 'role:admin']
- [x] Создать роуты:
  - `GET /athlete/bookings` → Athlete\BookingsController@index (stub)
  - `GET /coach/dashboard` → Coach\DashboardController@index
  - `GET /coach/profile` → Coach\ProfileController@edit (stub)
- [x] Создать контроллеры с заглушками (возвращают Inertia::render)
- [x] После логина редиректить:
  - athlete → `/athlete/bookings`
  - coach → `/coach/dashboard`
  - admin → `/admin` (Filament позже)
- [x] Настроить role-based redirect после логина в auth контроллере/handler
- [x] Mark completed

---

### Task 6: Создать Layout компоненты для Vue

**Цель:** Создать раздельные layouts для публичной части, атлета, тренера.

**Files:**
- Create: `resources/js/Layouts/PublicLayout.vue`
- Create: `resources/js/Layouts/AthleteLayout.vue`
- Create: `resources/js/Layouts/CoachLayout.vue`
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/Register.vue`

**Steps:**
- [ ] Создать PublicLayout.vue:
  - Header с логотипом split.fitness
  - Кнопки Войти / Регистрация
  - Footer (пустой пока)
- [ ] Создать AthleteLayout.vue:
  - Header с навигацией: Тренировки | Профиль
  - Аватар пользователя (справа)
  - Slot для контента
- [ ] Создать CoachLayout.vue:
  - Header с навигацией: Тренировки | Профиль | Выплаты
  - Аватар пользователя
  - Slot для контента
- [ ] Обернуть Login.vue и Register.vue в PublicLayout
- [ ] Создать заглушки страниц:
  - `resources/js/Pages/Athlete/Bookings/Index.vue` (с AthleteLayout)
  - `resources/js/Pages/Coach/Dashboard.vue` (с CoachLayout)
- [ ] Mark completed

---

### Task 7: Настроить Tailwind и базовые стили

**Цель:** Настроить Tailwind конфиг с цветами проекта, установить шрифты.

**Files:**
- Modify: `tailwind.config.js`
- Modify: `resources/css/app.css`
- Create: `resources/js/Components/UI/Button.vue`
- Create: `resources/js/Components/UI/Input.vue`

**Steps:**
- [ ] Обновить tailwind.config.js:
  - Добавить цвета проекта (если есть в Figma дизайне)
  - Добавить кастомные breakpoints при необходимости
- [ ] В app.css добавить базовые стили (reset, typography)
- [ ] Создать базовые UI компоненты:
  - Button.vue (primary, secondary, danger variants)
  - Input.vue (с поддержкой errors)
- [ ] Применить компоненты в Login.vue и Register.vue
- [ ] Проверить responsive на 350px - 428px (мобильные)
- [ ] Mark completed

---

### Task 8: Установить и настроить Pinia

**Цель:** Установить Pinia для state management, создать auth store.

**Files:**
- Create: `resources/js/stores/auth.js`
- Modify: `resources/js/app.js`

**Steps:**
- [ ] Установить pinia: `npm install pinia`
- [ ] Зарегистрировать Pinia в app.js
- [ ] Создать auth store:
  - state: user (из shared props)
  - getters: isAthlete, isCoach, isAdmin, fullName
  - actions: logout
- [ ] Использовать auth store в layouts (показывать user.fullName)
- [ ] Mark completed

---

### Task 9: Настроить Docker окружение (опционально)

**Цель:** Создать docker-compose.yml для локальной разработки.

**Files:**
- Create: `docker-compose.yml`
- Create: `docker/php/Dockerfile`
- Create: `.env.docker`

**Steps:**
- [ ] Создать docker-compose.yml с сервисами:
  - app (PHP 8.3 + Nginx)
  - mysql (8.0)
  - redis
- [ ] Создать Dockerfile для PHP с расширениями
- [ ] Добавить volume mounting для hot reload
- [ ] Добавить инструкцию в README.md
- [ ] Проверить `docker-compose up -d`
- [ ] Mark completed

---

### Task 10: Написать feature tests для регистрации и ролей

**Цель:** Покрыть тестами регистрацию с ролями и middleware проверку.

**Files:**
- Create: `tests/Feature/Auth/RegistrationWithRolesTest.php`
- Create: `tests/Feature/Middleware/RoleMiddlewareTest.php`

**Steps:**
- [ ] Создать RegistrationWithRolesTest:
  - Тест регистрации athlete → редирект на /athlete/bookings
  - Тест регистрации coach → редирект на /coach/dashboard
  - Тест валидации: роль обязательна
- [ ] Создать RoleMiddlewareTest:
  - Athlete не может зайти на /coach/*
  - Coach не может зайти на /athlete/*
  - Неавторизованный → редирект на /login
- [ ] Запустить `php artisan test`
- [ ] Убедиться, что все тесты проходят
- [ ] Mark completed

---

## Verification Notes

**После завершения всех задач проверить:**

1. **Регистрация и авторизация:**
   - Зарегистрировать нового атлета → попасть на /athlete/bookings
   - Зарегистрировать нового тренера → попасть на /coach/dashboard
   - Logout → редирект на публичную страницу

2. **Разделение доступа:**
   - Под атлетом попробовать зайти на /coach/dashboard → 403
   - Под тренером попробовать зайти на /athlete/bookings → 403

3. **UI и responsive:**
   - Проверить layouts на desktop (1920px), tablet (768px), mobile (375px)
   - Навигация работает корректно
   - Формы Login/Register валидируются

4. **База данных:**
   - `php artisan tinker` → проверить, что есть города и виды спорта
   - Проверить, что у созданного пользователя заполнены first_name, last_name, role

5. **Tests:**
   - Все feature tests проходят (`php artisan test`)

## Risks

1. **Migration conflicts** — если база уже содержит данные и требуется чистая установка, используйте отдельную тестовую БД. **Важно:** согласно проектным правилам (CLAUDE.md §88-101), команды `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe` СТРОГО ЗАПРЕЩЕНЫ на любых окружениях без явного письменного одобрения пользователя в текущей сессии. Эти команды уничтожают все данные. Всегда используйте только `php artisan migrate` для применения новых миграций.

2. **Breeze conflicts** — стандартный Breeze использует поле `name`, а мы переходим на `first_name/last_name`. Нужно обновить все упоминания в контроллерах и компонентах.

3. **Role enum в старых версиях MySQL** — MySQL < 8.0 не поддерживает native enum в миграциях. Решение: использовать `string` с validation rule или upgradeить MySQL.

4. **Docker performance на macOS** — volume mounting может быть медленным. Решение: использовать docker-sync или native development без Docker.

5. **Pinia SSR** — Inertia использует SSR, нужно убедиться, что Pinia state правильно гидратируется. Решение: использовать shared props для initial state.

---

**Definition of Done:**
- [ ] Обязательные задачи 1-8, 10 завершены
- [ ] Task 9 (Docker) опциональна — выполнить по желанию
- [ ] Validation commands выполнены успешно
- [ ] Feature tests проходят
- [ ] Ручная проверка в браузере пройдена
- [ ] Код прошёл review (если команда)
