<script setup>
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
    profile: Object,
    cities: Array,
    sports: Array,
});

const form = useForm({
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    middle_name: props.user?.middle_name || '',
    bio: props.profile?.bio || '',
    city_id: props.user?.city_id || null,
    sports: props.profile?.sports?.map(s => s.id) || [],
    experience_years: props.profile?.experience_years || null,
});

const avatarPreview = ref(props.user?.avatar_url || null);
const avatarFile = ref(null);

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadAvatar = () => {
    if (!avatarFile.value) return;

    const avatarForm = new FormData();
    avatarForm.append('avatar', avatarFile.value);

    window.axios.post(route('coach.profile.uploadAvatar'), avatarForm)
        .then(() => {
            avatarFile.value = null;
        })
        .catch(error => {
            console.error('Avatar upload failed:', error);
        });
};

const submit = () => {
    form.patch(route('coach.profile.update'), {
        preserveScroll: true,
    });
};

const toggleSport = (sportId) => {
    const index = form.sports.indexOf(sportId);
    if (index > -1) {
        form.sports.splice(index, 1);
    } else {
        form.sports.push(sportId);
    }
};
</script>

<template>
    <CoachLayout>
        <Head title="Редактировать профиль" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Редактировать профиль
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Avatar Upload Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Фото профиля
                                </label>
                                <div class="flex items-center space-x-4">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                        <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover">
                                        <svg v-else class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <input
                                            type="file"
                                            @change="handleAvatarChange"
                                            accept="image/*"
                                            class="hidden"
                                            ref="avatarInput"
                                        >
                                        <button
                                            type="button"
                                            @click="$refs.avatarInput.click()"
                                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                        >
                                            Выбрать фото
                                        </button>
                                        <button
                                            v-if="avatarFile"
                                            type="button"
                                            @click="uploadAvatar"
                                            class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                        >
                                            Загрузить
                                        </button>
                                        <p class="mt-1 text-xs text-gray-500">Максимум 5 МБ, только изображения</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">
                                        Фамилия <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    >
                                    <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                                </div>

                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">
                                        Имя <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    >
                                    <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                                </div>

                                <div>
                                    <label for="middle_name" class="block text-sm font-medium text-gray-700">
                                        Отчество
                                    </label>
                                    <input
                                        id="middle_name"
                                        v-model="form.middle_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>
                            </div>

                            <!-- Bio -->
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700">
                                    О себе <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="bio"
                                    v-model="form.bio"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    maxlength="1000"
                                    required
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ form.bio?.length || 0 }} / 1000</p>
                                <p v-if="form.errors.bio" class="mt-1 text-sm text-red-600">{{ form.errors.bio }}</p>
                            </div>

                            <!-- City and Experience -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="city_id" class="block text-sm font-medium text-gray-700">
                                        Город <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="city_id"
                                        v-model="form.city_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        required
                                    >
                                        <option :value="null">Выберите город</option>
                                        <option v-for="city in cities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.city_id" class="mt-1 text-sm text-red-600">{{ form.errors.city_id }}</p>
                                </div>

                                <div>
                                    <label for="experience_years" class="block text-sm font-medium text-gray-700">
                                        Опыт работы (лет)
                                    </label>
                                    <input
                                        id="experience_years"
                                        v-model.number="form.experience_years"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <p v-if="form.errors.experience_years" class="mt-1 text-sm text-red-600">{{ form.errors.experience_years }}</p>
                                </div>
                            </div>

                            <!-- Sports Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Виды спорта <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                                    <label
                                        v-for="sport in sports"
                                        :key="sport.id"
                                        class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="sport.id"
                                            :checked="form.sports.includes(sport.id)"
                                            @change="toggleSport(sport.id)"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <span class="text-sm text-gray-700">{{ sport.name }}</span>
                                    </label>
                                </div>
                                <p v-if="form.errors.sports" class="mt-1 text-sm text-red-600">{{ form.errors.sports }}</p>
                                <p class="mt-1 text-sm text-gray-500">Выбрано: {{ form.sports.length }}</p>
                            </div>

                            <!-- Diplomas Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Дипломы и сертификаты
                                </label>
                                <div v-if="profile?.diplomas?.length" class="mb-3 space-y-2">
                                    <div
                                        v-for="diploma in profile.diplomas"
                                        :key="diploma.id"
                                        class="flex items-center justify-between p-2 border border-gray-200 rounded"
                                    >
                                        <span class="text-sm text-gray-700">{{ diploma.name }}</span>
                                        <a :href="diploma.url" target="_blank" class="text-sm text-blue-600 hover:underline">
                                            Открыть
                                        </a>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">Загрузка дипломов будет доступна после создания UI компонентов (Task 5)</p>
                            </div>

                            <!-- Certificates Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Справки СМЗ
                                </label>
                                <div v-if="profile?.certificates?.length" class="mb-3 space-y-2">
                                    <div
                                        v-for="cert in profile.certificates"
                                        :key="cert.id"
                                        class="flex items-center justify-between p-2 border border-gray-200 rounded"
                                    >
                                        <span class="text-sm text-gray-700">{{ cert.name }}</span>
                                        <a :href="cert.url" target="_blank" class="text-sm text-blue-600 hover:underline">
                                            Открыть
                                        </a>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">Загрузка справок будет доступна после создания UI компонентов (Task 5)</p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                                </button>
                            </div>

                            <!-- Success Message -->
                            <div v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                Профиль успешно обновлён
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </CoachLayout>
</template>
