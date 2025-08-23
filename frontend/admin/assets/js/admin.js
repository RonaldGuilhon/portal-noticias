/**
 * Admin Panel JavaScript
 * Funcionalidades específicas do painel administrativo
 */

class AdminPanel {
    constructor() {
        this.apiBase = 'http://localhost:8001';
        const userData = localStorage.getItem('portal-user');
        this.authToken = null;
        if (userData) {
            try {
                const user = JSON.parse(userData);
                this.authToken = user.token;
            } catch (error) {
                console.error('Erro ao obter token:', error);
            }
        }
        this.userData = JSON.parse(localStorage.getItem('portal-user') || '{}');
        this.notifications = [];
        this.charts = {};
    }
    
    async init() {
        this.setupEventListeners();
        await this.checkAuthentication();
        this.loadNotifications();
    }
    
    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar.bind(this));
        }
        
        // Mobile sidebar handling
        this.setupMobileSidebar();
        
        // Form submissions
        this.setupFormHandlers();
        
        // Real-time updates
        this.setupRealTimeUpdates();
        
        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }
    
    async checkAuthentication() {
        if (!this.authToken || (this.userData.tipo !== 'admin' && this.userData.tipo !== 'editor')) {
            this.redirectToLogin();
            return false;
        }
        
        // Verificar se o token ainda é válido no servidor
        try {
            const response = await fetch('http://localhost:8001/auth/check-auth', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                // Token inválido ou expirado
                console.log('Token inválido ou expirado, redirecionando para login');
                localStorage.removeItem('portal-user');
                this.redirectToLogin();
                return false;
            }
            
            const result = await response.json();
            if (!result.logado || !['admin', 'editor'].includes(result.usuario?.tipo)) {
                console.log('Usuário não autorizado, redirecionando para login');
                localStorage.removeItem('portal-user');
                this.redirectToLogin();
                return false;
            }
            
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
            // Em caso de erro de rede, não redirecionar imediatamente
            // mas mostrar um aviso
            this.showNetworkError();
        }
        
        // Update user info in header
        this.updateUserInfo();
        return true;
    }
    
    redirectToLogin() {
        const currentPath = window.location.pathname;
        window.location.href = `../login.html?admin=1&redirect=${encodeURIComponent(currentPath)}`;
    }
    
    updateUserInfo() {
        const adminName = document.getElementById('admin-name');
        const adminAvatar = document.getElementById('admin-avatar');
        
        if (adminName && this.userData.nome) {
            adminName.textContent = this.userData.nome;
        }
        
        if (adminAvatar && this.userData.avatar) {
            adminAvatar.src = this.userData.avatar;
        }
    }
    
    showNetworkError() {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-warning alert-dismissible fade show';
        errorDiv.style.position = 'fixed';
        errorDiv.style.top = '20px';
        errorDiv.style.right = '20px';
        errorDiv.style.zIndex = '9999';
        errorDiv.innerHTML = `
            <strong>Aviso:</strong> Não foi possível verificar a autenticação. Verifique sua conexão.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(errorDiv);
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
    
    toggleSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        }
    }
    
    setupMobileSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        
        // Add overlay to body
        document.body.appendChild(overlay);
        
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle && window.innerWidth <= 992) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    }
    
    setupFormHandlers() {
        // Generic form submission handler
        document.addEventListener('submit', async (e) => {
            const form = e.target;
            if (form.classList.contains('admin-form')) {
                e.preventDefault();
                await this.handleFormSubmission(form);
            }
        });
        
        // File upload handlers
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.classList.contains('admin-file-input')) {
                this.handleFileUpload(e.target);
            }
        });
    }
    
    async handleFormSubmission(form) {
        const formData = new FormData(form);
        const action = form.getAttribute('data-action');
        const method = form.getAttribute('data-method') || 'POST';
        
        try {
            this.showLoading(true);
            
            const response = await fetch(`${this.apiBase}/${action}`, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Operação realizada com sucesso!', 'success');
                
                // Redirect if specified
                const redirect = form.getAttribute('data-redirect');
                if (redirect) {
                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 1500);
                }
                
                // Reload data if specified
                const reload = form.getAttribute('data-reload');
                if (reload) {
                    this.reloadData(reload);
                }
            } else {
                this.showNotification(result.message || 'Erro ao processar solicitação', 'error');
            }
        } catch (error) {
            console.error('Erro no formulário:', error);
            this.showNotification('Erro de conexão', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async handleFileUpload(input) {
        const files = input.files;
        if (!files.length) return;
        
        const formData = new FormData();
        for (let file of files) {
            formData.append('files[]', file);
        }
        
        try {
            this.showLoading(true);
            
            const response = await fetch(`${this.apiBase}/upload`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Upload realizado com sucesso!', 'success');
                
                // Trigger custom event for file upload success
                const event = new CustomEvent('fileUploaded', {
                    detail: { files: result.data }
                });
                document.dispatchEvent(event);
            } else {
                this.showNotification(result.message || 'Erro no upload', 'error');
            }
        } catch (error) {
            console.error('Erro no upload:', error);
            this.showNotification('Erro de conexão', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadNotifications() {
        try {
            const response = await fetch(`${this.apiBase}/notificacoes`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.notifications = result.data || [];
                this.updateNotificationUI();
            }
        } catch (error) {
            console.error('Erro ao carregar notificações:', error);
        }
    }
    
    updateNotificationUI() {
        const countElement = document.getElementById('notification-count');
        const listElement = document.getElementById('notifications-list');
        
        if (countElement) {
            const unreadCount = this.notifications.filter(n => !n.lida).length;
            countElement.textContent = unreadCount;
            countElement.style.display = unreadCount > 0 ? 'block' : 'none';
        }
        
        if (listElement) {
            if (this.notifications.length === 0) {
                listElement.innerHTML = '<li><span class="dropdown-item-text text-muted">Nenhuma notificação</span></li>';
            } else {
                listElement.innerHTML = this.notifications.slice(0, 5).map(notification => `
                    <li>
                        <a class="dropdown-item ${!notification.lida ? 'fw-bold' : ''}" href="#" 
                           onclick="adminPanel.markNotificationAsRead(${notification.id})">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-${this.getNotificationIcon(notification.tipo)} me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="notification-title">${notification.titulo}</div>
                                    <small class="text-muted">${this.formatTimeAgo(notification.data_criacao)}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                `).join('');
            }
        }
    }
    
    getNotificationIcon(tipo) {
        const icons = {
            'comentario': 'comment',
            'usuario': 'user',
            'noticia': 'newspaper',
            'sistema': 'cog',
            'erro': 'exclamation-triangle'
        };
        return icons[tipo] || 'bell';
    }
    
    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch(`${this.apiBase}/notificacoes/${notificationId}/read`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            if (response.ok) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.lida = true;
                    this.updateNotificationUI();
                }
            }
        } catch (error) {
            console.error('Erro ao marcar notificação como lida:', error);
        }
    }
    
    setupRealTimeUpdates() {
        // Update notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
        
        // Update dashboard stats every 5 minutes
        if (window.location.pathname.includes('index.html') || window.location.pathname.endsWith('/admin/')) {
            setInterval(() => {
                this.updateDashboardStats();
            }, 300000);
        }
    }
    
    async updateDashboardStats() {
        try {
            const response = await fetch(`${this.apiBase}/admin/dashboard/stats`, {
                headers: {
                    'Authorization': `Bearer ${this.authToken}`
                }
            });
            
            const result = await response.json();
            
            if (result.success && typeof updateDashboardStats === 'function') {
                updateDashboardStats(result.data);
            }
        } catch (error) {
            console.error('Erro ao atualizar estatísticas:', error);
        }
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.focusSearch();
            }
            
            // Ctrl/Cmd + N for new item
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.createNewItem();
            }
            
            // Escape to close modals/dropdowns
            if (e.key === 'Escape') {
                this.closeModals();
            }
        });
    }
    
    focusSearch() {
        const searchInput = document.querySelector('input[type="search"], .search-input');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    createNewItem() {
        const currentPage = window.location.pathname;
        
        if (currentPage.includes('noticias')) {
            window.location.href = 'noticias.html?action=create';
        } else if (currentPage.includes('categorias')) {
            window.location.href = 'categorias.html?action=create';
        } else if (currentPage.includes('tags')) {
            window.location.href = 'tags.html?action=create';
        }
    }
    
    closeModals() {
        // Close Bootstrap modals
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
        
        // Close dropdowns
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
    
    showLoading(show = true) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.toggle('show', show);
        }
    }
    
    showNotification(message, type = 'info', duration = 5000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${this.getAlertClass(type)} alert-dismissible fade show notification-toast`;
        notification.innerHTML = `
            <i class="fas fa-${this.getNotificationTypeIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add styles for positioning
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
    }
    
    getAlertClass(type) {
        const classes = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return classes[type] || 'info';
    }
    
    getNotificationTypeIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    async reloadData(dataType) {
        switch (dataType) {
            case 'dashboard':
                if (typeof loadDashboardData === 'function') {
                    await loadDashboardData();
                }
                break;
            case 'notifications':
                await this.loadNotifications();
                break;
            default:
                window.location.reload();
        }
    }
    
    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (days > 0) return `${days}d atrás`;
        if (hours > 0) return `${hours}h atrás`;
        if (minutes > 0) return `${minutes}m atrás`;
        return 'Agora';
    }
    
    // Utility methods for API calls
    async apiGet(endpoint) {
        const response = await fetch(`${this.apiBase}/${endpoint}`, {
            headers: {
                'Authorization': `Bearer ${this.authToken}`
            }
        });
        return response.json();
    }
    
    async apiPost(endpoint, data) {
        const response = await fetch(`${this.apiBase}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.authToken}`
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
    
    async apiPut(endpoint, data) {
        const response = await fetch(`${this.apiBase}/${endpoint}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.authToken}`
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
    
    async apiDelete(endpoint) {
        const response = await fetch(`${this.apiBase}/${endpoint}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${this.authToken}`
            }
        });
        return response.json();
    }
}

// Utility functions for admin panel
const AdminUtils = {
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    },
    
    formatDate(dateString, format = 'short') {
        const date = new Date(dateString);
        
        if (format === 'short') {
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } else if (format === 'long') {
            return date.toLocaleDateString('pt-BR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } else if (format === 'datetime') {
            return date.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        return date.toLocaleDateString('pt-BR');
    },
    
    truncateText(text, maxLength = 100) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    },
    
    generateSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
    },
    
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    validateURL(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                adminPanel.showNotification('Copiado para a área de transferência!', 'success', 2000);
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            adminPanel.showNotification('Copiado para a área de transferência!', 'success', 2000);
        }
    },
    
    downloadFile(url, filename) {
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    },
    
    confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
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

// Initialize admin panel when DOM is loaded
let adminPanel;
document.addEventListener('DOMContentLoaded', async function() {
    adminPanel = new AdminPanel();
    await adminPanel.init();
    
    // Restore sidebar state
    const sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    const sidebar = document.getElementById('admin-sidebar');
    if (sidebar && sidebarCollapsed) {
        sidebar.classList.add('collapsed');
    }
});

// Export for global access
window.AdminPanel = AdminPanel;
window.AdminUtils = AdminUtils;