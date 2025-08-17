/**
 * Sistema de Push Notifications
 * Portal de Notícias
 */

class PushNotificationManager {
    constructor() {
        this.swRegistration = null;
        this.isSubscribed = false;
        this.applicationServerKey = null;
        this.apiBaseUrl = 'http://localhost:8001';
        
        this.init();
    }

    /**
     * Inicializar o sistema de push notifications
     */
    async init() {
        try {
            // Verificar suporte do navegador
            if (!this.checkBrowserSupport()) {
                console.warn('Push notifications não são suportadas neste navegador');
                return;
            }

            // Registrar Service Worker
            await this.registerServiceWorker();
            
            // Obter chave pública VAPID
            await this.getVapidKey();
            
            // Verificar status da subscription
            await this.checkSubscriptionStatus();
            
            // Configurar UI
            this.setupUI();
            
        } catch (error) {
            console.error('Erro ao inicializar push notifications:', error);
        }
    }

    /**
     * Verificar suporte do navegador
     */
    checkBrowserSupport() {
        return 'serviceWorker' in navigator && 
               'PushManager' in window && 
               'Notification' in window;
    }

    /**
     * Registrar Service Worker
     */
    async registerServiceWorker() {
        try {
            this.swRegistration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registrado:', this.swRegistration);
            
            // Escutar mensagens do Service Worker
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleServiceWorkerMessage(event.data);
            });
            
        } catch (error) {
            console.error('Erro ao registrar Service Worker:', error);
            throw error;
        }
    }

    /**
     * Obter chave pública VAPID
     */
    async getVapidKey() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/push/vapid-key`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.applicationServerKey = this.urlBase64ToUint8Array(data.publicKey);
            } else {
                throw new Error('Erro ao obter chave VAPID');
            }
            
        } catch (error) {
            console.error('Erro ao obter chave VAPID:', error);
            throw error;
        }
    }

    /**
     * Verificar status da subscription
     */
    async checkSubscriptionStatus() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            this.isSubscribed = subscription !== null;
            
            if (this.isSubscribed) {
                console.log('Usuário já está inscrito para push notifications');
            }
            
        } catch (error) {
            console.error('Erro ao verificar subscription:', error);
        }
    }

    /**
     * Configurar interface do usuário
     */
    setupUI() {
        // Botão de ativar/desativar push notifications
        const pushToggle = document.getElementById('push-notifications-toggle');
        if (pushToggle) {
            pushToggle.checked = this.isSubscribed;
            pushToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.subscribeToPush();
                } else {
                    this.unsubscribeFromPush();
                }
            });
        }

        // Botão de teste
        const testButton = document.getElementById('test-push-notification');
        if (testButton) {
            testButton.addEventListener('click', () => {
                this.sendTestNotification();
            });
        }

        // Checkboxes de preferências
        const preferenceCheckboxes = document.querySelectorAll('[id^="push-"]');
        preferenceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updatePreferences();
            });
        });
    }

    /**
     * Inscrever usuário para push notifications
     */
    async subscribeToPush() {
        try {
            // Solicitar permissão
            const permission = await Notification.requestPermission();
            
            if (permission !== 'granted') {
                console.warn('Permissão para notificações negada');
                this.updateToggleState(false);
                return;
            }

            // Criar subscription
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.applicationServerKey
            });

            // Enviar subscription para o servidor
            await this.sendSubscriptionToServer(subscription, 'subscribe');
            
            this.isSubscribed = true;
            console.log('Usuário inscrito para push notifications');
            
            // Mostrar mensagem de sucesso
            this.showNotification('Push notifications ativadas com sucesso!', 'success');
            
        } catch (error) {
            console.error('Erro ao inscrever para push notifications:', error);
            this.updateToggleState(false);
            this.showNotification('Erro ao ativar push notifications', 'error');
        }
    }

    /**
     * Cancelar inscrição de push notifications
     */
    async unsubscribeFromPush() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            
            if (subscription) {
                // Cancelar subscription no navegador
                await subscription.unsubscribe();
                
                // Notificar o servidor
                await this.sendSubscriptionToServer(subscription, 'unsubscribe');
            }
            
            this.isSubscribed = false;
            console.log('Usuário desinscrito de push notifications');
            
            this.showNotification('Push notifications desativadas', 'info');
            
        } catch (error) {
            console.error('Erro ao cancelar inscrição:', error);
            this.updateToggleState(true);
            this.showNotification('Erro ao desativar push notifications', 'error');
        }
    }

    /**
     * Enviar subscription para o servidor
     */
    async sendSubscriptionToServer(subscription, action) {
        try {
            const subscriptionData = {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                    auth: this.arrayBufferToBase64(subscription.getKey('auth'))
                },
                userAgent: navigator.userAgent
            };

            const response = await fetch(`${this.apiBaseUrl}/push/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify(subscriptionData)
            });

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const result = await response.json();
            console.log(`Subscription ${action} enviada:`, result);
            
        } catch (error) {
            console.error(`Erro ao enviar subscription (${action}):`, error);
            throw error;
        }
    }

    /**
     * Enviar notificação de teste
     */
    async sendTestNotification() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/push/test`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.showNotification('Notificação de teste enviada!', 'success');
                console.log('Teste enviado:', result);
            } else {
                throw new Error('Erro ao enviar teste');
            }
            
        } catch (error) {
            console.error('Erro ao enviar teste:', error);
            this.showNotification('Erro ao enviar notificação de teste', 'error');
        }
    }

    /**
     * Atualizar preferências de notificação
     */
    async updatePreferences() {
        try {
            const preferences = {
                push_breaking: document.getElementById('push-breaking')?.checked || false,
                push_interests: document.getElementById('push-interests')?.checked || false,
                push_comments: document.getElementById('push-comments')?.checked || false,
                push_newsletter: document.getElementById('push-newsletter')?.checked || false,
                push_system: document.getElementById('push-system')?.checked !== false, // Default true
                categorias_interesse: this.getSelectedCategories()
            };

            const response = await fetch(`${this.apiBaseUrl}/push/preferences`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify(preferences)
            });

            if (response.ok) {
                console.log('Preferências atualizadas');
            } else {
                throw new Error('Erro ao atualizar preferências');
            }
            
        } catch (error) {
            console.error('Erro ao atualizar preferências:', error);
        }
    }

    /**
     * Obter categorias selecionadas
     */
    getSelectedCategories() {
        const categoryCheckboxes = document.querySelectorAll('[name="categoria_interesse"]:checked');
        return Array.from(categoryCheckboxes).map(cb => parseInt(cb.value));
    }

    /**
     * Lidar com mensagens do Service Worker
     */
    handleServiceWorkerMessage(data) {
        switch (data.type) {
            case 'notification-click':
                console.log('Notificação clicada:', data.notificationId);
                this.registerNotificationClick(data.notificationId);
                break;
            case 'notification-close':
                console.log('Notificação fechada:', data.notificationId);
                this.registerNotificationClose(data.notificationId);
                break;
            case 'sync-notifications':
                this.syncPendingNotifications();
                break;
        }
    }

    /**
     * Registrar clique na notificação
     */
    async registerNotificationClick(notificationId) {
        try {
            await fetch(`${this.apiBaseUrl}/push/click`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify({ id: notificationId })
            });
        } catch (error) {
            console.error('Erro ao registrar clique:', error);
        }
    }

    /**
     * Registrar fechamento da notificação
     */
    async registerNotificationClose(notificationId) {
        try {
            await fetch(`${this.apiBaseUrl}/push/close`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify({ id: notificationId })
            });
        } catch (error) {
            console.error('Erro ao registrar fechamento:', error);
        }
    }

    /**
     * Sincronizar notificações pendentes
     */
    async syncPendingNotifications() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/push/sync`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                console.log('Sincronização concluída:', result);
            }
        } catch (error) {
            console.error('Erro na sincronização:', error);
        }
    }

    /**
     * Atualizar estado do toggle
     */
    updateToggleState(isEnabled) {
        const toggle = document.getElementById('push-notifications-toggle');
        if (toggle) {
            toggle.checked = isEnabled;
        }
        this.isSubscribed = isEnabled;
    }

    /**
     * Mostrar notificação na interface
     */
    showNotification(message, type = 'info') {
        // Implementar sistema de notificações da UI
        // Por enquanto, usar alert simples
        if (type === 'error') {
            console.error(message);
        } else {
            console.log(message);
        }
        
        // Tentar usar sistema de toast se existir
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }

    /**
     * Obter token de autenticação
     */
    getAuthToken() {
        return localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '';
    }

    /**
     * Converter URL Base64 para Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Converter ArrayBuffer para Base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    /**
     * Obter estatísticas de push notifications
     */
    async getStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/push/stats`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Erro ao obter estatísticas:', error);
        }
        return null;
    }

    /**
     * Método público para enviar notificação personalizada
     */
    async sendCustomNotification(title, body, options = {}) {
        try {
            const payload = {
                title,
                body,
                type: options.type || 'system',
                url: options.url,
                icon: options.icon,
                image: options.image,
                userId: options.userId,
                categoryId: options.categoryId
            };

            const response = await fetch(`${this.apiBaseUrl}/push/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                const result = await response.json();
                console.log('Notificação enviada:', result);
                return result;
            } else {
                throw new Error('Erro ao enviar notificação');
            }
        } catch (error) {
            console.error('Erro ao enviar notificação personalizada:', error);
            throw error;
        }
    }
}

// Inicializar quando o DOM estiver carregado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pushManager = new PushNotificationManager();
    });
} else {
    window.pushManager = new PushNotificationManager();
}

// Exportar para uso global
window.PushNotificationManager = PushNotificationManager;