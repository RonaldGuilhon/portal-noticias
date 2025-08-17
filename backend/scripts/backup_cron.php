#!/usr/bin/env php
<?php
/**
 * Script de Backup Automático para Cron Job
 * Portal de Notícias
 * 
 * Uso:
 * php backup_cron.php [--type=full|incremental] [--force] [--quiet]
 * 
 * Exemplos de configuração no crontab:
 * # Backup completo diário às 2:00
 * 0 2 * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php --type=full
 * 
 * # Backup incremental a cada hora
 * 0 * * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php --type=incremental
 * 
 * # Verificação e execução de backups agendados a cada 15 minutos
 * 0/15 * * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php
 */

// Definir que é execução via CLI
define('CLI_EXECUTION', true);

// Incluir dependências
require_once __DIR__ . '/../utils/BackupScheduler.php';
require_once __DIR__ . '/../config/config.php';

class BackupCronJob {
    private $scheduler;
    private $options;
    private $quiet;
    
    public function __construct($argv) {
        $this->scheduler = new BackupScheduler();
        $this->options = $this->parseArguments($argv);
        $this->quiet = isset($this->options['quiet']);
    }
    
    /**
     * Executar job de backup
     */
    public function run() {
        try {
            $this->log("Iniciando job de backup - " . date('Y-m-d H:i:s'));
            
            // Verificar se mysqldump está disponível
            if (!BackupManager::checkMysqldumpAvailable()) {
                $this->log("ERRO: mysqldump não está disponível", 'ERROR');
                exit(1);
            }
            
            if (isset($this->options['type'])) {
                // Executar tipo específico de backup
                $this->runSpecificBackup($this->options['type']);
            } else {
                // Executar verificação de agendamento
                $this->runScheduledCheck();
            }
            
            $this->log("Job de backup concluído com sucesso");
            exit(0);
            
        } catch (Exception $e) {
            $this->log("ERRO no job de backup: " . $e->getMessage(), 'ERROR');
            exit(1);
        }
    }
    
    /**
     * Executar backup específico
     */
    private function runSpecificBackup($type) {
        $force = isset($this->options['force']);
        
        switch ($type) {
            case 'full':
            case 'completo':
                $this->log("Executando backup completo...");
                $result = $this->scheduler->runManualBackup('full');
                break;
                
            case 'incremental':
                $this->log("Executando backup incremental...");
                $result = $this->scheduler->runManualBackup('incremental');
                break;
                
            default:
                throw new Exception("Tipo de backup inválido: {$type}");
        }
        
        if ($result['sucesso']) {
            $this->log("Backup {$type} concluído: {$result['arquivo']} ({$result['tamanho']})");
        } else {
            throw new Exception("Falha no backup {$type}: {$result['erro']}");
        }
    }
    
    /**
     * Executar verificação de agendamento
     */
    private function runScheduledCheck() {
        $this->log("Verificando backups agendados...");
        
        $result = $this->scheduler->checkSchedule();
        
        if ($result['sucesso']) {
            if (empty($result['executados'])) {
                $this->log("Nenhum backup agendado para execução no momento");
            } else {
                $this->log("Backups executados: " . implode(', ', $result['executados']));
            }
        } else {
            throw new Exception("Erro na verificação de agendamento: " . ($result['erro'] ?? 'Erro desconhecido'));
        }
    }
    
    /**
     * Analisar argumentos da linha de comando
     */
    private function parseArguments($argv) {
        $options = [];
        
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (strpos($arg, '--') === 0) {
                $arg = substr($arg, 2);
                
                if (strpos($arg, '=') !== false) {
                    list($key, $value) = explode('=', $arg, 2);
                    $options[$key] = $value;
                } else {
                    $options[$arg] = true;
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Log de mensagens
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$level}] {$message}";
        
        // Log para arquivo
        $log_file = __DIR__ . '/../logs/backup_cron.log';
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Output para console (se não estiver em modo quiet)
        if (!$this->quiet) {
            echo $log_message . PHP_EOL;
        }
    }
    
    /**
     * Mostrar ajuda
     */
    public static function showHelp() {
        echo "\nScript de Backup Automático - Portal de Notícias\n";
        echo "================================================\n\n";
        echo "Uso: php backup_cron.php [opções]\n\n";
        echo "Opções:\n";
        echo "  --type=TYPE        Tipo de backup (full, incremental)\n";
        echo "  --force            Forçar execução mesmo se não estiver agendado\n";
        echo "  --quiet            Executar em modo silencioso\n";
        echo "  --help             Mostrar esta ajuda\n\n";
        echo "Exemplos:\n";
        echo "  php backup_cron.php                           # Verificar agendamentos\n";
        echo "  php backup_cron.php --type=full               # Backup completo\n";
        echo "  php backup_cron.php --type=incremental        # Backup incremental\n";
        echo "  php backup_cron.php --type=full --quiet       # Backup completo silencioso\n\n";
        echo "Configuração do Crontab:\n";
        echo "  # Backup completo diário às 2:00\n";
        echo "  0 2 * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php --type=full --quiet\n\n";
        echo "  # Backup incremental a cada hora\n";
        echo "  0 * * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php --type=incremental --quiet\n\n";
        echo "  # Verificação de agendamentos a cada 15 minutos\n";
        echo "  */15 * * * * /usr/bin/php /caminho/para/backend/scripts/backup_cron.php --quiet\n\n";
    }
}

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Este script deve ser executado via linha de comando.');
}

// Verificar argumentos
if (isset($argv[1]) && ($argv[1] === '--help' || $argv[1] === '-h')) {
    BackupCronJob::showHelp();
    exit(0);
}

// Executar job
try {
    $job = new BackupCronJob($argv);
    $job->run();
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}