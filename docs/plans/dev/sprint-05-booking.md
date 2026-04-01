---
plan_type: dev
sprint: 5
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 5
dependencies: sprint-04-map.md
---

# Sprint 5: Бронирование

## Overview

Реализовать систему бронирования с резервацией слотов, защитой от oversell, TTL на pending bookings, кабинет атлета с записями.

**Цель спринта:** Атлет может записаться на тренировку, слот резервируется транзакционно, неоплаченные брони истекают через 15 минут.

**Источник требований:** Architecture spec §8.2 (Бронирование и гонки), §11.5

## Context

**Текущее состояние:**
- Workouts published
- Атлет может видеть карточку тренировки

**Целевое состояние:**
- Таблица `bookings`
- Транзакционная резервация слота (SELECT FOR UPDATE)
- TTL 15 минут на pending_payment bookings
- Job ExpirePendingBookingJob
- Кабинет атлета: список броней

**Технические решения:**
- DB::transaction + lockForUpdate
- Scheduler: every minute check expired bookings
- Booking states: pending_payment, paid, expired, cancelled, refunded

## Error Handling & Logging Strategy

**Error Boundaries:**
- Oversell exception: `App\Exceptions\Booking\OversellException` при slots_booked >= slots_total
- Transaction deadlock: retry logic (max 3 attempts), затем error response
- Duplicate booking: ValidationException "Вы уже записаны"
- Expired booking job failures: не блокировать scheduler, логировать и продолжить

**Logging:**
- Structured context: `booking_id`, `workout_id`, `athlete_id`, `slots_count`, `status`, `transaction_id`
- Levels:
  - `critical`: oversell произошёл (защита не сработала) — требует немедленного расследования
  - `error`: transaction deadlock after retries, duplicate booking attempt
  - `warning`: booking expired (TTL), ExpirePendingBookingJob processing >1000 bookings/run
  - `info`: booking created, booking expired, slot released
- **Никогда не логировать**: payment details (в этом спринте только pending)
- **Всегда логировать**: slots_booked before/after для audit trail

**Применение в спринте:**
- Task 2 (ReserveSlotAction): логировать critical если oversell detection fails
- Task 6 (ExpirePendingBookingJob): логировать info per expired booking с context
- Task 7 (OversellTest): проверять, что oversell exception логируется как critical

## Validation Commands

```bash
php artisan migrate:status
php artisan test --filter=BookingTest
php artisan test --filter=OversellTest

# Проверка scheduler
php artisan schedule:test

# Ручная проверка oversell:
# - Создать workout с 1 местом
# - Попробовать создать 2 брони одновременно → вторая должна получить ошибку
```

---

### Task 1: Создать таблицу bookings

**Files:**
- Create: `database/migrations/xxxx_create_bookings_table.php`
- Create: `app/Models/Booking.php`

**Steps:**
- [x] Создать миграцию bookings:
  - id, workout_id foreign, athlete_id (user_id) foreign
  - slots_count int default 1, slot_price decimal, total_amount decimal
  - status enum('pending_payment','paid','expired','cancelled','refunded') default 'pending_payment'
  - payment_status enum('pending','paid','failed','refunded') default 'pending'
  - booked_at nullable, cancelled_at nullable, cancellation_reason nullable
  - timestamps, indexes
- [x] Создать Booking model:
  - belongsTo Workout, User (athlete)
  - hasOne Payment
  - casts: status, payment_status, booked_at
- [x] Mark completed

---

### Task 2: Создать Actions для бронирования

**Files:**
- Create: `app/Actions/Booking/CreateBookingAction.php`
- Create: `app/Actions/Booking/ReserveSlotAction.php`

**Steps:**
- [x] Создать ReserveSlotAction:
  - DB::transaction
  - $workout->lockForUpdate()
  - Проверить: slots_booked < slots_total
  - Если нет мест → throw ValidationException
  - Increment slots_booked
  - Commit
- [x] Создать CreateBookingAction:
  - Вызвать ReserveSlotAction
  - Создать booking (pending_payment, slot_price, total_amount)
  - Return booking
- [x] Mark completed

---

### Task 3: Создать API endpoint для бронирования

**Files:**
- Create: `app/Http/Controllers/Api/BookingController.php`
- Create: `app/Http/Requests/CreateBookingRequest.php`
- Modify: `routes/api.php`

**Steps:**
- [x] Создать BookingController:
  - store(CreateBookingRequest) → CreateBookingAction
  - Return booking with payment_url (placeholder пока)
- [x] Создать CreateBookingRequest:
  - workout_id required exists
  - slots_count default 1 (для MVP всегда 1)
  - Проверить: user->role === 'athlete'
  - Проверить: workout published, starts_at > now()
  - Проверить: user не записан повторно на эту тренировку
- [x] Добавить роут: `POST /api/bookings` (middleware auth, role:athlete)
- [x] Mark completed

---

### Task 4: Создать кабинет атлета — список броней

**Files:**
- Create: `resources/js/Pages/Athlete/Bookings/Index.vue`
- Create: `app/Http/Controllers/Athlete/BookingsController.php`
- Modify: `routes/web.php`

**Steps:**
- [x] Создать BookingsController:
  - index() — bookings текущего атлета, eager load workout.sport.coach
  - Разделить на: upcoming, past, cancelled
- [x] Создать Index.vue:
  - Tabs: Ближайшие | Прошедшие | Отменённые
  - Карточка брони: дата, время, место, тренер, статус, цена
  - Кнопка "Отменить" (если >24 часа, pending оплаты нет)
- [x] Добавить роут: `GET /athlete/bookings` → index
- [x] Mark completed

---

### Task 5: Добавить кнопку "Записаться" на карте

**Files:**
- Modify: `resources/js/Components/Map/WorkoutBottomCard.vue`
- Modify: `resources/js/Pages/Public/Map/Index.vue`

**Steps:**
- [x] В WorkoutBottomCard:
  - Кнопка "Записаться"
  - Если не auth → редирект /login?redirect=/map
  - Если auth (athlete) → POST /api/bookings {workout_id}
  - После успеха → redirect /booking/{id}/payment (placeholder)
- [x] Показать "Мест нет" если slots_booked >= slots_total
- [x] Mark completed

---

### Task 6: Создать Job для истечения броней

**Files:**
- Create: `app/Jobs/ExpirePendingBookingJob.php`
- Modify: `routes/console.php`

**Steps:**
- [ ] Создать ExpirePendingBookingJob:
  - Query: bookings where status=pending_payment AND created_at < now() - 15 minutes
  - Для каждой:
    - DB::transaction
    - booking->workout->lockForUpdate()
    - booking->update(['status' => 'expired'])
    - workout->decrement('slots_booked', booking->slots_count)
    - commit
- [ ] В `routes/console.php` добавить schedule:
  - `$schedule->job(ExpirePendingBookingJob::class)->everyMinute();`
- [ ] Mark completed

---

### Task 7: Написать oversell protection tests

**Files:**
- Create: `tests/Feature/Booking/OversellTest.php`
- Create: `tests/Feature/Booking/BookingTest.php`

**Steps:**
- [ ] Создать OversellTest:
  - Тест: workout с 1 местом, создать 2 брони параллельно → вторая ошибка
  - Тест: DB isolation levels работают
- [ ] Создать BookingTest:
  - Тест создания брони
  - Тест валидации (повторная запись)
  - Тест истечения (created_at - 20 minutes → job → expired)
- [ ] Запустить тесты
- [ ] Mark completed

---

### Task 8: Добавить уведомление тренеру о новой записи

**Files:**
- Create: `app/Listeners/NotifyCoachNewBooking.php`
- Create: `app/Events/BookingCreated.php`
- Modify: `app/Actions/Booking/CreateBookingAction.php`

**Steps:**
- [ ] Создать событие BookingCreated
- [ ] Создать listener (заглушка, email в Sprint 7)
- [ ] В CreateBookingAction dispatch event
- [ ] Mark completed

---

### Task 9: Добавить проверку дублирования брони

**Files:**
- Modify: `app/Http/Requests/CreateBookingRequest.php`

**Steps:**
- [ ] В CreateBookingRequest добавить rule:
  - Проверить: нет активной брони (pending_payment или paid) у этого атлета на этот workout
  - Если есть → ValidationException "Вы уже записаны"
- [ ] Mark completed

---

### Task 10: Создать детальную страницу брони

**Files:**
- Create: `resources/js/Pages/Athlete/Bookings/Show.vue`
- Modify: `app/Http/Controllers/Athlete/BookingsController.php`

**Steps:**
- [ ] В BookingsController добавить:
  - show(Booking $booking) → authorize (только свои брони)
- [ ] Создать Show.vue:
  - Детали тренировки, тренер, место, время
  - Статус оплаты, чек (если paid)
  - Кнопка "Отменить" (если условия позволяют)
- [ ] Добавить роут: `GET /athlete/bookings/{booking}`
- [ ] Mark completed

---

## Verification Notes

1. Создать booking → slots_booked увеличился
2. Не оплатить 15 минут → job истёк бронь, slots_booked уменьшился
3. Попробовать создать 2 брони на последнее место → вторая ошибка
4. Проверить список броней в /athlete/bookings

## Risks

1. **Race condition** — без lockForUpdate будет oversell. Решение: обязательно использовать transaction + lock.
2. **Job не запускается** — scheduler не работает без cron. Решение: добавить `* * * * * cd /path && php artisan schedule:run`.
3. **Expired bookings накапливаются** — если job падает. Решение: мониторинг queue failed jobs.

---

**Definition of Done:**
- Booking создаётся транзакционно
- Oversell защита работает
- ExpirePendingBookingJob истекает брони
- Кабинет атлета показывает записи
- Tests проходят
