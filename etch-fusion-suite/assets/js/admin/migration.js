import { post } from './api.js';
import { showToast, updateProgress } from './ui.js';

const ACTION_START_MIGRATION = 'efs_start_migration';
const ACTION_GET_PROGRESS = 'efs_get_migration_progress';
const ACTION_PROCESS_BATCH = 'efs_migrate_batch';
const ACTION_CANCEL_MIGRATION = 'efs_cancel_migration';

let pollTimer = null;
let activeMigrationId = window.efsData?.migrationId || null;

const setActiveMigrationId = (migrationId) => {
    if (!migrationId) {
        activeMigrationId = null;
        if (window.efsData) {
            delete window.efsData.migrationId;
        }
        return;
    }

    activeMigrationId = migrationId;
    window.efsData = {
        ...(window.efsData || {}),
        migrationId,
    };
};

const getActiveMigrationId = () => activeMigrationId || window.efsData?.migrationId || null;

const requestProgress = async (params = {}) => {
    const migrationId = params?.migrationId || getActiveMigrationId();
    if (!migrationId) {
        return {};
    }

    const data = await post(ACTION_GET_PROGRESS, {
        ...params,
        migrationId,
    });

    if (data?.migrationId) {
        setActiveMigrationId(data.migrationId);
    }

    const progress = data?.progress || {};
    updateProgress({
        percentage: progress.percentage || 0,
        status: progress.status || progress.current_step || '',
        steps: data?.steps || progress.steps || [],
    });
    if (data?.completed) {
        showToast('Migration completed successfully.', 'success');
        stopProgressPolling();
        setActiveMigrationId(null);
    }
    return data;
};

export const startMigration = async (payload) => {
    const data = await post(ACTION_START_MIGRATION, payload);
    if (data?.migrationId) {
        setActiveMigrationId(data.migrationId);
    }

    showToast(data?.message || 'Migration started.', 'success');
    updateProgress({
        percentage: data?.progress?.percentage || 0,
        status: data?.progress?.status || '',
        steps: data?.steps || [],
    });
    startProgressPolling({ migrationId: getActiveMigrationId() });
    return data;
};

export const processBatch = async (payload) => {
    const migrationId = payload?.migrationId || getActiveMigrationId();
    const data = await post(ACTION_PROCESS_BATCH, {
        ...payload,
        migrationId,
    });

    if (data?.migrationId) {
        setActiveMigrationId(data.migrationId);
    }

    const progress = data?.progress || {};
    updateProgress({
        percentage: progress.percentage || 0,
        status: progress.status || progress.current_step || '',
        steps: data?.steps || progress.steps || [],
    });
    if (data?.completed) {
        showToast('Migration completed successfully.', 'success');
        stopProgressPolling();
        setActiveMigrationId(null);
    }
    return data;
};

export const cancelMigration = async (payload) => {
    const migrationId = payload?.migrationId || getActiveMigrationId();
    const data = await post(ACTION_CANCEL_MIGRATION, {
        ...payload,
        migrationId,
    });
    showToast(data?.message || 'Migration cancelled.', 'info');
    stopProgressPolling();
    setActiveMigrationId(null);
    return data;
};

export const startProgressPolling = (params = {}, intervalMs = 3000) => {
    stopProgressPolling();
    const migrationId = params?.migrationId || getActiveMigrationId();
    if (!migrationId) {
        return;
    }
    const pollParams = {
        ...params,
        migrationId,
    };
    const poll = async () => {
        try {
            await requestProgress(pollParams);
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
