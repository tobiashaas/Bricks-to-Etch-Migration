const ensureAjaxContext = () => {
    if (!window.b2eData) {
        throw new Error('B2E admin data is not available on window.b2eData.');
    }
    const { ajaxUrl, nonce } = window.b2eData;
    if (!ajaxUrl) {
        throw new Error('AJAX URL is not defined in window.b2eData.ajaxUrl.');
    }
    if (!nonce) {
        throw new Error('Nonce is not defined in window.b2eData.nonce.');
    }
    return { ajaxUrl, nonce };
};

const appendValue = (params, key, value) => {
    if (value === undefined || value === null) {
        return;
    }
    if (Array.isArray(value)) {
        value.forEach((item) => params.append(`${key}[]`, item));
        return;
    }
    params.append(key, value);
};

const toSearchParams = (payload) => {
    if (payload instanceof URLSearchParams) {
        return payload;
    }
    if (payload instanceof FormData) {
        const params = new URLSearchParams();
        for (const [key, value] of payload.entries()) {
            appendValue(params, key, value);
        }
        return params;
    }
    const params = new URLSearchParams();
    Object.entries(payload || {}).forEach(([key, value]) => {
        appendValue(params, key, value);
    });
    return params;
};

export const post = async (action, payload = {}) => {
    const { ajaxUrl, nonce } = ensureAjaxContext();
    const params = toSearchParams(payload);
    params.set('action', action);
    params.set('nonce', nonce);

    const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: params.toString(),
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    const result = await response.json();
    if (!result?.success) {
        const errorMessage = typeof result?.data === 'string' ? result.data : 'Request failed.';
        throw new Error(errorMessage);
    }

    return result.data ?? {};
};

export const serializeForm = (form) => {
    if (!form) {
        return {};
    }
    const formData = new FormData(form);
    const output = {};
    for (const [key, value] of formData.entries()) {
        if (Object.prototype.hasOwnProperty.call(output, key)) {
            output[key] = Array.isArray(output[key]) ? [...output[key], value] : [output[key], value];
        } else {
            output[key] = value;
        }
    }
    return output;
};

export const getInitialData = (key, defaultValue = null) => {
    if (!window.b2eData || typeof window.b2eData !== 'object') {
        return defaultValue;
    }
    return Object.prototype.hasOwnProperty.call(window.b2eData, key)
        ? window.b2eData[key]
        : defaultValue;
};
