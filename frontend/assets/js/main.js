/**
 * JavaScript Principal - Portal de Notícias
 * Funcionalidades modernas e interativas
 */

// Configurações globais
const CONFIG = {
    API_BASE_URL: 'http://localhost:8000',
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
        console.log('PortalNoticias.init() chamada');
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
        console.log('loadInitialData chamada');
        
        // Carrega notícias em destaque se estivermos na página inicial
        const featuredContainer = document.querySelector('.featured-news');
        console.log('Featured container encontrado:', !!featuredContainer);
        if (featuredContainer) {
            console.log('Carregando notícias em destaque...');
            this.loadFeaturedNews();
        }

        // Carrega notícias mais lidas
        const popularContainer = document.querySelector('.popular-news');
        console.log('Popular container encontrado:', !!popularContainer);
        if (popularContainer) {
            console.log('Carregando notícias populares...');
            this.loadPopularNews();
        }

        // Carrega últimas notícias na home
        const newsListContainer = document.querySelector('.news-list');
        if (newsListContainer) {
            console.log('Carregando últimas notícias...');
            this.loadLatestNews();
        }

        // Carrega tags populares na home
        const tagsContainer = document.getElementById('tags-cloud');
        if (tagsContainer) {
            console.log('Carregando tags populares...');
            this.loadPopularTags();
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

            // Se temos notícias armazenadas localmente, usa elas primeiro
            if (this.allLatestNews && this.currentNewsCount < this.allLatestNews.length) {
                this.loadMoreFromLocal();
                return;
            }

            // Caso contrário, faz requisição à API
            const page = parseInt(document.querySelector('.news-list').dataset.page || '1') + 1;
            const response = await this.apiRequest(`/noticias?page=${page}`);
            const news = response.noticias || response.data || response;
            
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
            console.log('Fazendo requisição para /noticias/destaques');
            const response = await this.apiRequest('/noticias/destaques');
            console.log('Resposta da API destaques:', response);
            const news = response.noticias || response.data || response;
            console.log('Notícias extraídas (destaques):', news);
            this.displayFeaturedNews(news);
        } catch (error) {
            console.error('Erro ao carregar notícias em destaque:', error);
        }
    }

    // Carrega notícias populares
    async loadPopularNews() {
        try {
            console.log('Fazendo requisição para /noticias/populares');
            const response = await this.apiRequest('/noticias/populares');
            console.log('Resposta da API populares:', response);
            const news = response.noticias || response.data || response;
            console.log('Notícias extraídas (populares):', news);
            this.displayPopularNews(news);
        } catch (error) {
            console.error('Erro ao carregar notícias populares:', error);
        }
    }

    // Carrega categorias
    async loadCategories() {
        try {
            const result = await this.apiRequest('/categorias');
            if (result.success && result.data && result.data.categorias) {
                this.displayCategories(result.data.categorias);
            }
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    // Carrega últimas notícias
    async loadLatestNews() {
        try {
            console.log('Fazendo requisição para /noticias');
            const response = await this.apiRequest('/noticias?limit=12');
            console.log('Resposta da API últimas notícias:', response);
            const news = response.noticias || response.data || response;
            console.log('Notícias extraídas (últimas):', news);
            this.displayLatestNews(news);
        } catch (error) {
            console.error('Erro ao carregar últimas notícias:', error);
        }
    }

    // Carrega tags populares
    async loadPopularTags() {
        try {
            console.log('Fazendo requisição para /tags?action=popular');
            const response = await this.apiRequest('/tags?action=popular&limit=20');
            console.log('Resposta da API tags populares:', response);
            if (response.success) {
                this.displayPopularTags(response.data);
            }
        } catch (error) {
            console.error('Erro ao carregar tags populares:', error);
        }
    }

    // Exibe notícias em destaque no carrossel
    displayFeaturedNews(news) {
        console.log('displayFeaturedNews chamada com:', news);
        const track = document.getElementById('carousel-track');
        const indicators = document.getElementById('carousel-indicators');
        
        if (!track || !indicators) {
            console.error('Elementos do carrossel não encontrados');
            return;
        }
        
        if (!news || !news.length) {
            console.error('News vazio ou sem length:', news);
            return;
        }

        console.log('Criando carrossel com', news.length, 'notícias em destaque');
        
        // Limpa o conteúdo atual
        track.innerHTML = '';
        indicators.innerHTML = '';
        
        // Cria slides
        news.forEach((item, index) => {
            const slide = document.createElement('div');
            slide.className = 'carousel-slide';
            slide.innerHTML = this.createNewsCard(item, 'featured');
            track.appendChild(slide);
            
            // Cria indicador
            const indicator = document.createElement('button');
            indicator.className = `carousel-indicator ${index === 0 ? 'active' : ''}`;
            indicator.setAttribute('data-slide', index);
            indicator.setAttribute('aria-label', `Ir para notícia ${index + 1}`);
            indicators.appendChild(indicator);
        });
        
        // Atualiza variáveis do carrossel
        this.totalSlides = news.length;
        this.currentSlide = 0;
        
        // Inicializa carrossel
        this.updateCarouselPosition();
        this.updateCarouselControls();
        this.startAutoPlay();
        
        console.log('Carrossel de destaque criado com sucesso');
    }

    // Exibe notícias populares (limitado a 3)
    displayPopularNews(news) {
        console.log('displayPopularNews chamada com:', news);
        const container = document.querySelector('.popular-news');
        console.log('Container popular encontrado:', !!container);
        if (!container) {
            console.error('Container .popular-news não encontrado');
            return;
        }
        if (!news || !news.length) {
            console.error('News vazio ou sem length:', news);
            return;
        }

        // Limita a apenas 3 notícias mais lidas
        const limitedNews = news.slice(0, 3);
        console.log('Criando', limitedNews.length, 'cards de notícias populares (limitado a 3)');
        container.innerHTML = limitedNews.map((item, index) => `
            <div class="popular-item d-flex mb-3">
                <div class="popular-rank me-3">
                    <span class="badge bg-primary">${index + 1}</span>
                </div>
                <div class="popular-content flex-grow-1">
                    <h6 class="popular-title mb-1">
                        <a href="/noticia/${item.slug}">${item.titulo}</a>
                    </h6>
                    <div class="popular-meta">
                        <small class="text-muted">
                            <i class="fas fa-eye me-1"></i>
                            ${PortalUtils.formatNumber(item.visualizacoes)}
                        </small>
                    </div>
                </div>
            </div>
        `).join('');
        console.log('Cards populares criados com sucesso');
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

    // Exibe últimas notícias
    displayLatestNews(news) {
        console.log('displayLatestNews chamada com:', news);
        const container = document.querySelector('.news-list');
        console.log('Container news-list encontrado:', !!container);
        if (!container) {
            console.error('Container .news-list não encontrado');
            return;
        }
        if (!news || !news.length) {
            console.error('News vazio ou sem length:', news);
            return;
        }

        // Armazena todas as notícias para uso posterior
        this.allLatestNews = news;
        this.currentNewsCount = 3;
        
        // Limitar a 3 notícias iniciais
        const limitedNews = news.slice(0, this.currentNewsCount);
        console.log('Criando', limitedNews.length, 'cards de últimas notícias (limitado a 3)');
        
        // Adiciona classe para estilo de lista e remove classes de grade
        container.className = 'news-list home-news-list';
        
        container.innerHTML = limitedNews.map(item => 
            this.createHomeListCard(item)
        ).join('');
        
        // Configura o botão "Carregar mais"
        this.setupLoadMoreButton();
        
        console.log('Cards de últimas notícias criados com sucesso');
    }

    // Exibe tags populares
    displayPopularTags(tags) {
        console.log('displayPopularTags chamada com:', tags);
        const container = document.getElementById('tags-cloud');
        console.log('Container tags-cloud encontrado:', !!container);
        if (!container) {
            console.error('Container #tags-cloud não encontrado');
            return;
        }
        if (!tags || !tags.length) {
            console.error('Tags vazio ou sem length:', tags);
            return;
        }

        console.log('Criando', tags.length, 'tags populares');
        container.innerHTML = tags.map(tag => `
            <a href="/tag/${tag.slug}" class="tag" style="font-size: ${Math.min(1.2, 0.8 + (tag.total_noticias / 10))}em;">
                ${tag.nome}
            </a>
        `).join('');
        console.log('Tags populares criadas com sucesso');
    }

    // Cria card de notícia
    createNewsCard(news, type = 'default') {
        const baseCard = `
            <article class="card news-card ${type === 'featured' ? 'news-card-featured' : ''}">
                ${news.imagem ? `
                    <img src="${news.imagem}" alt="${news.titulo}" class="card-img-top lazy" data-src="${news.imagem}">
                ` : `
                    <img src="/assets/images/default-news.svg" alt="${news.titulo}" class="card-img-top">
                `}
                
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
                            <img src="${news.autor_foto || '/assets/images/default-avatar.svg'}" alt="${news.autor}">
                            <span>${news.autor}</span>
                        </div>
                        
                        <div class="news-card-stats">
                            <span class="news-card-stat">
                                <i class="icon-eye"></i>
                                ${PortalUtils.formatNumber(news.visualizacoes)}
                            </span>
                            <span class="news-card-stat">
                                <i class="icon-heart"></i>
                                ${PortalUtils.formatNumber(news.curtidas)}
                            </span>
                            <span class="news-card-stat">
                                <i class="icon-comment"></i>
                                ${PortalUtils.formatNumber(news.comentarios)}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        `;
        
        return baseCard;
    }

    // Cria card de notícia no estilo de lista para a home
    createHomeListCard(news) {
        const listCard = `
            <article class="card news-card news-card-horizontal">
                <div class="news-card-image">
                    ${news.imagem ? `
                        <img src="${news.imagem}" alt="${news.titulo || 'Notícia'}" class="lazy" data-src="${news.imagem}">
                    ` : `
                        <img src="/assets/images/default-news.svg" alt="${news.titulo || 'Notícia'}">
                    `}
                    <div class="news-card-overlay">
                        <a href="/noticia/${news.slug || '#'}" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            Ler mais
                        </a>
                    </div>
                    <div class="news-card-category">${news.categoria || news.categoria_nome || 'Geral'}</div>
                </div>
                
                <div class="card-body">
                    <div class="news-card-meta mb-2">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            ${this.formatDate(news.data_publicacao || news.created_at || new Date())}
                        </small>
                    </div>
                    <h3 class="news-card-title">
                        <a href="/noticia/${news.slug || '#'}">${news.titulo || 'Título não disponível'}</a>
                    </h3>
                    
                    <p class="news-card-excerpt">${news.resumo || news.conteudo ? news.conteudo.substring(0, 150) + '...' : 'Resumo não disponível'}</p>
                    
                    <div class="news-card-meta">
                        <div class="news-card-author">
                            <img src="${news.autor_foto || '/assets/images/default-avatar.svg'}" alt="${news.autor || 'Autor'}">
                            <span>${news.autor || news.autor_nome || 'Autor desconhecido'}</span>
                        </div>
                        
                        <div class="news-card-stats">
                            <span class="news-card-stat">
                                <i class="fas fa-eye"></i>
                                ${PortalUtils.formatNumber(news.visualizacoes || 0)}
                            </span>
                            <span class="news-card-stat">
                                <i class="fas fa-heart"></i>
                                ${PortalUtils.formatNumber(news.curtidas || 0)}
                            </span>
                            <span class="news-card-stat">
                                <i class="fas fa-comment"></i>
                                ${PortalUtils.formatNumber(news.comentarios || 0)}
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        `;
        
        return listCard;
    }

    // Formata data para exibição
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('pt-BR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Configura o botão "Carregar mais"
    setupLoadMoreButton() {
        const loadMoreBtn = document.querySelector('#load-more-news');
        if (!loadMoreBtn) return;

        // Mostra/esconde o botão baseado na disponibilidade de mais notícias
        const hasMoreNews = this.allLatestNews && this.currentNewsCount < this.allLatestNews.length;
        loadMoreBtn.style.display = hasMoreNews ? 'block' : 'none';

        // Remove event listeners anteriores
        const newBtn = loadMoreBtn.cloneNode(true);
        loadMoreBtn.parentNode.replaceChild(newBtn, loadMoreBtn);

        // Adiciona novo event listener
        newBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.loadMoreFromLocal();
        });
    }

    // Carrega mais notícias do cache local
    loadMoreFromLocal() {
        if (!this.allLatestNews) return;

        const nextBatch = 3; // Carrega mais 3 notícias por vez
        const startIndex = this.currentNewsCount;
        const endIndex = Math.min(startIndex + nextBatch, this.allLatestNews.length);
        
        const moreNews = this.allLatestNews.slice(startIndex, endIndex);
        this.appendNews(moreNews);
        
        this.currentNewsCount = endIndex;
        
        // Atualiza o botão "Carregar mais"
        this.setupLoadMoreButton();
    }

    // Adiciona notícias ao container
    appendNews(news) {
        const container = document.querySelector('.news-list');
        if (!container) return;

        // Verifica se está na home (tem classe home-news-list)
        const isHomePage = container.classList.contains('home-news-list');
        
        const newsHtml = news.map(item => {
            if (isHomePage) {
                return this.createHomeListCard(item);
            } else {
                return this.createNewsCard(item, 'col-12');
            }
        }).join('');
        
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
                           (this.currentUser.tipo === 'admin' || 
                            this.currentUser.tipo === 'editor');
            if (isAdmin) {
                el.style.display = 'block';
                el.style.visibility = 'visible';
            } else {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            }
        });
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
        this.currentSlide = 0;
        this.totalSlides = 0;
        this.autoPlayInterval = null;
        this.isAutoPlaying = false;
        
        // Bind carousel events
        this.bindCarouselEvents();
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

    // Métodos do Carrossel
    bindCarouselEvents() {
        // Navegação com botões
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.prevSlide());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextSlide());
        }
        
        // Navegação com indicadores
        document.addEventListener('click', (e) => {
            if (e.target.matches('.carousel-indicator')) {
                const slideIndex = parseInt(e.target.dataset.slide);
                this.goToSlide(slideIndex);
            }
        });
        
        // Pausa autoplay no hover
        const carouselContainer = document.querySelector('.featured-carousel-container');
        if (carouselContainer) {
            carouselContainer.addEventListener('mouseenter', () => this.pauseAutoPlay());
            carouselContainer.addEventListener('mouseleave', () => this.resumeAutoPlay());
        }
        
        // Navegação por teclado
        document.addEventListener('keydown', (e) => {
            if (document.activeElement && document.activeElement.closest('.featured-carousel-container')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nextSlide();
                }
            }
        });
        
        // Touch/Swipe support
        this.initTouchSupport();
    }
    
    initTouchSupport() {
        const carousel = document.getElementById('featured-carousel');
        if (!carousel) return;
        
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
            this.pauseAutoPlay();
        });
        
        carousel.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        });
        
        carousel.addEventListener('touchend', () => {
            if (!isDragging) return;
            
            const diffX = startX - currentX;
            const threshold = 50;
            
            if (Math.abs(diffX) > threshold) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
            
            isDragging = false;
            this.resumeAutoPlay();
        });
    }
    
    nextSlide() {
        if (this.totalSlides <= 1) return;
        
        this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
        this.updateCarouselPosition();
        this.updateCarouselControls();
    }
    
    prevSlide() {
        if (this.totalSlides <= 1) return;
        
        this.currentSlide = this.currentSlide === 0 ? this.totalSlides - 1 : this.currentSlide - 1;
        this.updateCarouselPosition();
        this.updateCarouselControls();
    }
    
    goToSlide(index) {
        if (index < 0 || index >= this.totalSlides) return;
        
        this.currentSlide = index;
        this.updateCarouselPosition();
        this.updateCarouselControls();
    }
    
    updateCarouselPosition() {
        const track = document.getElementById('carousel-track');
        if (!track) return;
        
        const translateX = -this.currentSlide * 100;
        track.style.transform = `translateX(${translateX}%)`;
    }
    
    updateCarouselControls() {
        // Atualiza indicadores
        const indicators = document.querySelectorAll('.carousel-indicator');
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === this.currentSlide);
        });
        
        // Atualiza botões de navegação
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');
        
        if (prevBtn && nextBtn) {
            // Para carrossel infinito, sempre habilitado
            prevBtn.disabled = false;
            nextBtn.disabled = false;
        }
    }
    
    startAutoPlay() {
        if (this.totalSlides <= 1) return;
        
        this.stopAutoPlay();
        this.isAutoPlaying = true;
        
        this.autoPlayInterval = setInterval(() => {
            if (this.isAutoPlaying) {
                this.nextSlide();
            }
        }, 5000); // 5 segundos
    }
    
    stopAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
        this.isAutoPlaying = false;
    }
    
    pauseAutoPlay() {
        this.isAutoPlaying = false;
    }
    
    resumeAutoPlay() {
        if (this.autoPlayInterval && this.totalSlides > 1) {
            this.isAutoPlaying = true;
        }
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

// Função global de logout
window.logout = function() {
    if (confirm('Tem certeza que deseja sair?')) {
        localStorage.removeItem('authToken');
        localStorage.removeItem('portal-user');
        localStorage.removeItem('userData');
        window.location.href = 'login.html';
    }
};

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
        // Verifica se o número é válido
        if (num === null || num === undefined || isNaN(num)) {
            return '0';
        }
        
        const numValue = Number(num);
        if (numValue >= 1000000) {
            return (numValue / 1000000).toFixed(1) + 'M';
        } else if (numValue >= 1000) {
            return (numValue / 1000).toFixed(1) + 'K';
        }
        return numValue.toString();
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