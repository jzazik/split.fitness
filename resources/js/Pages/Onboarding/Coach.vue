<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AvatarUploader from '@/Components/UI/AvatarUploader.vue';
import MultiSelect from '@/Components/UI/MultiSelect.vue';
import FileUploader from '@/Components/UI/FileUploader.vue';

const props = defineProps({
    user: Object,
    profile: Object,
    cities: Array,
    sports: Array,
});

const currentStep = ref(1);
const totalSteps = 5;

const form = useForm({
    first_name: props.user?.first_name || '',
    last_name: props.user?.last_name || '',
    middle_name: props.user?.middle_name || '',
    bio: props.profile?.bio || '',
    city_id: props.user?.city_id || null,
    sports: props.profile?.sports || [],
    experience_years: props.profile?.experience_years || null,
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
        await window.axios.post(route('coach.profile.uploadAvatar'), formData);
    } catch (error) {
        console.error('Avatar upload failed:', error);
    }
};

const progress = computed(() => {
    return (currentStep.value / totalSteps) * 100;
});

const canProceedToStep2 = computed(() => {
    return form.first_name && form.last_name;
});

const canProceedToStep3 = computed(() => {
    return form.sports.length > 0;
});

const canProceedToStep4 = computed(() => {
    return form.city_id && form.bio && form.bio.length >= 50;
});

const diplomaUploaderRef = ref(null);

const handleDiplomaUpload = (files) => {
    const formData = new FormData();
    files.forEach(file => {
        formData.append('diplomas[]', file);
    });

    window.axios.post(route('coach.profile.uploadDiploma'), formData)
        .then(() => {
            console.log('Diplomas uploaded during onboarding');
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
            console.log('Diploma removed during onboarding');
            router.reload({ preserveScroll: true });
        })
        .catch(error => {
            console.error('Diploma removal failed:', error);
        });
};

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
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
        <Head title="Настройка профиля тренера" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Настройка профиля тренера</h2>
            <p class="mt-2 text-sm text-gray-600">
                Заполните информацию о себе, чтобы начать работу
            </p>
        </div>

        <div class="mb-6">
            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full bg-blue-600 transition-all duration-300"
                    :style="{ width: progress + '%' }"
                ></div>
            </div>
            <p class="mt-2 text-sm text-gray-600 text-center">
                Шаг {{ currentStep }} из {{ totalSteps }}
            </p>
        </div>

        <div v-if="currentStep === 1" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Загрузите фото и укажите ФИО</h3>

            <div class="flex justify-center">
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
                <label for="middle_name" class="block text-sm font-medium text-gray-700">
                    Отчество
                </label>
                <input
                    id="middle_name"
                    v-model="form.middle_name"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <button
                type="button"
                @click="nextStep"
                :disabled="!canProceedToStep2"
                class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
            >
                Далее
            </button>
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Выберите виды спорта</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Виды спорта <span class="text-red-500">*</span>
                </label>
                <MultiSelect
                    v-model="form.sports"
                    :options="sports"
                    option-label="name"
                    option-value="id"
                    placeholder="Выберите виды спорта"
                />
                <p class="mt-1 text-sm text-gray-500">
                    Выбрано: {{ form.sports.length }}
                </p>
                <div v-if="form.errors.sports" class="mt-1 text-sm text-red-600">
                    {{ form.errors.sports }}
                </div>
            </div>

            <div>
                <label for="experience_years" class="block text-sm font-medium text-gray-700">
                    Стаж работы тренером (лет)
                </label>
                <input
                    id="experience_years"
                    v-model.number="form.experience_years"
                    type="number"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="prevStep"
                    class="flex-1 rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                >
                    Назад
                </button>
                <button
                    type="button"
                    @click="nextStep"
                    :disabled="!canProceedToStep3"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                    Далее
                </button>
            </div>
        </div>

        <div v-if="currentStep === 3" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Выберите город и расскажите о себе</h3>

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
                <div v-if="form.errors.city_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.city_id }}
                </div>
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700">
                    О себе <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="5"
                    maxlength="1000"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Расскажите о своем опыте, достижениях, подходе к тренировкам..."
                    required
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    {{ form.bio?.length || 0 }} / 1000 символов (минимум 50)
                </p>
                <div v-if="form.errors.bio" class="mt-1 text-sm text-red-600">
                    {{ form.errors.bio }}
                </div>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="prevStep"
                    class="flex-1 rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                >
                    Назад
                </button>
                <button
                    type="button"
                    @click="nextStep"
                    :disabled="!canProceedToStep4"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                    Далее
                </button>
            </div>
        </div>

        <div v-if="currentStep === 4" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Загрузите дипломы (необязательно)</h3>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <p class="text-sm text-blue-900">
                    Загрузите фотографии или сканы ваших дипломов, сертификатов и других документов, подтверждающих ваше образование и квалификацию. Этот шаг необязателен, но он повышает доверие к вашему профилю.
                </p>
            </div>

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

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="prevStep"
                    class="flex-1 rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                >
                    Назад
                </button>
                <button
                    type="button"
                    @click="nextStep"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    {{ (profile?.diplomas || []).length > 0 ? 'Далее' : 'Пропустить' }}
                </button>
            </div>
        </div>

        <div v-if="currentStep === 5" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Готово!</h3>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <p class="text-sm text-blue-900">
                    Ваш профиль будет отправлен на модерацию. После одобрения администратором вы сможете публиковать тренировки и принимать заявки от атлетов.
                </p>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 space-y-2">
                <p class="text-sm font-medium text-gray-900">Проверьте введённые данные:</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li><strong>ФИО:</strong> {{ form.last_name }} {{ form.first_name }} {{ form.middle_name }}</li>
                    <li><strong>Город:</strong> {{ cities.find(c => c.id === form.city_id)?.name }}</li>
                    <li><strong>Виды спорта:</strong> {{ sports.filter(s => form.sports.includes(s.id)).map(s => s.name).join(', ') }}</li>
                    <li v-if="form.experience_years"><strong>Стаж:</strong> {{ form.experience_years }} лет</li>
                    <li><strong>О себе:</strong> {{ form.bio.substring(0, 100) }}...</li>
                </ul>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    @click="prevStep"
                    class="flex-1 rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300"
                >
                    Назад
                </button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="flex-1 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Сохранение...' : 'Завершить' }}
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
