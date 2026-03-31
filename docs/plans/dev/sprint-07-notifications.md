---
plan_type: dev
sprint: 7
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 7
dependencies: sprint-06-payments.md
---

# Sprint 7: Уведомления и scheduler

## Overview

Реализовать email уведомления для атлетов и тренеров, reminder jobs за 2 часа до тренировки, завершение тренировок, обработка отмены атлетом (с возвратом если >24 часа).

**Цель спринта:** Email уведомления работают автоматически, система фоново управляет lifecycle тренировок и броней.

**Источник требований:** Architecture spec §8.4 (отмена атлетом), §15 (scheduler), §26 (уведомления)

## Context

**Текущее состояние:**
- Payments работают
- Bookings создаются и оплачиваются
- Нет email уведомлений

**Целевое состояние:**
- Email templates (Blade Mailable)
- Jobs: SendBookingCreated, SendPaymentSuccess, SendWorkoutReminder, SendCoachReminder
- Scheduler: reminder за 2 часа, завершение тренировок
- Отмена атлетом: CancelBookingAction с refund (если >24 часа)
- Отмена тренировки тренером: логика с возвратами

**Технические решения:**
- Laravel Notifications + Mailable
- Queue: redis driver
- Scheduler: cron каждые 10 минут для reminders
- Refund через Тинькофф Cancel API

## Error Handling & Logging Strategy

**Error Boundaries:**
- Email send failures: catch MailException, retry via queue (max 3 attempts), затем log error
- Refund API failures: catch external API exception, не блокировать отмену booking, показать "Возврат будет обработан вручную"
- Scheduler job failures: не блокировать следующие runs, логировать и continue
- Cancellation validation: ValidationException "<24 hours" с clear user message

**Logging:**
- Structured context: `booking_id`, `user_id`, `email`, `notification_type`, `refund_amount`, `external_refund_id`
- Levels:
  - `critical`: reminders дублируются (sent_at flag не работает)
  - `error`: email send failure after retries, refund API failure
  - `warning`: reminder not sent (no email), cancellation attempt <24h
  - `info`: email sent successfully, refund succeeded, reminder scheduled
- **Никогда не логировать**: email content with PII, refund credentials
- **Всегда логировать**: notification_type, sent_at timestamp для idempotency tracking

**Применение в спринте:**
- Task 3 (Jobs): retry logic для email send failures
- Task 5 (scheduler): идемпотентность reminders через sent_at flag или unique job ID
- Task 7 (CancelBookingAction): логировать error если refund fails, info если succeeds
- Task 8 (refund): логировать external API request/response для debugging

## Validation Commands

```bash
php artisan migrate:status
php artisan queue:work --once
php artisan schedule:test
php artisan test --filter=NotificationTest
php artisan test --filter=CancellationTest

# Ручная проверка:
# - Создать booking → проверить email "Бронь создана"
# - Оплатить → email "Оплата успешна"
# - Создать workout за 1.5 часа → запустить scheduler → email reminder
```

---

### Task 1: Настроить email драйвер

**Files:**
- Modify: `.env`
- Modify: `config/mail.php`

**Steps:**
- [ ] Настроить SMTP в .env (Mailtrap для dev, реальный SMTP для prod)
- [ ] Проверить `php artisan tinker` → Mail::raw('test', fn($m) => $m->to('test@example.com'))
- [ ] Mark completed

---

### Task 2: Создать Mailable шаблоны

**Files:**
- Create: `app/Mail/BookingCreated.php`
- Create: `app/Mail/PaymentSuccess.php`
- Create: `app/Mail/WorkoutReminder.php`
- Create: `app/Mail/CoachNewBooking.php`
- Create: `resources/views/emails/**/*.blade.php`

**Steps:**
- [ ] Создать BookingCreated Mailable:
  - Параметры: $booking
  - View: emails.booking-created
  - Содержимое: "Вы записались на тренировку {workout.title}, оплатите в течение 15 минут"
- [ ] Создать PaymentSuccess:
  - "Оплата успешна! Ждём вас {starts_at}"
- [ ] Создать WorkoutReminder:
  - "Напоминание: тренировка через 2 часа"
- [ ] Создать CoachNewBooking:
  - "Новая запись на вашу тренировку от {athlete.name}"
- [ ] Создать Blade templates с дизайном
- [ ] Mark completed

---

### Task 3: Создать Jobs для отправки email

**Files:**
- Create: `app/Jobs/SendBookingCreatedNotification.php`
- Create: `app/Jobs/SendPaymentSuccessNotification.php`
- Create: `app/Jobs/SendCoachNewBookingNotification.php`
- Create: `app/Jobs/SendWorkoutReminderJob.php`

**Steps:**
- [ ] Создать SendBookingCreatedNotification:
  - Принимать $booking
  - Mail::to($booking->athlete->email)->send(new BookingCreated($booking))
- [ ] Аналогично для остальных jobs
- [ ] Mark completed

---

### Task 4: Подключить Jobs к событиям

**Files:**
- Create: `app/Events/BookingCreated.php`
- Create: `app/Events/BookingPaid.php`
- Create: `app/Listeners/SendBookingEmails.php`
- Modify: `app/Actions/Booking/CreateBookingAction.php`
- Modify: `app/Jobs/HandlePaymentWebhookJob.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Steps:**
- [ ] Создать событие BookingCreated, BookingPaid
- [ ] Создать listener для каждого события → dispatch jobs
- [ ] В CreateBookingAction: dispatch BookingCreated
- [ ] В HandlePaymentWebhookJob: dispatch BookingPaid
- [ ] Зарегистрировать listeners в AppServiceProvider (или включить event discovery)
- [ ] Mark completed

---

### Task 5: Реализовать scheduler для reminders

**Files:**
- Modify: `routes/console.php`
- Create: `app/Jobs/SendWorkoutRemindersJob.php`

**Steps:**
- [ ] Создать SendWorkoutRemindersJob:
  - Query: workouts где starts_at между now()+2h и now()+2h10m
  - Для каждой тренировки: найти paid bookings
  - Dispatch SendWorkoutReminderJob для каждого атлета
  - Dispatch SendCoachWorkoutReminderJob для тренера
- [ ] В `routes/console.php` добавить schedule:
  - `$schedule->job(SendWorkoutRemindersJob::class)->everyTenMinutes();`
- [ ] Mark completed

---

### Task 6: Реализовать завершение тренировок

**Files:**
- Modify: `routes/console.php`

**Steps:**
- [ ] В `routes/console.php` добавить schedule:
  - ```php
    $schedule->call(function () {
        Workout::where('status', 'published')
               ->where('starts_at', '<', now()->subHours(2))
               ->update(['status' => 'completed']);
    })->hourly();
    ```
- [ ] Mark completed

---

### Task 7: Реализовать отмену брони атлетом

**Files:**
- Create: `app/Actions/Booking/CancelBookingAction.php`
- Create: `app/Http/Controllers/Athlete/BookingCancellationController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать CancelBookingAction:
  - Проверить: booking.status = 'paid'
  - Проверить: workout.starts_at - now() > 24 hours
  - Если условия выполнены:
    - Вызвать TinkoffPaymentService->refund(payment, amount)
    - Обновить booking.status = 'cancelled', cancelled_at = now()
    - Обновить payment.status = 'refunded'
    - Decrement workout.slots_booked
    - Отправить email атлету и тренеру
  - Иначе → throw ValidationException "Отмена возможна только за 24 часа"
- [ ] Создать BookingCancellationController:
  - cancel(Booking $booking) → authorize, CancelBookingAction, редирект с сообщением
- [ ] Добавить роут: `POST /athlete/bookings/{booking}/cancel`
- [ ] Во фронте Bookings/Index.vue:
  - Кнопка "Отменить" видна только если >24 часа и paid
  - Подтверждающий диалог
- [ ] Mark completed

---

### Task 8: Реализовать refund в TinkoffPaymentService

**Files:**
- Modify: `app/Services/Payment/TinkoffPaymentService.php`

**Steps:**
- [ ] Реализовать метод refund:
  - Вызвать Тинькофф Cancel API
  - Параметры: PaymentId, Amount (полная сумма или частичная)
  - Сохранить raw_request/response в payment
  - Return success/failure
- [ ] Mark completed

---

### Task 9: Добавить email про отмену

**Files:**
- Create: `app/Mail/BookingCancelled.php`
- Create: `app/Mail/CoachBookingCancelled.php`
- Modify: `app/Actions/Booking/CancelBookingAction.php`

**Steps:**
- [ ] Создать Mailable для атлета: "Ваша запись отменена, возврат в течение 3-5 дней"
- [ ] Создать Mailable для тренера: "Атлет {name} отменил запись на {workout}"
- [ ] В CancelBookingAction dispatch email jobs
- [ ] Mark completed

---

### Task 10: Написать feature tests

**Files:**
- Create: `tests/Feature/Notification/NotificationTest.php`
- Create: `tests/Feature/Booking/CancellationTest.php`

**Steps:**
- [ ] Создать NotificationTest:
  - Тест отправки email при создании брони
  - Тест отправки email при оплате
  - Тест reminder job (mock scheduler)
- [ ] Создать CancellationTest:
  - Тест отмены >24 часа → refund успешен
  - Тест отмены <24 часа → ValidationException
  - Тест отмены pending брони → ошибка (можно отменять только paid)
  - Тест refund вызывается через Тинькофф API (mock)
- [ ] Запустить тесты
- [ ] Mark completed

---

## Verification Notes

1. Создать booking → проверить email "Бронь создана"
2. Оплатить → email "Оплата успешна" + тренеру "Новая запись"
3. Создать workout starts_at = now() + 1.5 hours → запустить scheduler → email reminder
4. Отменить бронь >24 часа → refund прошёл, email отправлен
5. Попробовать отменить <24 часа → ошибка

## Risks

1. **Queue не обрабатывается** — забыть запустить `queue:work`. Решение: supervisor или Horizon.
2. **Email не отправляются** — неправильные SMTP credentials. Решение: тестировать через Mailtrap.
3. **Reminder дублируются** — scheduler запускается дважды. Решение: проверять sent_at flag или использовать unique job.
4. **Refund fails** — Тинькофф API недоступен. Решение: обрабатывать ошибку, показывать сообщение "Попробуйте позже".

---

**Definition of Done:**
- Email уведомления отправляются на все события
- Reminders работают за 2 часа
- Отмена атлетом работает с refund
- Завершение тренировок автоматическое
- Tests проходят
