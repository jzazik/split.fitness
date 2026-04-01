---
plan_type: dev
sprint: 4
status: ready
created: 2026-03-31
source: docs/plans/plan-as-is.md §29 Этап 4
dependencies: sprint-03-workouts.md
---

# Sprint 4: Карта для атлета

## Overview

Реализовать публичную карту с тренировками, фильтрацию по городу/спорту/дате, карточку тренировки в bottom sheet, lightweight API для карты.

**Цель спринта:** Атлет может открыть карту, увидеть маркеры тренировок, отфильтровать по параметрам, кликнуть на маркер и увидеть детали тренировки.

**Источник требований:** Architecture spec §11.4, §12

## Context

**Текущее состояние:**
- Тренировки существуют в БД со статусом published
- Модель Workout готова с relationships

**Целевое состояние:**
- Публичная страница `/map` с картой
- API endpoint `GET /api/workouts/map` с фильтрами
- Lightweight payload (только нужные поля)
- Кластеризация маркеров (leaflet.markercluster)
- Bottom card с деталями тренировки
- Фильтры: город, спорт, дата
- Только опубликованные тренировки (`status=published`, `starts_at>now()`)

**Технические решения:**
- API Resource для map payload
- Query builder с bbox фильтрацией
- Кластеризация через leaflet.markercluster
- Bottom sheet через Headless UI Dialog

## Error Handling & Logging Strategy

**Error Boundaries:**
- API failures: graceful degradation, показать empty state с retry button
- Bbox query timeout: limit 200 results, show warning "Too many workouts, zoom in"
- Frontend map errors: catch Leaflet exceptions, не крашить весь UI

**Logging:**
- Structured context: `city_id`, `sport_id`, `bbox`, `result_count`, `request_duration`
- Levels:
  - `warning`: result count >200 (performance issue), query duration >1s
  - `info`: map loaded, filters applied, workout card opened
  - `debug`: bbox coordinates для debugging viewport issues
- **Никогда не логировать**: user location coordinates без consent
- **Логировать**: aggregated metrics (loads per hour, popular cities/sports)

**Применение в спринте:**
- Task 1 (MapController): логировать warning если result count >200
- Task 6 (bbox optimization): логировать info с bbox + result_count для monitoring
- Task 10 (error handling): graceful degradation, не показывать stack traces

## Validation Commands

```bash
# Тесты
php artisan test --filter=MapApiTest

# API проверка
curl "http://localhost/api/workouts/map?city_id=1&sport_id=2"

# Ручная проверка
# - Открыть /map → увидеть маркеры
# - Применить фильтр по городу → маркеры обновились
# - Кликнуть на маркер → bottom card с деталями
# - Кластеризация работает (много маркеров → cluster badge)
```

---

### Task 1: Создать API endpoint для карты

**Files:**
- Create: `app/Http/Controllers/Api/MapController.php`
- Create: `app/Http/Resources/WorkoutMapResource.php`
- Modify: `routes/api.php`
- Modify: `bootstrap/app.php`

**Steps:**
- [x] Создать MapController:
  - Method: index(Request $request)
  - Query: workouts where status=published AND starts_at>now()
  - Фильтры: city_id, sport_id, date_from, date_to
  - Опционально bbox: northEastLat/Lng, southWestLat/Lng (для viewport)
  - Eager load: sport, coach.user, city
  - Return: WorkoutMapResource::collection
- [x] Создать WorkoutMapResource (lightweight):
  - id, lat, lng, sport_name, starts_at
  - slot_price, slots_total, slots_booked
  - coach_name, coach_avatar_url, coach_rating
- [x] В `bootstrap/app.php` подключить API routes (`api: __DIR__.'/../routes/api.php'`)
- [x] Добавить роут: `GET /api/workouts/map`
- [x] Mark completed

---

### Task 2: Установить leaflet.markercluster

**Files:**
- Modify: `package.json`
- Create: `resources/js/composables/useMarkerCluster.js`

**Steps:**
- [x] Установить: `npm install leaflet.markercluster`
- [x] Импортировать CSS в app.css
- [x] Создать composable useMarkerCluster:
  - Функция createClusterGroup() → возвращает L.markerClusterGroup()
  - Кастомная иконка кластера (показывать количество)
- [x] Mark completed

---

### Task 3: Создать публичную страницу карты

**Files:**
- Create: `resources/js/Pages/Public/Map/Index.vue`
- Create: `app/Http/Controllers/PublicMapController.php`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать PublicMapController:
  - index() — вернуть Inertia::render('Public/Map/Index', [cities, sports])
- [ ] Создать Index.vue:
  - Fullscreen карта Leaflet
  - Загрузить workouts через axios GET /api/workouts/map
  - Показать маркеры с кластеризацией
  - При клике на маркер → emit workoutId
- [ ] Добавить роут: `GET /map` (публичный, без auth)
- [ ] Mark completed

---

### Task 4: Добавить фильтры

**Files:**
- Create: `resources/js/Components/Map/MapFilters.vue`
- Modify: `resources/js/Pages/Public/Map/Index.vue`

**Steps:**
- [ ] Создать MapFilters.vue:
  - Select города
  - Chips для видов спорта (multiple)
  - DatePicker для даты ("Сегодня", "Завтра", "На этой неделе")
  - Кнопка "Сбросить фильтры"
- [ ] В Index.vue:
  - При изменении фильтров → запрос к API с новыми параметрами
  - Обновить маркеры на карте
- [ ] Mark completed

---

### Task 5: Создать bottom card тренировки

**Files:**
- Create: `resources/js/Components/Map/WorkoutBottomCard.vue`
- Modify: `resources/js/Pages/Public/Map/Index.vue`

**Steps:**
- [ ] Создать WorkoutBottomCard.vue:
  - Слайд-ап панель (Headless UI Transition)
  - Показывать: фото тренера, имя, рейтинг
  - Место, спорт (badge), время начала, длительность
  - Цена слота, "осталось X мест"
  - Кнопка "Записаться" → если не auth → редирект на /login
  - Кнопка закрытия (X)
- [ ] В Index.vue:
  - При клике на маркер → открыть bottom card с данными тренировки
  - Передать выбранную тренировку в WorkoutBottomCard
- [ ] Mark completed

---

### Task 6: Добавить bbox фильтрацию для оптимизации

**Files:**
- Modify: `app/Http/Controllers/Api/MapController.php`
- Modify: `resources/js/Pages/Public/Map/Index.vue`

**Steps:**
- [ ] В MapController:
  - Принимать параметры bbox (ne_lat, ne_lng, sw_lat, sw_lng)
  - Фильтровать: WHERE lat BETWEEN sw_lat AND ne_lat AND lng BETWEEN sw_lng AND ne_lng
- [ ] Во фронте Index.vue:
  - При moveend карты → получить viewport bounds
  - Отправить bbox в API запросе
  - Обновить маркеры
- [ ] Limit результатов (max 200 workouts per request)
- [ ] Mark completed

---

### Task 7: Добавить выбор города на главной

**Files:**
- Create: `resources/js/Pages/Welcome.vue`
- Modify: `routes/web.php`

**Steps:**
- [ ] Создать Welcome.vue (landing):
  - Заголовок "Найди тренировку рядом с тобой"
  - Select города или auto-detect location
  - Кнопка "Найти тренировки" → редирект на /map?city_id=X
- [ ] Обновить роут `/` → Welcome.vue
- [ ] Mark completed

---

### Task 8: Оптимизировать query с индексами

**Files:**
- Modify: `database/migrations/xxxx_add_indexes_to_workouts_table.php` (if not done)
- Create: `database/migrations/xxxx_add_spatial_index_to_workouts.php`

**Steps:**
- [ ] Добавить spatial index на (lat, lng) если MySQL 8+
- [ ] Или composite index (['status', 'starts_at', 'city_id'])
- [ ] Запустить миграцию
- [ ] Mark completed

---

### Task 9: Написать feature tests для Map API

**Files:**
- Create: `tests/Feature/Api/MapApiTest.php`

**Steps:**
- [ ] Создать MapApiTest:
  - Тест получения workouts без фильтров
  - Тест фильтрации по city_id
  - Тест фильтрации по sport_id
  - Тест bbox фильтрации
  - Тест что draft и cancelled не возвращаются
  - Тест что прошедшие тренировки не возвращаются
- [ ] Запустить тесты
- [ ] Mark completed

---

### Task 10: Добавить loading states и error handling

**Files:**
- Modify: `resources/js/Pages/Public/Map/Index.vue`
- Create: `resources/js/Components/UI/LoadingSpinner.vue`

**Steps:**
- [ ] Создать LoadingSpinner.vue
- [ ] В Index.vue:
  - Показывать spinner при загрузке workouts
  - Обработать ошибку API (показать toast)
  - Empty state: "Нет тренировок по выбранным фильтрам"
- [ ] Mark completed

---

## Verification Notes

1. Открыть /map → увидеть карту с маркерами
2. Применить фильтр город → маркеры обновились
3. Кликнуть на маркер → bottom card открылся с деталями
4. Проверить кластеризацию (создать несколько тренировок близко)
5. Проверить bbox: сдвинуть карту → новые маркеры подгрузились
6. Проверить mobile responsive (bottom card должен занимать низ экрана)

## Risks

1. **Слишком много маркеров** — без bbox может быть >1000 точек. Решение: limit 200 + bbox обязателен.
2. **Leaflet SSR** — требует window. Решение: dynamic import и рендер карты только после монтирования на клиенте.
3. **Кластеризация не работает** — забыть подключить CSS. Решение: проверить import стилей.

---

**Definition of Done:**
- Карта работает на /map
- Фильтры применяются корректно
- Bottom card открывается при клике
- API оптимизирован (bbox, limit, indexes)
- Tests проходят
