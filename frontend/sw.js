// =============================================
// Service Worker para Push Notifications
// Portal de Notícias
// =============================================

const CACHE_NAME = 'portal-noticias-v1';
const API_BASE = 'http://localhost:8001';

// Instalar Service Worker
self.addEventListener('install', event => {
    console.log('Service Worker instalado');
    self.skipWaiting();
});

// Ativar Service Worker
self.addEventListener('activate', event => {
    console.log('Service Worker ativado');
    event.waitUntil(self.clients.claim());
});

// Escutar push notifications
self.addEventListener('push', event => {
    console.log('Push notification recebida:', event);
    
    if (!event.data) {
        console.log('Push notification sem dados');
        return;
    }
    
    let data;
    try {
        data = event.data.json();
    } catch (e) {
        console.error('Erro ao parsear dados da push notification:', e);
        data = {
            title: 'Portal de Notícias',
            body: event.data.text() || 'Nova notificação',
            icon: '/assets/img/icon-192x192.png',
            badge: '/assets/img/badge-72x72.png'
        };
    }
    
    const options = {
        body: data.body || data.message,
        icon: data.icon || '/assets/img/icon-192x192.png',
        badge: data.badge || '/assets/img/badge-72x72.png',
        image: data.image,
        data: {
            url: data.url || '/',
            notificationId: data.id,
            type: data.type
        },
        actions: [
            {
                action: 'open',
                title: 'Abrir',
                icon: '/assets/img/action-open.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: '/assets/img/action-close.png'
            }
        ],
        requireInteraction: data.requireInteraction || false,
        silent: data.silent || false,
        vibrate: data.vibrate || [200, 100, 200],
        tag: data.tag || 'portal-noticias'
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Portal de Notícias', options)
    );
});

// Escutar cliques nas notificações
self.addEventListener('notificationclick', event => {
    console.log('Clique na notificação:', event);
    
    event.notification.close();
    
    const data = event.notification.data;
    const action = event.action;
    
    if (action === 'close') {
        return;
    }
    
    // Registrar clique na notificação
    if (data.notificationId) {
        fetch(`${API_BASE}/push/click`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notificationId: data.notificationId,
                action: action || 'open'
            })
        }).catch(err => console.error('Erro ao registrar clique:', err));
    }
    
    // Abrir URL da notificação
    const urlToOpen = data.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Verificar se já existe uma janela aberta
                for (let client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.focus();
                        client.navigate(urlToOpen);
                        return;
                    }
                }
                
                // Abrir nova janela se não houver nenhuma aberta
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Escutar fechamento das notificações
self.addEventListener('notificationclose', event => {
    console.log('Notificação fechada:', event);
    
    const data = event.notification.data;
    
    // Registrar fechamento da notificação
    if (data.notificationId) {
        fetch(`${API_BASE}/push/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notificationId: data.notificationId
            })
        }).catch(err => console.error('Erro ao registrar fechamento:', err));
    }
});

// Sincronização em background
self.addEventListener('sync', event => {
    console.log('Background sync:', event);
    
    if (event.tag === 'push-notification-sync') {
        event.waitUntil(
            // Sincronizar notificações pendentes
            fetch(`${API_BASE}/push/sync`, {
                method: 'POST'
            }).catch(err => console.error('Erro na sincronização:', err))
        );
    }
});

// Gerenciar mensagens do cliente
self.addEventListener('message', event => {
    console.log('Mensagem recebida no SW:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_NAME });
    }
});

// Interceptar requisições (opcional - para cache)
self.addEventListener('fetch', event => {
    // Implementar cache se necessário
    // Por enquanto, apenas deixar passar
    return;
});