<script setup>
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AvatarUploader from '@/Components/UI/AvatarUploader.vue';
import FileUploader from '@/Components/UI/FileUploader.vue';
import MultiSelect from '@/Components/UI/MultiSelect.vue';

const props = defineProps({
    user: Object,
    profile: Object,
    cities: Array,
    sports: Array,
});

const diplomaUploaderRef = ref(null);
const certificateUploaderRef = ref(null);

const form = useForm({
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    middle_name: props.user?.middle_name || '',
    bio: props.profile?.bio || '',
    city_id: props.user?.city_id || null,
    sports: props.profile?.sports?.map(s => s.id) || [],
    experience_years: props.profile?.experience_years || null,
});

const handleAvatarUpload = (file) => {
    const formData = new FormData();
    formData.append('avatar', file);

    window.axios.post(route('coach.profile.uploadAvatar'), formData)
        .then(response => {
            console.log('Avatar uploaded successfully');
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Avatar upload failed:', error);
        });
};

const handleAvatarRemove = () => {
    window.axios.delete(route('coach.profile.deleteAvatar'))
        .then(() => {
            console.log('Avatar removed successfully');
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Avatar removal failed:', error);
        });
};

const handleDiplomaUpload = (files) => {
    const formData = new FormData();
    files.forEach(file => {
        formData.append('diplomas[]', file);
    });

    window.axios.post(route('coach.profile.uploadDiploma'), formData)
        .then(() => {
            console.log('Diplomas uploaded successfully');
            if (diplomaUploaderRef.value) {
                diplomaUploaderRef.value.clearSelectedFiles();
            }
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Diploma upload failed:', error);
            const errorMessage = error.response?.data?.message || 'Не удалось загрузить файлы. Попробуйте снова.';
            if (diplomaUploaderRef.value) {
                diplomaUploaderRef.value.showUploadError(errorMessage);
            }
        });
};

const handleDiplomaRemove = (file) => {
    window.axios.delete(route('coach.profile.deleteDiploma', file.id))
        .then(() => {
            console.log('Diploma removed successfully');
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Diploma removal failed:', error);
        });
};

const handleCertificateUpload = (files) => {
    const formData = new FormData();
    files.forEach(file => {
        formData.append('certificates[]', file);
    });

    window.axios.post(route('coach.profile.uploadCertificate'), formData)
        .then(() => {
            console.log('Certificates uploaded successfully');
            if (certificateUploaderRef.value) {
                certificateUploaderRef.value.clearSelectedFiles();
            }
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Certificate upload failed:', error);
            const errorMessage = error.response?.data?.message || 'Не удалось загрузить файлы. Попробуйте снова.';
            if (certificateUploaderRef.value) {
                certificateUploaderRef.value.showUploadError(errorMessage);
            }
        });
};

const handleCertificateRemove = (file) => {
    window.axios.delete(route('coach.profile.deleteCertificate', file.id))
        .then(() => {
            console.log('Certificate removed successfully');
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Certificate removal failed:', error);
        });
};

const submit = () => {
    form.patch(route('coach.profile.update'), {
        preserveScroll: true,
    });
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
                                <AvatarUploader
                                    :current-url="user?.avatar_url"
                                    :max-size-mb="5"
                                    @upload="handleAvatarUpload"
                                    @remove="handleAvatarRemove"
                                />
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
                                <MultiSelect
                                    v-model="form.sports"
                                    :options="sports"
                                    label="Выберите виды спорта"
                                    placeholder="Поиск видов спорта..."
                                    value-key="id"
                                    label-key="name"
                                />
                                <p v-if="form.errors.sports" class="mt-1 text-sm text-red-600">{{ form.errors.sports }}</p>
                            </div>

                            <!-- Diplomas Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Дипломы и сертификаты
                                </label>
                                <FileUploader
                                    ref="diplomaUploaderRef"
                                    :existing-files="profile?.diplomas || []"
                                    :max-size-mb="10"
                                    accept="image/*,.pdf"
                                    :multiple="true"
                                    label="Добавить диплом"
                                    @upload="handleDiplomaUpload"
                                    @remove="handleDiplomaRemove"
                                />
                            </div>

                            <!-- Certificates Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Справки СМЗ
                                </label>
                                <FileUploader
                                    ref="certificateUploaderRef"
                                    :existing-files="profile?.certificates || []"
                                    :max-size-mb="10"
                                    accept="image/*,.pdf"
                                    :multiple="true"
                                    label="Добавить справку"
                                    @upload="handleCertificateUpload"
                                    @remove="handleCertificateRemove"
                                />
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
