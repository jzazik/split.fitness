<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    currentUrl: {
        type: String,
        default: null,
    },
    maxSizeMb: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['upload', 'remove']);

const isDragging = ref(false);
const preview = ref(props.currentUrl);
const selectedFile = ref(null);
const error = ref('');

const maxSizeBytes = computed(() => props.maxSizeMb * 1024 * 1024);

const validateFile = (file) => {
    error.value = '';

    if (!file.type.startsWith('image/')) {
        error.value = 'Можно загружать только изображения';
        return false;
    }

    if (file.size > maxSizeBytes.value) {
        error.value = `Максимальный размер файла: ${props.maxSizeMb} МБ`;
        return false;
    }

    return true;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file && validateFile(file)) {
        processFile(file);
    }
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;

    const file = event.dataTransfer.files[0];
    if (file && validateFile(file)) {
        processFile(file);
    }
};

const processFile = (file) => {
    selectedFile.value = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const uploadFile = () => {
    if (selectedFile.value) {
        emit('upload', selectedFile.value);
        selectedFile.value = null;
    }
};

const removeAvatar = () => {
    preview.value = null;
    selectedFile.value = null;
    emit('remove');
};

const triggerFileInput = () => {
    document.getElementById('avatar-file-input').click();
};
</script>

<template>
    <div class="flex items-start space-x-4">
        <!-- Avatar Preview -->
        <div
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            :class="[
                'w-24 h-24 rounded-full overflow-hidden flex items-center justify-center transition-all cursor-pointer',
                isDragging ? 'ring-4 ring-blue-500 ring-opacity-50' : '',
                preview ? 'bg-gray-100' : 'bg-gray-200'
            ]"
            @click="triggerFileInput"
        >
            <img
                v-if="preview"
                :src="preview"
                alt="Avatar preview"
                class="w-full h-full object-cover"
            >
            <svg
                v-else
                class="w-12 h-12 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>

        <!-- Controls -->
        <div class="flex-1">
            <input
                id="avatar-file-input"
                type="file"
                @change="handleFileSelect"
                accept="image/*"
                class="hidden"
            >

            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="triggerFileInput"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
                    >
                        Выбрать фото
                    </button>

                    <button
                        v-if="selectedFile"
                        type="button"
                        @click="uploadFile"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                    >
                        Загрузить
                    </button>

                    <button
                        v-if="preview && !selectedFile"
                        type="button"
                        @click="removeAvatar"
                        class="px-4 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 transition"
                    >
                        Удалить
                    </button>
                </div>

                <p class="text-xs text-gray-500">
                    Максимум {{ maxSizeMb }} МБ, только изображения
                </p>

                <p v-if="isDragging" class="text-xs text-blue-600">
                    Отпустите файл для загрузки
                </p>

                <p v-if="error" class="text-xs text-red-600">
                    {{ error }}
                </p>
            </div>
        </div>
    </div>
</template>
