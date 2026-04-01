---
plan_type: dev
sprint: 3
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 3
dependencies: sprint-02-profiles.md
---

# Sprint 3: Создание тренировок

## Overview

Реализовать функционал создания, редактирования и публикации тренировок тренером. Добавить карту для выбора точки проведения, reverse geocoding для получения адреса, расчёт цены слота.

**Цель спринта:** Тренер может создать черновик тренировки, выбрать место на карте, опубликовать тренировку. Тренировки сохраняются со статусами draft/published.

**Источник требований:** Architecture spec §2.1 (Тренировка), §11.3, §20

## Context

**Текущее состояние:**
- CoachProfile существует с модерацией
- Справочник sports готов
- Layouts и навигация работают

**Целевое состояние:**
- Таблица `workouts` с полями: coach_id, sport_id, city_id, lat/lng, адрес, дата/время, цена, места
- CRUD тренировок в кабинете тренера
- Карта Leaflet для выбора точки
- Reverse geocoding через Nominatim (OpenStreetMap)
- Расчёт slot_price = ceil(total_price / slots_total)
- Статусы: draft, published, cancelled, completed
- Policy: только approved тренер может публиковать

**Технические решения:**
- Leaflet.js для карты
- Nominatim API для reverse geocoding (с rate limit осторожно)
- Actions: CreateWorkoutAction, PublishWorkoutAction
- Policies: WorkoutPolicy (authorize publish)

## Error Handling & Logging Strategy

**Error Boundaries:**
- Workout exceptions: `App\Exceptions\Workout\WorkoutPublishException`, `WorkoutCancellationException`
- Policy authorization failures: abort(403), логировать warning
- Nominatim API failures: fallback на manual address entry, не блокировать создание

**Logging:**
- Structured context: `workout_id`, `coach_id`, `status`, `starts_at`, `slots_total`, `slots_booked`
- Levels:
  - `error`: PublishWorkoutAction failures (moderation pending), CancelWorkoutAction with paid bookings
  - `warning`: Nominatim rate limit exceeded, timezone conversion issues
  - `info`: workout created, published, cancelled
- **Никогда не логировать**: coach personal addresses (только workout.location_name)
- **Логировать**: lat/lng, city_id, external API responses для debugging

**Применение в спринте:**
- Task 2 (Nominatim): логировать warning при rate limit, fallback to manual
- Task 6 (publish): логировать error если moderation_status != 'approved'
- Task 8 (cancel): логировать error если есть paid bookings

## Validation Commands

```bash
# Миграции
php artisan migrate:status

# Тесты
php artisan test --filter=WorkoutTest
php artisan test --filter=WorkoutPolicyTest

# Проверка в tinker
php artisan tinker
>>> $workout = Workout::first();
>>> $workout->coach;
>>> $workout->sport;
>>> $workout->slot_price; // должен быть рассчитан

# Ручная проверка
# - Создать тренировку как approved тренер → успех
# - Создать тренировку как pending тренер → кнопка "Опубликовать" disabled
# - Проверить карту: клик → маркер устанавливается, адрес загружается
```

---

### Task 1: Создать таблицу workouts

**Files:**
- Create: `database/migrations/xxxx_create_workouts_table.php`
- Create: `app/Models/Workout.php`

**Steps:**
- [x] Создать миграцию workouts:
  - id, coach_id foreign, sport_id foreign, city_id foreign
  - title string nullable, description text nullable
  - location_name string, address string nullable
  - lat decimal(10,8), lng decimal(11,8)
  - starts_at datetime, duration_minutes int
  - total_price decimal(10,2), slot_price decimal(10,2), slots_total int, slots_booked int default 0
  - status enum('draft','published','cancelled','completed') default 'draft'
  - published_at datetime nullable, cancelled_at datetime nullable
  - timestamps, indexes
- [x] Создать Workout model:
  - belongsTo User (coach), Sport, City
  - hasMany Bookings
  - casts: starts_at, published_at, cancelled_at, status
  - accessor isPublished(), isDraft()
- [x] Запустить миграцию
- [x] Mark completed

---

### Task 2: Установить Leaflet и настроить карту

**Files:**
- Modify: `package.json`
- Create: `resources/js/Components/Map/WorkoutMap.vue`
- Create: `resources/js/composables/useMap.js`

**Steps:**
- [ ] Установить: `npm install leaflet vue-leaflet`
- [ ] Создать WorkoutMap.vue:
  - Показывать карту Leaflet (OpenStreetMap tiles)
  - Принимать props: initialLat, initialLng, editable
  - При клике на карту: установить маркер, emit coordinates
  - Показывать текущий маркер
- [ ] Создать composable useMap:
  - Функция reverseGeocode(lat, lng) → возвращает адрес через Nominatim
  - Rate limit: не чаще 1 запроса в секунду (debounce)
- [ ] Mark completed

---

### Task 3: Создать форму создания тренировки

**Files:**
- Create: `resources/js/Pages/Coach/Workouts/Create.vue`
- Create: `app/Http/Controllers/Coach/WorkoutController.php`
- Create: `app/Http/Requests/Coach/StoreWorkoutRequest.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать WorkoutController с методами:
  - index() — список тренировок тренера
  - create() — форма создания
  - store(StoreWorkoutRequest) — сохранение draft
- [ ] Создать StoreWorkoutRequest:
  - sport_id required exists
  - city_id required exists
  - lat, lng required numeric
  - location_name required
  - starts_at required future datetime
  - duration_minutes required int >0
  - total_price required numeric >0
  - slots_total required int >0
- [ ] Создать Create.vue:
  - Поля: sport (select), city (select)
  - WorkoutMap (клик → выбор точки)
  - location_name (заполняется автоматически через reverseGeocode)
  - address (можно редактировать)
  - DatePicker для starts_at, TimePicker для времени
  - Input: duration_minutes, total_price, slots_total
  - Показывать preview: "Цена слота: {слот_price} ₽"
  - Кнопка "Сохранить черновик"
- [ ] Добавить роуты:
  - `GET /coach/workouts` → index
  - `GET /coach/workouts/create` → create
  - `POST /coach/workouts` → store
- [ ] Mark completed

---

### Task 4: Реализовать расчёт цены слота

**Files:**
- Create: `app/Actions/Workout/CalculateSlotPriceAction.php`
- Modify: `app/Http/Controllers/Coach/WorkoutController.php`

**Steps:**
- [ ] Создать CalculateSlotPriceAction:
  - Метод execute($totalPrice, $slotsTotal)
  - Возвращает ceil($totalPrice / $slotsTotal)
- [ ] В WorkoutController@store:
  - После валидации вызвать CalculateSlotPriceAction
  - Сохранить slot_price в workout
- [ ] Во фронте Create.vue:
  - Computed property slotPrice: Math.ceil(form.total_price / form.slots_total)
  - Показывать: "Атлет будет платить {slotPrice} ₽ за место"
  - Показывать: "Вы получите {slotPrice * slots_total} ₽" (может быть чуть больше total_price)
- [ ] Mark completed

---

### Task 5: Создать список тренировок тренера

**Files:**
- Create: `resources/js/Pages/Coach/Workouts/Index.vue`
- Modify: `app/Http/Controllers/Coach/WorkoutController.php`

**Steps:**
- [ ] В WorkoutController@index:
  - Получить тренировки текущего тренера
  - Eager load: sport, city
  - Сортировать по starts_at desc
  - Пагинация (15 per page)
- [ ] Создать Index.vue:
  - Таблица тренировок:
    - Колонки: #ID, Дата, Время, Место, Спорт, Цена, Занято мест, Статус
    - Действия: Редактировать, Опубликовать (если draft), Отменить
  - Кнопка "Создать тренировку" → /coach/workouts/create
  - Фильтры: по статусу (draft/published/cancelled)
- [ ] Mark completed

---

### Task 6: Реализовать публикацию тренировки

**Files:**
- Create: `app/Actions/Workout/PublishWorkoutAction.php`
- Create: `app/Policies/WorkoutPolicy.php`
- Modify: `app/Http/Controllers/Coach/WorkoutController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать PublishWorkoutAction:
  - Проверить: тренер approved (moderation_status)
  - Проверить: starts_at в будущем
  - Обновить: status = 'published', published_at = now()
  - Если ошибка → throw ValidationException
- [ ] Создать WorkoutPolicy:
  - publish(User $user, Workout $workout):
    - return $user->id === $workout->coach_id
      && $user->coachProfile->moderation_status === 'approved';
  - update(User $user, Workout $workout):
    - return $user->id === $workout->coach_id;
- [ ] В WorkoutController добавить метод:
  - publish(Workout $workout) → вызвать PublishWorkoutAction, редирект с сообщением
- [ ] Добавить роут: `POST /coach/workouts/{workout}/publish` → publish
- [ ] Во фронте Index.vue:
  - Кнопка "Опубликовать" видна только если draft
  - Если moderation_status !== 'approved' → кнопка disabled с tooltip "Дождитесь модерации"
- [ ] Mark completed

---

### Task 7: Реализовать редактирование тренировки

**Files:**
- Create: `resources/js/Pages/Coach/Workouts/Edit.vue`
- Create: `app/Http/Requests/Coach/UpdateWorkoutRequest.php`
- Modify: `app/Http/Controllers/Coach/WorkoutController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] В WorkoutController добавить:
  - edit(Workout $workout) → authorize, вернуть форму
  - update(UpdateWorkoutRequest, Workout $workout) → authorize, сохранить
- [ ] Создать UpdateWorkoutRequest (валидация как в StoreWorkoutRequest)
- [ ] Создать Edit.vue:
  - Такая же форма, как Create.vue
  - Поля заполнены текущими данными
  - Кнопка "Сохранить изменения"
  - Если статус published → показать warning "Изменения видны атлетам сразу"
- [ ] Добавить роуты:
  - `GET /coach/workouts/{workout}/edit` → edit
  - `PATCH /coach/workouts/{workout}` → update
- [ ] Mark completed

---

### Task 8: Добавить отмену тренировки (базовая логика)

**Files:**
- Create: `app/Actions/Workout/CancelWorkoutAction.php`
- Modify: `app/Http/Controllers/Coach/WorkoutController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать CancelWorkoutAction:
  - Проверить: нет оплаченных броней (bookings.payment_status = 'paid')
  - Если есть оплаченные → throw ValidationException "Нельзя отменить, есть оплаченные записи"
  - Обновить: status = 'cancelled', cancelled_at = now()
- [ ] В WorkoutController добавить:
  - cancel(Workout $workout) → authorize, вызвать CancelWorkoutAction
- [ ] Добавить роут: `POST /coach/workouts/{workout}/cancel` → cancel
- [ ] Во фронте Index.vue:
  - Кнопка "Отменить" только для published
  - Подтверждающий диалог
- [ ] Mark completed

---

### Task 9: Добавить индексы в БД

**Files:**
- Create: `database/migrations/xxxx_add_indexes_to_workouts_table.php`

**Steps:**
- [ ] Создать миграцию с индексами:
  - index('coach_id')
  - index('city_id')
  - index('sport_id')
  - index('status')
  - index('starts_at')
  - composite index(['city_id', 'status', 'starts_at'])
- [ ] Запустить миграцию
- [ ] Mark completed

---

### Task 10: Написать feature tests

**Files:**
- Create: `tests/Feature/Coach/WorkoutTest.php`
- Create: `tests/Feature/Coach/WorkoutPolicyTest.php`

**Steps:**
- [ ] Создать WorkoutTest:
  - Тест создания черновика
  - Тест публикации (approved тренер)
  - Тест публикации (pending тренер → ошибка)
  - Тест редактирования своей тренировки
  - Тест редактирования чужой тренировки → 403
  - Тест отмены тренировки без броней
  - Тест отмены тренировки с оплаченными бронями → ValidationException
- [ ] Создать WorkoutPolicyTest:
  - Тест authorize publish для approved тренера
  - Тест deny publish для pending тренера
  - Тест update only own workouts
- [ ] Запустить `php artisan test`
- [ ] Mark completed

---

## Verification Notes

1. **Создание тренировки:**
   - Зайти как approved тренер → создать тренировку
   - Выбрать точку на карте → адрес загрузился автоматически
   - Указать цену 1000₽, 3 места → preview показывает "334 ₽ за место"
   - Сохранить черновик → видна в списке со статусом draft

2. **Публикация:**
   - Нажать "Опубликовать" → статус changed to published
   - Зайти как pending тренер → кнопка "Опубликовать" disabled

3. **Редактирование:**
   - Отредактировать время, место → сохранить → изменения применились

4. **Отмена:**
   - Отменить тренировку без броней → success
   - Создать бронь с оплатой → попробовать отменить → ошибка

## Risks

1. **Nominatim rate limit** — бесплатный API ограничен 1 req/sec. Решение: debounce + fallback на ручной ввод адреса.

2. **Leaflet SSR** — Leaflet требует window. Решение: динамический import компонента и рендер только в браузере (`onMounted`/`defineAsyncComponent`).

3. **Timezone issues** — starts_at должен храниться в UTC. Решение: использовать Carbon, конвертировать timezone города.

4. **Policy не срабатывает** — забыть authorize в контроллере. Решение: добавить middleware `Authorize` или вызвать `$this->authorize('publish', $workout)`.

---

**Definition of Done:**
- Все 10 задач завершены
- Тренер может создать, отредактировать, опубликовать тренировку
- Карта работает, reverse geocoding получает адрес
- slot_price рассчитывается корректно
- Tests проходят
