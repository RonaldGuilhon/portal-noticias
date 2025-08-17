<?php
/**
 * Script de Teste do Sistema de Backup
 * Portal de Notícias
 */

require_once __DIR__ . '/utils/BackupManager.php';
require_once __DIR__ . '/utils/BackupScheduler.php';
require_once __DIR__ . '/controllers/BackupController.php';

class BackupTester {
    private $backup_manager;
    private $backup_scheduler;
    private $backup_controller;
    private $test_results;
    
    public function __construct() {
        $this->backup_manager = new BackupManager();
        $this->backup_scheduler = new BackupScheduler();
        $this->backup_controller = new BackupController();
        $this->test_results = [];
    }
    
    /**
     * Executar todos os testes
     */
    public function runAllTests() {
        echo "\n=== TESTE DO SISTEMA DE BACKUP ===\n\n";
        
        $this->testSystemRequirements();
        $this->testBackupManager();
        $this->testBackupScheduler();
        $this->testBackupController();
        $this->testFileOperations();
        
        $this->showResults();
    }
    
    /**
     * Testar requisitos do sistema
     */
    private function testSystemRequirements() {
        echo "1. Testando requisitos do sistema...\n";
        
        // Verificar mysqldump
        $mysqldump_available = BackupManager::checkMysqldumpAvailable();
        $this->addResult('mysqldump_disponivel', $mysqldump_available, 'mysqldump está disponível');
        
        // Verificar diretórios
        $backup_dir = __DIR__ . '/backups/';
        $logs_dir = __DIR__ . '/logs/';
        
        $backup_dir_exists = file_exists($backup_dir);
        $this->addResult('diretorio_backup_existe', $backup_dir_exists, 'Diretório de backup existe');
        
        if (!$backup_dir_exists) {
            mkdir($backup_dir, 0755, true);
            echo "  → Diretório de backup criado\n";
        }
        
        $logs_dir_exists = file_exists($logs_dir);
        $this->addResult('diretorio_logs_existe', $logs_dir_exists, 'Diretório de logs existe');
        
        if (!$logs_dir_exists) {
            mkdir($logs_dir, 0755, true);
            echo "  → Diretório de logs criado\n";
        }
        
        // Verificar permissões
        $backup_writable = is_writable($backup_dir);
        $logs_writable = is_writable($logs_dir);
        
        $this->addResult('backup_gravavel', $backup_writable, 'Diretório de backup é gravável');
        $this->addResult('logs_gravavel', $logs_writable, 'Diretório de logs é gravável');
        
        // Verificar extensões PHP
        $gzip_available = function_exists('gzopen');
        $this->addResult('gzip_disponivel', $gzip_available, 'Extensão gzip disponível');
        
        echo "\n";
    }
    
    /**
     * Testar BackupManager
     */
    private function testBackupManager() {
        echo "2. Testando BackupManager...\n";
        
        try {
            // Testar backup completo
            echo "  → Criando backup completo de teste...\n";
            $result = $this->backup_manager->createFullBackup('teste_backup_' . date('Y-m-d_H-i-s') . '.sql');
            
            $this->addResult('backup_completo', $result['success'], 'Backup completo criado');
            
            if ($result['success']) {
                echo "    ✓ Backup criado: {$result['arquivo']} ({$result['tamanho']})\n";
                
                // Verificar se o arquivo existe
                $backup_path = __DIR__ . '/backups/' . $result['arquivo'];
                $file_exists = file_exists($backup_path);
                $this->addResult('arquivo_backup_existe', $file_exists, 'Arquivo de backup existe');
                
                if ($file_exists) {
                    $file_size = filesize($backup_path);
                    echo "    ✓ Arquivo existe e tem {$file_size} bytes\n";
                }
            } else {
                echo "    ✗ Erro: {$result['erro']}\n";
            }
            
            // Testar listagem de backups
            echo "  → Testando listagem de backups...\n";
            $backups = $this->backup_manager->listBackups();
            $this->addResult('listagem_backups', is_array($backups), 'Listagem de backups funciona');
            echo "    ✓ " . count($backups) . " backups encontrados\n";
            
            // Testar estatísticas
            echo "  → Testando estatísticas...\n";
            $stats = $this->backup_manager->getBackupStats();
            $this->addResult('estatisticas_backup', is_array($stats), 'Estatísticas de backup funcionam');
            
        } catch (Exception $e) {
            $this->addResult('backup_manager_erro', false, 'BackupManager sem erros');
            echo "    ✗ Erro no BackupManager: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar BackupScheduler
     */
    private function testBackupScheduler() {
        echo "3. Testando BackupScheduler...\n";
        
        try {
            // Testar configuração de agendamento
            echo "  → Testando configuração de agendamento...\n";
            $config = [
                'backup_completo' => [
                    'habilitado' => true,
                    'intervalo' => 'diario',
                    'hora' => '02:00'
                ],
                'backup_incremental' => [
                    'habilitado' => true,
                    'intervalo' => 'horario',
                    'minutos' => 0
                ]
            ];
            
            $result = $this->backup_scheduler->setSchedule($config);
            $this->addResult('configuracao_agendamento', $result['success'], 'Configuração de agendamento funciona');
            
            // Testar obtenção de configuração
            echo "  → Testando obtenção de configuração...\n";
            $schedule = $this->backup_scheduler->getSchedule();
            $this->addResult('obtencao_configuracao', $schedule['success'], 'Obtenção de configuração funciona');
            
            // Testar verificação de agendamento
            echo "  → Testando verificação de agendamento...\n";
            $check_result = $this->backup_scheduler->checkSchedule();
            $this->addResult('verificacao_agendamento', $check_result['success'], 'Verificação de agendamento funciona');
            
            // Testar status
            echo "  → Testando status do agendador...\n";
            $status = $this->backup_scheduler->getStatus();
            $this->addResult('status_agendador', is_array($status), 'Status do agendador funciona');
            
        } catch (Exception $e) {
            $this->addResult('backup_scheduler_erro', false, 'BackupScheduler sem erros');
            echo "    ✗ Erro no BackupScheduler: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar BackupController
     */
    private function testBackupController() {
        echo "4. Testando BackupController...\n";
        
        try {
            // Simular requisições
            echo "  → Testando endpoint de status...\n";
            $response = $this->backup_controller->handleRequest('GET', 'status');
            $data = json_decode($response, true);
            $this->addResult('controller_status', $data['success'] ?? false, 'Endpoint de status funciona');
            
            echo "  → Testando endpoint de verificação...\n";
            $response = $this->backup_controller->handleRequest('GET', 'check');
            $data = json_decode($response, true);
            $this->addResult('controller_check', $data['success'] ?? false, 'Endpoint de verificação funciona');
            
            echo "  → Testando endpoint de listagem...\n";
            $response = $this->backup_controller->handleRequest('GET', 'list');
            $data = json_decode($response, true);
            $this->addResult('controller_list', $data['success'] ?? false, 'Endpoint de listagem funciona');
            
        } catch (Exception $e) {
            $this->addResult('backup_controller_erro', false, 'BackupController sem erros');
            echo "    ✗ Erro no BackupController: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Testar operações de arquivo
     */
    private function testFileOperations() {
        echo "5. Testando operações de arquivo...\n";
        
        try {
            // Testar criação de arquivo de teste
            $test_file = __DIR__ . '/backups/teste_arquivo.txt';
            $test_content = 'Teste de backup - ' . date('Y-m-d H:i:s');
            
            echo "  → Testando criação de arquivo...\n";
            $write_result = file_put_contents($test_file, $test_content);
            $this->addResult('criacao_arquivo', $write_result !== false, 'Criação de arquivo funciona');
            
            // Testar leitura de arquivo
            echo "  → Testando leitura de arquivo...\n";
            $read_content = file_get_contents($test_file);
            $this->addResult('leitura_arquivo', $read_content === $test_content, 'Leitura de arquivo funciona');
            
            // Testar compressão (se disponível)
            if (function_exists('gzopen')) {
                echo "  → Testando compressão...\n";
                $compressed_file = $test_file . '.gz';
                
                $gz = gzopen($compressed_file, 'wb9');
                gzwrite($gz, $test_content);
                gzclose($gz);
                
                $compression_works = file_exists($compressed_file) && filesize($compressed_file) > 0;
                $this->addResult('compressao_arquivo', $compression_works, 'Compressão de arquivo funciona');
                
                // Limpar arquivos de teste
                if (file_exists($compressed_file)) {
                    unlink($compressed_file);
                }
            }
            
            // Limpar arquivo de teste
            if (file_exists($test_file)) {
                unlink($test_file);
            }
            
        } catch (Exception $e) {
            $this->addResult('operacoes_arquivo_erro', false, 'Operações de arquivo sem erros');
            echo "    ✗ Erro nas operações de arquivo: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Adicionar resultado de teste
     */
    private function addResult($key, $success, $description) {
        $this->test_results[$key] = [
            'success' => $success,
            'descricao' => $description
        ];
        
        $status = $success ? '✓' : '✗';
        $color = $success ? '\033[32m' : '\033[31m'; // Verde ou vermelho
        $reset = '\033[0m';
        
        echo "    {$color}{$status}{$reset} {$description}\n";
    }
    
    /**
     * Mostrar resultados finais
     */
    private function showResults() {
        echo "\n=== RESULTADOS DOS TESTES ===\n\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = array_sum(array_column($this->test_results, 'success'));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "Total de testes: {$total_tests}\n";
        echo "\033[32mTestes aprovados: {$passed_tests}\033[0m\n";
        echo "\033[31mTestes falharam: {$failed_tests}\033[0m\n\n";
        
        if ($failed_tests > 0) {
            echo "\033[31mTestes que falharam:\033[0m\n";
            foreach ($this->test_results as $key => $result) {
                if (!$result['success']) {
                    echo "  ✗ {$result['descricao']}\n";
                }
            }
            echo "\n";
        }
        
        $success_rate = round(($passed_tests / $total_tests) * 100, 1);
        echo "Taxa de sucesso: {$success_rate}%\n\n";
        
        if ($success_rate >= 90) {
            echo "\033[32m🎉 Sistema de backup está funcionando corretamente!\033[0m\n";
        } elseif ($success_rate >= 70) {
            echo "\033[33m⚠️  Sistema de backup está parcialmente funcional. Verifique os erros acima.\033[0m\n";
        } else {
            echo "\033[31m❌ Sistema de backup apresenta problemas significativos. Corrija os erros antes de usar.\033[0m\n";
        }
        
        echo "\n";
    }
}

// Executar testes
if (php_sapi_name() === 'cli') {
    // Execução via linha de comando
    $tester = new BackupTester();
    $tester->runAllTests();
} else {
    // Execução via web (apenas em desenvolvimento)
    if (defined('AMBIENTE') && AMBIENTE === 'desenvolvimento') {
        header('Content-Type: text/plain; charset=utf-8');
        $tester = new BackupTester();
        $tester->runAllTests();
    } else {
        http_response_code(403);
        echo 'Acesso negado. Execute via linha de comando ou em ambiente de desenvolvimento.';
    }
}