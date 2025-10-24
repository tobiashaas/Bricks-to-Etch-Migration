import { initUI, showToast } from './ui.js';
import { startMigration, processBatch, cancelMigrationPolling } from './migration.js';
import { validateApiKey, validateMigrationToken } from './validation.js';
import { saveSettings, testConnection, generateMigrationKey, copyKeyToClipboard, loadSettings } from './settings.js';
import { autoRefreshLogs, stopAutoRefreshLogs, clearLogs, fetchLogs } from './logs.js';

const bindMigrationForm = () => {
    const form = document.querySelector('[data-b2e-migration-form]');
    if (!form) {
        return;
    }
    const startButton = form.querySelector('[data-b2e-start-migration]');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        startButton?.setAttribute('disabled', 'disabled');
        try {
            await startMigration(form);
            showToast('Migration started.', 'success');
        } catch (error) {
            showToast(error.message || 'Unable to start migration.', 'error');
            startButton?.removeAttribute('disabled');
        }
    });

    document.querySelectorAll('[data-b2e-migration-batch]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.setAttribute('disabled', 'disabled');
            try {
                const payload = button.dataset.b2eMigrationBatch ? JSON.parse(button.dataset.b2eMigrationBatch) : {};
                await processBatch(payload);
            } catch (error) {
                showToast(error.message || 'Batch failed.', 'error');
            } finally {
                button.removeAttribute('disabled');
            }
        });
    });

    const cancelButton = document.querySelector('[data-b2e-cancel-migration]');
    cancelButton?.addEventListener('click', () => {
        cancelMigrationPolling();
        showToast('Migration cancelled.', 'warning');
        startButton?.removeAttribute('disabled');
    });
};

const bindValidationForms = () => {
    document.querySelectorAll('[data-b2e-validate-api]').forEach((form) => {
        const field = form.querySelector('[name="api_key"]');
        const submit = form.querySelector('[type="submit"]');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await validateApiKey({ field, submitButton: submit });
            } catch (error) {
                // error handled in validation module
            }
        });
    });

    document.querySelectorAll('[data-b2e-validate-token]').forEach((form) => {
        const field = form.querySelector('[name="migration_token"]');
        const submit = form.querySelector('[type="submit"]');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await validateMigrationToken({ field, submitButton: submit });
            } catch (error) {
                // error handled in validation module
            }
        });
    });
};

const bindSettingsForms = () => {
    const saveForm = document.querySelector('[data-b2e-settings-form]');
    if (saveForm) {
        saveForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = saveForm.querySelector('[type="submit"]');
            try {
                await saveSettings(saveForm, submit);
            } catch (error) {
                // notification handled in module
            }
        });
    }

    document.querySelectorAll('[data-b2e-test-connection]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            try {
                await testConnection(form, submit);
            } catch (error) {
                // handled in module
            }
        });
    });

    document.querySelectorAll('[data-b2e-generate-key]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            try {
                await generateMigrationKey(form, submit);
            } catch (error) {
                // handled in module
            }
        });
    });

    document.querySelectorAll('[data-b2e-copy-button]').forEach((button) => {
        button.addEventListener('click', async () => {
            const selector = button.getAttribute('data-b2e-target');
            if (!selector) {
                return;
            }
            const target = document.querySelector(selector);
            if (!target) {
                return;
            }
            try {
                await copyKeyToClipboard(target);
            } catch (error) {
                // handled in module
            }
        });
    });
};

const bindLogs = () => {
    const clearButton = document.querySelector('[data-b2e-clear-logs]');
    if (clearButton) {
        clearButton.addEventListener('click', async () => {
            clearButton.setAttribute('disabled', 'disabled');
            try {
                await clearLogs();
            } catch (error) {
                // handled in module
            } finally {
                clearButton.removeAttribute('disabled');
            }
        });
    }

    const logsContainer = document.querySelector('[data-b2e-logs]');
    if (logsContainer) {
        autoRefreshLogs();
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoRefreshLogs();
            } else {
                autoRefreshLogs();
            }
        });
    }

    document.addEventListener('b2e:migration-complete', () => {
        fetchLogs();
    });
};

const bootstrap = () => {
    initUI();
    loadSettings();
    bindMigrationForm();
    bindValidationForms();
    bindSettingsForms();
    bindLogs();
};

document.addEventListener('DOMContentLoaded', bootstrap);
