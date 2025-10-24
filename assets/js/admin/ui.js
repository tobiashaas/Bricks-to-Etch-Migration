const TOAST_DEFAULT_DURATION = 5000;
let toastContainer = null;

const ensureToastContainer = () => {
    if (toastContainer && document.body.contains(toastContainer)) {
        return toastContainer;
    }
    toastContainer = document.createElement('div');
    toastContainer.className = 'b2e-toast-container';
    document.body.appendChild(toastContainer);
    return toastContainer;
};

export const showToast = (message, type = 'info', duration = TOAST_DEFAULT_DURATION) => {
    if (!message) {
        return null;
    }
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `b2e-toast b2e-toast--${type}`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.textContent = message;

    container.appendChild(toast);
    window.requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    const removeToast = () => {
        toast.classList.remove('is-visible');
        toast.addEventListener('transitionend', () => {
            toast.remove();
        }, { once: true });
    };

    if (duration > 0) {
        window.setTimeout(removeToast, duration);
    }

    toast.addEventListener('click', removeToast);
    return toast;
};

export const updateProgressBar = (percentage = 0) => {
    const progress = Math.min(Math.max(Number(percentage) || 0, 0), 100);
    const progressEl = document.querySelector('[data-b2e-progress]');
    if (!progressEl) {
        return;
    }
    progressEl.style.setProperty('--b2e-progress', `${progress}%`);
    progressEl.setAttribute('data-b2e-progress-value', progress.toFixed(0));
};

export const toggleSection = (sectionId, force) => {
    if (!sectionId) {
        return;
    }
    const section = document.getElementById(sectionId);
    if (!section) {
        return;
    }
    const isExpanded = force !== undefined ? Boolean(force) : !section.classList.contains('is-expanded');
    section.classList.toggle('is-expanded', isExpanded);
};

export const showLoadingState = (element) => {
    if (!element) {
        return;
    }
    element.classList.add('is-loading');
    element.toggleAttribute('disabled', true);
};

export const hideLoadingState = (element) => {
    if (!element) {
        return;
    }
    element.classList.remove('is-loading');
    element.toggleAttribute('disabled', false);
};

export const bindDismissibleSections = () => {
    document.querySelectorAll('[data-b2e-toggle]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetId = trigger.getAttribute('data-b2e-toggle');
            toggleSection(targetId);
        });
    });
};

export const bindCopyToClipboard = () => {
    document.querySelectorAll('[data-b2e-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = button.getAttribute('data-b2e-copy');
            const input = document.querySelector(target);
            if (!input) {
                return;
            }
            try {
                await navigator.clipboard.writeText(input.value || input.textContent || '');
                showToast(button.dataset.toastSuccess || 'Copied to clipboard.', 'success');
            } catch (error) {
                showToast(button.dataset.toastError || 'Unable to copy to clipboard.', 'error');
            }
        });
    });
};

export const initUI = () => {
    bindDismissibleSections();
    bindCopyToClipboard();
};

document.addEventListener('b2e:migration-progress', (event) => {
    const detail = event.detail || {};
    if (detail.percentage !== undefined) {
        updateProgressBar(detail.percentage);
    }
    if (detail.status) {
        const current = document.querySelector('[data-b2e-current-step]');
        if (current) {
            current.textContent = detail.status;
        }
    }
});

document.addEventListener('b2e:migration-complete', (event) => {
    const detail = event.detail || {};
    if (detail.message) {
        showToast(detail.message, 'success');
    }
    updateProgressBar(100);
});

document.addEventListener('b2e:migration-error', (event) => {
    const detail = event.detail || {};
    if (detail.message) {
        showToast(detail.message, 'error');
    }
});
