/**
 * Admin JavaScript for Bricks to Etch Migration Plugin
 * Modern Vanilla JavaScript - No jQuery Dependencies
 */

'use strict';

// Global variables
let migrationInProgress = false;
let progressInterval = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initEventHandlers();
    checkMigrationStatus();
});

/**
 * Initialize event handlers
 */
function initEventHandlers() {
    // Tab navigation
    const tabButtons = document.querySelectorAll('.b2e-tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });
    
    // Export tab handlers
    const generateExportBtn = document.getElementById('generate-export-api-key');
    if (generateExportBtn) {
        generateExportBtn.addEventListener('click', generateExportApiKey);
    }
    
    const copyExportBtn = document.getElementById('copy-export-api-key');
    if (copyExportBtn) {
        copyExportBtn.addEventListener('click', copyExportApiKeyToClipboard);
    }
    
    const testExportBtn = document.getElementById('test-export-connection');
    if (testExportBtn) {
        testExportBtn.addEventListener('click', testExportConnection);
    }
    
    const exportForm = document.getElementById('b2e-export-form');
    if (exportForm) {
        exportForm.addEventListener('submit', startExport);
    }
    
    // Import tab handlers
    const testImportBtn = document.getElementById('test-import-connection');
    if (testImportBtn) {
        testImportBtn.addEventListener('click', testImportConnection);
    }
    
    const saveImportBtn = document.getElementById('save-import-settings');
    if (saveImportBtn) {
        saveImportBtn.addEventListener('click', saveImportSettings);
    }
    
    // Clear logs
    const clearLogsBtn = document.getElementById('clear-logs');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', clearLogs);
    }
}

/**
 * Switch between tabs
 */
function switchTab(tabName) {
    // Update tab buttons
    const tabButtons = document.querySelectorAll('.b2e-tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Update tab content
    const tabContents = document.querySelectorAll('.b2e-tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
    });
    
    const activeContent = document.getElementById(`${tabName}-tab`);
    if (activeContent) {
        activeContent.classList.add('active');
    }
}

/**
 * Generate new API key for export
 */
function generateExportApiKey() {
    const button = this;
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Generating...';
    
    // Generate a secure random API key
    const apiKey = 'b2e_' + generateSecureRandomString(32);
    
    // Update the input field
    const apiKeyInput = document.getElementById('export_api_key');
    if (apiKeyInput) {
        apiKeyInput.value = apiKey;
    }
    
    // Show the generated key display
    const keyDisplay = document.getElementById('export-api-key-value');
    const keyContainer = document.getElementById('export-generated-key-display');
    
    if (keyDisplay) {
        keyDisplay.textContent = apiKey;
    }
    
    if (keyContainer) {
        keyContainer.style.display = 'block';
    }
    
    button.disabled = false;
    button.textContent = originalText;
    
    // Show success message
    showNotice('API key generated successfully! Copy it to your target site.', 'success');
}

/**
 * Copy export API key to clipboard
 */
function copyExportApiKeyToClipboard() {
    const keyDisplay = document.getElementById('export-api-key-value');
    if (!keyDisplay) {
        showNotice('No API key to copy. Please generate one first.', 'error');
        return;
    }
    
    const apiKey = keyDisplay.textContent;
    if (!apiKey) {
        showNotice('No API key to copy. Please generate one first.', 'error');
        return;
    }
    
    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(apiKey).then(function() {
            showNotice('API key copied to clipboard! Paste it in your target site.', 'success');
        }).catch(function() {
            fallbackCopyToClipboard(apiKey);
        });
    } else {
        fallbackCopyToClipboard(apiKey);
    }
}

/**
 * Fallback copy to clipboard for older browsers
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showNotice('API key copied to clipboard!', 'success');
    } catch (err) {
        showNotice('Failed to copy API key. Please copy manually.', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Generate secure random string
 */
function generateSecureRandomString(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    
    // Use crypto.getRandomValues if available (more secure)
    if (window.crypto && window.crypto.getRandomValues) {
        const array = new Uint8Array(length);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < length; i++) {
            result += chars[array[i] % chars.length];
        }
    } else {
        // Fallback to Math.random
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
    }
    
    return result;
}

/**
 * Test export connection to target site
 */
function testExportConnection() {
    const button = this;
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Testing...';
    
    const targetUrl = document.getElementById('target_url').value;
    const apiKey = document.getElementById('export_api_key').value;
    
    if (!targetUrl || !apiKey) {
        showNotice('Please enter both target URL and API key.', 'error');
        button.disabled = false;
        button.textContent = originalText;
        return;
    }
    
    // Test API connection
    fetch(targetUrl + '/wp-json/b2e/v1/auth/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            api_key: apiKey
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            showNotice('Connection successful! Target site is ready for migration.', 'success');
            updateValidationResults(data);
        } else {
            showNotice('Connection failed. Please check your settings.', 'error');
        }
    })
    .catch(error => {
        let message = 'Connection failed. ';
        if (error.status === 401) {
            message += 'Invalid API key. Make sure you copied it correctly.';
        } else if (error.status === 404) {
            message += 'Plugin not found on target site.';
        } else {
            message += 'Please check the target URL.';
        }
        showNotice(message, 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

/**
 * Test import connection (validate API key on this site)
 */
function testImportConnection() {
    const button = this;
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Testing...';
    
    const apiKey = document.getElementById('import_api_key').value;
    
    if (!apiKey) {
        showNotice('Please enter an API key.', 'error');
        button.disabled = false;
        button.textContent = originalText;
        return;
    }
    
    // Test API key validation
    const formData = new FormData();
    formData.append('action', 'b2e_validate_import_key');
    formData.append('api_key', apiKey);
    formData.append('nonce', b2eData.nonce);
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotice('API key is valid! This site is ready to receive data.', 'success');
        } else {
            showNotice('Invalid API key. Please check the key from your source site.', 'error');
        }
    })
    .catch(error => {
        showNotice('Connection test failed. Please try again.', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

/**
 * Start export/migration
 */
function startExport(e) {
    e.preventDefault();
    
    if (migrationInProgress) {
        showNotice('Migration is already in progress.', 'warning');
        return;
    }
    
    if (!confirm('Are you sure you want to start the migration? This will send data to your target site.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('target_url', document.getElementById('target_url').value);
    formData.append('api_key', document.getElementById('export_api_key').value);
    formData.append('cleanup_bricks_meta', document.getElementById('cleanup_bricks_meta').checked);
    formData.append('convert_div_to_flex', document.getElementById('convert_div_to_flex').checked);
    formData.append('action', 'b2e_start_migration');
    formData.append('nonce', b2eData.nonce);
    
    const targetUrl = document.getElementById('target_url').value;
    const apiKey = document.getElementById('export_api_key').value;
    
    if (!targetUrl || !apiKey) {
        showNotice('Please enter both target URL and API key.', 'error');
        return;
    }
    
    migrationInProgress = true;
    const startBtn = document.getElementById('start-export');
    startBtn.disabled = true;
    startBtn.textContent = 'Starting...';
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotice('Migration started successfully!', 'success');
            showProgressSection();
            startProgressTracking();
        } else {
            showNotice(data.data || 'An error occurred.', 'error');
        }
    })
    .catch(error => {
        showNotice('An error occurred while starting the migration.', 'error');
    })
    .finally(() => {
        startBtn.disabled = false;
        startBtn.textContent = 'Start Export/Migration';
        migrationInProgress = false;
    });
}

/**
 * Save import settings
 */
function saveImportSettings() {
    const button = this;
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Saving...';
    
    const formData = new FormData();
    formData.append('import_api_key', document.getElementById('import_api_key').value);
    formData.append('import_auto_accept', document.getElementById('import_auto_accept').checked);
    formData.append('action', 'b2e_save_import_settings');
    formData.append('nonce', b2eData.nonce);
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotice('Import settings saved successfully!', 'success');
        } else {
            showNotice('Failed to save settings.', 'error');
        }
    })
    .catch(error => {
        showNotice('An error occurred while saving settings.', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
    });
}

/**
 * Check migration status on page load
 */
function checkMigrationStatus() {
    const formData = new FormData();
    formData.append('action', 'b2e_get_progress');
    formData.append('nonce', b2eData.nonce);
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.status === 'running') {
            showProgressSection();
            startProgressTracking();
        }
    })
    .catch(error => {
        console.error('Error checking migration status:', error);
    });
}

/**
 * Start progress tracking
 */
function startProgressTracking() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    progressInterval = setInterval(function() {
        updateProgress();
    }, 2000); // Update every 2 seconds
}

/**
 * Update progress
 */
function updateProgress() {
    const formData = new FormData();
    formData.append('action', 'b2e_get_progress');
    formData.append('nonce', b2eData.nonce);
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const progress = data.data;
            updateProgressDisplay(progress);
            
            if (progress.status === 'completed' || progress.status === 'error') {
                clearInterval(progressInterval);
                progressInterval = null;
                
                if (progress.status === 'completed') {
                    showNotice('Migration completed successfully!', 'success');
                } else {
                    showNotice('Migration failed. Please check the logs.', 'error');
                }
            }
        }
    })
    .catch(error => {
        console.error('Error updating progress:', error);
    });
}

/**
 * Update progress display
 */
function updateProgressDisplay(progress) {
    const percentage = progress.percentage || 0;
    const currentStep = progress.current_step || '';
    
    const progressFill = document.querySelector('.b2e-progress-fill');
    const progressText = document.querySelector('#progress-text strong');
    const currentStepSpan = document.querySelector('#current-step');
    
    if (progressFill) {
        progressFill.style.width = percentage + '%';
    }
    
    if (progressText) {
        progressText.textContent = percentage + '%';
    }
    
    if (currentStepSpan) {
        currentStepSpan.textContent = currentStep;
    }
    
    // Update step indicators
    const stepElements = document.querySelectorAll('[data-step]');
    stepElements.forEach(element => {
        element.classList.remove('active', 'completed');
    });
    
    const steps = ['validation', 'cpts', 'acf_field_groups', 'metabox_configs', 'css_classes', 'posts', 'finalization'];
    const currentStepIndex = steps.indexOf(currentStep);
    
    steps.forEach(function(step, index) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        if (stepElement) {
            if (index < currentStepIndex) {
                stepElement.classList.add('completed');
            } else if (index === currentStepIndex) {
                stepElement.classList.add('active');
            }
        }
    });
}

/**
 * Show progress section
 */
function showProgressSection() {
    const progressSection = document.getElementById('progress-section');
    const progressDetails = document.getElementById('progress-details');
    
    if (progressSection) {
        progressSection.style.display = 'block';
    }
    
    if (progressDetails) {
        progressDetails.style.display = 'block';
    }
}

/**
 * Update validation results
 */
function updateValidationResults(data) {
    const validationResults = document.getElementById('validation-results');
    if (!validationResults) return;
    
    let html = '<div class="validation-success">';
    html += '<h4>✅ Connection Successful</h4>';
    html += '<ul>';
    
    if (data.plugins) {
        html += '<li><strong>Plugins Detected:</strong></li>';
        html += '<ul>';
        if (data.plugins.bricks) html += '<li>✅ Bricks Builder</li>';
        if (data.plugins.etch) html += '<li>✅ Etch PageBuilder</li>';
        if (data.plugins.acf) html += '<li>✅ Advanced Custom Fields</li>';
        if (data.plugins.metabox) html += '<li>✅ MetaBox</li>';
        if (data.plugins.jetengine) html += '<li>✅ JetEngine</li>';
        html += '</ul>';
    }
    
    html += '</ul>';
    html += '</div>';
    
    validationResults.innerHTML = html;
}

/**
 * Clear logs
 */
function clearLogs() {
    if (!confirm('Are you sure you want to clear all logs?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'b2e_clear_logs');
    formData.append('nonce', b2eData.nonce);
    
    fetch(b2eData.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const recentLogs = document.getElementById('recent-logs');
            if (recentLogs) {
                recentLogs.innerHTML = '<p>No recent activity.</p>';
            }
            showNotice('Logs cleared successfully.', 'success');
        }
    })
    .catch(error => {
        showNotice('Failed to clear logs.', 'error');
    });
}

/**
 * Show notice
 */
function showNotice(message, type) {
    const noticeClass = type === 'error' ? 'notice-error' : 
                       type === 'warning' ? 'notice-warning' : 'notice-success';
    
    const notice = document.createElement('div');
    notice.className = `notice ${noticeClass} is-dismissible`;
    notice.innerHTML = `<p>${message}</p>`;
    
    const wrap = document.querySelector('.wrap h1');
    if (wrap && wrap.parentNode) {
        wrap.parentNode.insertBefore(notice, wrap.nextSibling);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        if (notice.parentNode) {
            notice.style.opacity = '0';
            notice.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                if (notice.parentNode) {
                    notice.parentNode.removeChild(notice);
                }
            }, 300);
        }
    }, 5000);
}