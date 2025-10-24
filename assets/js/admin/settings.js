import { showToast, showLoadingState, hideLoadingState } from './ui.js';

const request = async (action, payload = {}) => {
    if (!window.b2eData || !window.b2eData.ajaxUrl || !window.b2eData.nonce) {
        throw new Error('Settings data missing.');
    }
    const params = new URLSearchParams();
    params.append('action', action);
    params.append('_ajax_nonce', window.b2eData.nonce);
    Object.entries(payload).forEach(([key, value]) => {
        params.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
    });
    const response = await fetch(window.b2eData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        credentials: 'same-origin',
        body: params.toString()
    });
    const result = await response.json();
    if (!response.ok || result.success === false) {
        const message = result?.data?.message || result?.data || result?.message || 'Request failed.';
        throw new Error(message);
    }
    return result.data ?? result;
};

const serializeForm = (form) => {
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        if (data[key]) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    });
    return data;
};

export const saveSettings = async (form, submitButton) => {
    if (!form) {
        throw new Error('Settings form missing.');
    }
    showLoadingState(submitButton || form.querySelector('[type="submit"]'));
    try {
        const data = serializeForm(form);
        const response = await request('b2e_save_settings', data);
        showToast(response?.message || 'Settings saved.', 'success');
        return response;
    } catch (error) {
        showToast(error.message || 'Unable to save settings.', 'error');
        throw error;
    } finally {
        hideLoadingState(submitButton || form.querySelector('[type="submit"]'));
    }
};

export const testConnection = async (form, submitButton) => {
    if (!form) {
        throw new Error('Settings form missing.');
    }
    showLoadingState(submitButton || form.querySelector('[type="submit"]'));
    try {
        const data = serializeForm(form);
        const response = await request('b2e_test_connection', data);
        showToast(response?.message || 'Connection succeeded.', 'success');
        return response;
    } catch (error) {
        showToast(error.message || 'Connection failed.', 'error');
        throw error;
    } finally {
        hideLoadingState(submitButton || form.querySelector('[type="submit"]'));
    }
};

export const generateMigrationKey = async (form, submitButton) => {
    if (!form) {
        throw new Error('Key generation form missing.');
    }
    showLoadingState(submitButton || form.querySelector('[type="submit"]'));
    try {
        const data = serializeForm(form);
        const response = await request('b2e_generate_migration_key', data);
        const input = form.querySelector('[data-b2e-migration-key]');
        if (input) {
            input.value = response?.key || '';
        }
        showToast(response?.message || 'Migration key generated.', 'success');
        return response;
    } catch (error) {
        showToast(error.message || 'Unable to generate migration key.', 'error');
        throw error;
    } finally {
        hideLoadingState(submitButton || form.querySelector('[type="submit"]'));
    }
};

export const copyKeyToClipboard = async (input) => {
    if (!input) {
        throw new Error('Key input missing.');
    }
    try {
        await navigator.clipboard.writeText(input.value || input.textContent || '');
        showToast('Migration key copied.', 'success');
    } catch (error) {
        showToast('Unable to copy key.', 'error');
        throw error;
    }
};

export const loadSettings = () => {
    const form = document.querySelector('[data-b2e-settings-form]');
    if (!form || !window.b2eData?.settings) {
        return;
    }
    Object.entries(window.b2eData.settings).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (!field) {
            return;
        }
        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
        } else {
            field.value = value;
        }
    });
};
