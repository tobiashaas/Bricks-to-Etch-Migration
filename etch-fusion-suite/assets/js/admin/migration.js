import { post } from './api.js';
import { showToast, updateProgress } from './ui.js';

const ACTION_START_MIGRATION = 'b2e_start_migration';
const ACTION_GET_PROGRESS = 'b2e_get_migration_progress';
const ACTION_PROCESS_BATCH = 'b2e_migrate_batch';
const ACTION_CANCEL_MIGRATION = 'b2e_cancel_migration';

let pollTimer = null;

const requestProgress = async (params = {}) => {
    const data = await post(ACTION_GET_PROGRESS, params);
    const progress = data?.progress || {};
    updateProgress({
        percentage: progress.percentage || 0,
        status: progress.status || progress.current_step || '',
        steps: data?.steps || progress.steps || [],
    });
    if (data?.completed) {
        showToast('Migration completed successfully.', 'success');
        stopProgressPolling();
    }
    return data;
};

export const startMigration = async (payload) => {
    const data = await post(ACTION_START_MIGRATION, payload);
    showToast(data?.message || 'Migration started.', 'success');
    updateProgress({
        percentage: data?.progress?.percentage || 0,
        status: data?.progress?.status || '',
        steps: data?.steps || [],
    });
    startProgressPolling({ migrationId: data?.migrationId });
    return data;
};

export const processBatch = async (payload) => {
    const data = await post(ACTION_PROCESS_BATCH, payload);
    const progress = data?.progress || {};
    updateProgress({
        percentage: progress.percentage || 0,
        status: progress.status || progress.current_step || '',
        steps: data?.steps || progress.steps || [],
    });
    if (data?.completed) {
        showToast('Migration completed successfully.', 'success');
        stopProgressPolling();
    }
    return data;
};

export const cancelMigration = async (payload) => {
    const data = await post(ACTION_CANCEL_MIGRATION, payload);
    showToast(data?.message || 'Migration cancelled.', 'info');
    stopProgressPolling();
    return data;
};

export const startProgressPolling = (params = {}, intervalMs = 3000) => {
    stopProgressPolling();
    const poll = async () => {
        try {
            await requestProgress(params);
        } catch (error) {
            console.error('Progress polling failed', error);
            showToast(error.message, 'error');
        }
    };
    pollTimer = window.setInterval(poll, intervalMs);
    poll();
};

export const stopProgressPolling = () => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
};
