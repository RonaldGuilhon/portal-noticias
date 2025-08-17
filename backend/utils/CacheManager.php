<?php
/**
 * Sistema de Cache
 * Portal de Notícias
 */

class CacheManager {
    private $cache_path;
    private $enabled;
    private $ttl;
    private $type;
    
    public function __construct() {
        $this->enabled = CACHE_CONFIG['enabled'] ?? false;
        $this->type = CACHE_CONFIG['type'] ?? 'file';
        $this->ttl = CACHE_CONFIG['ttl'] ?? 3600;
        $this->cache_path = CACHE_CONFIG['path'] ?? __DIR__ . '/../cache';
        
        // Criar diretório de cache se não existir
        if ($this->enabled && $this->type === 'file' && !file_exists($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
    }
    
    /**
     * Verificar se o cache está habilitado
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Gerar chave de cache
     */
    private function generateKey($key) {
        return md5($key);
    }
    
    /**
     * Obter caminho do arquivo de cache
     */
    private function getCacheFilePath($key) {
        $hash = $this->generateKey($key);
        return $this->cache_path . '/' . $hash . '.cache';
    }
    
    /**
     * Armazenar dados no cache
     */
    public function set($key, $data, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->ttl;
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        switch ($this->type) {
            case 'file':
                $file_path = $this->getCacheFilePath($key);
                return file_put_contents($file_path, serialize($cache_data)) !== false;
                
            default:
                return false;
        }
    }
    
    /**
     * Obter dados do cache
     */
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }
        
        switch ($this->type) {
            case 'file':
                $file_path = $this->getCacheFilePath($key);
                
                if (!file_exists($file_path)) {
                    return null;
                }
                
                $cache_data = unserialize(file_get_contents($file_path));
                
                // Verificar se expirou
                if ($cache_data['expires'] < time()) {
                    $this->delete($key);
                    return null;
                }
                
                return $cache_data['data'];
                
            default:
                return null;
        }
    }
    
    /**
     * Verificar se existe no cache
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Remover item do cache
     */
    public function delete($key) {
        if (!$this->enabled) {
            return false;
        }
        
        switch ($this->type) {
            case 'file':
                $file_path = $this->getCacheFilePath($key);
                if (file_exists($file_path)) {
                    return unlink($file_path);
                }
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * Limpar todo o cache
     */
    public function clear() {
        if (!$this->enabled) {
            return false;
        }
        
        switch ($this->type) {
            case 'file':
                $files = glob($this->cache_path . '/*.cache');
                $cleared = 0;
                
                foreach ($files as $file) {
                    if (unlink($file)) {
                        $cleared++;
                    }
                }
                
                return $cleared;
                
            default:
                return false;
        }
    }
    
    /**
     * Limpar cache expirado
     */
    public function clearExpired() {
        if (!$this->enabled) {
            return false;
        }
        
        switch ($this->type) {
            case 'file':
                $files = glob($this->cache_path . '/*.cache');
                $cleared = 0;
                
                foreach ($files as $file) {
                    $cache_data = unserialize(file_get_contents($file));
                    
                    if ($cache_data['expires'] < time()) {
                        if (unlink($file)) {
                            $cleared++;
                        }
                    }
                }
                
                return $cleared;
                
            default:
                return false;
        }
    }
    
    /**
     * Obter estatísticas do cache
     */
    public function getStats() {
        $stats = [
            'enabled' => $this->enabled,
            'type' => $this->type,
            'ttl' => $this->ttl,
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0
        ];
        
        if (!$this->enabled || $this->type !== 'file') {
            return $stats;
        }
        
        $files = glob($this->cache_path . '/*.cache');
        $stats['total_files'] = count($files);
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            
            $cache_data = unserialize(file_get_contents($file));
            if ($cache_data['expires'] < time()) {
                $stats['expired_files']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Habilitar cache
     */
    public function enable() {
        $this->enabled = true;
        
        // Criar diretório se necessário
        if ($this->type === 'file' && !file_exists($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
        
        return true;
    }
    
    /**
     * Desabilitar cache
     */
    public function disable() {
        $this->enabled = false;
        return true;
    }
    
    /**
     * Obter ou definir dados com callback
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data === null) {
            $data = $callback();
            $this->set($key, $data, $ttl);
        }
        
        return $data;
    }
}