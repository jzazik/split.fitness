---
plan_type: dev
sprint: 6
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 6
dependencies: sprint-05-booking.md
---

# Sprint 6: Платежи

## Overview

Интеграция с Тинькофф Acquiring: создание payment session, webhook обработка, фиксация paid/failed статусов, экраны успеха/ошибки.

**Цель спринта:** Атлет может оплатить бронь через Тинькофф, webhook обновляет статус, атлет видит подтверждение.

**Источник требований:** Architecture spec §13 (Платёжный контур — Тинькофф)

## Context

**Текущее состояние:**
- Booking создаётся со статусом pending_payment
- Нет интеграции с платёжкой

**Целевое состояние:**
- Таблица `payments`
- Тинькофф Init API → payment URL
- Webhook endpoint с signature verification
- Экраны: success, fail
- Refund support (для отмены атлетом в Sprint 7)

**Технические решения:**
- Package: `tcb13/laravel-tinkoff-acquiring` (требуется проверка совместимости с Laravel 12 перед установкой)
- Альтернатива: прямая интеграция через Tinkoff REST API, если пакет несовместим
- Webhook: signature проверка через sha256(params + Password)
- Idempotency: проверка payment.status перед обновлением
- Асинхронная обработка webhook через Job

## Error Handling & Logging Strategy

**Error Boundaries:**
- Payment exceptions: `App\Exceptions\Payment\PaymentException`, `PaymentProviderException`
- Webhook validation failures: abort(400) для invalid signature, но логировать для мониторинга атак
- Idempotency violations: silent skip, но логировать warning
- External API failures: wrap Tinkoff API errors в domain exceptions

**Logging:**
- Channel: использовать отдельный channel `payments` (config/logging.php)
- Structured context: `payment_id`, `booking_id`, `user_id`, `external_payment_id`, `amount`, `provider`
- Levels:
  - `critical`: payment webhook signature invalid (possible attack)
  - `error`: Tinkoff API failures, refund failures
  - `warning`: idempotency skip, unexpected payment status
  - `info`: payment created, payment succeeded, refund succeeded
- **Никогда не логировать**: card numbers, CVV, Tinkoff Password, raw webhook token
- **Всегда логировать**: raw_request_json, raw_response_json, raw_webhook_json (в БД, не в plain logs)

**Применение в спринте:**
- Task 3 (PaymentService): логировать error при API failures с context
- Task 5 (webhook): логировать critical при invalid signature
- Task 6 (signature verifier): не логировать token/password в plain text
- Task 10 (logging): настроить channel 'payments' с daily rotation

## Validation Commands

```bash
php artisan migrate:status
php artisan test --filter=PaymentTest
php artisan test --filter=WebhookTest

# Тестовая оплата (Тинькофф sandbox)
# - Создать booking
# - Получить payment_url
# - Оплатить тестовой картой
# - Проверить webhook получен
# - Проверить booking.status = paid
```

---

### Task 1: Создать таблицу payments

**Files:**
- Create: `database/migrations/xxxx_create_payments_table.php`
- Create: `app/Models/Payment.php`

**Steps:**
- [ ] Создать миграцию payments:
  - id, booking_id foreign unique, user_id foreign
  - provider string default 'tinkoff'
  - external_payment_id string, external_receipt_id nullable
  - amount decimal, currency string default 'RUB'
  - status enum('created','pending','succeeded','failed','refunded') default 'created'
  - paid_at nullable
  - raw_request_json, raw_response_json, raw_webhook_json (json columns)
  - timestamps, indexes
- [ ] Создать Payment model:
  - belongsTo Booking, User
  - casts: status, paid_at
- [ ] Mark completed

---

### Task 2: Установить Тинькофф пакет

**Files:**
- Modify: `composer.json`
- Create: `config/tinkoff.php`
- Modify: `.env`

**Steps:**
- [ ] **КРИТИЧНО:** Сначала проверить совместимость пакета с Laravel 12:
  - Открыть Packagist: https://packagist.org/packages/tcb13/laravel-tinkoff-acquiring
  - Проверить секцию "Requires" в composer.json пакета
  - Проверить последний релиз и поддерживаемые версии Laravel
  - Если версия Laravel 12 НЕ указана в requires — считать пакет несовместимым
- [ ] **Если пакет совместим** (Laravel 12 в requires):
  - Установить: `composer require tcb13/laravel-tinkoff-acquiring`
  - Опубликовать конфиг: `php artisan vendor:publish --provider="Spatie\TinkoffAcquiring\TinkoffServiceProvider"` (уточнить namespace в документации)
  - Перейти к добавлению env переменных
- [ ] **Если пакет НЕ совместим** (Laravel 12 отсутствует в requires):
  - Использовать прямую интеграцию через Tinkoff REST API
  - Документация: https://www.tinkoff.ru/kassa/develop/api/
  - Создать Service: `app/Services/Payment/TinkoffApiClient.php`
  - Реализовать методы Init, Cancel, GetState согласно API документации
- [ ] Добавить в .env:
  - TINKOFF_TERMINAL_KEY=test_terminal
  - TINKOFF_PASSWORD=test_password
  - TINKOFF_NOTIFICATION_URL=${APP_URL}/api/payments/webhook
- [ ] Mark completed

---

### Task 3: Создать PaymentService для Тинькофф

**Files:**
- Create: `app/Services/Payment/TinkoffPaymentService.php`
- Create: `app/Services/Payment/PaymentServiceInterface.php`

**Steps:**
- [ ] Создать interface PaymentServiceInterface:
  - createPayment(Booking $booking): PaymentSession
  - refund(Payment $payment, $amount): bool
- [ ] Создать TinkoffPaymentService:
  - Method createPayment:
    - Вызвать Тинькофф Init API
    - Параметры: Amount (в копейках), OrderId (booking_id), Description, URLs
    - Сохранить Payment record (status=created, external_payment_id, raw_response_json)
    - Return ['payment_url' => $response['PaymentURL'], 'payment_id' => ...]
  - Method refund (заглушка пока)
- [ ] Mark completed

---

### Task 4: Обновить CreateBookingAction для генерации payment

**Files:**
- Modify: `app/Actions/Booking/CreateBookingAction.php`
- Modify: `app/Http/Controllers/Api/BookingController.php`

**Steps:**
- [ ] В CreateBookingAction после создания брони:
  - Вызвать PaymentService->createPayment($booking)
  - Return ['booking' => $booking, 'payment_url' => $paymentUrl]
- [ ] В BookingController@store:
  - Return response с payment_url
- [ ] Во фронте (WorkoutBottomCard):
  - После успеха POST /api/bookings → редирект на payment_url (Тинькофф форма)
- [ ] Mark completed

---

### Task 5: Создать webhook endpoint

**Files:**
- Create: `app/Http/Controllers/Api/PaymentWebhookController.php`
- Create: `app/Jobs/HandlePaymentWebhookJob.php`
- Modify: `routes/api.php`
- Modify: `bootstrap/app.php`

**Steps:**
- [ ] Создать PaymentWebhookController:
  - handle(Request $request):
    - Validate signature (sha256)
    - Если invalid → abort(400)
    - Dispatch HandlePaymentWebhookJob::dispatch($request->all())
    - Return 'OK' (Тинькофф требует немедленный ответ)
- [ ] Создать HandlePaymentWebhookJob:
  - Найти Payment by external_payment_id
  - Проверить idempotency (если уже succeeded → skip)
  - Обновить payment.status, payment.paid_at
  - Обновить booking.status = 'paid', booking.payment_status = 'paid'
  - Сохранить raw_webhook_json
  - Dispatch BookingPaid event (для email)
- [ ] Добавить роут: `POST /api/payments/webhook` (без middleware auth)
- [ ] Исключить /api/payments/webhook из CSRF в `bootstrap/app.php` (конфигурация middleware)
- [ ] Mark completed

---

### Task 6: Создать signature verification

**Files:**
- Create: `app/Services/Payment/TinkoffSignatureVerifier.php`
- Modify: `app/Http/Controllers/Api/PaymentWebhookController.php`

**Steps:**
- [ ] Создать TinkoffSignatureVerifier:
  - Method verify(array $params, string $token): bool
  - Алгоритм: добавить Password, ksort, implode, sha256, compare с token
- [ ] В webhook контроллере использовать verifier
- [ ] Mark completed

---

### Task 7: Создать экраны успеха и ошибки

**Files:**
- Create: `resources/js/Pages/Booking/Success.vue`
- Create: `resources/js/Pages/Booking/Fail.vue`
- Create: `app/Http/Controllers/BookingPaymentController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать BookingPaymentController:
  - success(Booking $booking) → authorize, render Success.vue
  - fail(Booking $booking) → authorize, render Fail.vue
- [ ] Создать Success.vue:
  - "Ура! Вы записались на тренировку!"
  - Детали тренировки, тренер, место, время
  - Кнопка "Мои тренировки" → /athlete/bookings
  - Кнопка "Пригласить друга" (placeholder)
- [ ] Создать Fail.vue:
  - "Оплата не прошла"
  - Причина (если есть в webhook)
  - Кнопка "Попробовать снова" → создать новый payment
- [ ] Добавить роуты:
  - `GET /booking/{booking}/success` → success
  - `GET /booking/{booking}/fail` → fail
- [ ] Обновить TinkoffPaymentService:
  - SuccessURL = /booking/{booking_id}/success
  - FailURL = /booking/{booking_id}/fail
- [ ] Mark completed

---

### Task 8: Добавить polling статуса оплаты

**Files:**
- Create: `resources/js/composables/usePaymentPolling.js`
- Modify: `resources/js/Pages/Booking/Success.vue`

**Steps:**
- [ ] Создать usePaymentPolling:
  - Функция pollPaymentStatus(bookingId, maxAttempts=10, interval=3000)
  - GET /api/bookings/{id}/status каждые 3 секунды
  - Если status=paid → stop polling, show success
  - Если maxAttempts → показать "Проверьте статус в кабинете"
- [ ] В Success.vue:
  - При mount начать polling (если status ещё pending)
  - Показать loader "Проверяем статус оплаты..."
  - Когда paid → показать финальный экран
- [ ] Создать endpoint: `GET /api/bookings/{booking}/status` → return booking.payment_status
- [ ] Mark completed

---

### Task 9: Написать feature tests

**Files:**
- Create: `tests/Feature/Payment/PaymentTest.php`
- Create: `tests/Feature/Payment/WebhookTest.php`

**Steps:**
- [ ] Создать PaymentTest:
  - Тест создания payment через Тинькофф (mock API)
  - Тест сохранения payment record
- [ ] Создать WebhookTest:
  - Тест обработки успешного webhook
  - Тест signature verification (valid/invalid)
  - Тест idempotency (повторный webhook не меняет статус)
  - Тест обновления booking.status = paid
- [ ] Запустить тесты
- [ ] Mark completed

---

### Task 10: Добавить логирование платежей

**Files:**
- Modify: `app/Jobs/HandlePaymentWebhookJob.php`
- Create: `storage/logs/payments.log` (через config)

**Steps:**
- [ ] Настроить отдельный log channel 'payments' в config/logging.php
- [ ] В HandlePaymentWebhookJob:
  - Log::channel('payments')->info('Webhook received', [webhook data])
  - Log при успехе/ошибке обработки
- [ ] Mark completed

---

## Verification Notes

1. Создать booking → получить payment_url
2. Открыть payment_url → форма Тинькофф (sandbox)
3. Оплатить тестовой картой (4242 4242 4242 4242)
4. Webhook получен → booking.status = paid
5. Редирект на /booking/{id}/success → видим экран успеха
6. Проверить в БД: payment.status = succeeded, raw_webhook_json заполнен

## Risks

1. **Webhook не приходит** — firewall блокирует или URL недоступен. Решение: ngrok для локальной разработки, проверить TINKOFF_NOTIFICATION_URL.
2. **Signature invalid** — неправильный алгоритм или Password. Решение: использовать Тинькофф документацию, тестировать с реальными webhook.
3. **Idempotency не работает** — webhook обрабатывается дважды. Решение: проверять payment.status перед update.
4. **User вернулся на сайт раньше webhook** — polling не видит paid. Решение: maxAttempts=10, показать "Подождите".

---

**Definition of Done:**
- Payment создаётся через Тинькофф Init
- Webhook обрабатывается с signature verification
- Booking status обновляется на paid
- Экраны success/fail работают
- Polling статуса работает
- Tests проходят
