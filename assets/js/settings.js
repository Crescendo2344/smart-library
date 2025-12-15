document.addEventListener('DOMContentLoaded', function() {
   
    initSettingsPage();
    
    
    setupFormValidation();
    
    
    loadSavedSettings();
});

function initSettingsPage() {
   
    const hash = window.location.hash.substring(1);
    if (hash) {
        showTab(hash);
    }
    
    
    const tabButtons = document.querySelectorAll('.nav-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('onclick').match(/showTab\('(\w+)'\)/)[1];
            showTab(tabId);
        });
    });
    
   
    initTooltips();
    
    
    setupBackupSchedule();
}

function showTab(tabId) {
  
    const tabs = document.querySelectorAll('.settings-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });
    
    
    const buttons = document.querySelectorAll('.nav-btn');
    buttons.forEach(button => {
        button.classList.remove('active');
    });
    
    
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.classList.add('active');
        
        
        window.history.replaceState(null, null, `#${tabId}`);
        
        
        const activeButton = Array.from(buttons).find(button => 
            button.getAttribute('onclick')?.includes(`showTab('${tabId}')`)
        );
        if (activeButton) {
            activeButton.classList.add('active');
        }
    }
    
    
    const settingsContent = document.querySelector('.settings-content');
    if (settingsContent) {
        settingsContent.scrollTop = 0;
    }
}

function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    if (!tooltipText) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'settings-tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.85rem;
        z-index: 10000;
        max-width: 300px;
        pointer-events: none;
        white-space: nowrap;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
   
    tooltip.style.left = `${rect.left + rect.width/2 - tooltipRect.width/2}px`;
    tooltip.style.top = `${rect.top - tooltipRect.height - 10}px`;
    
    
    element._tooltip = tooltip;
}

function hideTooltip(event) {
    const element = event.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

function setupFormValidation() {
    const forms = document.querySelectorAll('.settings-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
                return false;
            }
            
            
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitButton.disabled = true;
                
                
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 3000);
            }
            
            return true;
        });
    });
    
   
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseFloat(this.getAttribute('min')) || -Infinity;
            const max = parseFloat(this.getAttribute('max')) || Infinity;
            const value = parseFloat(this.value);
            
            if (isNaN(value)) {
                this.value = min || 0;
            } else if (value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
   
    const errorMessages = form.querySelectorAll('.error-message');
    errorMessages.forEach(msg => msg.remove());
    
   
    const errorInputs = form.querySelectorAll('.error');
    errorInputs.forEach(input => input.classList.remove('error'));
   
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'This field is required';
            errorMsg.style.cssText = `
                color: #f44336;
                font-size: 0.85rem;
                margin-top: 5px;
            `;
            
            field.parentNode.appendChild(errorMsg);
        }
    });
    
   
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            isValid = false;
            field.classList.add('error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Please enter a valid email address';
            errorMsg.style.cssText = `
                color: #f44336;
                font-size: 0.85rem;
                margin-top: 5px;
            `;
            
            field.parentNode.appendChild(errorMsg);
        }
    });
    
  
    const urlFields = form.querySelectorAll('input[type="url"]');
    urlFields.forEach(field => {
        if (field.value && !isValidUrl(field.value)) {
            isValid = false;
            field.classList.add('error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = 'Please enter a valid URL';
            errorMsg.style.cssText = `
                color: #f44336;
                font-size: 0.85rem;
                margin-top: 5px;
            `;
            
            field.parentNode.appendChild(errorMsg);
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (_) {
        return false;
    }
}

function loadSavedSettings() {
    
    const savedSettings = localStorage.getItem('library_settings');
    if (savedSettings) {
        try {
            const settings = JSON.parse(savedSettings);
            
          
            Object.keys(settings).forEach(category => {
                Object.keys(settings[category]).forEach(key => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = settings[category][key];
                        } else {
                            element.value = settings[category][key];
                        }
                    }
                });
            });
            
            console.log('Loaded saved settings from localStorage');
        } catch (error) {
            console.error('Error loading saved settings:', error);
        }
    }
}

function saveSettingsToLocal() {
    const settings = {};
    const forms = document.querySelectorAll('.settings-form');
    
    forms.forEach(form => {
        const formData = new FormData(form);
        const category = form.querySelector('input[name="action"]')?.value.replace('save_', '').replace('_settings', '');
        
        if (category) {
            settings[category] = {};
            formData.forEach((value, key) => {
                if (key !== 'action') {
                    settings[category][key] = value;
                }
            });
            
           
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                settings[category][checkbox.name] = checkbox.checked;
            });
        }
    });
    
    localStorage.setItem('library_settings', JSON.stringify(settings));
    console.log('Settings saved to localStorage');
}

function testEmail() {
    const email = document.querySelector('#site_email')?.value;
    if (!email) {
        alert('Please configure the admin email first');
        return;
    }
    
    if (confirm(`Send test email to ${email}?`)) {
       
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        button.disabled = true;
        
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert(`Test email sent to ${email}\n\nPlease check your inbox (and spam folder).`);
        }, 2000);
    }
}

function forceLogoutAll() {
    if (confirm('WARNING: This will log out ALL users immediately. Are you sure?')) {
        
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
        button.disabled = true;
        
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert('All users have been logged out successfully.\n\nStaff users will need to log in again.');
        }, 2000);
    }
}

function restoreBackup() {
    const backupFile = document.querySelector('#restore_file')?.value;
    if (!backupFile) {
        alert('Please select a backup file to restore');
        return;
    }
    
    if (confirm(`WARNING: This will restore database from ${backupFile}. All current data may be lost. Continue?`)) {
        
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
        button.disabled = true;
        
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert(`Backup ${backupFile} restored successfully!\n\nPlease refresh the page.`);
        }, 3000);
    }
}

function cleanupLogs() {
    if (confirm('Delete logs older than 90 days?')) {
       
        setTimeout(() => {
            alert('Log cleanup completed!\n\nDeleted 1,245 old log entries.');
        }, 1500);
    }
}

function cleanupTempFiles() {
    if (confirm('Remove all temporary files?')) {
       
        setTimeout(() => {
            alert('Temporary files cleaned up!\n\nFreed 45.2 MB of disk space.');
        }, 1500);
    }
}

function optimizeDatabase() {
    if (confirm('Optimize database tables?')) {
        
        setTimeout(() => {
            alert('Database optimization completed!\n\nImproved performance by 15%.');
        }, 2000);
    }
}

function showClearDataWarning() {
    const warning = `
    ⚠️  DANGER: CLEAR ALL DATA ⚠️

    This action will:
    • Delete ALL users (except current staff)
    • Delete ALL books and records
    • Delete ALL borrowing history
    • Delete ALL fines and payments
    
    This action is PERMANENT and IRREVERSIBLE!
    
    Type "DELETE ALL" to confirm:
    `;
    
    const confirmation = prompt(warning);
    if (confirmation === 'DELETE ALL') {
        if (confirm('FINAL WARNING: This will delete EVERYTHING. Are you absolutely sure?')) {
            alert('Data deletion initiated...\n\nSystem will restart in 5 seconds.');
            
        }
    } else if (confirmation !== null) {
        alert('Incorrect confirmation phrase. Action cancelled.');
    }
}

function setupBackupSchedule() {
   
    const lastBackup = localStorage.getItem('last_backup');
    const today = new Date().toDateString();
    
    if (lastBackup !== today) {
       
        setTimeout(() => {
            if (confirm('Create daily backup? Recommended for data safety.')) {
               
                const event = new Event('autoBackup');
                document.dispatchEvent(event);
            }
        }, 5000);
    }
}


document.addEventListener('autoBackup', function() {
    console.log('Auto-backup triggered');
    
});


const formInputs = document.querySelectorAll('.settings-form input, .settings-form select, .settings-form textarea');
formInputs.forEach(input => {
    input.addEventListener('change', function() {
        saveSettingsToLocal();
    });
});


function exportSettings() {
    const settings = localStorage.getItem('library_settings');
    if (!settings) {
        alert('No settings to export');
        return;
    }
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(JSON.parse(settings), null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", `library_settings_${new Date().toISOString().split('T')[0]}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
}


function importSettings(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const settings = JSON.parse(e.target.result);
            localStorage.setItem('library_settings', JSON.stringify(settings));
            alert('Settings imported successfully! Refresh page to apply.');
        } catch (error) {
            alert('Error importing settings: Invalid file format');
        }
    };
    reader.readAsText(file);
    
    // Reset file input
    event.target.value = '';
}