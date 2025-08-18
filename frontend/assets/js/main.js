/**
 * JavaScript Principal - Portal de Notícias
 * Funcionalidades modernas e interativas
 */

// Configurações globais
const CONFIG = {
    API_BASE_URL: 'http://localhost:8001',
    ITEMS_PER_PAGE: 12,
    DEBOUNCE_DELAY: 300,
    ANIMATION_DURATION: 300,
    CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
    THEME_STORAGE_KEY: 'portal-theme',
    USER_STORAGE_KEY: 'portal-user'
};

// Classe principal da aplicação
class PortalNoticias {
    constructor() {
        this.currentUser = null;
        this.cache = new Map();
        this.debounceTimers = new Map();
        this.init();
    }

    // Inicialização
    init() {
        this.loadUserFromStorage();
        this.initTheme();
        this.bindEvents();
        this.initComponents();
        this.loadInitialData();
    }

    // Carrega usuário do localStorage
    loadUserFromStorage() {
        const userData = localStorage.getItem(CONFIG.USER_STORAGE_KEY);
        if (userData) {
            try {
                this.currentUser = JSON.parse(userData);
                this.updateUserInterface();
            } catch (error) {
                console.error('Erro ao carregar dados do usuário:', error);
                localStorage.removeItem(CONFIG.USER_STORAGE_KEY);
            }
        }
    }

    // Inicializa tema
    initTheme() {
        const savedTheme = localStorage.getItem(CONFIG.THEME_STORAGE_KEY) || 'light';
        this.setTheme(savedTheme);
    }

    // Define tema
    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(CONFIG.THEME_STORAGE_KEY, theme);
        
        // Atualiza ícone do botão de tema
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.innerHTML = theme === 'dark' ? '☀️' : '🌙';
            themeToggle.setAttribute('aria-label', `Mudar para tema ${theme === 'dark' ? 'claro' : 'escuro'}`);
        }
    }

    // Alterna tema
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    // Vincula eventos
    bindEvents() {
        // Tema
        document.addEventListener('click', (e) => {
            if (e.target.matches('.theme-toggle')) {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        // Menu mobile
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mobile-menu-toggle')) {
                e.preventDefault();
                this.toggleMobileMenu();
            }
        });

        // Busca
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSearch();
            });

            const searchInput = searchForm.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.debounce('search', () => {
                        this.handleSearchSuggestions(e.target.value);
                    }, CONFIG.DEBOUNCE_DELAY);
                });
            }
        }

        // Scroll infinito
        window.addEventListener('scroll', () => {
            this.debounce('scroll', () => {
                this.handleInfiniteScroll();
            }, 100);
        });

        // Likes
        document.addEventListener('click', (e) => {
            if (e.target.matches('.like-btn') || e.target.closest('.like-btn')) {
                e.preventDefault();
                const btn = e.target.matches('.like-btn') ? e.target : e.target.closest('.like-btn');
                this.handleLike(btn);
            }
        });

        // Compartilhamento
        document.addEventListener('click', (e) => {
            if (e.target.matches('.share-btn') || e.target.closest('.share-btn')) {
                e.preventDefault();
                const btn = e.target.matches('.share-btn') ? e.target : e.target.closest('.share-btn');
                this.handleShare(btn);
            }
        });

        // Formulários
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });

        // Lazy loading de imagens
        this.initLazyLoading();
    }

    // Inicializa componentes
    initComponents() {
        this.initCarousel();
        this.initModal();
        this.initTooltips();
        this.initDropdowns();
    }

    // Carrega dados iniciais
    loadInitialData() {
        // Carrega notícias em destaque se estivermos na página inicial
        if (document.querySelector('.featured-news')) {
            this.loadFeaturedNews();
        }

        // Carrega notícias mais lidas
        if (document.querySelector('.popular-news')) {
            this.loadPopularNews();
        }

        // Carrega categorias
        if (document.querySelector('.categories-list')) {
            this.loadCategories();
        }
    }

    // Debounce para otimizar performance
    debounce(key, func, delay) {
        if (this.debounceTimers.has(key)) {
            clearTimeout(this.debounceTimers.get(key));
        }
        
        const timer = setTimeout(() => {
            func();
            this.debounceTimers.delete(key);
        }, delay);
        
        this.debounceTimers.set(key, timer);
    }

    // Toggle menu mobile
    toggleMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (navMenu && toggle) {
            navMenu.classList.toggle('active');
            toggle.setAttribute('aria-expanded', navMenu.classList.contains('active'));
        }
    }

    // Manipula busca
    async handleSearch() {
        const searchInput = document.querySelector('.search-input');
        if (!searchInput) return;

        const query = searchInput.value.trim();
        if (!query) return;

        try {
            this.showLoading();
            const results = await this.apiRequest(`/noticias/buscar?q=${encodeURIComponent(query)}`);
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Erro na busca:', error);
            this.showError('Erro ao realizar busca. Tente novamente.');
        } finally {
            this.hideLoading();
        }
    }

    // Sugestões de busca
    async handleSearchSuggestions(query) {
        if (!query || query.length < 2) {
            this.hideSuggestions();
            return;
        }

        try {
            const suggestions = await this.apiRequest(`/noticias/sugestoes?q=${encodeURIComponent(query)}`);
            this.displaySuggestions(suggestions);
        } catch (error) {
            console.error('Erro ao carregar sugestões:', error);
        }
    }

    // Scroll infinito
    async handleInfiniteScroll() {
        const newsContainer = document.querySelector('.news-list');
        if (!newsContainer) return;

        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        
        if (scrollTop + clientHeight >= scrollHeight - 1000) {
            await this.loadMoreNews();
        }
    }

    // Carrega mais notícias
    async loadMoreNews() {
        const loadMoreBtn = document.querySelector('.load-more-btn');
        if (loadMoreBtn && loadMoreBtn.disabled) return;

        try {
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerHTML = '<span class="loading"></span> Carregando...';
            }

            const page = parseInt(document.querySelector('.news-list').dataset.page || '1') + 1;
            const news = await this.apiRequest(`/noticias?page=${page}`);
            
            if (news && news.length > 0) {
                this.appendNews(news);
                document.querySelector('.news-list').dataset.page = page;
            } else {
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erro ao carregar mais notícias:', error);
        } finally {
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                loadMoreBtn.innerHTML = 'Carregar mais';
            }
        }
    }

    // Manipula likes
    async handleLike(btn) {
        if (!this.currentUser) {
            this.showLoginModal();
            return;
        }

        const newsId = btn.dataset.newsId;
        if (!newsId) return;

        try {
            btn.disabled = true;
            const response = await this.apiRequest(`/noticias/${newsId}/like`, {
                method: 'POST'
            });

            if (response.success) {
                const countElement = btn.querySelector('.like-count');
                if (countElement) {
                    countElement.textContent = response.likes;
                }
                btn.classList.toggle('liked', response.liked);
            }
        } catch (error) {
            console.error('Erro ao curtir notícia:', error);
            this.showError('Erro ao curtir notícia.');
        } finally {
            btn.disabled = false;
        }
    }

    // Manipula compartilhamento
    handleShare(btn) {
        const url = btn.dataset.url || window.location.href;
        const title = btn.dataset.title || document.title;
        const text = btn.dataset.text || '';

        if (navigator.share) {
            navigator.share({ title, text, url })
                .catch(error => console.error('Erro ao compartilhar:', error));
        } else {
            this.showShareModal(url, title);
        }
    }

    // Manipula envio de formulários
    async handleFormSubmit(form) {
        const formData = new FormData(form);
        const action = form.action || form.dataset.action;
        const method = form.method || 'POST';

        try {
            this.showFormLoading(form);
            
            const response = await this.apiRequest(action, {
                method,
                body: formData
            });

            if (response.success) {
                this.showSuccess(response.message || 'Operação realizada com sucesso!');
                if (response.redirect) {
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    form.reset();
                }
            } else {
                this.showError(response.message || 'Erro ao processar formulário.');
            }
        } catch (error) {
            console.error('Erro no formulário:', error);
            this.showError('Erro ao enviar formulário. Tente novamente.');
        } finally {
            this.hideFormLoading(form);
        }
    }

    // Requisições à API
    async apiRequest(endpoint, options = {}) {
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        
        // Verifica cache
        if (options.method === 'GET' || !options.method) {
            const cached = this.getFromCache(url);
            if (cached) return cached;
        }

        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        // Adiciona token de autenticação se disponível
        if (this.currentUser && this.currentUser.token) {
            defaultOptions.headers['Authorization'] = `Bearer ${this.currentUser.token}`;
        }

        const config = { ...defaultOptions, ...options };
        
        // Se não é FormData, converte body para JSON
        if (config.body && !(config.body instanceof FormData)) {
            config.body = JSON.stringify(config.body);
        } else if (config.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        // Armazena no cache se for GET
        if (config.method === 'GET') {
            this.setCache(url, data);
        }

        return data;
    }

    // Cache
    getFromCache(key) {
        const cached = this.cache.get(key);
        if (cached && Date.now() - cached.timestamp < CONFIG.CACHE_DURATION) {
            return cached.data;
        }
        this.cache.delete(key);
        return null;
    }

    setCache(key, data) {
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    // Lazy loading de imagens
    initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // Carrega notícias em destaque
    async loadFeaturedNews() {
        try {
            const news = await this.apiRequest('/controllers/NoticiaController.php?action=featured');
            this.displayFeaturedNews(news);
        } catch (error) {
            console.error('Erro ao carregar notícias em destaque:', error);
        }
    }

    // Carrega notícias populares
    async loadPopularNews() {
        try {
            const news = await this.apiRequest('/controllers/NoticiaController.php?action=popular');
            this.displayPopularNews(news);
        } catch (error) {
            console.error('Erro ao carregar notícias populares:', error);
        }
    }

    // Carrega categorias
    async loadCategories() {
        try {
            const categories = await this.apiRequest('/controllers/CategoriaController.php');
            this.displayCategories(categories);
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    // Exibe notícias em destaque
    displayFeaturedNews(news) {
        const container = document.querySelector('.featured-news');
        if (!container || !news.length) return;

        container.innerHTML = news.map(item => this.createNewsCard(item, 'featured')).join('');
        container.classList.add('fade-in');
    }

    // Exibe notícias populares
    displayPopularNews(news) {
        const container = document.querySelector('.popular-news');
        if (!container || !news.length) return;

        container.innerHTML = news.map(item => this.createNewsCard(item, 'compact')).join('');
    }

    // Exibe categorias
    displayCategories(categories) {
        const container = document.querySelector('.categories-list');
        if (!container || !categories.length) return;

        container.innerHTML = categories.map(category => `
            <li>
                <a href="/categoria/${category.slug}" class="d-flex justify-content-between align-items-center">
                    <span>${category.nome}</span>
                    <span class="badge">${category.total_noticias}</span>
                </a>
            </li>
        `).join('');
    }

    // Cria card de notícia
    createNewsCard(news, type = 'default') {
        const baseCard = `
            <article class="card news-card ${type === 'featured' ? 'news-card-featured' : ''}">
                ${news.imagem ? `
                    <img src="${news.imagem}" alt="${news.titulo}" class="card-img lazy" data-src="${news.imagem}">
                ` : ''}
                
                <div class="news-card-category">${news.categoria}</div>
                
                <div class="card-body">
                    <h3 class="news-card-title">
                        <a href="/noticia/${news.slug}">${news.titulo}</a>
                    </h3>
                    
                    ${type !== 'compact' ? `
                        <p class="news-card-excerpt">${news.resumo}</p>
                    ` : ''}
                    
                    <div class="news-card-meta">
                        <div class="news-card-author">
                            <img src="${news.autor_foto || '/assets/img/default-avatar.png'}" alt="${news.autor}">
                            <span>${news.autor}</span>
                        </div>
                        
                        <div class="news-card-stats">
                            <span class="news-card-stat">
                                <i class="icon-eye"></i>
                                ${news.visualizacoes}
                            </span>
                            <span class="news-card-stat">
                                <i class="icon-heart"></i>
                                ${news.curtidas}
                            </span>
                            <span class="news-card-stat">
                                <i class="icon-comment"></i>
                                ${news.comentarios}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        `;
        
        return baseCard;
    }

    // Adiciona notícias ao container
    appendNews(news) {
        const container = document.querySelector('.news-list');
        if (!container) return;

        const newsHtml = news.map(item => this.createNewsCard(item)).join('');
        container.insertAdjacentHTML('beforeend', newsHtml);
        
        // Reinicializa lazy loading para novas imagens
        this.initLazyLoading();
    }

    // Atualiza interface do usuário
    updateUserInterface() {
        console.log('updateUserInterface chamada, currentUser:', this.currentUser);
        
        // Atualizar informações do usuário
        const userElements = document.querySelectorAll('[data-user-info]');
        userElements.forEach(element => {
            const info = element.dataset.userInfo;
            if (this.currentUser && this.currentUser[info]) {
                element.textContent = this.currentUser[info];
            } else {
                element.textContent = 'Usuário';
            }
        });

        // Mostra/esconde elementos baseado no login
        const loggedInElements = document.querySelectorAll('.logged-in-only');
        const loggedOutElements = document.querySelectorAll('.logged-out-only');
        const adminOnlyElements = document.querySelectorAll('.admin-only');
        
        console.log(`Elementos encontrados: logged-in: ${loggedInElements.length}, logged-out: ${loggedOutElements.length}, admin-only: ${adminOnlyElements.length}`);
        
        // Elementos para usuários logados
        loggedInElements.forEach(el => {
            if (this.currentUser) {
                el.style.display = 'block';
                el.style.visibility = 'visible';
            } else {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            }
        });
        
        // Elementos para usuários não logados
        loggedOutElements.forEach(el => {
            if (this.currentUser) {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            } else {
                el.style.display = 'block';
                el.style.visibility = 'visible';
            }
        });
        
        // Mostra elementos administrativos apenas para admin/editor
        adminOnlyElements.forEach(el => {
            const isAdmin = this.currentUser && 
                           (this.currentUser.tipo_usuario === 'admin' || 
                            this.currentUser.tipo_usuario === 'editor');
            if (isAdmin) {
                el.style.display = 'block';
                el.style.visibility = 'visible';
            } else {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            }
        });
        
        console.log('updateUserInterface concluída');
    }

    // Utilitários de UI
    showLoading() {
        const loader = document.querySelector('.main-loader') || this.createLoader();
        loader.style.display = 'block';
    }

    hideLoading() {
        const loader = document.querySelector('.main-loader');
        if (loader) loader.style.display = 'none';
    }

    createLoader() {
        const loader = document.createElement('div');
        loader.className = 'main-loader';
        loader.innerHTML = '<div class="loading"></div>';
        loader.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        `;
        document.body.appendChild(loader);
        return loader;
    }

    showFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading"></span> Enviando...';
        }
    }

    hideFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.originalText || 'Enviar';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} slide-up`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        `;

        switch (type) {
            case 'success':
                notification.style.backgroundColor = 'var(--success-color)';
                break;
            case 'error':
                notification.style.backgroundColor = 'var(--danger-color)';
                break;
            default:
                notification.style.backgroundColor = 'var(--info-color)';
        }

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Inicializa componentes específicos
    initCarousel() {
        // Implementar carousel se necessário
    }

    initModal() {
        // Implementar modais se necessário
    }

    initTooltips() {
        // Implementar tooltips se necessário
    }

    initDropdowns() {
        // Implementar dropdowns se necessário
    }
}

// Inicializa a aplicação quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.portalApp = new PortalNoticias();
    });
} else {
    window.portalApp = new PortalNoticias();
}

// Utilitários globais
window.PortalUtils = {
    // Formata data
    formatDate(date, format = 'dd/mm/yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year);
    },

    // Formata números
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    },

    // Trunca texto
    truncateText(text, length = 100) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    },

    // Valida email
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Gera slug
    generateSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
    }
};