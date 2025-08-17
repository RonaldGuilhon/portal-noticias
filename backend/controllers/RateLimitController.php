<?php
/**
 * Controlador para monitoramento de Rate Limiting
 * Portal de Notícias
 */

require_once __DIR__ . '/../utils/RateLimiter.php';
require_once __DIR__ . '/../config/config.php';

class RateLimitController {
    private $rate_limiter;
    
    public function __construct() {
        $this->rate_limiter = new RateLimiter();
    }
    
    /**
     * Processar requisições do controlador
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'stats';
        
        // Verificar se é administrador para acessar estatísticas
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'erro' => 'Acesso negado',
                'mensagem' => 'Apenas administradores podem acessar as estatísticas de rate limiting'
            ]);
            return;
        }
        
        switch($action) {
            case 'stats':
                $this->getStats();
                break;
            case 'clear':
                $this->clearStats();
                break;
            case 'status':
                $this->getStatus();
                break;
            default:
                http_response_code(404);
                echo json_encode(['erro' => 'Ação não encontrada']);
        }
    }
    
    /**
     * Obter estatísticas de rate limiting
     */
    private function getStats() {
        $storage_path = __DIR__ . '/../logs/rate_limits.json';
        
        if (!file_exists($storage_path)) {
            echo json_encode([
                'estatisticas' => [],
                'total_identificadores' => 0,
                'total_requisicoes' => 0,
                'arquivo_existe' => false
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents($storage_path), true) ?: [];
        $current_time = time();
        $stats = [];
        $total_requests = 0;
        
        foreach ($data as $identifier => $timestamps) {
            $recent_requests = array_filter($timestamps, function($timestamp) use ($current_time) {
                return ($current_time - $timestamp) <= 3600; // Últimas 1 hora
            });
            
            $very_recent_requests = array_filter($timestamps, function($timestamp) use ($current_time) {
                return ($current_time - $timestamp) <= 300; // Últimos 5 minutos
            });
            
            $stats[] = [
                'identificador' => $identifier,
                'tipo' => $this->getIdentifierType($identifier),
                'requisicoes_ultima_hora' => count($recent_requests),
                'requisicoes_ultimos_5min' => count($very_recent_requests),
                'ultima_requisicao' => !empty($timestamps) ? date('Y-m-d H:i:s', max($timestamps)) : null,
                'primeira_requisicao' => !empty($timestamps) ? date('Y-m-d H:i:s', min($timestamps)) : null,
                'total_requisicoes' => count($timestamps)
            ];
            
            $total_requests += count($timestamps);
        }
        
        // Ordenar por requisições recentes
        usort($stats, function($a, $b) {
            return $b['requisicoes_ultima_hora'] - $a['requisicoes_ultima_hora'];
        });
        
        echo json_encode([
            'estatisticas' => $stats,
            'total_identificadores' => count($data),
            'total_requisicoes' => $total_requests,
            'arquivo_existe' => true,
            'timestamp_atual' => $current_time,
            'data_atual' => date('Y-m-d H:i:s', $current_time)
        ]);
    }
    
    /**
     * Limpar estatísticas antigas
     */
    private function clearStats() {
        $storage_path = __DIR__ . '/../logs/rate_limits.json';
        
        if (file_exists($storage_path)) {
            unlink($storage_path);
        }
        
        echo json_encode([
            'success' => true,
            'mensagem' => 'Estatísticas de rate limiting foram limpas'
        ]);
    }
    
    /**
     * Obter status atual do sistema
     */
    private function getStatus() {
        $current_identifier = RateLimiter::getIdentifier();
        
        // Obter informações para diferentes limites
        $limits = [
            'default' => ['limit' => 1000, 'window' => 3600],
            'auth/login' => ['limit' => 10, 'window' => 900],
            'auth/register' => ['limit' => 5, 'window' => 3600],
            'auth/forgot-password' => ['limit' => 3, 'window' => 3600]
        ];
        
        $status = [];
        foreach ($limits as $endpoint => $config) {
            $info = $this->rate_limiter->getLimitInfo(
                $current_identifier, 
                $config['limit'], 
                $config['window']
            );
            
            $status[$endpoint] = [
                'limite' => $info['limit'],
                'restantes' => $info['remaining'],
                'reset_em' => date('Y-m-d H:i:s', $info['reset_time']),
                'janela_segundos' => $info['window'],
                'porcentagem_usado' => round((($info['limit'] - $info['remaining']) / $info['limit']) * 100, 2)
            ];
        }
        
        echo json_encode([
            'identificador_atual' => $current_identifier,
            'status_por_endpoint' => $status,
            'timestamp' => time(),
            'data_hora' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Determinar tipo do identificador
     */
    private function getIdentifierType($identifier) {
        if (strpos($identifier, 'user_') === 0) {
            return 'Usuário Logado';
        } elseif (strpos($identifier, 'token_') === 0) {
            return 'Token API';
        } elseif (strpos($identifier, 'ip_') === 0) {
            return 'Endereço IP';
        }
        return 'Desconhecido';
    }
    
    /**
     * Verificar se o usuário é administrador
     */
    private function isAdmin() {
        // Para desenvolvimento, permitir acesso sem autenticação
        if (defined('AMBIENTE') && AMBIENTE === 'desenvolvimento') {
            return true;
        }
        
        // Em produção, verificar se o usuário está logado e é admin
        return isset($_SESSION['usuario_id']) && 
               isset($_SESSION['tipo_usuario']) && 
               $_SESSION['tipo_usuario'] === 'admin';
    }
}

// Processar requisição se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'RateLimitController.php') {
    $controller = new RateLimitController();
    $controller->processarRequisicao();
}