import { ref } from 'vue';

const formatDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const getToday = () => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
};

const presetDefinitions = [
    { value: 'today', label: 'Сегодня' },
    { value: 'tomorrow', label: 'Завтра' },
    { value: 'week', label: 'Эта неделя' },
    { value: 'next_week', label: 'След. неделя' },
    { value: 'custom', label: 'Дата' },
];

const computeRange = (preset) => {
    const today = getToday();

    switch (preset) {
        case 'today':
            return { dateFrom: formatDate(today), dateTo: formatDate(today) };

        case 'tomorrow': {
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            return { dateFrom: formatDate(tomorrow), dateTo: formatDate(tomorrow) };
        }

        case 'week': {
            const dayOfWeek = today.getDay();
            const weekEnd = new Date(today);
            weekEnd.setDate(today.getDate() + (dayOfWeek === 0 ? 0 : 7 - dayOfWeek));
            return { dateFrom: formatDate(today), dateTo: formatDate(weekEnd) };
        }

        case 'next_week': {
            const dayOfWeek = today.getDay();
            const daysToNextMonday = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
            const nextMonday = new Date(today);
            nextMonday.setDate(today.getDate() + daysToNextMonday);
            const nextSunday = new Date(nextMonday);
            nextSunday.setDate(nextMonday.getDate() + 6);
            return { dateFrom: formatDate(nextMonday), dateTo: formatDate(nextSunday) };
        }

        default:
            return null;
    }
};

export function useDatePresets() {
    const selectedPreset = ref(null);

    return {
        presets: presetDefinitions,
        selectedPreset,
        computeRange,
        formatDate,
    };
}
