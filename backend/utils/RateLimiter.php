<?php
/**
 * Rate Limiter - Sistema de limitação de taxa de requisições
 * Portal de Notícias
 */

class RateLimiter {
    private $storage_path;
    private $default_limit;
    private $default_window;
    
    public function __construct($storage_path = null, $default_limit = 1000, $default_window = 3600) {
        $this->storage_path = $storage_path ?: __DIR__ . '/../logs/rate_limits.json';
        $this->default_limit = $default_limit;
        $this->default_window = $default_window; // 1 hora em segundos
        
        // Criar diretório se não existir
        $dir = dirname($this->storage_path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Verificar se a requisição está dentro do limite
     */
    public function isAllowed($identifier, $limit = null, $window = null) {
        $limit = $limit ?: $this->default_limit;
        $window = $window ?: $this->default_window;
        
        $current_time = time();
        $window_start = $current_time - $window;
        
        // Carregar dados existentes
        $data = $this->loadData();
        
        // Limpar dados antigos
        $this->cleanOldData($data, $window_start);
        
        // Verificar limite para o identificador
        if (!isset($data[$identifier])) {
            $data[$identifier] = [];
        }
        
        // Contar requisições na janela atual
        $requests_in_window = 0;
        foreach ($data[$identifier] as $timestamp) {
            if ($timestamp >= $window_start) {
                $requests_in_window++;
            }
        }
        
        // Verificar se excedeu o limite
        if ($requests_in_window >= $limit) {
            return false;
        }
        
        // Registrar a requisição atual
        $data[$identifier][] = $current_time;
        
        // Salvar dados
        $this->saveData($data);
        
        return true;
    }
    
    /**
     * Obter informações sobre o limite atual
     */
    public function getLimitInfo($identifier, $limit = null, $window = null) {
        $limit = $limit ?: $this->default_limit;
        $window = $window ?: $this->default_window;
        
        $current_time = time();
        $window_start = $current_time - $window;
        
        $data = $this->loadData();
        
        if (!isset($data[$identifier])) {
            return [
                'limit' => $limit,
                'remaining' => $limit,
                'reset_time' => $current_time + $window,
                'window' => $window
            ];
        }
        
        // Contar requisições na janela atual
        $requests_in_window = 0;
        $oldest_request = null;
        
        foreach ($data[$identifier] as $timestamp) {
            if ($timestamp >= $window_start) {
                $requests_in_window++;
                if ($oldest_request === null || $timestamp < $oldest_request) {
                    $oldest_request = $timestamp;
                }
            }
        }
        
        $remaining = max(0, $limit - $requests_in_window);
        $reset_time = $oldest_request ? $oldest_request + $window : $current_time + $window;
        
        return [
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_time' => $reset_time,
            'window' => $window
        ];
    }
    
    /**
     * Carregar dados do arquivo
     */
    private function loadData() {
        if (!file_exists($this->storage_path)) {
            return [];
        }
        
        $content = file_get_contents($this->storage_path);
        $data = json_decode($content, true);
        
        return $data ?: [];
    }
    
    /**
     * Salvar dados no arquivo
     */
    private function saveData($data) {
        $content = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($this->storage_path, $content, LOCK_EX);
    }
    
    /**
     * Limpar dados antigos
     */
    private function cleanOldData(&$data, $window_start) {
        foreach ($data as $identifier => &$timestamps) {
            $timestamps = array_filter($timestamps, function($timestamp) use ($window_start) {
                return $timestamp >= $window_start;
            });
            
            // Remover identificadores vazios
            if (empty($timestamps)) {
                unset($data[$identifier]);
            }
        }
    }
    
    /**
     * Obter identificador único para a requisição
     */
    public static function getIdentifier($request = null) {
        // Usar IP como identificador padrão
        $ip = self::getClientIP();
        
        // Se houver autenticação, usar ID do usuário
        if (isset($_SESSION['usuario_id'])) {
            return 'user_' . $_SESSION['usuario_id'];
        }
        
        // Para requisições de API com token
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            if (!empty($token)) {
                return 'token_' . substr(md5($token), 0, 8);
            }
        }
        
        return 'ip_' . $ip;
    }
    
    /**
     * Obter IP real do cliente
     */
    private static function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Para X-Forwarded-For, pegar o primeiro IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Middleware para aplicar rate limiting
     */
    public static function middleware($limits = []) {
        $rate_limiter = new self();
        
        // Configurações padrão por endpoint
        $default_limits = [
            'auth/login' => ['limit' => 10, 'window' => 900], // 10 tentativas por 15 min
            'auth/register' => ['limit' => 5, 'window' => 3600], // 5 registros por hora
            'auth/forgot-password' => ['limit' => 3, 'window' => 3600], // 3 tentativas por hora
            'default' => ['limit' => 1000, 'window' => 3600] // 1000 requisições por hora
        ];
        
        // Mesclar com limites personalizados
        $limits = array_merge($default_limits, $limits);
        
        // Determinar endpoint atual
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($request_uri, PHP_URL_PATH);
        $path = trim(str_replace('/backend', '', $path), '/');
        
        // Encontrar limite aplicável
        $applicable_limit = $limits['default'];
        foreach ($limits as $pattern => $limit_config) {
            if ($pattern !== 'default' && strpos($path, $pattern) === 0) {
                $applicable_limit = $limit_config;
                break;
            }
        }
        
        $identifier = self::getIdentifier();
        $limit = $applicable_limit['limit'];
        $window = $applicable_limit['window'];
        
        // Verificar se está dentro do limite
        if (!$rate_limiter->isAllowed($identifier, $limit, $window)) {
            $limit_info = $rate_limiter->getLimitInfo($identifier, $limit, $window);
            
            // Headers de rate limiting
            header('X-RateLimit-Limit: ' . $limit_info['limit']);
            header('X-RateLimit-Remaining: ' . $limit_info['remaining']);
            header('X-RateLimit-Reset: ' . $limit_info['reset_time']);
            
            // Resposta de erro
            http_response_code(429);
            echo json_encode([
                'erro' => 'Muitas requisições',
                'mensagem' => 'Limite de requisições excedido. Tente novamente mais tarde.',
                'limite' => $limit_info['limit'],
                'reset_em' => date('Y-m-d H:i:s', $limit_info['reset_time'])
            ]);
            exit();
        }
        
        // Adicionar headers informativos
        $limit_info = $rate_limiter->getLimitInfo($identifier, $limit, $window);
        header('X-RateLimit-Limit: ' . $limit_info['limit']);
        header('X-RateLimit-Remaining: ' . $limit_info['remaining']);
        header('X-RateLimit-Reset: ' . $limit_info['reset_time']);
    }
}