<?php
/**
 * Agendador de Backups Automáticos
 * Portal de Notícias
 */

require_once __DIR__ . '/BackupManager.php';
require_once __DIR__ . '/../config/config.php';

class BackupScheduler {
    private $backup_manager;
    private $schedule_file;
    private $log_file;
    
    public function __construct() {
        $this->backup_manager = new BackupManager();
        $this->schedule_file = __DIR__ . '/../logs/backup_schedule.json';
        $this->log_file = __DIR__ . '/../logs/backup_scheduler.log';
        
        // Criar diretório de logs se não existir
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    /**
     * Configurar agendamento de backups
     */
    public function setSchedule($config) {
        $default_config = [
            'backup_completo' => [
                'habilitado' => true,
                'intervalo' => 'diario', // diario, semanal, mensal
                'hora' => '02:00',
                'dia_semana' => 0, // 0 = domingo, 1 = segunda, etc.
                'dia_mes' => 1 // para backup mensal
            ],
            'backup_incremental' => [
                'habilitado' => true,
                'intervalo' => 'horario', // horario, diario
                'minutos' => 0 // minuto da hora para executar
            ],
            'limpeza_automatica' => [
                'habilitado' => true,
                'manter_backups' => 30,
                'manter_dias' => 90
            ]
        ];
        
        $merged_config = array_merge_recursive($default_config, $config);
        
        file_put_contents($this->schedule_file, json_encode($merged_config, JSON_PRETTY_PRINT));
        
        $this->log('Configuração de agendamento atualizada');
        
        return [
            'sucesso' => true,
            'configuracao' => $merged_config
        ];
    }
    
    /**
     * Obter configuração atual
     */
    public function getSchedule() {
        if (!file_exists($this->schedule_file)) {
            return $this->setSchedule([]);
        }
        
        $config = json_decode(file_get_contents($this->schedule_file), true);
        return [
            'sucesso' => true,
            'configuracao' => $config
        ];
    }
    
    /**
     * Executar verificação de agendamento
     */
    public function checkSchedule() {
        $config_result = $this->getSchedule();
        if (!$config_result['sucesso']) {
            return ['sucesso' => false, 'erro' => 'Erro ao obter configuração'];
        }
        
        $config = $config_result['configuracao'];
        $now = new DateTime();
        $executed = [];
        
        // Verificar backup completo
        if ($config['backup_completo']['habilitado']) {
            if ($this->shouldRunFullBackup($config['backup_completo'], $now)) {
                $this->log('Iniciando backup completo agendado');
                $result = $this->backup_manager->createFullBackup();
                
                if ($result['sucesso']) {
                    $this->log('Backup completo concluído: ' . $result['arquivo']);
                    $executed[] = 'backup_completo';
                } else {
                    $this->log('Erro no backup completo: ' . $result['erro'], 'ERROR');
                }
            }
        }
        
        // Verificar backup incremental
        if ($config['backup_incremental']['habilitado']) {
            if ($this->shouldRunIncrementalBackup($config['backup_incremental'], $now)) {
                $this->log('Iniciando backup incremental agendado');
                $result = $this->backup_manager->createIncrementalBackup();
                
                if ($result['sucesso']) {
                    $this->log('Backup incremental concluído: ' . $result['arquivo']);
                    $executed[] = 'backup_incremental';
                } else {
                    $this->log('Erro no backup incremental: ' . $result['erro'], 'ERROR');
                }
            }
        }
        
        // Verificar limpeza automática
        if ($config['limpeza_automatica']['habilitado']) {
            if ($this->shouldRunCleanup($now)) {
                $this->log('Iniciando limpeza automática');
                $this->runAutomaticCleanup($config['limpeza_automatica']);
                $executed[] = 'limpeza_automatica';
            }
        }
        
        return [
            'sucesso' => true,
            'executados' => $executed,
            'timestamp' => $now->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Verificar se deve executar backup completo
     */
    private function shouldRunFullBackup($config, $now) {
        $last_run = $this->getLastRun('backup_completo');
        
        switch ($config['intervalo']) {
            case 'diario':
                if (!$last_run || $last_run->format('Y-m-d') !== $now->format('Y-m-d')) {
                    $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $config['hora']);
                    return $now >= $target_time;
                }
                break;
                
            case 'semanal':
                if (!$last_run || $last_run->format('Y-W') !== $now->format('Y-W')) {
                    if ($now->format('w') == $config['dia_semana']) {
                        $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $config['hora']);
                        return $now >= $target_time;
                    }
                }
                break;
                
            case 'mensal':
                if (!$last_run || $last_run->format('Y-m') !== $now->format('Y-m')) {
                    if ($now->format('j') == $config['dia_mes']) {
                        $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $config['hora']);
                        return $now >= $target_time;
                    }
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Verificar se deve executar backup incremental
     */
    private function shouldRunIncrementalBackup($config, $now) {
        $last_run = $this->getLastRun('backup_incremental');
        
        switch ($config['intervalo']) {
            case 'horario':
                if (!$last_run || $last_run->format('Y-m-d H') !== $now->format('Y-m-d H')) {
                    return $now->format('i') >= $config['minutos'];
                }
                break;
                
            case 'diario':
                if (!$last_run || $last_run->format('Y-m-d') !== $now->format('Y-m-d')) {
                    $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . ($config['hora'] ?? '03:00'));
                    return $now >= $target_time;
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Verificar se deve executar limpeza
     */
    private function shouldRunCleanup($now) {
        $last_run = $this->getLastRun('limpeza_automatica');
        
        // Executar limpeza uma vez por dia
        return !$last_run || $last_run->format('Y-m-d') !== $now->format('Y-m-d');
    }
    
    /**
     * Obter última execução de um tipo de backup
     */
    private function getLastRun($type) {
        $run_file = __DIR__ . "/../logs/last_run_{$type}.txt";
        
        if (!file_exists($run_file)) {
            return null;
        }
        
        $timestamp = file_get_contents($run_file);
        return DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Registrar última execução
     */
    private function setLastRun($type, $datetime = null) {
        $datetime = $datetime ?: new DateTime();
        $run_file = __DIR__ . "/../logs/last_run_{$type}.txt";
        
        file_put_contents($run_file, $datetime->format('Y-m-d H:i:s'));
    }
    
    /**
     * Executar limpeza automática
     */
    private function runAutomaticCleanup($config) {
        try {
            $backups = $this->backup_manager->listBackups();
            $deleted_count = 0;
            
            // Ordenar por data (mais antigos primeiro)
            usort($backups, function($a, $b) {
                return strtotime($a['data_criacao']) - strtotime($b['data_criacao']);
            });
            
            // Manter apenas os N backups mais recentes
            if (count($backups) > $config['manter_backups']) {
                $to_delete = array_slice($backups, 0, count($backups) - $config['manter_backups']);
                
                foreach ($to_delete as $backup) {
                    $filepath = __DIR__ . '/../backups/' . $backup['nome_arquivo'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                        $deleted_count++;
                    }
                }
            }
            
            // Remover backups mais antigos que X dias
            $cutoff_date = new DateTime();
            $cutoff_date->sub(new DateInterval('P' . $config['manter_dias'] . 'D'));
            
            foreach ($backups as $backup) {
                $backup_date = new DateTime($backup['data_criacao']);
                if ($backup_date < $cutoff_date) {
                    $filepath = __DIR__ . '/../backups/' . $backup['nome_arquivo'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                        $deleted_count++;
                    }
                }
            }
            
            $this->log("Limpeza automática concluída. {$deleted_count} backups removidos.");
            $this->setLastRun('limpeza_automatica');
            
        } catch (Exception $e) {
            $this->log('Erro na limpeza automática: ' . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Executar backup manual
     */
    public function runManualBackup($type = 'full') {
        $this->log("Iniciando backup manual: {$type}");
        
        if ($type === 'full') {
            $result = $this->backup_manager->createFullBackup();
            $this->setLastRun('backup_completo');
        } else {
            $result = $this->backup_manager->createIncrementalBackup();
            $this->setLastRun('backup_incremental');
        }
        
        if ($result['sucesso']) {
            $this->log("Backup manual concluído: {$result['arquivo']}");
        } else {
            $this->log("Erro no backup manual: {$result['erro']}", 'ERROR');
        }
        
        return $result;
    }
    
    /**
     * Obter status do agendador
     */
    public function getStatus() {
        $config_result = $this->getSchedule();
        $config = $config_result['configuracao'];
        
        $status = [
            'agendador_ativo' => true,
            'mysqldump_disponivel' => BackupManager::checkMysqldumpAvailable(),
            'configuracao' => $config,
            'proximas_execucoes' => $this->getNextExecutions($config),
            'ultimas_execucoes' => [
                'backup_completo' => $this->getLastRun('backup_completo'),
                'backup_incremental' => $this->getLastRun('backup_incremental'),
                'limpeza_automatica' => $this->getLastRun('limpeza_automatica')
            ],
            'estatisticas' => $this->backup_manager->getBackupStats()
        ];
        
        return $status;
    }
    
    /**
     * Calcular próximas execuções
     */
    private function getNextExecutions($config) {
        $now = new DateTime();
        $next = [];
        
        // Próximo backup completo
        if ($config['backup_completo']['habilitado']) {
            $next['backup_completo'] = $this->calculateNextFullBackup($config['backup_completo'], $now);
        }
        
        // Próximo backup incremental
        if ($config['backup_incremental']['habilitado']) {
            $next['backup_incremental'] = $this->calculateNextIncrementalBackup($config['backup_incremental'], $now);
        }
        
        return $next;
    }
    
    /**
     * Calcular próximo backup completo
     */
    private function calculateNextFullBackup($config, $now) {
        $next = clone $now;
        
        switch ($config['intervalo']) {
            case 'diario':
                $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $config['hora']);
                if ($now >= $target_time) {
                    $next->add(new DateInterval('P1D'));
                }
                $next->setTime(...explode(':', $config['hora']));
                break;
                
            case 'semanal':
                $days_until = ($config['dia_semana'] - $now->format('w') + 7) % 7;
                if ($days_until === 0) {
                    $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $config['hora']);
                    if ($now >= $target_time) {
                        $days_until = 7;
                    }
                }
                $next->add(new DateInterval('P' . $days_until . 'D'));
                $next->setTime(...explode(':', $config['hora']));
                break;
                
            case 'mensal':
                $next->setDate($next->format('Y'), $next->format('n'), $config['dia_mes']);
                $next->setTime(...explode(':', $config['hora']));
                if ($next <= $now) {
                    $next->add(new DateInterval('P1M'));
                }
                break;
        }
        
        return $next->format('Y-m-d H:i:s');
    }
    
    /**
     * Calcular próximo backup incremental
     */
    private function calculateNextIncrementalBackup($config, $now) {
        $next = clone $now;
        
        switch ($config['intervalo']) {
            case 'horario':
                $next->setTime($next->format('H'), $config['minutos'], 0);
                if ($next <= $now) {
                    $next->add(new DateInterval('PT1H'));
                }
                break;
                
            case 'diario':
                $hora = $config['hora'] ?? '03:00';
                $target_time = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $hora);
                if ($now >= $target_time) {
                    $next->add(new DateInterval('P1D'));
                }
                $next->setTime(...explode(':', $hora));
                break;
        }
        
        return $next->format('Y-m-d H:i:s');
    }
    
    /**
     * Registrar log
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obter logs recentes
     */
    public function getLogs($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($logs, -$lines);
    }
}