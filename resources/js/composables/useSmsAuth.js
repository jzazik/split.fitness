import { ref, computed } from 'vue';
import axios from 'axios';

function formatPhoneDisplay(value) {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('8') && digits.length <= 11) {
        digits = '7' + digits.slice(1);
    }

    if (!digits.startsWith('7') && digits.length > 0) {
        digits = '7' + digits;
    }

    digits = digits.slice(0, 11);

    if (digits.length === 0) return '';
    if (digits.length <= 1) return `+${digits}`;
    if (digits.length <= 4) return `+${digits[0]} ${digits.slice(1)}`;
    if (digits.length <= 7) return `+${digits[0]} ${digits.slice(1, 4)} ${digits.slice(4)}`;
    if (digits.length <= 9) return `+${digits[0]} ${digits.slice(1, 4)} ${digits.slice(4, 7)}-${digits.slice(7)}`;
    return `+${digits[0]} ${digits.slice(1, 4)} ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9)}`;
}

/**
 * @param {Object} [options]
 * @param {(data: any) => void|Promise<void>} [options.onVerified] — called after successful SMS verification instead of the default redirect.
 */
export function useSmsAuth(options = {}) {
    const phone = ref('');
    const code = ref('');
    const step = ref('phone'); // 'phone' | 'code' | 'register'
    const loading = ref(false);
    const errors = ref({});
    const cooldown = ref(0);

    let cooldownTimer = null;

    const phoneDigits = computed(() => phone.value.replace(/\D/g, ''));

    const isPhoneValid = computed(() => {
        const d = phoneDigits.value;
        return (d.startsWith('7') && d.length === 11) || d.length === 10;
    });

    const phoneFormatted = computed(() => {
        const digits = phoneDigits.value;
        if (digits.startsWith('8') && digits.length === 11) {
            return '+7' + digits.slice(1);
        }
        if (digits.startsWith('7') && digits.length === 11) {
            return '+7' + digits.slice(1);
        }
        if (digits.length === 10) {
            return '+7' + digits;
        }
        return phone.value.startsWith('+') ? phone.value : '+' + digits;
    });

    function onPhoneInput() {
        phone.value = formatPhoneDisplay(phone.value);
    }

    function startCooldown(seconds = 60) {
        cooldown.value = seconds;
        clearInterval(cooldownTimer);
        cooldownTimer = setInterval(() => {
            cooldown.value--;
            if (cooldown.value <= 0) {
                clearInterval(cooldownTimer);
            }
        }, 1000);
    }

    async function sendCode() {
        loading.value = true;
        errors.value = {};

        try {
            await axios.post(route('auth.sms.send'), {
                phone: phoneFormatted.value,
            });
            step.value = 'code';
            startCooldown();
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors || {};
                if (errors.value.phone?.[0]?.includes('уже отправлен')) {
                    step.value = 'code';
                    startCooldown();
                }
            } else if (e.response?.status === 429) {
                const retryAfter = parseInt(e.response.headers['retry-after'], 10) || 60;
                startCooldown(retryAfter);
                errors.value = { phone: [`Слишком много попыток. Подождите ${retryAfter} сек.`] };
            } else {
                errors.value = { phone: ['Произошла ошибка. Попробуйте позже.'] };
            }
        } finally {
            loading.value = false;
        }
    }

    async function verifyCode() {
        loading.value = true;
        errors.value = {};

        try {
            const { data } = await axios.post(route('auth.sms.verify'), {
                phone: phoneFormatted.value,
                code: code.value,
            });

            if (options.onVerified) {
                await options.onVerified(data);
                return data;
            }

            if (data.action === 'login') {
                window.location.href = data.redirect;
                return data;
            }

            if (data.action === 'register') {
                step.value = 'register';
                return data;
            }
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors || {};
            } else {
                errors.value = { code: ['Произошла ошибка. Попробуйте позже.'] };
            }
        } finally {
            loading.value = false;
        }
    }

    async function registerWithPhone(formData) {
        loading.value = true;
        errors.value = {};

        try {
            const { data } = await axios.post(route('auth.sms.register'), {
                phone: phoneFormatted.value,
                ...formData,
            });

            window.location.href = data.redirect;
            return data;
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors || {};
            } else {
                errors.value = { phone: ['Произошла ошибка. Попробуйте позже.'] };
            }
        } finally {
            loading.value = false;
        }
    }

    function reset() {
        phone.value = '';
        step.value = 'phone';
        code.value = '';
        errors.value = {};
        clearInterval(cooldownTimer);
        cooldown.value = 0;
    }

    function goBack() {
        if (step.value === 'register') {
            step.value = 'code';
        } else if (step.value === 'code') {
            step.value = 'phone';
            code.value = '';
        }
        errors.value = {};
    }

    function destroy() {
        clearInterval(cooldownTimer);
    }

    return {
        phone,
        code,
        step,
        loading,
        errors,
        cooldown,
        isPhoneValid,
        phoneFormatted,
        onPhoneInput,
        sendCode,
        verifyCode,
        registerWithPhone,
        reset,
        goBack,
        destroy,
    };
}
