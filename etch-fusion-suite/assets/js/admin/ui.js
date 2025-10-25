import { getInitialData } from './api.js';

const TOAST_VISIBLE_CLASS = 'is-visible';
const TOAST_CONTAINER_CLASS = 'b2e-toast-container';

const ensureToastContainer = () => {
    let container = document.querySelector(`.${TOAST_CONTAINER_CLASS}`);
    if (!container) {
        container = document.createElement('div');
        container.className = TOAST_CONTAINER_CLASS;
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('role', 'status');
        document.body.appendChild(container);
    }
    return container;
};

export const showToast = (message, type = 'info', { duration = 4000 } = {}) => {
    if (!message) {
        return null;
    }
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `b2e-toast b2e-toast--${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    window.requestAnimationFrame(() => {
        toast.classList.add(TOAST_VISIBLE_CLASS);
    });

    const hide = () => {
        toast.classList.remove(TOAST_VISIBLE_CLASS);
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    };

    if (duration > 0) {
        window.setTimeout(hide, duration);
    }

    toast.addEventListener('click', hide, { once: true });
    return toast;
};

const bindCopyButtons = () => {
    document.querySelectorAll('[data-b2e-copy-button], [data-b2e-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const selector = button.getAttribute('data-b2e-target') || button.getAttribute('data-b2e-copy');
            const successMessage = button.getAttribute('data-toast-success') || 'Copied to clipboard.';
            if (!selector) {
                return;
            }
            const target = document.querySelector(selector);
            if (!target) {
                return;
            }
            const value = target.value || target.textContent || '';
            try {
                await navigator.clipboard.writeText(value);
                showToast(successMessage, 'success');
            } catch (error) {
                console.error('Clipboard copy failed', error);
                showToast('Unable to copy to clipboard.', 'error');
            }
        });
    });
};

export const setLoading = (element, isLoading) => {
    if (!element) {
        return;
    }
    element.toggleAttribute('aria-busy', Boolean(isLoading));
    element.disabled = Boolean(isLoading);
};

export const updateProgress = ({ percentage = 0, status = '', steps = [] }) => {
    const progressRoot = document.querySelector('[data-b2e-progress]');
    const progressFill = progressRoot?.querySelector('.b2e-progress-fill');
    const currentStep = document.querySelector('[data-b2e-current-step]');
    const stepsList = document.querySelector('[data-b2e-steps]');

    progressRoot?.setAttribute('aria-valuenow', String(percentage));
    if (progressFill) {
        progressFill.style.width = `${percentage}%`;
    }
    if (currentStep) {
        currentStep.textContent = status || '';
    }
    if (stepsList) {
        stepsList.innerHTML = '';
        steps.forEach((step) => {
            const li = document.createElement('li');
            li.className = 'b2e-migration-step';
            if (step.completed) {
                li.classList.add('is-complete');
            }
            if (step.active) {
                li.classList.add('is-active');
            }
            li.textContent = step.label || step.slug || '';
            stepsList.appendChild(li);
        });
    }
};

const syncInitialProgress = () => {
    const progress = getInitialData('progress_data');
    if (!progress) {
        return;
    }
    updateProgress({
        percentage: progress.percentage || 0,
        status: progress.current_step || progress.status || '',
        steps: progress.steps || [],
    });
};

export const initUI = () => {
    bindCopyButtons();
    syncInitialProgress();
};
