<script setup>
import AthleteLayout from '@/Layouts/AthleteLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
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

const handleAvatarUpload = (file) => {
    const formData = new FormData();
    formData.append('avatar', file);

    window.axios.post(route('athlete.profile.uploadAvatar'), formData)
        .then(response => {
            console.log('Avatar uploaded successfully');
        })
        .catch(error => {
            console.error('Avatar upload failed:', error);
        });
};

const handleAvatarRemove = () => {
    window.axios.delete(route('athlete.profile.deleteAvatar'))
        .then(() => {
            console.log('Avatar removed successfully');
        })
        .catch(error => {
            console.error('Avatar removal failed:', error);
        });
};

const submit = () => {
    form.patch(route('athlete.profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AthleteLayout>
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
                                <AvatarUploader
                                    :current-url="user?.avatar_url"
                                    :max-size-mb="5"
                                    @upload="handleAvatarUpload"
                                    @remove="handleAvatarRemove"
                                />
                            </div>

                            <!-- Personal Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">
                                        Фамилия <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        required
                                    >
                                    <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                    Телефон
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="+7 (___) ___-__-__"
                                >
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>

                            <!-- City -->
                            <div>
                                <label for="city_id" class="block text-sm font-medium text-gray-700">
                                    Город
                                </label>
                                <select
                                    id="city_id"
                                    v-model="form.city_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option :value="null">Выберите город</option>
                                    <option v-for="city in cities" :key="city.id" :value="city.id">
                                        {{ city.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.city_id" class="mt-1 text-sm text-red-600">{{ form.errors.city_id }}</p>
                            </div>

                            <!-- Emergency Contact -->
                            <div>
                                <label for="emergency_contact" class="block text-sm font-medium text-gray-700">
                                    Контакт для экстренной связи
                                </label>
                                <input
                                    id="emergency_contact"
                                    v-model="form.emergency_contact"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="Имя и телефон близкого человека"
                                >
                                <p v-if="form.errors.emergency_contact" class="mt-1 text-sm text-red-600">{{ form.errors.emergency_contact }}</p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Опционально. Этот контакт будет использован только в чрезвычайных ситуациях.
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
    </AthleteLayout>
</template>
