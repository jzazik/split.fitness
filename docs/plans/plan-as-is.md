---
plan_type: architecture
status: draft
created: 2026-03-31
version: 1.0
source: Initial product vision and technical design
---

> **Тип документа:** Архитектурная спецификация (не исполняемый dev-план)
>
> Этот документ описывает продуктовое видение, техническую архитектуру и общий подход к реализации.
> Для исполнения агентами создавайте отдельные dev-планы в `docs/plans/dev/` в формате Ralphex.

**Статус плана:** READY — все критические вопросы разрешены

**Принятые решения:**
- ✅ Политика отмены: возврат если >24 часа до тренировки
- ✅ Округление цены: округлять вверх (атлет платит чуть больше)
- ✅ Модерация тренеров: ручная проверка перед первой публикацией
- ✅ Платёжная система: Тинькофф Acquiring
- ✅ Напоминание: за 2 часа до тренировки

---

# 0. Дизайн (бета версия)

[Figma: split.fitness beta design — screens and flows](https://www.figma.com/design/o8PrgPSjIfCRC79RmRuZai/spl-26)

**Scope:** UI mockups для карты атлета, дашборда тренера, флоу бронирования и экранов профиля.
**Status:** Beta (подлежит изменениям на основе обратной связи MVP).

# 1. Цель продукта

Есть две роли:

- **Атлет** — находит тренировки на карте, смотрит карточку, записывается, оплачивает слот, получает подтверждение и видит запись в личном кабинете.
- **Тренер** — регистрируется, заполняет профиль, создаёт тренировки, публикует их на карте, управляет своими тренировками и участниками.

---

# 1.5. Вне скоупа MVP

**Не разрабатываем в первой версии:**
- Отзывы и рейтинги (механика упоминается в схеме данных, но не реализуется)
- Чат между тренером и атлетом
- Подписки / абонементы
- Реферальная программа
- Детальная аналитика для тренера (revenue dashboard)
- Push-уведомления
- SMS-уведомления
- Социальные сети логин (OAuth)
- Сложная модерация контента
- Многоуровневые отмены с частичными возвратами

**Реализуется позже по приоритету бизнеса.**

---

# 2. Продуктовая модель

## 2.1 Основные сущности

### Пользователь
Один пользователь в системе. У него может быть одна роль:

- athlete
- coach
- admin

На старте лучше делать **одну роль на аккаунт**, без смешанного режима. Иначе лишняя сложность в кабинетах, логике доступа и onboarding.

**Поля пользователя:**
- ФИО: first_name, last_name, middle_name (опционально)
- Контакты: email, phone
- Верификация: email_verified_at, phone_verified_at
- Профиль: avatar_path (опционально), city_id (опционально)
- Статус: status (active/blocked)

### Профиль тренера
Отдельная сущность, расширяющая пользователя:

- фото
- ФИО
- bio / рассказ о себе
- город
- список видов спорта
- рейтинг
- статус модерации

### Профиль атлета
На MVP можно сделать очень лёгким:

- имя
- фото опционально
- телефон
- город опционально

### Вид спорта
Справочник:

- running
- functional
- yoga
- cycling
- boxing
- etc.

### Город
Справочник городов или свободный ввод на MVP. Лучше справочник, потому что потом завязан фильтр, SEO, аналитика.

### Тренировка
Главная сущность:

- тренер
- спорт
- город
- геоточка
- адрес / текстовое описание места
- дата/время старта
- длительность
- общая стоимость тренировки
- количество мест
- статус
- опубликована или нет

### Бронирование
Связь атлета и тренировки:

- статус брони
- цена слота на момент покупки
- количество слотов, если захотите позже покупать не 1, а несколько
- статус оплаты
- id платежа во внешней системе

### Платёж
Отдельная сущность для аудита:

- booking_id
- user_id
- provider
- external_payment_id
- amount
- status
- receipt_url / receipt data
- raw payload для логов

### Отзывы и рейтинг
Для MVP можно не делать полноценные отзывы. Рейтинг сначала можно оставить техническим полем, не отображать или отображать как заглушку. Иначе возникнут споры, модерация и UX для завершённой тренировки.

---

# 3. Архитектурный принцип

Делать как **монолит Laravel**, где:

- Laravel — backend, auth, бизнес-логика, API, очереди, платежи, уведомления
- Inertia — glue layer между backend и frontend
- Vue 3 — интерфейс SPA-стиля
- PWA — мобильноподобный опыт, установка на телефон, кеш оболочки, push позже

Это оптимально для старта, потому что:

- одна кодовая база
- меньше накладных расходов
- быстрое развитие
- просто вести роли, кабинеты, серверный рендеринг данных страниц
- можно потом отделить mobile app, если проект взлетит

---

# 4. Техническая архитектура

## 4.1 Backend

### Основа
- Laravel 12 (проект использует последнюю версию)
- PHP 8.3+
- MySQL 8 или PostgreSQL
- Redis для кеша, очередей, rate limit
- Horizon для очередей
- Laravel Scout не нужен на старте
- Laravel Policies / Gates для ролей и доступа
- Laravel Form Requests для валидации
- Service layer для бизнес-логики
- DTO / Data classes по желанию, но без фанатизма

### Что вынести в отдельные слои
Нельзя всё лепить в контроллеры. Сразу разделить:

- **Controllers** — принимают запрос
- **Requests** — валидируют
- **Actions / Services** — бизнес-логика
- **Repositories** — не обязательны; в Laravel часто избыточны
- **Policies** — доступ
- **Jobs** — фоновые действия
- **Listeners** — реакции на события
- **Resources** — формирование ответа

---

## 4.2 Frontend

### Основа
- Vue 3
- Inertia.js
- Pinia для локального клиентского состояния
- VueUse
- Tailwind CSS
- Headless UI / собственные компоненты
- Leaflet или Mapbox GL JS для карты

### Почему Leaflet на старте лучше
Если нет сильной потребности в сложной векторной карте:

- дешевле
- проще
- быстрее внедрить
- легко ставить маркеры и кластеры

Если нужен красивый продуктовый вид и сложные карты — Mapbox.

---

## 4.3 PWA

### Что должно быть
- web app manifest
- service worker
- offline cache для shell
- иконки
- install prompt
- fallback экран без сети
- кеширование статики и последних страниц

### Что не надо обещать на старте
Полную offline-работу с картой и оплатой. Это не нужно для MVP. PWA здесь — это:

- установка на домашний экран
- быстрый запуск
- ощущение приложения
- кеш интерфейса

---

# 5. Роли и доступы

## 5.1 Роли
В таблице users поле:

- role = athlete | coach | admin

## 5.2 Доступы

### Атлет
Может:
- видеть карту
- видеть карточки тренировок
- записываться
- оплачивать
- видеть свои бронирования
- редактировать профиль

### Тренер
Может:
- редактировать свой профиль
- создавать тренировки
- публиковать / снимать с публикации
- видеть список своих тренировок
- видеть записи на тренировки
- отменять тренировку при нужной логике

### Админ
Может:
- модерировать тренеров
- смотреть пользователей
- смотреть тренировки
- смотреть платежи
- блокировать сущности

---

# 6. Пользовательские сценарии

## 6.1 Сценарий тренера

1. Регистрация
2. Выбор роли «Тренер»
3. Подтверждение телефона / email
4. Заполнение профиля:
   - фото
   - ФИО
   - о себе
   - виды спорта
   - город
5. Попадание в кабинет
6. Создание тренировки:
   - точка на карте
   - адрес / описание места
   - дата
   - время
   - стоимость тренировки целиком
   - число мест
   - вид спорта
7. Публикация
8. Тренировка появляется на карте

## 6.2 Сценарий атлета

1. Заходит в приложение
2. Видит карту с тренировками
3. Нажимает на точку
4. Видит карточку тренировки
5. Нажимает «Записаться»
6. Если не авторизован — auth flow
7. Переходит на экран оплаты
8. Оплачивает слот
9. Получает успешный статус
10. Бронь появляется в кабинете

---

# 7. Структура базы данных

Ниже — базовый вариант.

## 7.1 users
- id
- role
- email
- phone
- password
- first_name
- last_name
- middle_name nullable
- avatar_path nullable
- city_id nullable
- email_verified_at nullable
- phone_verified_at nullable
- status
- created_at
- updated_at

## 7.2 coach_profiles
- id
- user_id
- bio
- experience_years nullable
- rating_avg default 0
- rating_count default 0
- moderation_status
- is_public boolean
- created_at
- updated_at

## 7.3 athlete_profiles
- id
- user_id
- emergency_contact nullable
- created_at
- updated_at

## 7.4 sports
- id
- slug
- name
- icon nullable
- is_active

## 7.5 coach_sports
pivot:
- id
- coach_profile_id
- sport_id

## 7.6 cities
- id
- name
- slug
- country_code
- lat
- lng

## 7.7 workouts
- id
- coach_id (user_id тренера)
- sport_id
- city_id
- title nullable
- description nullable
- location_name
- address nullable
- lat
- lng
- starts_at
- duration_minutes
- total_price
- slots_total
- slots_booked default 0
- status
- published_at nullable
- cancelled_at nullable
- created_at
- updated_at

### status для workouts
- draft
- published
- cancelled
- completed

## 7.8 bookings
- id
- workout_id
- athlete_id
- slots_count default 1
- slot_price
- total_amount
- status
- booked_at nullable
- cancelled_at nullable
- cancellation_reason nullable
- payment_status
- created_at
- updated_at

### status для bookings
- pending_payment
- paid
- cancelled
- refunded
- expired

## 7.9 payments
- id
- booking_id
- user_id
- provider
- external_payment_id
- external_receipt_id nullable
- amount
- currency
- status
- paid_at nullable
- raw_request_json nullable
- raw_response_json nullable
- raw_webhook_json nullable
- created_at
- updated_at

### status для payments
- created
- pending
- succeeded
- failed
- refunded

## 7.10 workout_attendance
Если позже захотите отмечать факт посещения:
- id
- workout_id
- athlete_id
- status
- checked_in_at nullable

## 7.11 reviews
Потом:
- id
- workout_id
- coach_id
- athlete_id
- rating
- text
- status
- created_at

## 7.12 notifications
Можно использовать стандартные notifications Laravel.

---

# 8. Критическая бизнес-логика

## 8.1 Цена для атлета

Ты описал логику так:

**цена слота = общая стоимость тренировки / количество мест**

Значит:
- тренер указывает `total_price`
- тренер указывает `slots_total`
- backend считает `slot_price = total_price / slots_total`

Нельзя считать только на фронте. Это обязательно должно жить на backend, иначе расхождения, округления, уязвимости.

### Правильный подход
В workouts хранить:
- total_price
- slots_total

А `slot_price`:
- либо вычислять динамически
- либо записывать при публикации

Для стабильности лучше **записывать slot_price в момент публикации**, чтобы потом всё было консистентно.

Добавить поле:
- slot_price

И делать его immutable после публикации.

---

## 8.2 Бронирование и гонки

Самое опасное место — oversell, когда два атлета платят за последний слот одновременно.

### Нельзя
- просто проверить `slots_booked < slots_total`
- потом создать запись
- потом принять оплату

Так появятся гонки.

### Нужна схема резервации
Лучше так:

1. Атлет нажимает «Записаться»
2. Система создаёт `booking` в статусе `pending_payment`
3. На уровне БД или транзакции резервируется слот
4. Пользователь получает payment session
5. Если платёж успешен — `booking -> paid`
6. Если неуспешен / истёк — бронь освобождается

### Реализация
Через транзакцию и `SELECT ... FOR UPDATE`:

- блокируем строку `workouts`
- проверяем свободные места
- увеличиваем `slots_booked`
- создаём booking pending_payment
- коммит

Если платёж не завершён за **15 минут**:
- cron / queue job ExpirePendingBookingJob
- booking -> expired
- slots_booked -= 1

**Техническая деталь:**
- Условие: `booking.created_at + 15 minutes < now() AND status = pending_payment`
- Job запускается каждую 1 минуту через scheduler
- Атомарная операция с блокировкой строки workout

Это обязательная часть.

---

## 8.3 Отмена тренировки тренером

Нужны правила:

### Вариант для MVP
- если нет оплаченных броней — можно отменять сразу
- если есть оплаченные брони — отмена через админский флаг или с запуском refund flow

Иначе тренер будет ломать оплаты.

---

## 8.4 Отмена записи атлетом

**✅ РЕШЕНИЕ ПРИНЯТО**

**Политика возврата:**
- **Более 24 часов до тренировки** — полный возврат средств через Тинькофф refund API
- **Менее 24 часов до тренировки** — отмена запрещена (кнопка недоступна)

**Реализация:**

### Backend
1. Проверка времени: `workout.starts_at - now() > 24 hours`
2. Если условие выполнено:
   - Создать refund через Тинькофф API
   - `booking.status = cancelled`
   - `booking.cancelled_at = now()`
   - `payment.status = refunded`
   - `workout.slots_booked -= booking.slots_count`
3. Email уведомления атлету и тренеру

### Frontend (кабинет атлета)
- Кнопка "Отменить" видна только если `>24 часа до тренировки`
- Подтверждающий диалог с текстом политики
- После подтверждения → API запрос → показать статус "Отменено, возврат в течение 3-5 дней"

### State transitions
- `paid -> cancelled` (если успешный refund)
- `paid -> refund_pending` (если refund в процессе) → `refunded` (после подтверждения от Тинькофф)

Это обязательная часть MVP.

---

# 9. Экранная структура приложения

---

## 9.1 Публичная часть

### Экран карты
Первый основной экран атлета:

- карта города
- маркеры тренировок
- фильтры
- нижняя карточка выбранной тренировки
- CTA записаться

### Экран выбора города
Если геолокация не определилась:
- выбрать город вручную

### Экран логина / регистрации
- вход
- регистрация
- выбор роли

---

## 9.2 Кабинет тренера

### Dashboard
- ближайшие тренировки
- количество записей
- доход по тренировкам позже
- статус профиля

### Профиль тренера
- фото
- ФИО
- bio
- город
- виды спорта

### Мои тренировки
- список тренировок
- фильтр по статусу
- создание новой

### Создание / редактирование тренировки
- карта
- точка
- адрес
- дата/время
- спорт
- стоимость
- места
- публикация

### Детали тренировки
- кто записан
- сколько мест занято
- статус
- отмена / завершение

---

## 9.3 Кабинет атлета

### Мои записи
- ближайшие
- прошедшие
- отменённые

### Детали записи
- дата
- время
- место
- тренер
- чек / статус оплаты

### Профиль
- имя
- телефон
- email
- фото

---

# 10. Маршрутизация

## 10.1 Web routes
Так как Inertia — большая часть UI может идти через web routes.

### Public
- `/`
- `/map`
- `/login`
- `/register`
- `/city/:slug`
- `/workouts/:id` — опционально, если нужен shareable page

### Athlete
- `/athlete/bookings`
- `/athlete/bookings/:id`
- `/athlete/profile`

### Coach
- `/coach/dashboard`
- `/coach/profile`
- `/coach/workouts`
- `/coach/workouts/create`
- `/coach/workouts/:id`
- `/coach/workouts/:id/edit`

### Admin
- `/admin/users`
- `/admin/coaches`
- `/admin/workouts`
- `/admin/payments`

**Примечание о структуре роутов:** Префиксы `/athlete/` и `/coach/` жёстко привязаны к роли пользователя. Смена роли не поддерживается (один аккаунт = одна роль навсегда, §2.1). Если в будущем потребуется смена ролей, маршруты нужно будет рефакторить на `/dashboard/` с проверкой роли внутри.

---

## 10.2 API routes
Для асинхронных действий:

- `GET /api/workouts/map`
- `POST /api/bookings`
- `POST /api/payments/create`
- `POST /api/payments/webhook`
- `POST /api/workouts/:id/cancel`
- `POST /api/bookings/:id/cancel`
- `GET /api/me`
- `PATCH /api/coach/profile`
- `POST /api/coach/workouts`

---

# 11. Backend-модули

## 11.1 Auth module
Нужен:
- регистрация
- логин
- logout
- reset password
- email verification
- желательно phone verification позже

### Рекомендуемый пакет
- Laravel Breeze + Inertia + Vue
или
- Laravel Jetstream, но он тяжелее

Для такого проекта лучше **Breeze + собственная доработка**.

---

## 11.2 Coach profile module
Функции:
- создать профиль
- обновить профиль
- загрузить фото
- выбрать виды спорта
- выбрать город

### Валидация
- ФИО обязательны
- хотя бы один спорт обязателен
- фото по размеру и mime
- bio длина ограничена

---

## 11.3 Workout module
Функции:
- создать черновик
- опубликовать
- снять с публикации
- отменить
- завершить
- получить данные для карты

### Ограничения
- нельзя создать тренировку в прошлом
- slots_total > 0
- total_price > 0
- координаты обязательны
- спорт обязателен

---

## 11.4 Map discovery module
Функции:
- отдать список тренировок по городу / bbox
- фильтры:
  - дата
  - спорт
  - цена слота
  - только с доступными местами

### Оптимизация
Нельзя сразу грузить весь город без ограничений, если будет много точек.
Нужно работать по:

- текущему viewport карты
- текущему zoom
- текущим фильтрам

Параметры:
- northEastLat
- northEastLng
- southWestLat
- southWestLng

---

## 11.5 Booking module
Функции:
- создание брони
- резерв слота
- отмена брони
- истечение брони
- получение списка броней пользователя

---

## 11.6 Payment module
Функции:
- создание payment session
- обработка callback / webhook
- фиксация успешной оплаты
- фиксация неуспешной оплаты
- возвраты потом

### Важно
Платёж нельзя считать успешным по redirect пользователя обратно.
Источник истины — только webhook платёжной системы.

---

## 11.7 Notification module
Нужны уведомления:

### Атлету
- бронь создана
- оплата успешна
- тренировка отменена
- напоминание за X часов

### Тренеру
- кто-то записался
- тренировка скоро начинается
- тренировка отменена / проблема с оплатой

Каналы:
- email
- web notifications later
- push later
- SMS later

На MVP достаточно email.

---

# 12. Карта и геологика

## 12.1 Что отображать
На карте только опубликованные тренировки:

- `status = published`
- `starts_at > now`
- `slots_booked < slots_total` или показывать и full, если нужно

## 12.2 Формат ответа для карты
На карту не надо отдавать всю сущность. Нужен облегчённый payload:

- workout_id
- lat
- lng
- sport_name
- starts_at
- slot_price
- slots_total
- slots_booked
- coach_name
- coach_avatar
- coach_rating

## 12.3 Кластеризация
Если точек станет много:
- leaflet.markercluster
или аналогичный инструмент

## 12.4 Геокодинг
Тренер ставит точку на карте, но лучше ещё получать человекочитаемый адрес.

Нужен reverse geocoding:
- по lat/lng получить адрес
- сохранить строкой

Можно через:
- Mapbox Geocoding
- OpenStreetMap Nominatim на старте, но осторожно по лимитам

---

# 13. Платёжный контур

**✅ Платёжная система:** Тинькофф Acquiring

## 13.1 Базовый поток (Тинькофф)

1. Атлет нажимает "Записаться"
2. Backend создаёт `booking` со статусом `pending_payment`
3. Backend вызывает Тинькофф API `Init` → получает `PaymentId` и `PaymentURL`
4. Фронт редиректит атлета на `PaymentURL` (форма оплаты Тинькофф)
5. Атлет оплачивает картой
6. Тинькофф отправляет webhook на `/api/payments/webhook` (уведомление о статусе)
7. Backend обрабатывает webhook:
   - Проверяет signature (Token)
   - Обновляет `booking.status = paid` и `payment.status = succeeded`
   - Отправляет email атлету и тренеру
8. Тинькофф редиректит атлета на `SuccessURL` → фронт показывает экран успеха

## 13.2 Тинькофф API методы

### Init (создание платежа)
```http
POST https://securepay.tinkoff.ru/v2/Init
{
  "TerminalKey": "YOUR_TERMINAL",
  "Amount": 50000,  // в копейках (500 руб)
  "OrderId": "booking_123",
  "Description": "Тренировка: Лужники, 30 ноября",
  "SuccessURL": "https://split.fitness/booking/success",
  "FailURL": "https://split.fitness/booking/fail",
  "NotificationURL": "https://split.fitness/api/payments/webhook"
}
```

### Cancel (отмена/возврат)
```http
POST https://securepay.tinkoff.ru/v2/Cancel
{
  "TerminalKey": "YOUR_TERMINAL",
  "PaymentId": "123456789",
  "Amount": 50000  // полный или частичный возврат
}
```

## 13.3 Что хранить в `payments`

```php
// database/migrations/xxxx_create_payments_table.php
$table->foreignId('booking_id')->constrained();
$table->foreignId('user_id')->constrained();
$table->string('provider')->default('tinkoff');
$table->string('external_payment_id');  // PaymentId от Тинькофф
$table->decimal('amount', 10, 2);
$table->string('currency', 3)->default('RUB');
$table->string('status');  // created, pending, succeeded, failed, refunded
$table->timestamp('paid_at')->nullable();
$table->json('raw_request_json')->nullable();   // Init request
$table->json('raw_response_json')->nullable();  // Init response
$table->json('raw_webhook_json')->nullable();   // Webhook payload
```

## 13.4 Безопасность Тинькофф

### Webhook signature verification
```php
// Controllers/Api/PaymentWebhookController.php
public function handle(Request $request)
{
    $token = $request->input('Token');
    $expectedToken = $this->generateToken($request->except('Token'));

    if ($token !== $expectedToken) {
        abort(400, 'Invalid signature');
    }

    // Обработка webhook...
}

private function generateToken(array $params)
{
    $params['Password'] = config('services.tinkoff.password');
    ksort($params);
    return hash('sha256', implode('', $params));
}
```

### Idempotency
- Проверять `booking.payment_status` перед обновлением
- Если уже `paid` → пропускать повторный webhook
- Логировать все webhook в `raw_webhook_json`

### Важные моменты
- ✅ Источник истины — только webhook, НЕ redirect пользователя
- ✅ Webhook может прийти раньше, чем пользователь вернётся на сайт
- ✅ Webhook может прийти несколько раз (обрабатывать идемпотентно)
- ✅ В тестовом режиме использовать `securepay.tinkoff.ru/v2/` с тестовым TerminalKey

## 13.5 Рекомендуемый пакет
```bash
composer require tcb13/laravel-tinkoff-acquiring
```
Или собственная обёртка через HTTP client.

---

# 14. Состояния и state machine

## 14.1 Workout state machine
- draft
- published
- cancelled
- completed

### Переходы
- draft -> published
- published -> cancelled
- published -> completed

## 14.2 Booking state machine
- pending_payment
- paid
- expired
- cancelled
- refunded

### Переходы
- pending_payment -> paid
- pending_payment -> expired
- paid -> cancelled
- paid -> refunded

## 14.3 Payment state machine
- created
- pending
- succeeded
- failed
- refunded

Это нужно описать прямо в коде, а не держать в голове.

---

# 15. Очереди и фоновые задачи

Нужны сразу.

## Jobs
- SendBookingCreatedNotification — атлету после резервации слота
- SendPaymentSuccessNotification — атлету после успешной оплаты
- SendCoachNewBookingNotification — тренеру о новой записи
- SendWorkoutReminderJob — атлету **за 2 часа** до тренировки
- SendCoachWorkoutReminderJob — тренеру за 2 часа до тренировки
- ExpirePendingBookingJob — истечение неоплаченных броней (15 минут)
- HandlePaymentWebhookJob — асинхронная обработка Тинькофф webhook
- ReverseGeocodeWorkoutLocationJob — получение адреса по координатам
- CompleteWorkoutJob — перевод тренировки в статус completed

## Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Истечение pending bookings каждую минуту
    $schedule->job(ExpirePendingBookingJob::class)
             ->everyMinute();

    // Напоминания за 2 часа до тренировки (каждые 10 минут проверка)
    $schedule->call(function () {
        $workouts = Workout::where('starts_at', '<=', now()->addHours(2))
                           ->where('starts_at', '>', now()->addHours(2)->subMinutes(10))
                           ->where('status', 'published')
                           ->with('bookings.athlete')
                           ->get();

        foreach ($workouts as $workout) {
            SendWorkoutReminderJob::dispatch($workout);
        }
    })->everyTenMinutes();

    // Завершение прошедших тренировок (раз в час)
    $schedule->call(function () {
        Workout::where('status', 'published')
               ->where('starts_at', '<', now()->subHours(2))
               ->update(['status' => 'completed']);
    })->hourly();
}
```

---

# 16. PWA-реализация

## 16.1 Manifest
- name
- short_name
- icons
- theme_color
- background_color
- display standalone

## 16.2 Service worker
Кешировать:
- js/css
- иконки
- shell страниц

Не кешировать агрессивно:
- платежные страницы
- чувствительные данные кабинета
- live-status бронирований

## 16.3 Offline UX
Если нет сети:
- показать cached shell
- экран “нет соединения”
- без фейкового доступа к оплате или бронированию

## 16.4 Installability
- добавить баннер установки
- отдельная UI-подсказка для iOS

---

# 17. UI-компоненты Vue

Нужна нормальная дизайн-система, иначе всё развалится.

## Базовые компоненты
- Button
- Input
- Textarea
- Select
- MultiSelect
- DatePicker
- TimePicker
- AvatarUploader
- Modal
- Drawer / BottomSheet
- Card
- Badge
- EmptyState
- SkeletonLoader

## Доменные компоненты
- WorkoutMap
- WorkoutMarker
- WorkoutBottomCard
- CoachProfileCard
- BookingStatusBadge
- SlotsIndicator
- PriceDisplay
- PaymentStatusCard

---

# 18. Структура frontend-кода

Примерно так:

```bash
resources/js/
  app.js
  Pages/
    Auth/
    Public/
      Map/Index.vue
      Workout/Show.vue
    Athlete/
      Dashboard.vue
      Bookings/Index.vue
      Bookings/Show.vue
      Profile/Edit.vue
    Coach/
      Dashboard.vue
      Profile/Edit.vue
      Workouts/Index.vue
      Workouts/Create.vue
      Workouts/Edit.vue
      Workouts/Show.vue
    Admin/
  Components/
    UI/
    Map/
    Workout/
    Booking/
    Coach/
  Layouts/
    AppLayout.vue
    PublicLayout.vue
    CoachLayout.vue
    AthleteLayout.vue
    AdminLayout.vue
  Stores/
    auth.js
    map.js
    booking.js
  Composables/
    useMap.js
    useAuth.js
    usePayment.js
  Utils/
  Constants/
```

---

# 19. Структура backend-кода

```bash
app/
  Actions/
    Booking/
    Payment/
    Workout/
    Coach/
  Http/
    Controllers/
      Web/
      Api/
    Requests/
  Models/
  Policies/
  Services/
    Payment/
    Geocoding/
    Notification/
  Jobs/
  Events/
  Listeners/
  Notifications/
```

---

# 20. Контроллеры и бизнес-действия

## Пример разделения для тренировок

### Controller
- CoachWorkoutController
- MapWorkoutController

### Actions
- CreateWorkoutAction
- PublishWorkoutAction
- CancelWorkoutAction
- GetMapWorkoutsAction

### Requests
- StoreWorkoutRequest
- UpdateWorkoutRequest
- PublishWorkoutRequest

Так не будет жирных контроллеров.

---

# 21. Валидация

## 21.1 Регистрация
- email уникален
- phone уникален
- password strength

## 21.2 Профиль тренера
- имя/фамилия обязательны
- sports минимум 1
- city обязателен
- avatar image max size

## 21.3 Тренировка
- future datetime
- positive price
- slots_total integer > 0
- coordinates required
- sport exists

## 21.4 Бронирование
- пользователь athlete
- тренировка опубликована
- время ещё не прошло
- есть свободные места
- пользователь не записан повторно

---

# 22. Безопасность

## 22.1 Обязательно
- CSRF
- rate limit login / booking / payment endpoints
- policies на все coach/athlete/admin действия
- sanitization текста bio и description
- upload validation
- signed webhooks
- audit logs платежей

## 22.2 Нельзя допустить
- тренер редактирует чужую тренировку
- атлет оплачивает отрицательную сумму
- фронт присылает свою цену и backend ей верит
- бронирование без проверки доступности слотов
- публикация тренировки в прошлом

## 22.3 Защита персональных данных

План хранит персональные данные (телефон, email, историю местоположений).

**Минимум для MVP:**
- Privacy Policy страница (обязательна при регистрации, ссылка в футере)
- Согласие на обработку персональных данных при регистрации (checkbox)
- Возможность удаления аккаунта (функция в кабинете пользователя + в админке)
- Логирование доступа к персональным данным в админке (audit trail)

**Не в MVP, но архитектурно подготовиться:**
- GDPR-совместимый data export (JSON выгрузка профиля, броней, платежей)
- Право на забвение (soft delete + anonymization чувствительных полей)
- Согласие на email-рассылки (отдельно от обязательных уведомлений)

---

# 23. Индексы в БД

Иначе карта и поиск быстро станут медленными.

## Для workouts
Индексы:
- coach_id
- city_id
- sport_id
- status
- starts_at
- published_at
- composite: `(city_id, status, starts_at)`
- composite: `(sport_id, status, starts_at)`

## Для bookings
- workout_id
- athlete_id
- status
- payment_status
- composite: `(workout_id, athlete_id)`

## Для payments
- booking_id
- external_payment_id
- status

---

# 24. Производительность

## 24.1 Что убьёт систему
- подгрузка всех тренировок города сразу
- N+1 на тренерах и спортах
- жирные payload’ы
- отсутствие кеша справочников

## 24.2 Что делать
- eager loading
- pagination в списках кабинета
- bbox query для карты
- кешировать sports, cities
- отдельные lightweight resources для карты

---

# 25. Аналитика

Сразу заложить события.

## Атлет
- app_opened
- city_selected
- map_marker_clicked
- workout_card_viewed
- booking_started
- payment_started
- payment_succeeded
- payment_failed

## Тренер
- coach_registered
- coach_profile_completed
- workout_created
- workout_published
- workout_cancelled

Через:
- PostHog
или
- self-hosted later

Не откладывать. Иначе потом не поймёшь воронку.

---

# 26. Уведомления и коммуникации

## Email-шаблоны
Нужны письма:
- подтверждение регистрации
- успешная оплата
- напоминание о тренировке
- отмена тренировки
- новый участник тренеру

## Позже
- push notifications
- WhatsApp / Telegram / SMS
Но не в MVP.

---

# 27. Админка

Без неё быстро начнётся хаос.

## Минимум в админке
- список тренеров
- просмотр профиля тренера
- статус модерации (approve/reject)
- список тренировок
- список броней
- список платежей
- ручной просмотр ошибок
- dashboard с метриками

## Реализация на Filament 3

**Filament** — самый быстрый путь для админки Laravel.

### Установка

```bash
# Установка Filament
composer require filament/filament:"^3.2"

# Создание админ-панели
php artisan filament:install --panels

# Создание первого админа
php artisan make:filament-user

# Генерация ресурсов (примеры)
php artisan make:filament-resource User --generate
php artisan make:filament-resource CoachProfile --generate
php artisan make:filament-resource Workout --generate
php artisan make:filament-resource Booking --generate
php artisan make:filament-resource Payment --generate
```

### Архитектура
- **Основной продукт:** Inertia/Vue (для тренеров и атлетов)
- **Админка:** Filament (отдельный роут `/admin`)
- **Разделение ролей:** `role = admin` → доступ к Filament, остальные роли → Inertia

### Ресурсы Filament для MVP

```php
// app/Filament/Resources/
CoachProfileResource.php  // Модерация тренеров (approve/reject)
WorkoutResource.php        // Просмотр всех тренировок
BookingResource.php        // Просмотр броней
PaymentResource.php        // Просмотр платежей + raw JSON
UserResource.php           // Управление пользователями
```

### Кастомные страницы

```php
// app/Filament/Pages/
Dashboard.php              // Метрики: кол-во тренеров, тренировок, выручка
ModerationQueue.php        // Очередь модерации тренеров
```

### Что настроить в Filament
1. **Navigation** — группировка меню: Users, Coaches, Workouts, Payments
2. **Filters** — фильтры по статусам, датам, городам
3. **Actions** — approve/reject для тренеров, cancel для тренировок
4. **Widgets** — статистика на dashboard
5. **Auth** — middleware проверка `role = admin`

### Преимущества подхода
- Не нужно строить админку с нуля
- Auto-generated CRUD за 5 минут
- Встроенная авторизация, навигация, поиск
- Легко кастомизировать через Actions и Pages
- Отдельный UI от основного продукта

Это нормальная схема. Не нужно тянуть админку в основной фронт.

---

# 28. Модерация

**✅ РЕШЕНИЕ ПРИНЯТО:** Ручная модерация перед первой публикацией

## Тренеры

### Статусы модерации
- `pending` — ожидает проверки (по умолчанию при регистрации)
- `approved` — одобрен, может публиковать тренировки
- `rejected` — отклонён, публикация запрещена

### Правила доступа

**Статус `pending`:**
- ✅ Может редактировать профиль
- ✅ Может создавать черновики тренировок
- ❌ **Не может публиковать** тренировки на карту
- UI показывает badge "На модерации" и сообщение "Ваш профиль на проверке"

**Статус `approved`:**
- ✅ Полный доступ к созданию и публикации тренировок
- ✅ Тренировки сразу появляются на карте

**Статус `rejected`:**
- ✅ Может редактировать профиль и повторно отправить на модерацию
- ❌ Публикация запрещена
- UI показывает причину отклонения (admin может указать при reject)

### Процесс модерации

1. Тренер регистрируется → `coach_profiles.moderation_status = pending`
2. Email админу о новом тренере (опционально)
3. Админ в Filament:
   - Просматривает профиль
   - Проверяет дипломы, справки (если загружены)
   - Нажимает **Approve** или **Reject** (с указанием причины)
4. Email тренеру о решении
5. Если Approve → тренер может публиковать

### Backend реализация

```php
// Policies/WorkoutPolicy.php
public function publish(User $user, Workout $workout)
{
    return $user->id === $workout->coach_id
        && $user->coachProfile->moderation_status === 'approved';
}
```

### Frontend guard
- Кнопка "Опубликовать" disabled если `moderation_status !== 'approved'`
- Tooltip с объяснением

**Почему так:** Иначе карта быстро наполнится мусором и недостоверными тренерами.

---

# 29. Этапы разработки

---

## Этап 1. Базовый каркас проекта

### Задачи
- поднять Laravel
- подключить Inertia + Vue
- настроить auth
- роли
- Tailwind
- базовые layouts
- Docker / dev environment
- CI
- staging

### Результат
Голый проект с авторизацией, ролями и базовой навигацией.

---

## Этап 2. Профили пользователей

### Задачи
- профиль тренера
- профиль атлета
- загрузка фото
- города
- виды спорта
- валидация
- onboarding flow

### Результат
Тренер может полностью заполнить профиль.

---

## Этап 3. Создание тренировок

### Задачи
- форма создания тренировки
- карта выбора точки
- reverse geocoding
- расчёт цены слота
- статусы draft/published
- список тренировок тренера

### Результат
Тренер публикует тренировки.

---

## Этап 4. Карта для атлета

### Задачи
- публичная карта
- фильтрация по городу/спорту/дате
- bottom card тренировки
- lightweight API
- карта и маркеры

### Результат
Атлет может видеть и открывать тренировки.

---

## Этап 5. Бронирование

### Задачи
- создание booking
- резерв слота
- блокировка от oversell
- TTL на pending booking
- кабинет атлета

### Результат
Можно начать процесс записи.

---

## Этап 6. Платежи

### Задачи
- интеграция платёжки
- создание payment session
- webhook
- фиксация paid/failed
- email чек или ссылка на чек
- экран успеха/ошибки

### Результат
Полный цикл оплаты.

---

## Этап 7. Уведомления и scheduler

### Задачи
- email notifications
- reminder jobs
- expire jobs
- completion jobs

### Результат
Система живёт не только в момент клика, но и фоново обслуживает процесс.

---

## Этап 8. PWA

### Задачи
- manifest
- service worker
- installable app
- offline fallback
- icons/splash

### Результат
Приложение ставится на телефон и ощущается как мобильное.

---

## Этап 9. Админка и модерация

### Задачи
- Filament admin
- approve coaches
- просмотр платежей
- просмотр тренировок
- простая поддержка

### Результат
Есть управляемость системы.

---

## Этап 10. Полировка и аналитика

### Задачи
- event tracking
- error monitoring
- UX polishing
- performance fixes
- edge cases

### Результат
Продукт готов к первым реальным пользователям.

---

# 30. Приоритет MVP

Если резать жёстко, MVP должен включать только это:

## Обязательно
- регистрация двух ролей
- профиль тренера
- создание тренировки
- карта с тренировками
- карточка тренировки
- бронирование
- оплата
- кабинет атлета с ближайшей тренировкой
- кабинет тренера со своими тренировками

## Необязательно на MVP
- отзывы
- чат
- подписки
- рефералка
- сложная аналитика тренера
- пуши
- многоуровневые отмены и частичные refunds
- соцлогин
- сложная модерация контента

---

# 31. Подводные камни

## 31.1 Цена слота и округление

**✅ РЕШЕНИЕ ПРИНЯТО**

Если общая стоимость не делится ровно на число мест, **округляем вверх**.

**Пример:** 1000 ₽ / 3 места = 333.33 ₽ → атлет платит **334 ₽**, тренер получает 1002 ₽ (3 × 334)

**Реализация:**

### Database
```php
// migrations/xxxx_create_workouts_table.php
$table->decimal('total_price', 10, 2); // Цена, указанная тренером
$table->decimal('slot_price', 10, 2);  // Рассчитанная цена за место
$table->integer('slots_total');
```

### Backend (при публикации тренировки)
```php
// Actions/Workout/PublishWorkoutAction.php
$slotPrice = ceil($workout->total_price / $workout->slots_total);
$workout->update(['slot_price' => $slotPrice]);
```

### Frontend (форма создания тренировки)
```vue
// Показать preview цены слота
const slotPrice = computed(() => {
  return Math.ceil(form.total_price / form.slots_total)
})
```
Показывать тренеру: "Атлет будет платить: 334 ₽ за место (вы получите 1002 ₽ за 3 места)"

### Валидация
- `total_price > 0`
- `slots_total > 0`
- Допустимы нецелые деления

**Почему так:**
- Простота реализации
- Тренер видит, сколько реально получит
- Разница минимальная (копейки)
- Не нужны сложные проверки

---

## 31.2 Часовые пояса
Если города разные — всё хранить в UTC, отображать в timezone города или пользователя.
Если пока один рынок — всё равно хранить аккуратно через Carbon и UTC.

---

## 31.3 Дублирующие брони
Один атлет не должен купить два места на одну и ту же тренировку случайно, если это не предусмотрено.

---

## 31.4 Удаление тренера
Нельзя просто удалять. Только soft delete и только после обработки связанных данных.

---

## 31.5 Платёж успешен, а фронт не знает
Это нормально. Истина — webhook. Фронт должен уметь опрашивать статус после возврата.

---

# 32. Рекомендуемые пакеты

## Laravel

### Обязательные
```bash
composer require laravel/breeze           # Auth scaffolding
composer require inertiajs/inertia-laravel # Server-side adapter
composer require laravel/horizon           # Queue monitoring
composer require filament/filament:"^3.2"  # Admin panel
composer require tcb13/laravel-tinkoff-acquiring  # Тинькофф payments
```

### Рекомендуемые
```bash
composer require spatie/laravel-medialibrary  # Загрузка фото (avatar, diplomas)
composer require sentry/sentry-laravel        # Error tracking
composer require spatie/laravel-query-builder # API filters для карты
```

### Не нужны на старте
- `spatie/laravel-permission` — роли простые, достаточно поля `users.role`
- `laravel/scout` — полнотекстовый поиск не нужен, есть фильтры
- `laravel/cashier` — подписки не в MVP

## Frontend

### Обязательные
```bash
npm install @inertiajs/vue3      # Inertia client
npm install vue                   # Vue 3
npm install pinia                 # State management
npm install @vueuse/core          # Composition utilities
npm install leaflet               # Карты
npm install vue-leaflet           # Vue wrapper для Leaflet
npm install dayjs                 # Date formatting
```

### Рекомендуемые
```bash
npm install vite-plugin-pwa       # PWA support
npm install @headlessui/vue       # UI components
npm install @heroicons/vue        # Icons
npm install axios                 # HTTP client (уже в Laravel)
```

### Для карты
```bash
npm install leaflet
npm install vue-leaflet
npm install leaflet.markercluster  # Кластеризация маркеров
```

## Dev dependencies
```bash
npm install -D @vitejs/plugin-vue
npm install -D autoprefixer
npm install -D postcss
npm install -D tailwindcss
```

---

# 33. Схема деплоя

## Инфраструктура
- app server
- db
- redis
- queue worker
- scheduler
- storage for uploads
- CDN позже

## Минимальный production stack
- Nginx
- PHP-FPM
- MySQL/Postgres
- Redis
- Supervisor / Horizon
- S3-compatible storage для фото

---

# 34. Тестирование

## Backend
- feature tests:
  - регистрация
  - создание тренировки
  - бронирование
  - oversell protection
  - webhook обработка
- unit tests:
  - расчёт цены
  - state transitions
  - expire logic

## Frontend
- критические e2e:
  - тренер создаёт тренировку
  - атлет находит тренировку
  - атлет оплачивает
  - бронь появляется в кабинете

---

# 35. Что я бы зафиксировал как техническое решение сразу

1. **Монолит Laravel**
2. **Breeze + Inertia + Vue**
3. **Filament для админки**
4. **Leaflet для карты на MVP**
5. **Redis обязателен**
6. **Платежи только через webhook-истину**
7. **Бронирование через резервацию слота и TTL**
8. **Деньги хранить integer minor units**
9. **Статусы оформить как enum**
10. **PWA только как installable shell, не fake-offline app**

---

# 36. Рекомендуемая последовательность разработки по неделям

## Спринт 1
- базовый проект
- auth
- роли
- layouts
- cities/sports справочники

## Спринт 2
- профиль тренера
- профиль атлета
- загрузка фото
- onboarding

## Спринт 3
- CRUD тренировок
- карта выбора точки
- статусы тренировок

## Спринт 4
- публичная карта
- карточка тренировки
- фильтры

## Спринт 5
- booking engine
- slot reservation
- athlete cabinet

## Спринт 6
- payments
- webhook
- success/fail flows

## Спринт 7
- notifications
- jobs
- scheduler
- edge cases

## Спринт 8
- PWA
- админка
- аналитика
- стабилизация

---

# 37. Критерии готовности MVP

**MVP считается готовым к запуску, когда выполнены все критерии:**

## 37.1 Функциональные критерии
- [ ] Тренер может зарегистрироваться, заполнить профиль, создать и опубликовать тренировку
- [ ] Атлет может найти тренировку на карте, записаться, оплатить через платёжную систему
- [ ] Атлет видит свою бронь в личном кабинете после успешной оплаты
- [ ] Платёжный цикл работает end-to-end: создание → оплата → webhook → подтверждение
- [ ] Oversell protection работает (невозможно купить больше мест, чем доступно)
- [ ] Email-уведомления отправляются корректно:
  - Подтверждение регистрации
  - Успешная оплата атлету
  - Уведомление о записи тренеру
  - Напоминание о тренировке за 2 часа
- [ ] Админ может модерировать тренеров (approve/reject)
- [ ] Админ может просматривать платежи и бронирования

## 37.2 Технические критерии
- [ ] Все критические feature tests проходят:
  - Регистрация двух ролей
  - Создание и публикация тренировки
  - Бронирование с резервацией слота
  - Oversell protection (транзакционная безопасность)
  - Webhook обработка платежей
- [ ] PWA manifest и service worker настроены, приложение устанавливается на iOS и Android
- [ ] Staging environment развёрнут и работает идентично production
- [ ] Monitoring и error tracking настроены (Sentry, Bugsnag или аналог)
- [ ] Production deployment pipeline настроен и протестирован
- [ ] Database backups настроены (автоматические ежедневные бэкапы)
- [ ] Queue worker и scheduler работают стабильно

## 37.3 Качественные критерии
- [ ] UI responsive на мобильных устройствах (350px - 428px width)
- [ ] Время загрузки карты < 3 секунд (измерено на staging)
- [ ] Нет критических security issues:
  - CSRF protection работает
  - Rate limiting настроен на auth и payment endpoints
  - Policy checks работают (тренер не может редактировать чужие тренировки)
  - Webhook signature verification реализована
- [ ] Основные edge cases обработаны:
  - Expired pending bookings очищаются
  - Отмена тренировки с оплаченными бронями заблокирована или обрабатывается корректно
  - Платёж успешен, но фронт не получил ответ (polling статуса работает)

## 37.4 Бизнес-правила
- ✅ Политика отмены: возврат если >24 часа до тренировки
- ✅ Округление цены: округлять вверх (атлет платит чуть больше)
- ✅ Модерация тренеров: ручная проверка перед первой публикацией
- ✅ Платёжная система: Тинькофф Acquiring
- ✅ Время напоминания: за 2 часа до тренировки

**После прохождения всех критериев MVP готов к soft launch с ограниченной аудиторией.**

---

# 38. Итоговая минимальная доменная схема

## Тренер:
`users -> coach_profiles -> coach_sports -> workouts`

## Атлет:
`users -> athlete_profiles -> bookings -> payments`

## Публичная витрина:
`cities + sports + workouts on map`

---

# 39. Самое важное, без чего проект сломается

- неправильная модель бронирования
- отсутствие транзакций при резерве слота
- доверие фронту в вопросе цены
- отсутствие webhook-first логики оплаты
- отсутствие статусов и state transitions
- отсутствие админки и модерации тренеров
- попытка сделать слишком много до запуска

**Для начала разработки:**
1. Разрешить открытые вопросы в §8.4 и §31.1
2. Создать исполняемые dev-планы в `docs/plans/dev/` в формате Ralphex
3. Следовать архитектурным принципам из этого документа
