# Планы разработки split.fitness

Эта директория содержит архитектурную спецификацию и исполняемые dev-планы для проекта split.fitness.

## Структура

```
docs/plans/
├── README.md                    # Этот файл
├── plan-as-is.md               # Архитектурная спецификация (reference)
├── dev/                         # Исполняемые dev-планы (Ralphex format)
│   ├── sprint-01-foundation.md
│   ├── sprint-02-profiles.md
│   ├── sprint-03-workouts.md
│   ├── sprint-04-map.md
│   ├── sprint-05-booking.md
│   ├── sprint-06-payments.md
│   ├── sprint-07-notifications.md
│   └── sprint-08-pwa-admin.md
└── product/                     # Product планы (будут созданы при необходимости)
```

## Типы планов

### Architecture Spec (plan-as-is.md)

**Цель:** Comprehensive architecture reference документ.

**Содержит:**
- Продуктовое видение и роли
- Техническая архитектура (Laravel 12 + Inertia + Vue 3)
- Схема БД (workouts, bookings, payments, profiles)
- Бизнес-логика (бронирование, платежи, модерация)
- Принятые решения (Тинькофф, Filament, округление цен)
- Рекомендуемые пакеты и структура кода

**Статус:** ✅ READY — все критические вопросы разрешены.

**Использование:**
- Reference при разработке
- Источник для создания dev-планов
- Onboarding для новых разработчиков

---

### Dev Plans (sprint-XX-*.md)

**Цель:** Executable task breakdowns для autonomous agents (rubx-laravel) или команды разработчиков.

**Формат:** Ralphex (см. `toolkits/rubx-agentic-tools/conduct/backend/ralphex-format.md`)

**Структура каждого плана:**
- `## Overview` — что делаем и зачем
- `## Context` — текущее состояние и целевое
- `## Validation Commands` — команды для проверки после выполнения
- `### Task N: <title>` — конкретные задачи с чекбоксами
- `## Verification Notes` — ручная проверка функциональности
- `## Risks` — известные риски и решения

**Статус:** Все планы READY для исполнения.

---

## Последовательность спринтов

| Sprint | Название | Зависимость | Описание |
|--------|----------|-------------|----------|
| 1 | Foundation | — | Laravel + Inertia + Vue + роли + справочники |
| 2 | Profiles | Sprint 1 | Профили тренера/атлета, onboarding, загрузка фото |
| 3 | Workouts | Sprint 2 | CRUD тренировок, карта выбора места, публикация |
| 4 | Map | Sprint 3 | Публичная карта, фильтры, bottom card |
| 5 | Booking | Sprint 4 | Бронирование с oversell protection, кабинет атлета |
| 6 | Payments | Sprint 5 | Тинькофф интеграция, webhook, success/fail |
| 7 | Notifications | Sprint 6 | Email уведомления, reminders, отмена атлетом |
| 8 | PWA + Admin | Sprint 7 | PWA, Filament админка, аналитика |

**Общая длительность:** ~8 недель (1 спринт = 1 неделя при команде 2-3 разработчика)

---

## Как использовать dev-планы

### Для автономных агентов (rubx-laravel)

```bash
# Выполнить спринт через агента
/rubx-coder docs/plans/dev/sprint-01-foundation.md

# Агент:
# - Прочитает план
# - Выполнит все задачи последовательно
# - Запустит validation commands
# - Отметит задачи completed
```

### Для команды разработчиков

1. **Product Owner / Tech Lead:**
   - Прочитать architecture spec (plan-as-is.md)
   - Выбрать спринт для реализации
   - Назначить dev-план команде

2. **Разработчик:**
   - Открыть соответствующий sprint-XX-*.md
   - Прочитать Overview и Context
   - Выполнить задачи (Task 1-10) последовательно
   - Отмечать чекбоксы по мере выполнения
   - Запустить Validation Commands после завершения
   - Провести ручную проверку из Verification Notes

3. **QA / Reviewer:**
   - Проверить Definition of Done
   - Запустить тесты
   - Провести ручное тестирование по Verification Notes

---

## Принятые решения

Все критические бизнес-правила разрешены:

- ✅ **Отмена атлетом:** возврат если >24 часа до тренировки
- ✅ **Округление цены:** округлять вверх (атлет платит чуть больше)
- ✅ **Модерация тренеров:** ручная проверка перед первой публикацией
- ✅ **Платёжная система:** Тинькофф Acquiring
- ✅ **Время напоминания:** за 2 часа до тренировки
- ✅ **Админка:** Filament 3
- ✅ **Карты:** Leaflet + OpenStreetMap

---

## Validation после каждого спринта

Каждый dev-план содержит секцию **Validation Commands** — набор команд для автоматической проверки:

```bash
# Пример из Sprint 1
php artisan migrate:status
php artisan test --filter=RoleMiddlewareTest
php artisan route:list | grep -E "athlete|coach|admin"
npm run build
```

После выполнения всех команд успешно — спринт считается технически завершённым.

Затем провести ручную проверку из **Verification Notes**.

---

## MVP Definition of Done

После завершения **Sprint 8** проверить итоговые критерии из architecture spec §37:

### Функциональные критерии
- [ ] Тренер может создать и опубликовать тренировку
- [ ] Атлет может найти, записаться, оплатить
- [ ] Платёжный цикл работает end-to-end
- [ ] Oversell protection работает
- [ ] Email уведомления отправляются
- [ ] Админ может модерировать тренеров

### Технические критерии
- [ ] Feature tests проходят
- [ ] PWA устанавливается
- [ ] Staging развёрнут
- [ ] Monitoring настроен
- [ ] Production pipeline готов

### Качественные критерии
- [ ] UI responsive (350px - 1920px)
- [ ] Карта загружается < 3 секунд
- [ ] Нет критических security issues

**После прохождения всех критериев → MVP готов к soft launch!**

---

## Следующие шаги

1. **Начать с Sprint 1:** `docs/plans/dev/sprint-01-foundation.md`
2. **Выполнить последовательно** Sprint 1-8
3. **После Sprint 8:** Production deployment
4. **Soft launch** с ограниченной аудиторией
5. **Итерации:** На основе feedback пользователей

---

## Обновление планов

Если требования изменились:

1. **Architecture spec (plan-as-is.md):**
   - Обновить соответствующие секции
   - Обновить статус и версию в frontmatter
   - Добавить в "Открытые вопросы" если нужны решения

2. **Dev планы:**
   - Если спринт ещё не начат → обновить plan
   - Если спринт в процессе → создать отдельный plan для изменений
   - Если спринт завершён → создать новый plan для рефакторинга

---

## Контакты и поддержка

- **Архитектурные вопросы:** См. architecture spec §39
- **Технические вопросы:** См. dev-план конкретного спринта
- **Бизнес-правила:** См. architecture spec §8, §31

---

**Удачной разработки! 🚀**
