const DEFAULT_POLL_INTERVAL = 4000;
let pollingTimer = null;
let activeMigrationId = null;

const request = async (action, payload = {}) => {
    if (!window.efsData || !window.efsData.ajaxUrl || !window.efsData.nonce) {
        throw new Error('Migration data is not initialized.');
    }
    const params = new URLSearchParams();
    params.append('action', action);
    params.append('_ajax_nonce', window.efsData.nonce);
    Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }
        params.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
    });
    const response = await fetch(window.efsData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: params.toString()
    });
    const text = await response.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (error) {
        throw new Error('Unexpected response from server.');
    }
    if (!response.ok || data.success === false) {
        const message = data?.data?.message || data?.data || data?.message || 'Migration request failed.';
        throw new Error(message);
    }
    return data.data ?? data;
};

const updateMigrationStatus = (status) => {
    const statusElement = document.querySelector('[data-efs-current-step]');
    if (statusElement) {
        statusElement.textContent = status;
    }
};

const renderSteps = (steps = []) => {
    const list = document.querySelector('[data-efs-steps]');
    if (!list) {
        return;
    }
    list.innerHTML = '';
    steps.forEach((step) => {
        const item = document.createElement('li');
        item.className = `b2e-migration-step${step.completed ? ' is-complete' : ''}${step.active ? ' is-active' : ''}`;
        item.textContent = step.label || step.slug || '';
        list.appendChild(item);
    });
};

const updateProgress = (progress) => {
    const event = new CustomEvent('efs:migration-progress', { detail: progress });
    document.dispatchEvent(event);
};

const stopPolling = () => {
    if (pollingTimer) {
        window.clearInterval(pollingTimer);
        pollingTimer = null;
    }
};

export const startMigration = async (form) => {
    if (!form) {
        throw new Error('Migration form element is missing.');
    }
    if (activeMigrationId) {
        throw new Error('A migration is already in progress.');
    }
    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => {
        payload[key] = value;
    });
    const response = await request('b2e_start_migration', payload);
    activeMigrationId = response?.migrationId || response?.id || null;
    updateMigrationSteps(response?.steps || []);
    updateProgress(response?.progress || { percentage: 0, status: 'Starting migration' });
    pollProgress();
    return response;
};

export const processBatch = async (batch = {}) => {
    if (!activeMigrationId) {
        throw new Error('No migration is running.');
    }
    const payload = { migrationId: activeMigrationId, batch };
    const response = await request('b2e_migrate_batch', payload);
    if (response?.steps) {
        updateMigrationSteps(response.steps);
    }
    if (response?.progress) {
        updateProgress(response.progress);
    }
    if (response?.completed) {
        handleMigrationComplete(response);
    }
    return response;
};

export const pollProgress = (interval = DEFAULT_POLL_INTERVAL) => {
    stopPolling();
    pollingTimer = window.setInterval(async () => {
        if (!activeMigrationId) {
            stopPolling();
            return;
        }
        try {
            const response = await request('b2e_get_migration_progress', { migrationId: activeMigrationId });
            if (response?.steps) {
                updateMigrationSteps(response.steps);
            }
            if (response?.progress) {
                updateProgress(response.progress);
            }
            if (response?.completed) {
                handleMigrationComplete(response);
            }
        } catch (error) {
            stopPolling();
            const event = new CustomEvent('b2e:migration-error', { detail: { message: error.message } });
            document.dispatchEvent(event);
        }
    }, interval);
};

export const updateMigrationSteps = (steps = []) => {
    renderSteps(steps);
    const active = steps.find((step) => step.active);
    if (active?.label) {
        updateMigrationStatus(active.label);
    }
};

export const handleMigrationComplete = (result = {}) => {
    stopPolling();
    const event = new CustomEvent('b2e:migration-complete', { detail: result });
    document.dispatchEvent(event);
    activeMigrationId = null;
};

export const cancelMigrationPolling = () => {
    activeMigrationId = null;
    stopPolling();
};
