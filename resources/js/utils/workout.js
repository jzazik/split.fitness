export const getInitials = (name) => {
  if (!name?.trim()) return '??';
  const parts = name.trim().split(' ').filter(p => p.length > 0);
  if (parts.length >= 2 && parts[0].length > 0 && parts[1].length > 0) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() || '??';
};

export const shortCoachName = (name) => {
  if (!name) return '';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) return `${parts[0]} ${parts[1][0]}.`;
  return parts[0] || '';
};

export const formatWorkoutTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const formatWorkoutDate = (dateString) => {
  const date = new Date(dateString);
  const day = date.getDate();
  const months = [
    'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
    'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
  ];
  const weekdays = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
  return `${day} ${months[date.getMonth()]}, ${weekdays[date.getDay()]}`;
};

export const availableSlots = (workout) => {
  return Math.max(0, (workout.slots_total || 0) - (workout.slots_booked || 0));
};

export const formatPrice = (value) => {
  const num = Number(value);
  if (isNaN(num)) return '0';
  return Number.isInteger(num) ? String(num) : num.toFixed(0);
};

export const availabilityLabel = (workout) => {
  const available = availableSlots(workout);
  if (available === 0) return 'мест нет';
  if (available === 1) return 'осталось одно место';
  if (available <= 4) return `осталось ${available} места`;
  return `осталось ${available} мест`;
};
