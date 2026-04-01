<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    existingFiles: {
        type: Array,
        default: () => [],
    },
    maxSizeMb: {
        type: Number,
        default: 10,
    },
    accept: {
        type: String,
        default: 'image/*,.pdf',
    },
    multiple: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: 'Добавить файл',
    },
});

const emit = defineEmits(['upload', 'remove']);

const selectedFiles = ref([]);
const error = ref('');

const maxSizeBytes = computed(() => props.maxSizeMb * 1024 * 1024);

const allFiles = computed(() => {
    return [
        ...props.existingFiles.map(f => ({ ...f, isExisting: true })),
        ...selectedFiles.value.map((f, idx) => ({
            id: `new-${idx}`,
            name: f.name,
            size: f.size,
            type: f.type,
            file: f,
            isExisting: false,
        })),
    ];
});

const getFileIcon = (file) => {
    const type = file.type || file.mime_type || '';

    if (type.includes('pdf')) {
        return 'pdf';
    }
    if (type.startsWith('image/')) {
        return 'image';
    }
    return 'file';
};

const getFileIconSvg = (iconType) => {
    const icons = {
        pdf: `<path fill="currentColor" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7.414A2 2 0 0018.414 6L14 1.586A2 2 0 0012.586 1H7zm0 2h5v4a1 1 0 001 1h4v7H7V4zm2 8a1 1 0 011-1h2a1 1 0 110 2h-1v1h1a1 1 0 110 2h-2a1 1 0 01-1-1v-3z"/>`,
        image: `<path fill="currentColor" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>`,
        file: `<path fill="currentColor" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7.414A2 2 0 0018.414 6L14 1.586A2 2 0 0012.586 1H7zm0 2h5v4a1 1 0 001 1h4v7H7V4z"/>`,
    };
    return icons[iconType] || icons.file;
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const validateFile = (file) => {
    if (file.size > maxSizeBytes.value) {
        error.value = `Файл "${file.name}" превышает максимальный размер ${props.maxSizeMb} МБ`;
        return false;
    }
    return true;
};

const handleFileSelect = (event) => {
    error.value = '';
    const files = Array.from(event.target.files);

    const validFiles = files.filter(validateFile);

    if (props.multiple) {
        selectedFiles.value.push(...validFiles);
    } else {
        selectedFiles.value = validFiles.slice(0, 1);
    }

    event.target.value = '';
};

const uploadFiles = () => {
    if (selectedFiles.value.length > 0) {
        emit('upload', selectedFiles.value);
        selectedFiles.value = [];
    }
};

const removeFile = (file) => {
    if (file.isExisting) {
        emit('remove', file);
    } else {
        const idx = selectedFiles.value.findIndex(f => f.name === file.name);
        if (idx > -1) {
            selectedFiles.value.splice(idx, 1);
        }
    }
};

const triggerFileInput = () => {
    document.getElementById(`file-input-${Math.random()}`).click();
};
</script>

<template>
    <div class="space-y-3">
        <!-- File List -->
        <div v-if="allFiles.length > 0" class="space-y-2">
            <div
                v-for="file in allFiles"
                :key="file.id"
                class="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition"
            >
                <div class="flex items-center space-x-3 flex-1 min-w-0">
                    <!-- File Icon -->
                    <div class="flex-shrink-0">
                        <svg
                            class="w-8 h-8"
                            :class="{
                                'text-red-500': getFileIcon(file) === 'pdf',
                                'text-blue-500': getFileIcon(file) === 'image',
                                'text-gray-500': getFileIcon(file) === 'file',
                            }"
                            viewBox="0 0 20 20"
                            v-html="getFileIconSvg(getFileIcon(file))"
                        />
                    </div>

                    <!-- File Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate">
                            {{ file.name || file.file_name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ formatFileSize(file.size) }}
                            <span v-if="!file.isExisting" class="text-blue-600 ml-1">
                                (новый)
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <a
                        v-if="file.isExisting && file.url"
                        :href="file.url"
                        target="_blank"
                        class="text-sm text-blue-600 hover:underline"
                    >
                        Открыть
                    </a>

                    <button
                        type="button"
                        @click="removeFile(file)"
                        class="text-red-600 hover:text-red-700 transition"
                        :title="file.isExisting ? 'Удалить файл' : 'Отменить'"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Add File Button -->
        <div class="flex items-center space-x-2">
            <input
                :id="`file-input-${Math.random()}`"
                type="file"
                @change="handleFileSelect"
                :accept="accept"
                :multiple="multiple"
                class="hidden"
            >

            <button
                type="button"
                @click="triggerFileInput"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
            >
                {{ label }}
            </button>

            <button
                v-if="selectedFiles.length > 0"
                type="button"
                @click="uploadFiles"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            >
                Загрузить ({{ selectedFiles.length }})
            </button>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="text-xs text-red-600">
            {{ error }}
        </p>

        <!-- Help Text -->
        <p class="text-xs text-gray-500">
            Максимум {{ maxSizeMb }} МБ на файл
        </p>
    </div>
</template>
