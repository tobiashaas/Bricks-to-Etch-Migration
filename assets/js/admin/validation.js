const request = async (action, payload = {}) => {
    if (!window.efsData || !window.efsData.ajaxUrl || !window.efsData.nonce) {
        throw new Error('Validation data missing.');
    }
    const params = new URLSearchParams();
    params.append('action', action);
    params.append('_ajax_nonce', window.efsData.nonce);
    Object.entries(payload).forEach(([key, value]) => {
        params.append(key, value);
    });
    const response = await fetch(window.efsData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        credentials: 'same-origin',
        body: params.toString()
    });
    const result = await response.json();
    if (!response.ok || result.success === false) {
        const message = result?.data?.message || result?.data || result?.message || 'Validation failed.';
        throw new Error(message);
    }
    return result.data ?? result;
};

const setLoading = (element, isLoading) => {
    if (!element) {
        return;
    }
    element.toggleAttribute('disabled', isLoading);
    element.classList.toggle('is-loading', isLoading);
};

const showFieldError = (field, message) => {
    if (!field) {
        return;
    }
    const container = field.closest('[data-efs-field]');
    if (!container) {
        return;
    }
    let messageEl = container.querySelector('[data-efs-error]');
    if (!messageEl) {
        messageEl = document.createElement('p');
        messageEl.className = 'b2e-error-message';
        messageEl.dataset.efsError = '';
        container.appendChild(messageEl);
    }
    messageEl.textContent = message;
    container.classList.add('has-error');
};

const clearFieldError = (field) => {
    if (!field) {
        return;
    }
    const container = field.closest('[data-b2e-field]');
    if (!container) {
        return;
    }
    container.classList.remove('has-error');
    const messageEl = container.querySelector('[data-efs-error]');
    if (messageEl) {
        messageEl.textContent = '';
    }
};

export const validateApiKey = async ({ field, submitButton }) => {
    if (!field) {
        throw new Error('Validation field missing.');
    }
    clearFieldError(field);
    setLoading(submitButton, true);
    try {
        const value = field.value.trim();
        if (!value) {
            throw new Error('API key is required.');
        }
        const response = await request('b2e_validate_api_key', { apiKey: value });
        handleValidationSuccess(field, response, 'API key is valid.');
        return response;
    } catch (error) {
        handleValidationError(field, error.message);
        throw error;
    } finally {
        setLoading(submitButton, false);
    }
};

export const validateMigrationToken = async ({ field, submitButton }) => {
    if (!field) {
        throw new Error('Validation field missing.');
    }
    clearFieldError(field);
    setLoading(submitButton, true);
    try {
        const value = field.value.trim();
        if (!value) {
            throw new Error('Migration token is required.');
        }
        const response = await request('b2e_validate_migration_token', { migrationToken: value });
        handleValidationSuccess(field, response, 'Migration token is valid.');
        return response;
    } catch (error) {
        handleValidationError(field, error.message);
        throw error;
    } finally {
        setLoading(submitButton, false);
    }
};

export const handleValidationSuccess = (field, data, fallbackMessage = 'Validation succeeded.') => {
    if (!field) {
        return;
    }
    clearFieldError(field);
    const container = field.closest('[data-b2e-field]');
    if (!container) {
        return;
    }
    container.classList.add('has-success');
    const message = data?.message || fallbackMessage;
    let successEl = container.querySelector('[data-efs-success]');
    if (!successEl) {
        successEl = document.createElement('p');
        successEl.className = 'b2e-success-message';
        successEl.dataset.efsSuccess = '';
        container.appendChild(successEl);
    }
    successEl.textContent = message;
};

export const handleValidationError = (field, message = 'Validation failed.') => {
    showFieldError(field, message);
};
