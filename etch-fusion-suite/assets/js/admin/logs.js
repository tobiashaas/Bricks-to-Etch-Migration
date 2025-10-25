import { post, getInitialData } from './api.js';
import { showToast } from './ui.js';

const ACTION_FETCH_LOGS = 'b2e_get_logs';
const ACTION_CLEAR_LOGS = 'b2e_clear_logs';

let refreshTimer = null;

const renderLogs = (logs = []) => {
    const container = document.querySelector('[data-b2e-logs]');
    if (!container) {
        return;
    }
    container.innerHTML = '';
    if (!Array.isArray(logs) || logs.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'b2e-log-empty';
        empty.textContent = 'No logs yet. Migration activity will appear here.';
        container.appendChild(empty);
        return;
    }
    logs.forEach((log) => {
        const article = document.createElement('article');
        article.className = `b2e-log-entry b2e-log-entry--${log.level || 'info'}`;

        const header = document.createElement('header');
        header.className = 'b2e-log-entry__header';

        if (log.timestamp) {
            const time = document.createElement('time');
            time.dateTime = log.timestamp;
            time.textContent = log.timestamp;
            header.appendChild(time);
        }

        if (log.code) {
            const code = document.createElement('span');
            code.className = 'b2e-log-entry__code';
            code.textContent = log.code;
            header.appendChild(code);
        }

        article.appendChild(header);

        if (log.message) {
            const message = document.createElement('p');
            message.className = 'b2e-log-entry__message';
            message.textContent = log.message;
            article.appendChild(message);
        }

        container.appendChild(article);
    });
};

const fetchLogs = async () => {
    try {
        const data = await post(ACTION_FETCH_LOGS);
        renderLogs(data?.logs || []);
    } catch (error) {
        console.error('Fetch logs failed', error);
        showToast(error.message, 'error');
    }
};

const clearLogs = async () => {
    try {
        const data = await post(ACTION_CLEAR_LOGS);
        showToast(data?.message || 'Logs cleared.', 'success');
        renderLogs([]);
    } catch (error) {
        console.error('Clear logs failed', error);
        showToast(error.message, 'error');
    }
};

export const startAutoRefreshLogs = (intervalMs = 5000) => {
    stopAutoRefreshLogs();
    refreshTimer = window.setInterval(fetchLogs, intervalMs);
};

export const stopAutoRefreshLogs = () => {
    if (refreshTimer) {
        window.clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

const bindLogControls = () => {
    const clearButton = document.querySelector('[data-b2e-clear-logs]');
    clearButton?.addEventListener('click', clearLogs);
};

const hydrateInitialLogs = () => {
    const initialLogs = getInitialData('logs', []);
    renderLogs(initialLogs);
};

export const initLogs = () => {
    hydrateInitialLogs();
    bindLogControls();
};
