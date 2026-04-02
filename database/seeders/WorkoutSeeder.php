<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WorkoutSeeder extends Seeder
{
    /**
     * Realistic workout locations per city: parks, stadiums, gyms, outdoor spots.
     */
    private const LOCATIONS = [
        'moscow' => [
            ['name' => 'Парк Горького', 'address' => 'ул. Крымский Вал, 9', 'lat' => 55.7312, 'lng' => 37.6019],
            ['name' => 'Лужники', 'address' => 'ул. Лужники, 24с1', 'lat' => 55.7155, 'lng' => 37.5537],
            ['name' => 'ВДНХ', 'address' => 'пр-т Мира, 119', 'lat' => 55.8267, 'lng' => 37.6375],
            ['name' => 'Парк Сокольники', 'address' => 'ул. Сокольнический Вал, 1', 'lat' => 55.7920, 'lng' => 37.6741],
            ['name' => 'Нескучный сад', 'address' => 'Ленинский пр-т, 30', 'lat' => 55.7218, 'lng' => 37.5921],
            ['name' => 'Фили парк', 'address' => 'Большая Филёвская ул., 22', 'lat' => 55.7451, 'lng' => 37.4921],
            ['name' => 'Измайловский парк', 'address' => 'аллея Большого Круга, 7', 'lat' => 55.7688, 'lng' => 37.7518],
            ['name' => 'Воробьёвы горы', 'address' => 'ул. Косыгина, 28', 'lat' => 55.7105, 'lng' => 37.5443],
            ['name' => 'Битцевский лесопарк', 'address' => 'Новоясеневский тупик, 1', 'lat' => 55.5990, 'lng' => 37.5563],
            ['name' => 'Царицыно парк', 'address' => 'ул. Дольская, 1', 'lat' => 55.6148, 'lng' => 37.6866],
            ['name' => 'Тушинский стадион', 'address' => 'Волоколамское ш., 69', 'lat' => 55.8268, 'lng' => 37.4361],
            ['name' => 'Спортхаб Олимпийский', 'address' => 'Олимпийский пр-т, 16с1', 'lat' => 55.7785, 'lng' => 37.6356],
        ],
        'saint-petersburg' => [
            ['name' => 'Елагин остров', 'address' => 'Елагин остров, 4', 'lat' => 59.9790, 'lng' => 30.2590],
            ['name' => 'Крестовский остров', 'address' => 'Южная дорога, 25', 'lat' => 59.9710, 'lng' => 30.2390],
            ['name' => 'Таврический сад', 'address' => 'ул. Потёмкинская, 2', 'lat' => 59.9460, 'lng' => 30.3750],
            ['name' => 'Парк 300-летия', 'address' => 'Приморский пр-т, 74', 'lat' => 59.9840, 'lng' => 30.2020],
            ['name' => 'Парк Победы', 'address' => 'Московский пр-т, 188', 'lat' => 59.8680, 'lng' => 30.3270],
            ['name' => 'Удельный парк', 'address' => 'пр-т Энгельса, 28', 'lat' => 60.0108, 'lng' => 30.3170],
            ['name' => 'Приморский парк', 'address' => 'Приморский пр-т, 50', 'lat' => 59.9780, 'lng' => 30.2280],
            ['name' => 'Стадион Петровский', 'address' => 'Петровский остров, 2', 'lat' => 59.9530, 'lng' => 30.2720],
        ],
        'ekaterinburg' => [
            ['name' => 'ЦПКиО Маяковского', 'address' => 'ул. Мичурина, 230', 'lat' => 56.8265, 'lng' => 60.5972],
            ['name' => 'Шарташский лесопарк', 'address' => 'ул. Отдыха, 105', 'lat' => 56.8508, 'lng' => 60.6705],
            ['name' => 'Набережная Исети', 'address' => 'ул. Горького, 4А', 'lat' => 56.8381, 'lng' => 60.6035],
            ['name' => 'Дендропарк', 'address' => 'ул. 8 Марта, 37А', 'lat' => 56.8200, 'lng' => 60.6080],
            ['name' => 'Стадион Динамо', 'address' => 'ул. Ещё, 8', 'lat' => 56.8430, 'lng' => 60.5880],
            ['name' => 'Парк Зелёная роща', 'address' => 'ул. Куйбышева, 63', 'lat' => 56.8340, 'lng' => 60.6150],
        ],
        'kazan' => [
            ['name' => 'Парк Горького', 'address' => 'ул. Николая Ершова, 57', 'lat' => 55.7914, 'lng' => 49.1447],
            ['name' => 'Казанка набережная', 'address' => 'ул. Федосеевская, 1', 'lat' => 55.7930, 'lng' => 49.1080],
            ['name' => 'Стадион Казань-Арена', 'address' => 'пр-т Ямашева, 115А', 'lat' => 55.8210, 'lng' => 49.1610],
            ['name' => 'Парк Урицкого', 'address' => 'ул. Агрономическая, 1', 'lat' => 55.7750, 'lng' => 49.1310],
            ['name' => 'Озеро Кабан', 'address' => 'ул. Марджани, 5', 'lat' => 55.7840, 'lng' => 49.1220],
            ['name' => 'Парк Тысячелетия', 'address' => 'ул. Хади Такташа, 2', 'lat' => 55.7890, 'lng' => 49.1350],
        ],
        'novosibirsk' => [
            ['name' => 'Михайловская набережная', 'address' => 'ул. Большевистская, 12', 'lat' => 55.0260, 'lng' => 82.9358],
            ['name' => 'Центральный парк', 'address' => 'ул. Мичурина, 8', 'lat' => 55.0405, 'lng' => 82.9206],
            ['name' => 'Парк Берёзовая роща', 'address' => 'ул. Ельцовская, 1', 'lat' => 55.0445, 'lng' => 82.8970],
            ['name' => 'Бугринская роща', 'address' => 'ул. Бугринская роща, 1', 'lat' => 55.0050, 'lng' => 82.8870],
            ['name' => 'Стадион Спартак', 'address' => 'ул. Ленина, 26', 'lat' => 55.0310, 'lng' => 82.9210],
            ['name' => 'Заельцовский парк', 'address' => 'ул. Дачная, 2', 'lat' => 55.0650, 'lng' => 82.9250],
        ],
    ];

    private const WORKOUT_TEMPLATES = [
        'running' => [
            ['title' => 'Утренняя пробежка', 'desc' => 'Лёгкий бег для начинающих с разминкой и заминкой. Темп подстраиваем под группу.'],
            ['title' => 'Интервальная тренировка', 'desc' => 'Чередование ускорений и лёгкого бега. Подходит для среднего уровня.'],
            ['title' => 'Длинная дистанция', 'desc' => 'Бег 10–15 км в спокойном темпе. Отличная подготовка к полумарафону.'],
            ['title' => 'Трейл-раннинг', 'desc' => 'Бег по пересечённой местности с набором высоты. Берите кроссовки с хорошим протектором.'],
        ],
        'functional' => [
            ['title' => 'Full Body Functional', 'desc' => 'Комплексная тренировка на все группы мышц с собственным весом и резинками.'],
            ['title' => 'HIIT на свежем воздухе', 'desc' => 'Высокоинтенсивная интервальная тренировка. 45 минут — полное выгорание!'],
            ['title' => 'Круговая тренировка', 'desc' => 'Станции с упражнениями на силу, выносливость и координацию.'],
        ],
        'yoga' => [
            ['title' => 'Хатха-йога утренняя', 'desc' => 'Мягкая практика для пробуждения тела. Все уровни подготовки.'],
            ['title' => 'Виньяса-флоу', 'desc' => 'Динамичная практика с синхронизацией дыхания и движения.'],
            ['title' => 'Йога в парке', 'desc' => 'Практика на свежем воздухе. Коврики предоставляются.'],
            ['title' => 'Йога для спины', 'desc' => 'Терапевтическая практика для снятия напряжения в спине и шее.'],
        ],
        'cycling' => [
            ['title' => 'Групповой заезд', 'desc' => 'Катаемся группой 30–40 км. Средний темп, подходит для любого уровня.'],
            ['title' => 'Скоростная тренировка', 'desc' => 'Работа над техникой педалирования и ускорениями. Нужен шоссейный велосипед.'],
        ],
        'boxing' => [
            ['title' => 'Бокс для начинающих', 'desc' => 'Основы стойки, ударов и защиты. Перчатки выдаём на месте.'],
            ['title' => 'Боксёрская кардио', 'desc' => 'Тренировка на мешках и лапах с элементами кардио. Сжигаем до 800 ккал!'],
            ['title' => 'Спарринг-тренировка', 'desc' => 'Для опытных боксёров. Лёгкий контакт, работа над тактикой.'],
        ],
        'crossfit' => [
            ['title' => 'WOD на воздухе', 'desc' => 'Тренировка дня на открытом воздухе. Гири, санки, турник — всё включено.'],
            ['title' => 'CrossFit Beginners', 'desc' => 'Знакомство с основными движениями кроссфита. Безопасность прежде всего.'],
        ],
        'swimming' => [
            ['title' => 'Плавание в открытой воде', 'desc' => 'Тренировка по плаванию на открытой воде. Нужен гидрокостюм.'],
            ['title' => 'Техника кроля', 'desc' => 'Отработка техники вольного стиля с видеоразбором.'],
        ],
        'pilates' => [
            ['title' => 'Пилатес на коврике', 'desc' => 'Классический мат-пилатес для укрепления кора и улучшения осанки.'],
            ['title' => 'Пилатес + растяжка', 'desc' => 'Комбинированная тренировка: силовые упражнения пилатес и глубокая растяжка.'],
        ],
        'stretching' => [
            ['title' => 'Утренняя растяжка', 'desc' => 'Мягкая растяжка всего тела для гибкости и хорошего самочувствия.'],
            ['title' => 'Шпагат за 30 дней', 'desc' => 'Интенсивный курс по растяжке. Занятие включает разогрев и работу над продольным шпагатом.'],
        ],
        'martial-arts' => [
            ['title' => 'Тайский бокс', 'desc' => 'Тренировка по муай-тай: удары руками, ногами, коленями и локтями.'],
            ['title' => 'ММА для начинающих', 'desc' => 'Основы смешанных единоборств: стойка, партер, клинч.'],
        ],
        'tennis' => [
            ['title' => 'Групповой теннис', 'desc' => 'Отработка подачи и приёма на открытых кортах. Ракетки можно взять в аренду.'],
            ['title' => 'Теннис для продвинутых', 'desc' => 'Тактическая игра: работа у сетки, резаные удары, розыгрыш очков.'],
        ],
        'basketball' => [
            ['title' => 'Стритбол 3×3', 'desc' => 'Игровая тренировка на открытой площадке. Формируем команды на месте.'],
        ],
        'volleyball' => [
            ['title' => 'Пляжный волейбол', 'desc' => 'Тренировка и игра на песчаных кортах. Приходите парами или поодиночке.'],
        ],
        'football' => [
            ['title' => 'Мини-футбол 5×5', 'desc' => 'Арендованное поле, формирование команд, полноценная игра с разминкой.'],
        ],
        'weightlifting' => [
            ['title' => 'Тяжёлая атлетика: рывок', 'desc' => 'Техническая тренировка по рывку. Индивидуальный разбор ошибок.'],
        ],
        'dance' => [
            ['title' => 'Танцевальная кардио', 'desc' => 'Зумба-микс: латина, хип-хоп, реггетон. Весело и энергично!'],
            ['title' => 'Хип-хоп в парке', 'desc' => 'Разучиваем связку в стиле хип-хоп. Все уровни.'],
        ],
        'hiking' => [
            ['title' => 'Хайкинг по лесопарку', 'desc' => 'Пешая прогулка 8–12 км по лесным тропам. Берите воду и лёгкий перекус.'],
        ],
    ];

    public function run(): void
    {
        $cities = City::all()->keyBy('slug');
        $sports = Sport::where('is_active', true)->get()->keyBy('slug');

        if ($cities->isEmpty() || $sports->isEmpty()) {
            $this->command->warn('Run SportsSeeder and CitiesSeeder first.');

            return;
        }

        $coaches = $this->createCoaches($cities, 15);

        $workouts = [];
        foreach (self::LOCATIONS as $citySlug => $locations) {
            $city = $cities->get($citySlug);
            if (! $city) {
                continue;
            }

            $cityCoaches = $coaches->where('city_id', $city->id);
            if ($cityCoaches->isEmpty()) {
                continue;
            }

            foreach ($locations as $location) {
                $count = rand(2, 5);
                for ($i = 0; $i < $count; $i++) {
                    $sport = $sports->random();
                    $template = $this->pickTemplate($sport->slug);
                    $coach = $cityCoaches->random();

                    $slotsTotal = rand(2, 12);
                    $totalPrice = rand(5, 50) * 100;
                    $slotPrice = ceil($totalPrice / $slotsTotal);
                    $slotsBooked = rand(0, $slotsTotal);

                    $workouts[] = [
                        'coach_id' => $coach->id,
                        'sport_id' => $sport->id,
                        'city_id' => $city->id,
                        'title' => $template['title'],
                        'description' => $template['desc'],
                        'location_name' => $location['name'],
                        'address' => $location['address'],
                        'lat' => $this->jitter($location['lat'], 0.002),
                        'lng' => $this->jitter($location['lng'], 0.003),
                        'starts_at' => $this->randomFutureDate(),
                        'duration_minutes' => [30, 45, 60, 90, 120][array_rand([30, 45, 60, 90, 120])],
                        'total_price' => $totalPrice,
                        'slot_price' => $slotPrice,
                        'slots_total' => $slotsTotal,
                        'slots_booked' => $slotsBooked,
                        'status' => 'published',
                        'published_at' => now()->subDays(rand(1, 14)),
                        'cancelled_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($workouts, 100) as $chunk) {
            Workout::insert($chunk);
        }

        $this->command->info('Seeded '.count($workouts).' workouts across '.count(self::LOCATIONS).' cities.');
    }

    private function createCoaches(\Illuminate\Support\Collection $cities, int $count): \Illuminate\Support\Collection
    {
        $firstNames = [
            'Алексей', 'Дмитрий', 'Ольга', 'Мария', 'Иван',
            'Анна', 'Сергей', 'Екатерина', 'Андрей', 'Наталья',
            'Михаил', 'Татьяна', 'Артём', 'Юлия', 'Максим',
        ];

        $lastNames = [
            'Петров', 'Иванов', 'Сидорова', 'Козлова', 'Смирнов',
            'Кузнецова', 'Попов', 'Лебедева', 'Новиков', 'Морозова',
            'Волков', 'Павлова', 'Семёнов', 'Голубева', 'Виноградов',
        ];

        $coaches = collect();

        for ($i = 0; $i < $count; $i++) {
            $city = $cities->values()->random();

            $user = User::create([
                'role' => 'coach',
                'first_name' => $firstNames[$i],
                'last_name' => $lastNames[$i],
                'email' => 'coach'.($i + 1).'@split.fitness',
                'phone' => '+7900'.str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'city_id' => $city->id,
                'status' => 'active',
            ]);

            CoachProfile::create([
                'user_id' => $user->id,
                'bio' => 'Сертифицированный тренер с опытом работы в фитнес-индустрии.',
                'experience_years' => rand(2, 20),
                'rating_avg' => rand(35, 50) / 10,
                'rating_count' => rand(5, 120),
                'moderation_status' => 'approved',
                'is_public' => true,
            ]);

            $coaches->push($user);
        }

        return $coaches;
    }

    private function pickTemplate(string $sportSlug): array
    {
        $templates = self::WORKOUT_TEMPLATES[$sportSlug]
            ?? [['title' => 'Групповая тренировка', 'desc' => 'Тренировка под руководством профессионального тренера.']];

        return $templates[array_rand($templates)];
    }

    private function jitter(float $value, float $range): float
    {
        return round($value + (mt_rand() / mt_getrandmax() * 2 - 1) * $range, 8);
    }

    private function randomFutureDate(): Carbon
    {
        $date = now()->addDays(rand(1, 21));
        $hour = rand(7, 20);
        $minute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];

        return $date->setTime($hour, $minute);
    }
}
