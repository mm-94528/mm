// App JavaScript
const App = {
    // CSRF Token
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
    
    // Base URL
    baseUrl: window.location.origin,
    
    // Initialize
    init() {
        this.setupAjax();
        this.setupToasts();
        this.setupModals();
        this.setupForms();
        this.setupTables();
    },
    
    // Setup AJAX defaults
    setupAjax() {
        // Add CSRF token to all AJAX requests
        const originalFetch = window.fetch;
        window.fetch = (url, options = {}) => {
            options.headers = {
                ...options.headers,
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            };
            return originalFetch(url, options);
        };
    },
    
    // Toast notifications
    toast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        }[type] || 'ℹ';
        
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    // Setup toast notifications
    setupToasts() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
    },
    
    // Modal management
    modal(content, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        
        if (options.title) {
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h3>${options.title}</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">${content}</div>
            `;
        } else {
            modalContent.innerHTML = content;
        }
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Show modal
        setTimeout(() => modal.classList.add('active'), 10);
        
        // Close handlers
        const close = () => {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        };
        
        modal.querySelector('.modal-close')?.addEventListener('click', close);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        
        return { modal, close };
    },
    
    // Setup modals
    setupModals() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal]')) {
                e.preventDefault();
                const url = e.target.dataset.modal;
                this.loadModal(url);
            }
        });
    },
    
    // Load modal content via AJAX
    async loadModal(url) {
        try {
            const response = await fetch(url);
            const html = await response.text();
            this.modal(html);
        } catch (error) {
            this.toast('Errore nel caricamento', 'error');
        }
    },
    
    // Setup AJAX forms
    setupForms() {
        document.addEventListener('submit', async (e) => {
            if (e.target.matches('[data-ajax]')) {
                e.preventDefault();
                await this.submitForm(e.target);
            }
        });
    },
    
    // Submit form via AJAX
    async submitForm(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn?.textContent;
        
        try {
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner spinner-primary"></span> Invio...';
            }
            
            // Prepare form data
            const formData = new FormData(form);
            const method = form.method || 'POST';
            const action = form.action || window.location.href;
            
            // Handle method override
            if (formData.has('_method')) {
                const overrideMethod = formData.get('_method');
                formData.delete('_method');
            }
            
            // Send request
            const response = await fetch(action, {
                method: method,
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.toast(data.message || 'Operazione completata', 'success');
                
                // Handle redirect
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                }
                
                // Handle callback
                if (form.dataset.callback) {
                    window[form.dataset.callback](data);
                }
                
                // Reset form if specified
                if (form.dataset.reset === 'true') {
                    form.reset();
                }
            } else {
                this.toast(data.message || 'Si è verificato un errore', 'error');
                
                // Show validation errors
                if (data.errors) {
                    this.showValidationErrors(form, data.errors);
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.toast('Errore di connessione', 'error');
        } finally {
            // Restore button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    },
    
    // Show validation errors on form
    showValidationErrors(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        
        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = errors[field][0];
                input.parentNode.appendChild(feedback);
            }
        });
    },
    
    // Setup AJAX tables
    setupTables() {
        // Delete buttons
        document.addEventListener('click', async (e) => {
            if (e.target.matches('[data-delete]')) {
                e.preventDefault();
                
                if (confirm('Sei sicuro di voler eliminare questo elemento?')) {
                    const url = e.target.dataset.delete;
                    await this.delete(url);
                }
            }
        });
        
        // Edit buttons
        document.addEventListener('click', async (e) => {
            if (e.target.matches('[data-edit]')) {
                e.preventDefault();
                const url = e.target.dataset.edit;
                await this.loadModal(url);
            }
        });
    },
    
    // Delete request
    async delete(url) {
        try {
            const response = await fetch(url, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.toast(data.message || 'Eliminato con successo', 'success');
                
                // Reload table or remove row
                if (data.reload) {
                    window.location.reload();
                }
            } else {
                this.toast(data.message || 'Errore durante l\'eliminazione', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.toast('Errore di connessione', 'error');
        }
    },
    
    // Load content via AJAX
    async loadContent(url, target) {
        try {
            const response = await fetch(url);
            const html = await response.text();
            document.querySelector(target).innerHTML = html;
        } catch (error) {
            console.error('Load content error:', error);
            this.toast('Errore nel caricamento', 'error');
        }
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Export for use in other scripts
window.App = App;