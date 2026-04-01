---
plan_type: dev
sprint: 2
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 2
dependencies: sprint-01-foundation.md
---

# Sprint 2: Профили пользователей

## Overview

Реализовать полноценные профили для тренеров и атлетов с возможностью редактирования, загрузки фото, выбора видов спорта (для тренеров) и города. Добавить onboarding flow после регистрации.

**Цель спринта:** Тренер может заполнить профиль с дипломами и видами спорта. Атлет может заполнить базовую информацию. После первого входа пользователь проходит onboarding.

**Источник требований:** Architecture spec §2.1, §11.2

## Context

**Текущее состояние (после Sprint 1):**
- Users с полями role, first_name, last_name, phone, city_id
- Справочники cities и sports существуют
- Есть базовые layouts и навигация
- Регистрация работает с выбором роли

**Целевое состояние:**
- Таблицы `coach_profiles` и `athlete_profiles`
- Pivot `coach_sports` для связи тренер ↔ виды спорта
- Загрузка фото через Spatie Media Library
- Форма редактирования профиля для каждой роли
- Onboarding flow после первой регистрации
- Модерация тренеров (статус pending при создании профиля)

**Технические решения:**
- Spatie Media Library для загрузки avatar, дипломов, справок
- One-to-one: User → CoachProfile, User → AthleteProfile
- Many-to-many: CoachProfile ↔ Sports через coach_sports
- Middleware для проверки заполненности профиля (redirect на onboarding)

## Error Handling & Logging Strategy

**Error Boundaries:**
- Profile validation: `App\Exceptions\Profile\ProfileIncompleteException`
- File upload failures: catch Spatie exceptions, показать user-friendly error
- Onboarding redirect loop: исключить `/onboarding` и `/profile` из middleware

**Logging:**
- Structured context: `user_id`, `role`, `profile_type`, `moderation_status`, `file_type`, `file_size`
- Levels:
  - `error`: file upload failures >5MB, Media Library exceptions
  - `warning`: onboarding loop detected, profile incomplete after 7 days
  - `info`: profile completed, file uploaded, moderation status changed
- **Никогда не логировать**: diploma/certificate file contents, emergency contact details
- **Логировать**: file paths, sizes, mime types для debugging

**Применение в спринте:**
- Task 3 (Spatie): логировать info при успешной загрузке с file metadata
- Task 7 (onboarding): логировать warning если middleware loop
- Task 9 (auto-create): логировать info при создании профиля с user_id + role

## Validation Commands

```bash
# После завершения всех задач:

# 1. Миграции и сиды
php artisan migrate:status
php artisan storage:link

# 2. Тесты
php artisan test --filter=ProfileTest
php artisan test --filter=OnboardingTest
php artisan test --filter=MediaUploadTest

# 3. Проверка моделей
php artisan tinker
>>> $coach = User::where('role', 'coach')->first();
>>> $coach->coachProfile;
>>> $coach->coachProfile->sports;

# 4. Ручная проверка
# - Зарегистрировать тренера → попасть на onboarding
# - Заполнить профиль → загрузить фото, выбрать спорт
# - Зарегистрировать атлета → заполнить базовый профиль
# - Проверить, что профиль сохраняется
```

---

### Task 1: Создать таблицы профилей

**Цель:** Создать coach_profiles, athlete_profiles, coach_sports.

**Files:**
- Create: `database/migrations/xxxx_create_coach_profiles_table.php`
- Create: `database/migrations/xxxx_create_athlete_profiles_table.php`
- Create: `database/migrations/xxxx_create_coach_sports_table.php`

**Steps:**
- [x] Создать миграцию coach_profiles:
  - id, user_id unique foreign, bio text, experience_years int nullable
  - rating_avg decimal(3,2) default 0, rating_count int default 0
  - moderation_status enum('pending','approved','rejected') default 'pending'
  - is_public boolean default false, timestamps
- [x] Создать миграцию athlete_profiles:
  - id, user_id unique foreign, emergency_contact string nullable, timestamps
- [x] Создать миграцию coach_sports (pivot):
  - id, coach_profile_id foreign, sport_id foreign
  - unique constraint (coach_profile_id, sport_id)
- [x] Запустить `php artisan migrate`
- [x] Mark completed

---

### Task 2: Создать модели профилей

**Цель:** Создать CoachProfile, AthleteProfile с relationships.

**Files:**
- Create: `app/Models/CoachProfile.php`
- Create: `app/Models/AthleteProfile.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Sport.php`

**Steps:**
- [x] Создать CoachProfile:
  - belongsTo User
  - belongsToMany Sports через coach_sports
  - casts: moderation_status, is_public, rating_avg
- [x] Создать AthleteProfile:
  - belongsTo User
- [x] В User добавить relationships:
  - hasOne CoachProfile
  - hasOne AthleteProfile
  - accessor isCoach(), isAthlete()
- [x] В Sport добавить:
  - belongsToMany CoachProfiles через coach_sports
- [x] Mark completed

---

### Task 3: Установить Spatie Media Library

**Цель:** Установить и настроить Spatie для загрузки файлов.

**Files:**
- Modify: `composer.json`
- Create: `config/media-library.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/CoachProfile.php`

**Steps:**
- [x] Установить: `composer require spatie/laravel-medialibrary`
- [x] Опубликовать конфиг: `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"`
- [x] Запустить миграцию медиа: `php artisan migrate`
- [x] Добавить trait `InteractsWithMedia` в User:
  - Регистрировать collection 'avatar' (single file, max 5MB, image)
- [x] Добавить trait в CoachProfile:
  - collection 'diplomas' (multiple, max 10MB per file, pdf/image)
  - collection 'certificates' (справки СМЗ, multiple, pdf/image)
- [x] Запустить `php artisan storage:link`
- [x] Mark completed

---

### Task 4: Создать форму редактирования профиля тренера

**Цель:** Создать страницу редактирования профиля тренера в Vue.

**Files:**
- Create: `resources/js/Pages/Coach/Profile/Edit.vue`
- Create: `app/Http/Controllers/Coach/ProfileController.php`
- Create: `app/Http/Requests/Coach/UpdateProfileRequest.php`
- Modify: `routes/web.php`

**Steps:**
- [x] Создать ProfileController с методами:
  - edit() — возвращает Inertia::render с данными профиля
  - update(UpdateProfileRequest) — сохраняет данные
  - uploadAvatar(Request) — загружает фото
  - uploadDiploma(Request) — загружает диплом
- [x] Создать UpdateProfileRequest с валидацией:
  - first_name, last_name required
  - bio required max:1000
  - city_id required exists:cities
  - sports required array min:1 (массив sport_id)
  - experience_years nullable integer
- [x] Создать Edit.vue:
  - Форма с полями: first_name, last_name, middle_name, bio
  - AvatarUploader компонент
  - MultiSelect для выбора видов спорта
  - Select для выбора города
  - FileUploader для дипломов (multiple)
  - FileUploader для справок СМЗ
  - Кнопка "Сохранить"
- [x] Добавить роут: `GET /coach/profile` → ProfileController@edit
- [x] Добавить роут: `PATCH /coach/profile` → ProfileController@update
- [x] Mark completed

---

### Task 5: Создать UI компоненты для загрузки файлов

**Цель:** Создать переиспользуемые компоненты AvatarUploader, FileUploader.

**Files:**
- Create: `resources/js/Components/UI/AvatarUploader.vue`
- Create: `resources/js/Components/UI/FileUploader.vue`
- Create: `resources/js/Components/UI/MultiSelect.vue`

**Steps:**
- [ ] Создать AvatarUploader:
  - Круглый preview фото
  - Кнопка "Загрузить фото"
  - Drag & drop support
  - Валидация: только image, max 5MB
  - Emit uploaded file URL
- [ ] Создать FileUploader:
  - Список загруженных файлов
  - Кнопка "Добавить файл"
  - Поддержка multiple files
  - Показывать иконку по типу файла (pdf/image)
  - Кнопка удаления файла
- [ ] Создать MultiSelect:
  - Dropdown с чекбоксами
  - Поиск по списку
  - Показывать выбранные badges
  - v-model support
- [ ] Использовать компоненты в Coach/Profile/Edit.vue
- [ ] Mark completed

---

### Task 6: Создать форму редактирования профиля атлета

**Цель:** Создать упрощённую форму профиля атлета.

**Files:**
- Create: `resources/js/Pages/Athlete/Profile/Edit.vue`
- Create: `app/Http/Controllers/Athlete/ProfileController.php`
- Create: `app/Http/Requests/Athlete/UpdateProfileRequest.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать Athlete\ProfileController:
  - edit() — возвращает данные user + athleteProfile
  - update(UpdateProfileRequest) — сохраняет
  - uploadAvatar(Request)
- [ ] Создать UpdateProfileRequest:
  - first_name, last_name required
  - phone nullable unique (игнорировать текущего user)
  - city_id nullable exists
  - emergency_contact nullable string
- [ ] Создать Edit.vue:
  - Поля: first_name, last_name, phone
  - AvatarUploader
  - Select города (опционально)
  - emergency_contact (опционально)
  - Кнопка "Сохранить"
- [ ] Добавить роуты:
  - `GET /athlete/profile` → Athlete\ProfileController@edit
  - `PATCH /athlete/profile` → Athlete\ProfileController@update
- [ ] Mark completed

---

### Task 7: Реализовать onboarding flow

**Цель:** После регистрации редиректить на заполнение профиля.

**Files:**
- Create: `app/Http/Middleware/EnsureProfileCompleted.php`
- Create: `resources/js/Pages/Onboarding/Coach.vue`
- Create: `resources/js/Pages/Onboarding/Athlete.vue`
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php`

**Steps:**
- [ ] Создать middleware EnsureProfileCompleted:
  - Для coach: проверить, что coachProfile существует и заполнен (bio, sports, city)
  - Для athlete: проверить, что first_name заполнен
  - Если не заполнен → redirect на /onboarding
  - Исключить сам роут /onboarding из проверки
- [ ] Создать Onboarding/Coach.vue:
  - Шаг 1: Загрузить фото, ФИО
  - Шаг 2: Выбрать виды спорта
  - Шаг 3: Выбрать город, написать bio
  - Шаг 4: Загрузить дипломы (опционально на onboarding)
  - Progress bar (1/4, 2/4, ...)
  - Кнопка "Завершить" → сохранить и редирект на /coach/dashboard
- [ ] Создать Onboarding/Athlete.vue:
  - Одна страница: ФИО, фото (опционально), город (опционально)
  - Кнопка "Готово" → сохранить и редирект на /athlete/bookings
- [ ] Добавить роуты:
  - `GET /onboarding` → OnboardingController@show (определяет роль и показывает нужный компонент)
  - `POST /onboarding` → OnboardingController@store
- [ ] Применить middleware к защищённым роутам (кроме /onboarding, /profile)
- [ ] Mark completed

---

### Task 8: Добавить badge модерации в UI тренера

**Цель:** Показывать статус модерации в кабинете тренера.

**Files:**
- Modify: `resources/js/Layouts/CoachLayout.vue`
- Modify: `resources/js/Pages/Coach/Dashboard.vue`

**Steps:**
- [ ] В CoachLayout добавить badge под аватаром:
  - "На модерации" (pending) — жёлтый badge
  - "Одобрен" (approved) — зелёный badge
  - "Отклонён" (rejected) — красный badge с иконкой info
- [ ] На Dashboard.vue показать сообщение если pending:
  - "Ваш профиль на проверке. Вы сможете публиковать тренировки после одобрения администратором."
- [ ] Если rejected — показать причину отклонения (если админ указал)
- [ ] Кнопка "Отправить на повторную модерацию" (если rejected)
- [ ] Mark completed

---

### Task 9: Добавить автоматическое создание профилей

**Цель:** При регистрации автоматически создавать CoachProfile или AthleteProfile.

**Files:**
- Create: `app/Listeners/CreateUserProfile.php`
- Create: `app/Events/UserRegistered.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Steps:**
- [ ] Создать событие UserRegistered с $user
- [ ] Создать listener CreateUserProfile:
  - Если user.role === 'coach' → создать CoachProfile с moderation_status = 'pending'
  - Если user.role === 'athlete' → создать AthleteProfile
- [ ] В RegisteredUserController после создания user:
  - Dispatch UserRegistered
- [ ] Зарегистрировать listener в AppServiceProvider (или включить event discovery)
- [ ] Проверить: зарегистрировать нового пользователя → профиль создаётся автоматически
- [ ] Mark completed

---

### Task 10: Написать feature tests для профилей

**Цель:** Покрыть тестами редактирование профилей и onboarding.

**Files:**
- Create: `tests/Feature/Coach/ProfileTest.php`
- Create: `tests/Feature/Athlete/ProfileTest.php`
- Create: `tests/Feature/OnboardingTest.php`
- Create: `tests/Feature/MediaUploadTest.php`

**Steps:**
- [ ] Создать Coach/ProfileTest:
  - Тест обновления профиля с валидными данными
  - Тест валидации (bio required, sports required)
  - Тест загрузки avatar
  - Тест привязки видов спорта
- [ ] Создать Athlete/ProfileTest:
  - Тест обновления профиля
  - Тест валидации phone unique
- [ ] Создать OnboardingTest:
  - Новый тренер без профиля → редирект на /onboarding
  - После заполнения onboarding → редирект на dashboard
  - Тренер с заполненным профилем → доступ к dashboard
- [ ] Создать MediaUploadTest:
  - Тест загрузки avatar (проверка в storage)
  - Тест валидации размера файла (>5MB → ошибка)
  - Тест удаления файла
- [ ] Запустить `php artisan test`
- [ ] Mark completed

---

## Verification Notes

**После завершения всех задач проверить:**

1. **Onboarding flow тренера:**
   - Зарегистрировать нового тренера
   - Пройти onboarding (4 шага)
   - Проверить, что профиль создан с moderation_status = 'pending'
   - Попасть на /coach/dashboard, увидеть badge "На модерации"

2. **Onboarding flow атлета:**
   - Зарегистрировать нового атлета
   - Заполнить базовые данные
   - Попасть на /athlete/bookings

3. **Редактирование профиля:**
   - Зайти в /coach/profile, изменить bio, добавить вид спорта, загрузить диплом
   - Сохранить → проверить в БД, что данные обновились
   - Зайти в /athlete/profile, изменить телефон → сохранить

4. **Загрузка файлов:**
   - Загрузить фото тренера → увидеть в UI
   - Загрузить диплом → файл сохранился в storage/app/public/media
   - Попробовать загрузить файл >5MB → ошибка валидации

5. **Middleware:**
   - Создать тренера с незаполненным профилем → попытка зайти на /coach/workouts → редирект на /onboarding
   - Заполнить профиль → middleware пропускает

## Risks

1. **Media Library migrations** — если миграция уже была запущена в Sprint 1, может быть конфликт. Решение: проверить `php artisan migrate:status` перед запуском.

2. **File upload limits** — дефолтный PHP limit может быть 2MB. Решение: обновить `upload_max_filesize` и `post_max_size` в php.ini.

3. **Storage permissions** — папка `storage/app/public` может быть недоступна. Решение: `chmod -R 775 storage && php artisan storage:link`.

4. **Onboarding loop** — если middleware неправильно настроен, может быть редирект-луп. Решение: исключить `/onboarding` и `/profile` из middleware.

5. **CoachProfile не создаётся** — если listener не зарегистрирован или событие не dispatch. Решение: проверить регистрацию listeners в AppServiceProvider/автодискавери и очистить кеш `php artisan event:clear`.

6. **Multiselect performance** — если видов спорта много (>100), dropdown может лагать. Решение: добавить пагинацию или поиск в MultiSelect (на MVP достаточно ~10 видов спорта).

---

**Definition of Done:**
- Все 10 задач завершены
- Validation commands выполнены успешно
- Feature tests проходят
- Onboarding flow работает для обеих ролей
- Загрузка файлов работает корректно
- Badge модерации отображается у тренера
