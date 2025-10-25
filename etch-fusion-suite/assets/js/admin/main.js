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
    const form = document.querySelector('[data-b2e-migration-form]');
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = serializeForm(form);
        try {
            await startMigration(payload);
            startProgressPolling({ migrationId: payload.migrationId });
            startAutoRefreshLogs();
        } catch (error) {
            console.error('Start migration failed', error);
            showToast(error.message, 'error');
        }
    });

    document.querySelectorAll('[data-b2e-cancel-migration]').forEach((button) => {
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

    const progress = window.b2eData?.progress_data;
    if (progress && !progress.completed) {
        const { migrationId, percentage = 0, status } = progress;
        const isRunning = Boolean(migrationId) || (percentage > 0 && (!status || status !== 'completed'));
        if (isRunning) {
            startProgressPolling({ migrationId });
            startAutoRefreshLogs();
        }
    }
};

document.addEventListener('DOMContentLoaded', bootstrap);
