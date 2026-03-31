---
plan_type: dev
sprint: 8
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 8-10
dependencies: sprint-07-notifications.md
---

# Sprint 8: PWA, Админка, Аналитика

## Overview

Настроить PWA (installable app, service worker, offline fallback), развернуть Filament админку для модерации тренеров и управления системой, добавить базовую аналитику событий.

**Цель спринта:** Приложение устанавливается на телефон, админ может модерировать тренеров через Filament, события логируются для аналитики.

**Источник требований:** Architecture spec §16 (PWA), §27 (Админка — Filament), §25 (Аналитика)

## Context

**Текущее состояние:**
- Все основные функции работают
- Нет PWA
- Нет админки

**Целевое состояние:**
- Web app manifest
- Service worker с кешированием статики
- Install prompt
- Filament 3 админка с ресурсами: CoachProfiles, Workouts, Bookings, Payments, Users
- Dashboard с метриками
- Event tracking через PostHog или собственный logger

**Технические решения:**
- vite-plugin-pwa для PWA
- Filament 3 с middleware role:admin
- PostHog для аналитики (или Laravel Events → database)

## Error Handling & Logging Strategy

**Error Boundaries:**
- Service worker registration failures: catch и показать fallback message "Install later"
- Filament auth failures: abort(403) для non-admin, логировать warning (possible intrusion attempt)
- Analytics tracking failures: silent fail, не блокировать user flow
- Admin action failures (approve/reject): transaction rollback, показать error, логировать

**Logging:**
- Structured context: `user_id`, `admin_action`, `target_entity_id`, `event_name`, `sw_version`
- Levels:
  - `alert`: non-admin пытается зайти в /admin (possible attack)
  - `error`: moderation action failure, analytics event storage failure
  - `warning`: service worker registration failed, PostHog blocked by adblocker
  - `info`: admin logged in, coach approved/rejected, PWA installed, analytics event tracked
- **Никогда не логировать**: admin session tokens, full user profiles in analytics
- **Всегда логировать**: moderation decisions (admin_id, action, target_id, reason) для audit

**Применение в спринте:**
- Task 5 (Filament auth): логировать alert при попытке доступа non-admin
- Task 7 (moderation): логировать info/error для каждой approve/reject action
- Task 9 (analytics): silent fail при tracking failures, не крашить app
- Task 10 (tests): проверять audit logs для moderation actions

## Validation Commands

```bash
# PWA
npm run build
# Открыть в Chrome → DevTools → Application → Manifest
# Проверить Install button

# Filament
php artisan filament:install --panels
php artisan make:filament-user
# Открыть /admin → логин → dashboard

# Tests
php artisan test --filter=FilamentTest
php artisan test --filter=AnalyticsTest
```

---

### Task 1: Установить vite-plugin-pwa

**Files:**
- Modify: `package.json`
- Modify: `vite.config.js`
- Create: `public/manifest.json`

**Steps:**
- [ ] Установить: `npm install -D vite-plugin-pwa`
- [ ] Настроить vite.config.js:
  - Добавить VitePWA plugin
  - Указать manifest, workbox config
- [ ] Создать manifest.json:
  - name: "split.fitness"
  - short_name: "split"
  - icons: 192x192, 512x512
  - theme_color, background_color
  - display: standalone
  - start_url: /map
- [ ] Сгенерировать иконки (используя design или placeholder)
- [ ] Mark completed

---

### Task 2: Настроить service worker

**Files:**
- Modify: `vite.config.js`
- Create: `resources/js/sw.js` (если кастомная логика)

**Steps:**
- [ ] Настроить Workbox:
  - Кешировать js, css, иконки
  - NetworkFirst для API запросов
  - CacheFirst для статики
- [ ] Не кешировать агрессивно:
  - /api/payments/*
  - /api/bookings (sensitive)
- [ ] Создать offline fallback page (простой экран "Нет соединения")
- [ ] Mark completed

---

### Task 3: Добавить install prompt

**Files:**
- Create: `resources/js/composables/usePwaInstall.js`
- Modify: `resources/js/Layouts/PublicLayout.vue`

**Steps:**
- [ ] Создать usePwaInstall:
  - Слушать beforeinstallprompt event
  - Показывать кнопку "Установить приложение"
  - При клике: prompt.prompt()
- [ ] В PublicLayout добавить banner или button для установки
- [ ] Для iOS: показать инструкцию "Добавить на домашний экран" (Safari)
- [ ] Mark completed

---

### Task 4: Установить Filament 3

**Files:**
- Modify: `composer.json`
- Create: `config/filament.php`

**Steps:**
- [ ] Установить: `composer require filament/filament:"^3.2"`
- [ ] Запустить: `php artisan filament:install --panels`
- [ ] Создать первого админа: `php artisan make:filament-user`
- [ ] Проверить /admin → форма логина
- [ ] Mark completed

---

### Task 5: Настроить Filament auth для role:admin

**Files:**
- Modify: `app/Filament/Pages/Auth/Login.php` (custom login)
- Create: `app/Providers/FilamentServiceProvider.php`

**Steps:**
- [ ] Настроить middleware: только role=admin может зайти в /admin
- [ ] Кастомизировать логин (опционально):
  - Проверять user->role === 'admin'
  - Иначе показывать ошибку "Доступ только для администраторов"
- [ ] Mark completed

---

### Task 6: Создать Filament ресурсы

**Files:**
- Create: `app/Filament/Resources/CoachProfileResource.php`
- Create: `app/Filament/Resources/WorkoutResource.php`
- Create: `app/Filament/Resources/BookingResource.php`
- Create: `app/Filament/Resources/PaymentResource.php`
- Create: `app/Filament/Resources/UserResource.php`

**Steps:**
- [ ] Создать ресурсы:
  - `php artisan make:filament-resource CoachProfile --generate`
  - `php artisan make:filament-resource Workout --generate`
  - `php artisan make:filament-resource Booking --generate`
  - `php artisan make:filament-resource Payment --generate`
  - `php artisan make:filament-resource User --generate`
- [ ] Настроить Table columns (показывать нужные поля)
- [ ] Настроить Filters (по статусам, датам)
- [ ] Настроить Actions для модерации (approve/reject CoachProfile)
- [ ] Mark completed

---

### Task 7: Создать кастомную страницу модерации

**Files:**
- Create: `app/Filament/Pages/ModerationQueue.php`

**Steps:**
- [ ] Создать кастомную Filament Page:
  - Показывать CoachProfiles со статусом pending
  - Для каждого: фото, имя, bio, дипломы
  - Actions: Approve, Reject (с полем причины)
- [ ] При Approve:
  - moderation_status = 'approved'
  - Email тренеру "Профиль одобрен"
- [ ] При Reject:
  - moderation_status = 'rejected'
  - Сохранить причину
  - Email тренеру
- [ ] Mark completed

---

### Task 8: Создать Filament Dashboard

**Files:**
- Create: `app/Filament/Widgets/StatsOverview.php`
- Create: `app/Filament/Widgets/RecentBookings.php`

**Steps:**
- [ ] Создать StatsOverview widget:
  - Карточки: Всего тренеров, Тренировок, Броней, Выручка
- [ ] Создать RecentBookings widget:
  - Таблица последних 10 броней
- [ ] Зарегистрировать widgets в Dashboard
- [ ] Mark completed

---

### Task 9: Настроить аналитику событий

**Files:**
- Create: `app/Services/Analytics/AnalyticsService.php`
- Create: `database/migrations/xxxx_create_analytics_events_table.php` (если не PostHog)
- Modify: `config/services.php`

**Steps:**
- [ ] Выбрать подход:
  - Вариант 1: PostHog (external SaaS)
  - Вариант 2: Собственная таблица analytics_events
- [ ] Создать AnalyticsService:
  - Method track(user_id, event_name, properties)
- [ ] Логировать события:
  - app_opened (атлет открыл /map)
  - workout_card_viewed (клик на маркер)
  - booking_started (клик "Записаться")
  - payment_started (редирект на Тинькофф)
  - payment_succeeded, payment_failed
  - coach_registered, coach_profile_completed
  - workout_created, workout_published
- [ ] Интегрировать в контроллеры и actions
- [ ] Mark completed

---

### Task 10: Финальная полировка и тесты

**Files:**
- Create: `tests/Feature/Admin/FilamentTest.php`
- Create: `tests/Feature/PwaTest.php`

**Steps:**
- [ ] Создать FilamentTest:
  - Тест доступа админа к /admin
  - Тест что non-admin не может зайти
  - Тест approve/reject CoachProfile
- [ ] Создать PwaTest:
  - Тест наличия manifest.json
  - Тест service worker registration
- [ ] Запустить все тесты проекта: `php artisan test`
- [ ] Проверить responsive на мобильных (350px - 428px)
- [ ] Проверить Lighthouse (Performance, PWA score)
- [ ] Mark completed

---

## Verification Notes

1. **PWA:**
   - Открыть /map в Chrome → увидеть install prompt
   - Установить на Android/Desktop → app открывается fullscreen
   - Отключить сеть → видим offline fallback

2. **Filament:**
   - Зайти как admin в /admin
   - Модерировать тренера (approve/reject)
   - Посмотреть dashboard с метриками
   - Просмотреть таблицы Workouts, Bookings, Payments

3. **Аналитика:**
   - Открыть /map → событие app_opened залогировано
   - Записаться на тренировку → события booking_started, payment_started, payment_succeeded

## Risks

1. **Service worker кеширует старый код** — после деплоя пользователи видят старую версию. Решение: versioning в SW, skipWaiting().
2. **Install prompt не показывается** — нужны HTTPS, manifest, service worker. Решение: проверить все требования PWA.
3. **Filament медленный на больших таблицах** — если много записей. Решение: pagination, filters, indexes.
4. **PostHog блокируется adblockers** — события не логируются. Решение: использовать server-side tracking или собственную таблицу.

---

**Definition of Done:**
- PWA installable на мобильных и desktop
- Service worker кеширует статику
- Filament админка работает с модерацией
- Dashboard показывает метрики
- Аналитика логирует ключевые события
- All tests pass
- Lighthouse PWA score >90

---

## Post-Sprint: Production Deployment

После Sprint 8 готов production deployment:
- Настроить CI/CD pipeline
- Развернуть на production сервере
- Настроить Horizon для queue
- Настроить cron для scheduler
- Настроить мониторинг (Sentry)
- Настроить backups БД
- SSL сертификат
- CDN для статики (опционально)

**MVP готов к soft launch!**
