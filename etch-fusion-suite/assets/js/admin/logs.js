import { post, getInitialData } from './api.js';
import { showToast } from './ui.js';

const ACTION_FETCH_LOGS = 'efs_get_logs';
const ACTION_CLEAR_LOGS = 'efs_clear_logs';

let refreshTimer = null;

const formatTimestamp = (timestamp) => {
    if (!timestamp) {
        return '';
    }
    const date = new Date(timestamp);
    if (Number.isNaN(date.getTime())) {
        return timestamp;
    }
    return date.toLocaleString();
};

const formatContextValue = (value) => {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch (error) {
            return String(value);
        }
    }
    return String(value);
};

const renderLogs = (logs = []) => {
    const container = document.querySelector('[data-efs-logs]');
    if (!container) {
        return;
    }
    container.innerHTML = '';
    if (!Array.isArray(logs) || logs.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'efs-empty-state';
        empty.textContent = 'No logs yet. Migration activity will appear here.';
        container.appendChild(empty);
        return;
    }
    logs.forEach((log) => {
        const severity = log.severity || 'info';
        const article = document.createElement('article');
        article.className = `efs-log-entry efs-log-entry--${severity}`;

        const header = document.createElement('header');
        header.className = 'efs-log-entry__header';

        const meta = document.createElement('div');
        meta.className = 'efs-log-entry__meta';

        if (log.timestamp) {
            const time = document.createElement('time');
            time.dateTime = log.timestamp;
            time.textContent = formatTimestamp(log.timestamp);
            meta.appendChild(time);
        }

        if (log.event_type || log.code) {
            const code = document.createElement('span');
            code.className = 'efs-log-entry__code';
            code.textContent = log.event_type || log.code;
            meta.appendChild(code);
        }

        header.appendChild(meta);

        const badge = document.createElement('span');
        badge.className = `efs-log-entry__badge efs-log-entry__badge--${severity}`;
        badge.textContent = (severity.charAt(0).toUpperCase() + severity.slice(1)).replace(/_/g, ' ');
        header.appendChild(badge);

        article.appendChild(header);

        if (log.message) {
            const message = document.createElement('p');
            message.className = 'efs-log-entry__message';
            message.textContent = log.message;
            article.appendChild(message);
        }

        if (log.context && typeof log.context === 'object' && Object.keys(log.context).length > 0) {
            const contextList = document.createElement('dl');
            contextList.className = 'efs-log-entry__context';

            Object.entries(log.context).forEach(([key, value]) => {
                const item = document.createElement('div');
                item.className = 'efs-log-entry__context-item';

                const term = document.createElement('dt');
                term.textContent = key;

                const description = document.createElement('dd');
                description.textContent = formatContextValue(value);

                item.appendChild(term);
                item.appendChild(description);
                contextList.appendChild(item);
            });

            article.appendChild(contextList);
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
    const clearButton = document.querySelector('[data-efs-clear-logs]');
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
