/**
 * Gerenciador de Redes Sociais
 * Portal de Notícias
 */

class SocialMediaManager {
    constructor() {
        this.apiUrl = '/backend/controllers/SocialController.php';
        this.providers = {
            facebook: {
                name: 'Facebook',
                icon: 'fab fa-facebook-f',
                color: '#1877f2'
            },
            google: {
                name: 'Google',
                icon: 'fab fa-google',
                color: '#db4437'
            },
            twitter: {
                name: 'Twitter',
                icon: 'fab fa-twitter',
                color: '#1da1f2'
            },
            linkedin: {
                name: 'LinkedIn',
                icon: 'fab fa-linkedin-in',
                color: '#0077b5'
            }
        };
        this.init();
    }
    
    init() {
        this.loadProviders();
        this.setupEventListeners();
        this.loadUserConnections();
    }
    
    /**
     * Carregar providers disponíveis
     */
    async loadProviders() {
        try {
            const response = await fetch(`${this.apiUrl}?action=providers`);
            const data = await response.json();
            
            if (data.success) {
                this.availableProviders = data.providers;
                this.renderProviders();
            }
        } catch (error) {
            console.error('Erro ao carregar providers:', error);
        }
    }
    
    /**
     * Renderizar providers disponíveis
     */
    renderProviders() {
        const container = document.getElementById('social-providers');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.keys(this.availableProviders).forEach(providerId => {
            const provider = this.availableProviders[providerId];
            const providerInfo = this.providers[providerId];
            
            if (!providerInfo) return;
            
            const providerElement = document.createElement('div');
            providerElement.className = 'social-provider';
            providerElement.innerHTML = `
                <div class="provider-card ${provider.configured ? 'configured' : 'not-configured'}">
                    <div class="provider-icon" style="color: ${providerInfo.color}">
                        <i class="${providerInfo.icon}"></i>
                    </div>
                    <div class="provider-info">
                        <h5>${provider.name}</h5>
                        <p class="provider-status">
                            ${provider.configured ? 'Configurado' : 'Não configurado'}
                        </p>
                        <div class="provider-features">
                            ${provider.features.map(feature => `
                                <span class="feature-badge">${this.getFeatureName(feature)}</span>
                            `).join('')}
                        </div>
                    </div>
                    <div class="provider-actions">
                        ${provider.configured ? `
                            <button class="btn btn-sm btn-primary" onclick="socialManager.connectProvider('${providerId}')">
                                <i class="fas fa-link"></i> Conectar
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="socialManager.testProvider('${providerId}')">
                                <i class="fas fa-vial"></i> Testar
                            </button>
                        ` : `
                            <span class="text-muted">Configuração necessária</span>
                        `}
                    </div>
                </div>
            `;
            
            container.appendChild(providerElement);
        });
    }
    
    /**
     * Obter nome da funcionalidade
     */
    getFeatureName(feature) {
        const features = {
            'login': 'Login',
            'share': 'Compartilhar',
            'stats': 'Estatísticas'
        };
        return features[feature] || feature;
    }
    
    /**
     * Conectar com provider
     */
    async connectProvider(provider) {
        try {
            const response = await fetch(`${this.apiUrl}?action=auth-url&provider=${provider}`);
            const data = await response.json();
            
            if (data.success) {
                // Abrir popup para autenticação
                const popup = window.open(
                    data.auth_url,
                    'social-auth',
                    'width=600,height=600,scrollbars=yes,resizable=yes'
                );
                
                // Monitorar o popup
                this.monitorAuthPopup(popup, provider);
            } else {
                this.showError(data.error);
            }
        } catch (error) {
            console.error('Erro ao conectar provider:', error);
            this.showError('Erro ao conectar com ' + provider);
        }
    }
    
    /**
     * Monitorar popup de autenticação
     */
    monitorAuthPopup(popup, provider) {
        const checkClosed = setInterval(() => {
            if (popup.closed) {
                clearInterval(checkClosed);
                // Recarregar conexões do usuário
                setTimeout(() => {
                    this.loadUserConnections();
                }, 1000);
            }
        }, 1000);
        
        // Timeout após 5 minutos
        setTimeout(() => {
            if (!popup.closed) {
                popup.close();
                clearInterval(checkClosed);
            }
        }, 300000);
    }
    
    /**
     * Carregar conexões do usuário
     */
    async loadUserConnections() {
        try {
            const token = localStorage.getItem('auth_token');
            if (!token) return;
            
            const response = await fetch(`${this.apiUrl}?action=user-connections`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.userConnections = data.connections;
                this.renderUserConnections();
            }
        } catch (error) {
            console.error('Erro ao carregar conexões:', error);
        }
    }
    
    /**
     * Renderizar conexões do usuário
     */
    renderUserConnections() {
        const container = document.getElementById('user-connections');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!this.userConnections || this.userConnections.length === 0) {
            container.innerHTML = `
                <div class="no-connections">
                    <i class="fas fa-unlink fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma conta conectada</p>
                </div>
            `;
            return;
        }
        
        this.userConnections.forEach(connection => {
            const providerInfo = this.providers[connection.provider];
            if (!providerInfo) return;
            
            const connectionElement = document.createElement('div');
            connectionElement.className = 'connection-card';
            connectionElement.innerHTML = `
                <div class="connection-info">
                    <div class="connection-icon" style="color: ${providerInfo.color}">
                        <i class="${providerInfo.icon}"></i>
                    </div>
                    <div class="connection-details">
                        <h6>${providerInfo.name}</h6>
                        <small class="text-muted">
                            Conectado em ${this.formatDate(connection.created_at)}
                        </small>
                    </div>
                </div>
                <div class="connection-actions">
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="socialManager.disconnectProvider('${connection.provider}')">
                        <i class="fas fa-unlink"></i> Desconectar
                    </button>
                </div>
            `;
            
            container.appendChild(connectionElement);
        });
    }
    
    /**
     * Desconectar provider
     */
    async disconnectProvider(provider) {
        if (!confirm(`Deseja desconectar sua conta do ${this.providers[provider].name}?`)) {
            return;
        }
        
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch(`${this.apiUrl}?action=disconnect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ provider })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`Conta do ${this.providers[provider].name} desconectada com sucesso`);
                this.loadUserConnections();
            } else {
                this.showError(data.error);
            }
        } catch (error) {
            console.error('Erro ao desconectar provider:', error);
            this.showError('Erro ao desconectar conta');
        }
    }
    
    /**
     * Compartilhar conteúdo
     */
    async shareContent(provider, content) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch(`${this.apiUrl}?action=share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ provider, content })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`Conteúdo compartilhado no ${this.providers[provider].name}`);
                return data.result;
            } else {
                this.showError(data.error);
                return null;
            }
        } catch (error) {
            console.error('Erro ao compartilhar:', error);
            this.showError('Erro ao compartilhar conteúdo');
            return null;
        }
    }
    
    /**
     * Compartilhamento em lote
     */
    async bulkShare(providers, content) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch(`${this.apiUrl}?action=bulk-share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ providers, content })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`Conteúdo compartilhado em ${data.total_success} rede(s) social(is)`);
                if (data.total_errors > 0) {
                    this.showWarning(`${data.total_errors} compartilhamento(s) falharam`);
                }
                return data;
            } else {
                this.showError('Erro no compartilhamento em lote');
                return null;
            }
        } catch (error) {
            console.error('Erro no compartilhamento em lote:', error);
            this.showError('Erro no compartilhamento em lote');
            return null;
        }
    }
    
    /**
     * Obter estatísticas de compartilhamento
     */
    async getShareStats(url) {
        try {
            const response = await fetch(`${this.apiUrl}?action=share-stats&url=${encodeURIComponent(url)}`);
            const data = await response.json();
            
            if (data.success) {
                return data.stats;
            }
            return null;
        } catch (error) {
            console.error('Erro ao obter estatísticas:', error);
            return null;
        }
    }
    
    /**
     * Configurar listeners de eventos
     */
    setupEventListeners() {
        // Botões de compartilhamento rápido
        document.addEventListener('click', (e) => {
            if (e.target.matches('.share-btn')) {
                const provider = e.target.dataset.provider;
                const contentId = e.target.dataset.contentId;
                const contentType = e.target.dataset.contentType;
                
                this.showShareModal(provider, contentId, contentType);
            }
        });
        
        // Formulário de compartilhamento
        const shareForm = document.getElementById('share-form');
        if (shareForm) {
            shareForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleShareForm(e.target);
            });
        }
    }
    
    /**
     * Mostrar modal de compartilhamento
     */
    showShareModal(provider, contentId, contentType) {
        const modal = document.getElementById('shareModal');
        if (!modal) return;
        
        // Preencher dados do modal
        modal.querySelector('#share-provider').value = provider;
        modal.querySelector('#share-content-id').value = contentId;
        modal.querySelector('#share-content-type').value = contentType;
        
        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    /**
     * Processar formulário de compartilhamento
     */
    async handleShareForm(form) {
        const formData = new FormData(form);
        const provider = formData.get('provider');
        const message = formData.get('message');
        const contentId = formData.get('content_id');
        const contentType = formData.get('content_type');
        
        const content = {
            message,
            type: contentType,
            id: contentId,
            url: window.location.href
        };
        
        const result = await this.shareContent(provider, content);
        
        if (result) {
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('shareModal'));
            modal.hide();
            
            // Limpar formulário
            form.reset();
        }
    }
    
    /**
     * Utilitários
     */
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('pt-BR');
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showWarning(message) {
        this.showToast(message, 'warning');
    }
    
    showToast(message, type = 'info') {
        // Implementar sistema de toast/notificação
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Inicializar gerenciador quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.socialManager = new SocialMediaManager();
});

// Funções globais para compatibilidade
function shareToSocial(provider, contentId, contentType) {
    if (window.socialManager) {
        window.socialManager.showShareModal(provider, contentId, contentType);
    }
}

function connectSocialAccount(provider) {
    if (window.socialManager) {
        window.socialManager.connectProvider(provider);
    }
}

function disconnectSocialAccount(provider) {
    if (window.socialManager) {
        window.socialManager.disconnectProvider(provider);
    }
}