<?php
/**
 * Controlador de Cache
 * Portal de Notícias
 */

require_once __DIR__ . '/../../config-dev.php';
require_once __DIR__ . '/../utils/CacheManager.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CacheController {
    private $cache_manager;
    private $auth_middleware;
    
    public function __construct() {
        $this->cache_manager = new CacheManager();
        $this->auth_middleware = new AuthMiddleware();
    }
    
    /**
     * Processar requisições
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    return $this->handleGet($action);
                case 'POST':
                    return $this->handlePost($action);
                case 'DELETE':
                    return $this->handleDelete($action);
                default:
                    return $this->jsonResponse(['erro' => 'Método não permitido'], 405);
            }
        } catch (Exception $e) {
            return $this->jsonResponse(['erro' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Lidar com requisições GET
     */
    private function handleGet($action) {
        switch ($action) {
            case 'stats':
                return $this->getStats();
            case 'status':
                return $this->getStatus();
            default:
                return $this->jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Lidar com requisições POST
     */
    private function handlePost($action) {
        // Verificar autenticação para ações administrativas
        $user = $this->auth_middleware->authenticate();
        if (!$user || $user['tipo'] !== 'admin') {
            return $this->jsonResponse(['erro' => 'Acesso negado'], 403);
        }
        
        switch ($action) {
            case 'enable':
                return $this->enableCache();
            case 'disable':
                return $this->disableCache();
            case 'clear-expired':
                return $this->clearExpired();
            default:
                return $this->jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Lidar com requisições DELETE
     */
    private function handleDelete($action) {
        // Verificar autenticação
        $user = $this->auth_middleware->authenticate();
        if (!$user || $user['tipo'] !== 'admin') {
            return $this->jsonResponse(['erro' => 'Acesso negado'], 403);
        }
        
        switch ($action) {
            case 'clear':
                return $this->clearCache();
            default:
                return $this->jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Obter estatísticas do cache
     */
    private function getStats() {
        $stats = $this->cache_manager->getStats();
        
        // Formatar tamanho
        $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);
        
        return $this->jsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    /**
     * Obter status do cache
     */
    private function getStatus() {
        return $this->jsonResponse([
            'success' => true,
            'enabled' => $this->cache_manager->isEnabled(),
            'config' => [
                'type' => CACHE_CONFIG['type'] ?? 'file',
                'ttl' => CACHE_CONFIG['ttl'] ?? 3600,
                'path' => CACHE_CONFIG['path'] ?? 'backend/cache'
            ]
        ]);
    }
    
    /**
     * Habilitar cache
     */
    private function enableCache() {
        $result = $this->cache_manager->enable();
        
        return $this->jsonResponse([
            'success' => $result,
            'mensagem' => $result ? 'Cache habilitado com sucesso' : 'Erro ao habilitar cache'
        ]);
    }
    
    /**
     * Desabilitar cache
     */
    private function disableCache() {
        $result = $this->cache_manager->disable();
        
        return $this->jsonResponse([
            'success' => $result,
            'mensagem' => $result ? 'Cache desabilitado com sucesso' : 'Erro ao desabilitar cache'
        ]);
    }
    
    /**
     * Limpar todo o cache
     */
    private function clearCache() {
        $cleared = $this->cache_manager->clear();
        
        return $this->jsonResponse([
            'success' => true,
            'mensagem' => "Cache limpo com sucesso. {$cleared} arquivos removidos.",
            'files_cleared' => $cleared
        ]);
    }
    
    /**
     * Limpar cache expirado
     */
    private function clearExpired() {
        $cleared = $this->cache_manager->clearExpired();
        
        return $this->jsonResponse([
            'success' => true,
            'mensagem' => "Cache expirado limpo com sucesso. {$cleared} arquivos removidos.",
            'files_cleared' => $cleared
        ]);
    }
    
    /**
     * Formatar bytes em formato legível
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Resposta JSON
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Processar requisição se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'CacheController.php') {
    $controller = new CacheController();
    $controller->processarRequisicao();
}