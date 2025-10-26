import { initUI, showToast } from './ui.js';
import { bindSettings } from './settings.js';
import { bindValidation } from './validation.js';
import {
    startMigration,
    cancelMigration,
} from './migration.js';
import { initLogs, startAutoRefreshLogs, stopAutoRefreshLogs } from './logs.js';
import { serializeForm } from './api.js';
import { init as initTemplateExtractor } from './template-extractor.js';

const bindMigrationForm = () => {
    const form = document.querySelector('[data-efs-migration-form]');
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = serializeForm(form);
        try {
            await startMigration(payload);
            startProgressPolling();
            startAutoRefreshLogs();
        } catch (error) {
            console.error('Start migration failed', error);
            showToast(error.message, 'error');
        }
    });

    document.querySelectorAll('[data-efs-cancel-migration]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await cancelMigration();
                stopProgressPolling();
                stopAutoRefreshLogs();
            } catch (error) {
                console.error('Cancel migration failed', error);
                showToast(error.message, 'error');
            }
        });
    });
};

const bootstrap = () => {
    initUI();
    bindSettings();
    bindValidation();
    bindMigrationForm();
    initLogs();
    initTemplateExtractor();

    const progress = window.efsData?.progress_data;
    const localizedMigrationId = window.efsData?.migrationId || progress?.migrationId;
    if (progress && !progress.completed) {
        const { percentage = 0, status } = progress;
        const isRunning = Boolean(localizedMigrationId) || (percentage > 0 && (!status || status !== 'completed'));
        if (isRunning) {
            startProgressPolling({ migrationId: localizedMigrationId });
            startAutoRefreshLogs();
        }
    }
};

document.addEventListener('DOMContentLoaded', bootstrap);
