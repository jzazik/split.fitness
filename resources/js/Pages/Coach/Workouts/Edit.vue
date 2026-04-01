<script setup>
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import WorkoutMap from '@/Components/Map/WorkoutMap.vue';
import { useMap } from '@/composables/useMap';

const props = defineProps({
    workout: Object,
    cities: Array,
    sports: Array,
});

const { reverseGeocode, isLoading: isGeocodingLoading } = useMap();

// Format starts_at to datetime-local format (YYYY-MM-DDTHH:MM)
const formatDatetimeLocal = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    sport_id: props.workout.sport_id,
    city_id: props.workout.city_id,
    title: props.workout.title || '',
    description: props.workout.description || '',
    location_name: props.workout.location_name,
    address: props.workout.address || '',
    lat: props.workout.lat,
    lng: props.workout.lng,
    starts_at: formatDatetimeLocal(props.workout.starts_at),
    duration_minutes: props.workout.duration_minutes,
    total_price: props.workout.total_price,
    slots_total: props.workout.slots_total,
});

const coordinates = ref({
    lat: props.workout.lat,
    lng: props.workout.lng,
});

// Calculate slot price preview
const slotPrice = computed(() => {
    if (!form.total_price || !form.slots_total || form.slots_total <= 0) {
        return 0;
    }
    return Math.ceil(parseFloat(form.total_price) / parseInt(form.slots_total));
});

// Watch for coordinate changes and trigger reverse geocoding
watch(coordinates, async (newCoordinates) => {
    if (newCoordinates && newCoordinates.lat && newCoordinates.lng) {
        form.lat = newCoordinates.lat;
        form.lng = newCoordinates.lng;

        // Fetch address from coordinates
        const result = await reverseGeocode(newCoordinates.lat, newCoordinates.lng);
        if (result) {
            // Use display_name as location_name
            form.location_name = result.display_name || '';
            // Store the full address separately if needed
            if (!form.address) {
                form.address = result.display_name || '';
            }
        }
    }
}, { deep: true });

const submit = () => {
    form.patch(route('coach.workouts.update', props.workout.id));
};
</script>

<template>
    <CoachLayout>
        <Head title="Редактировать тренировку" />

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Редактировать тренировку</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Внесите необходимые изменения в тренировку.
                    </p>
                </div>

                <!-- Warning for published workouts -->
                <div v-if="workout.status === 'published'" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Внимание
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Изменения видны атлетам сразу после сохранения.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Sport and City -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Основная информация</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="sport_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Вид спорта *
                                </label>
                                <select
                                    id="sport_id"
                                    v-model="form.sport_id"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500"
                                    :class="{ 'border-red-300': form.errors.sport_id }"
                                >
                                    <option :value="null">Выберите вид спорта</option>
                                    <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                        {{ sport.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.sport_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.sport_id }}
                                </p>
                            </div>

                            <div>
                                <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Город *
                                </label>
                                <select
                                    id="city_id"
                                    v-model="form.city_id"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500"
                                    :class="{ 'border-red-300': form.errors.city_id }"
                                >
                                    <option :value="null">Выберите город</option>
                                    <option v-for="city in cities" :key="city.id" :value="city.id">
                                        {{ city.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.city_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.city_id }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <Input
                                v-model="form.title"
                                label="Название (опционально)"
                                placeholder="Например: Утренняя йога в парке"
                                :error="form.errors.title"
                            />
                        </div>

                        <div class="mt-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Описание (опционально)
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500"
                                :class="{ 'border-red-300': form.errors.description }"
                                placeholder="Опишите что будет на тренировке..."
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Место проведения</h2>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Выберите точку на карте *
                            </label>
                            <WorkoutMap
                                v-model="coordinates"
                                :editable="true"
                            />
                            <p v-if="isGeocodingLoading" class="mt-2 text-sm text-gray-500">
                                Загрузка адреса...
                            </p>
                            <p v-if="form.errors.lat || form.errors.lng" class="mt-2 text-sm text-red-600">
                                Выберите точку на карте
                            </p>
                        </div>

                        <div class="mt-4">
                            <Input
                                v-model="form.location_name"
                                label="Название места *"
                                placeholder="Заполнится автоматически или введите вручную"
                                :error="form.errors.location_name"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Это поле заполняется автоматически при выборе точки на карте
                            </p>
                        </div>

                        <div class="mt-4">
                            <Input
                                v-model="form.address"
                                label="Полный адрес (опционально)"
                                placeholder="Можно уточнить адрес"
                                :error="form.errors.address"
                            />
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Дата и время</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    label="Дата и время начала *"
                                    :error="form.errors.starts_at"
                                />
                            </div>

                            <div>
                                <Input
                                    v-model="form.duration_minutes"
                                    type="number"
                                    label="Длительность (минуты) *"
                                    placeholder="60"
                                    :error="form.errors.duration_minutes"
                                    min="1"
                                    max="480"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Pricing and Slots -->
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Стоимость и места</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Input
                                    v-model="form.total_price"
                                    type="number"
                                    label="Общая стоимость (₽) *"
                                    placeholder="1000"
                                    :error="form.errors.total_price"
                                    step="0.01"
                                    min="0.01"
                                />
                            </div>

                            <div>
                                <Input
                                    v-model="form.slots_total"
                                    type="number"
                                    label="Количество мест *"
                                    placeholder="3"
                                    :error="form.errors.slots_total"
                                    min="1"
                                    max="100"
                                />
                            </div>
                        </div>

                        <!-- Price Preview -->
                        <div v-if="slotPrice > 0" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-sm font-medium text-blue-900">
                                Цена слота: {{ slotPrice }} ₽
                            </p>
                            <p class="text-xs text-blue-700 mt-1">
                                Атлет будет платить {{ slotPrice }} ₽ за место
                            </p>
                            <p class="text-xs text-blue-700">
                                Вы получите {{ slotPrice * form.slots_total }} ₽ при полной записи
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end space-x-3">
                        <Button
                            type="button"
                            @click="$inertia.visit(route('coach.workouts.index'))"
                            class="bg-gray-200 text-gray-700 hover:bg-gray-300"
                        >
                            Отмена
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-primary-600 text-white hover:bg-primary-700"
                        >
                            {{ form.processing ? 'Сохранение...' : 'Сохранить изменения' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </CoachLayout>
</template>
