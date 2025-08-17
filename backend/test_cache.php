<?php
/**
 * Script de Teste do Sistema de Cache
 * Portal de Notícias
 */

require_once __DIR__ . '/../config-dev.php';
require_once __DIR__ . '/utils/CacheManager.php';
require_once __DIR__ . '/controllers/CacheController.php';

class CacheTester {
    private $cache_manager;
    private $cache_controller;
    private $test_results;
    
    public function __construct() {
        $this->cache_manager = new CacheManager();
        $this->cache_controller = new CacheController();
        $this->test_results = [];
    }
    
    /**
     * Executar todos os testes
     */
    public function runAllTests() {
        echo "=== TESTE DO SISTEMA DE CACHE ===\n\n";
        
        $this->testCacheManager();
        $this->testCacheOperations();
        $this->testCacheExpiration();
        $this->testCacheStats();
        $this->testCacheController();
        
        $this->showResults();
    }
    
    /**
     * Testar CacheManager básico
     */
    private function testCacheManager() {
        echo "1. Testando CacheManager...\n";
        
        try {
            // Testar instanciação
            echo "  → Testando instanciação...\n";
            $this->addResult('cache_manager_instancia', true, 'CacheManager instanciado');
            
            // Testar status inicial
            echo "  → Testando status inicial...\n";
            $enabled = $this->cache_manager->isEnabled();
            $this->addResult('cache_status_inicial', is_bool($enabled), 'Status do cache obtido');
            echo "    Cache habilitado: " . ($enabled ? 'Sim' : 'Não') . "\n";
            
            // Habilitar cache para testes
            if (!$enabled) {
                echo "  → Habilitando cache para testes...\n";
                $this->cache_manager->enable();
                $this->addResult('cache_habilitado', $this->cache_manager->isEnabled(), 'Cache habilitado para testes');
            }
            
        } catch (Exception $e) {
            $this->addResult('cache_manager_erro', false, 'CacheManager sem erros');
            echo "    ✗ Erro no CacheManager: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar operações básicas do cache
     */
    private function testCacheOperations() {
        echo "2. Testando operações básicas do cache...\n";
        
        try {
            // Testar set/get
            echo "  → Testando set/get...\n";
            $test_key = 'test_key_' . time();
            $test_data = ['message' => 'Hello Cache!', 'timestamp' => time()];
            
            $set_result = $this->cache_manager->set($test_key, $test_data);
            $this->addResult('cache_set', $set_result, 'Dados armazenados no cache');
            
            $get_result = $this->cache_manager->get($test_key);
            $this->addResult('cache_get', $get_result === $test_data, 'Dados recuperados do cache');
            
            // Testar has
            echo "  → Testando has...\n";
            $has_result = $this->cache_manager->has($test_key);
            $this->addResult('cache_has', $has_result, 'Verificação de existência funciona');
            
            // Testar delete
            echo "  → Testando delete...\n";
            $delete_result = $this->cache_manager->delete($test_key);
            $this->addResult('cache_delete', $delete_result, 'Remoção do cache funciona');
            
            $has_after_delete = $this->cache_manager->has($test_key);
            $this->addResult('cache_delete_verify', !$has_after_delete, 'Item removido do cache');
            
        } catch (Exception $e) {
            $this->addResult('cache_operations_erro', false, 'Operações básicas sem erros');
            echo "    ✗ Erro nas operações básicas: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar expiração do cache
     */
    private function testCacheExpiration() {
        echo "3. Testando expiração do cache...\n";
        
        try {
            // Testar TTL curto
            echo "  → Testando TTL de 2 segundos...\n";
            $test_key = 'test_expire_' . time();
            $test_data = 'Data that should expire';
            
            $this->cache_manager->set($test_key, $test_data, 2);
            $this->addResult('cache_ttl_set', $this->cache_manager->has($test_key), 'Item com TTL armazenado');
            
            echo "  → Aguardando 3 segundos...\n";
            sleep(3);
            
            $expired = !$this->cache_manager->has($test_key);
            $this->addResult('cache_ttl_expired', $expired, 'Item expirou corretamente');
            
        } catch (Exception $e) {
            $this->addResult('cache_expiration_erro', false, 'Expiração sem erros');
            echo "    ✗ Erro na expiração: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar estatísticas do cache
     */
    private function testCacheStats() {
        echo "4. Testando estatísticas do cache...\n";
        
        try {
            // Adicionar alguns itens para teste
            echo "  → Adicionando itens de teste...\n";
            for ($i = 1; $i <= 5; $i++) {
                $this->cache_manager->set("test_stats_{$i}", "Data {$i}");
            }
            
            // Testar estatísticas
            echo "  → Obtendo estatísticas...\n";
            $stats = $this->cache_manager->getStats();
            $this->addResult('cache_stats', is_array($stats), 'Estatísticas obtidas');
            
            echo "    Total de arquivos: {$stats['total_files']}\n";
            echo "    Tamanho total: {$stats['total_size']} bytes\n";
            echo "    Arquivos expirados: {$stats['expired_files']}\n";
            
            // Testar limpeza
            echo "  → Testando limpeza do cache...\n";
            $cleared = $this->cache_manager->clear();
            $this->addResult('cache_clear', $cleared >= 0, 'Cache limpo');
            echo "    Arquivos removidos: {$cleared}\n";
            
        } catch (Exception $e) {
            $this->addResult('cache_stats_erro', false, 'Estatísticas sem erros');
            echo "    ✗ Erro nas estatísticas: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar CacheController
     */
    private function testCacheController() {
        echo "5. Testando CacheController...\n";
        
        try {
            // Simular requisição GET para stats
            echo "  → Testando obtenção de estatísticas via controller...\n";
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['action'] = 'stats';
            
            ob_start();
            $this->cache_controller->processarRequisicao();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            $this->addResult('controller_stats', isset($response['success']), 'Controller retorna estatísticas');
            
            // Testar status
            echo "  → Testando obtenção de status via controller...\n";
            $_GET['action'] = 'status';
            
            ob_start();
            $this->cache_controller->processarRequisicao();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            $this->addResult('controller_status', isset($response['enabled']), 'Controller retorna status');
            
        } catch (Exception $e) {
            $this->addResult('cache_controller_erro', false, 'CacheController sem erros');
            echo "    ✗ Erro no CacheController: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Adicionar resultado do teste
     */
    private function addResult($test, $passed, $description) {
        $this->test_results[] = [
            'test' => $test,
            'passed' => $passed,
            'description' => $description
        ];
        
        $status = $passed ? '✓' : '✗';
        echo "    {$status} {$description}\n";
    }
    
    /**
     * Mostrar resultados finais
     */
    private function showResults() {
        echo "=== RESULTADOS DOS TESTES ===\n\n";
        
        $total = count($this->test_results);
        $passed = array_sum(array_column($this->test_results, 'passed'));
        $failed = $total - $passed;
        
        echo "Total de testes: {$total}\n";
        echo "Testes aprovados: {$passed}\n";
        echo "Testes falharam: {$failed}\n";
        echo "Taxa de sucesso: " . round(($passed / $total) * 100, 2) . "%\n\n";
        
        if ($failed > 0) {
            echo "Testes que falharam:\n";
            foreach ($this->test_results as $result) {
                if (!$result['passed']) {
                    echo "  ✗ {$result['description']}\n";
                }
            }
        } else {
            echo "🎉 Todos os testes passaram!\n";
        }
        
        echo "\n";
    }
}

// Executar testes
if (php_sapi_name() === 'cli') {
    // Execução via linha de comando
    $tester = new CacheTester();
    $tester->runAllTests();
} else {
    // Execução via web (apenas em desenvolvimento)
    if (defined('APP_CONFIG') && APP_CONFIG['environment'] === 'development') {
        header('Content-Type: text/plain; charset=utf-8');
        $tester = new CacheTester();
        $tester->runAllTests();
    } else {
        http_response_code(403);
        echo 'Acesso negado. Execute via linha de comando ou em ambiente de desenvolvimento.';
    }
}