import { ref, computed } from 'vue';
import axios from 'axios';

export function useSmsAuth() {
    const phone = ref('');
    const code = ref('');
    const step = ref('phone'); // 'phone' | 'code' | 'register'
    const loading = ref(false);
    const errors = ref({});
    const cooldown = ref(0);

    let cooldownTimer = null;

    const phoneFormatted = computed(() => {
        const digits = phone.value.replace(/\D/g, '');
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

    return {
        phone,
        code,
        step,
        loading,
        errors,
        cooldown,
        phoneFormatted,
        sendCode,
        verifyCode,
        registerWithPhone,
        reset,
        goBack,
    };
}
