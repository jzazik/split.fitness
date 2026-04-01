<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AvatarUploader from '@/Components/UI/AvatarUploader.vue';

const props = defineProps({
    user: Object,
    profile: Object,
    cities: Array,
});

const form = useForm({
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    phone: props.user?.phone || '',
    city_id: props.user?.city_id || null,
    emergency_contact: props.profile?.emergency_contact || '',
});

const avatarFile = ref(null);

const handleAvatarUpload = (file) => {
    avatarFile.value = file;
};

const uploadAvatar = async () => {
    if (!avatarFile.value) return;

    const formData = new FormData();
    formData.append('avatar', avatarFile.value);

    try {
        await window.axios.post(route('athlete.profile.uploadAvatar'), formData);
    } catch (error) {
        console.error('Avatar upload failed:', error);
    }
};

const submit = async () => {
    await uploadAvatar();
    form.post(route('onboarding.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Настройка профиля" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Настройка профиля</h2>
            <p class="mt-2 text-sm text-gray-600">
                Заполните базовую информацию о себе
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="flex justify-center mb-6">
                <AvatarUploader
                    :initial-url="user?.avatar_url"
                    @upload="handleAvatarUpload"
                />
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
                />
                <div v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.first_name }}
                </div>
            </div>

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
                />
                <div v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.last_name }}
                </div>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">
                    Телефон
                </label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="+7 (999) 123-45-67"
                />
                <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                    {{ form.errors.phone }}
                </div>
            </div>

            <div>
                <label for="city_id" class="block text-sm font-medium text-gray-700">
                    Город
                </label>
                <select
                    id="city_id"
                    v-model="form.city_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option :value="null">Выберите город</option>
                    <option v-for="city in cities" :key="city.id" :value="city.id">
                        {{ city.name }}
                    </option>
                </select>
                <div v-if="form.errors.city_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.city_id }}
                </div>
            </div>

            <div>
                <label for="emergency_contact" class="block text-sm font-medium text-gray-700">
                    Контакт для экстренных случаев
                </label>
                <input
                    id="emergency_contact"
                    v-model="form.emergency_contact"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Имя и телефон близкого человека"
                />
                <p class="mt-1 text-sm text-gray-500">
                    Необязательно, но может быть полезно в случае травмы
                </p>
                <div v-if="form.errors.emergency_contact" class="mt-1 text-sm text-red-600">
                    {{ form.errors.emergency_contact }}
                </div>
            </div>

            <div class="pt-4">
                <button
                    type="submit"
                    :disabled="form.processing || !form.first_name || !form.last_name"
                    class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Сохранение...' : 'Готово' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
