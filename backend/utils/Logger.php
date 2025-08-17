<?php

class Logger {
    private $logFile;
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: __DIR__ . '/../logs/app.log';
        
        // Criar diretório de logs se não existir
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log de informação
     * @param string $message Mensagem a ser logada
     * @param array $data Dados adicionais (opcional)
     */
    public function info($message, $data = []) {
        $this->log('INFO', $message, $data);
    }
    
    /**
     * Log de erro
     * @param string $message Mensagem de erro
     * @param array $data Dados adicionais (opcional)
     */
    public function error($message, $data = []) {
        $this->log('ERROR', $message, $data);
    }
    
    /**
     * Log de warning
     * @param string $message Mensagem de warning
     * @param array $data Dados adicionais (opcional)
     */
    public function warning($message, $data = []) {
        $this->log('WARNING', $message, $data);
    }
    
    /**
     * Log de debug
     * @param string $message Mensagem de debug
     * @param array $data Dados adicionais (opcional)
     */
    public function debug($message, $data = []) {
        $this->log('DEBUG', $message, $data);
    }
    
    /**
     * Método privado para escrever logs
     * @param string $level Nível do log
     * @param string $message Mensagem
     * @param array $data Dados adicionais
     */
    private function log($level, $message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $dataString = !empty($data) ? ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$dataString}" . PHP_EOL;
        
        // Escrever no arquivo de log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Em desenvolvimento, também exibir no console/error_log
        if (defined('DEBUG') && DEBUG) {
            error_log($logEntry);
        }
    }
}