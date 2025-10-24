import { showToast } from './ui.js';

let refreshTimer = null;

const request = async (action, payload = {}) => {
    if (!window.b2eData || !window.b2eData.ajaxUrl || !window.b2eData.nonce) {
        throw new Error('Logs data missing.');
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

const renderLogs = (logs = []) => {
    const container = document.querySelector('[data-b2e-logs]');
    if (!container) {
        return;
    }
    container.innerHTML = '';
    if (!logs.length) {
        container.innerHTML = '<p class="b2e-log-empty">No logs available.</p>';
        return;
    }
    logs.forEach((log) => {
        container.appendChild(renderLogEntry(log));
    });
};

export const renderLogEntry = (log = {}) => {
    const { level = 'info', timestamp, code, message } = log;
    const item = document.createElement('article');
    item.className = `b2e-log-entry b2e-log-entry--${level}`;

    const header = document.createElement('header');
    header.className = 'b2e-log-entry__header';

    const timeEl = document.createElement('time');
    if (timestamp) {
        timeEl.dateTime = timestamp;
        timeEl.textContent = new Date(timestamp).toLocaleString();
    }
    header.appendChild(timeEl);

    if (code) {
        const codeEl = document.createElement('span');
        codeEl.className = 'b2e-log-entry__code';
        codeEl.textContent = code;
        header.appendChild(codeEl);
    }

    const body = document.createElement('p');
    body.className = 'b2e-log-entry__message';
    body.textContent = message || '';

    item.appendChild(header);
    item.appendChild(body);
    return item;
};

export const fetchLogs = async () => {
    try {
        const response = await request('b2e_get_logs');
        renderLogs(response?.logs || []);
        return response;
    } catch (error) {
        showToast(error.message || 'Unable to fetch logs.', 'error');
        throw error;
    }
};

export const clearLogs = async () => {
    try {
        await request('b2e_clear_logs');
        renderLogs([]);
        showToast('Logs cleared.', 'success');
    } catch (error) {
        showToast(error.message || 'Unable to clear logs.', 'error');
        throw error;
    }
};

export const autoRefreshLogs = (interval = 7000) => {
    if (refreshTimer) {
        window.clearInterval(refreshTimer);
    }
    refreshTimer = window.setInterval(fetchLogs, interval);
    fetchLogs();
};

export const stopAutoRefreshLogs = () => {
    if (refreshTimer) {
        window.clearInterval(refreshTimer);
        refreshTimer = null;
    }
};
