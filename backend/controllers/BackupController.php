<?php
/**
 * Controlador de Backup
 * Portal de Notícias
 */

require_once __DIR__ . '/../utils/BackupManager.php';
require_once __DIR__ . '/../utils/BackupScheduler.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class BackupController {
    private $backup_manager;
    private $backup_scheduler;
    private $auth_middleware;
    
    public function __construct() {
        $this->backup_manager = new BackupManager();
        $this->backup_scheduler = new BackupScheduler();
        $this->auth_middleware = new AuthMiddleware();
    }
    
    /**
     * Processar requisição
     */
    public function handleRequest($method, $action, $data = []) {
        // Verificar se é ambiente de desenvolvimento ou usuário é admin
        if (!$this->isAuthorized()) {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Acesso negado. Apenas administradores podem acessar o sistema de backup.'
            ], 403);
        }
        
        switch ($method) {
            case 'GET':
                return $this->handleGet($action);
            case 'POST':
                return $this->handlePost($action, $data);
            case 'PUT':
                return $this->handlePut($action, $data);
            case 'DELETE':
                return $this->handleDelete($action, $data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Método não permitido'
                ], 405);
        }
    }
    
    /**
     * Processar requisições GET
     */
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                return $this->listBackups();
            case 'status':
                return $this->getStatus();
            case 'stats':
                return $this->getStats();
            case 'schedule':
                return $this->getSchedule();
            case 'logs':
                return $this->getLogs();
            case 'check':
                return $this->checkSystem();
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Ação não encontrada'
                ], 404);
        }
    }
    
    /**
     * Processar requisições POST
     */
    private function handlePost($action, $data) {
        switch ($action) {
            case 'create':
                return $this->createBackup($data);
            case 'restore':
                return $this->restoreBackup($data);
            case 'run-scheduled':
                return $this->runScheduledBackups();
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Ação não encontrada'
                ], 404);
        }
    }
    
    /**
     * Processar requisições PUT
     */
    private function handlePut($action, $data) {
        switch ($action) {
            case 'schedule':
                return $this->updateSchedule($data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Ação não encontrada'
                ], 404);
        }
    }
    
    /**
     * Processar requisições DELETE
     */
    private function handleDelete($action, $data) {
        switch ($action) {
            case 'backup':
                return $this->deleteBackup($data);
            case 'cleanup':
                return $this->cleanupBackups($data);
            default:
                return $this->jsonResponse([
                    'success' => false,
                    'erro' => 'Ação não encontrada'
                ], 404);
        }
    }
    
    /**
     * Listar backups disponíveis
     */
    private function listBackups() {
        $backups = $this->backup_manager->listBackups();
        
        return $this->jsonResponse([
            'success' => true,
            'backups' => $backups,
            'total' => count($backups)
        ]);
    }
    
    /**
     * Obter status do sistema de backup
     */
    private function getStatus() {
        $status = $this->backup_scheduler->getStatus();
        
        return $this->jsonResponse([
            'success' => true,
            'status' => $status
        ]);
    }
    
    /**
     * Obter estatísticas de backup
     */
    private function getStats() {
        $stats = $this->backup_manager->getBackupStats();
        
        return $this->jsonResponse([
            'success' => true,
            'estatisticas' => $stats
        ]);
    }
    
    /**
     * Obter configuração de agendamento
     */
    private function getSchedule() {
        $schedule = $this->backup_scheduler->getSchedule();
        
        return $this->jsonResponse($schedule);
    }
    
    /**
     * Obter logs do sistema
     */
    private function getLogs() {
        $lines = $_GET['lines'] ?? 100;
        $logs = $this->backup_scheduler->getLogs($lines);
        
        return $this->jsonResponse([
            'success' => true,
            'logs' => $logs,
            'total_linhas' => count($logs)
        ]);
    }
    
    /**
     * Verificar sistema de backup
     */
    private function checkSystem() {
        $checks = [
            'mysqldump_disponivel' => BackupManager::checkMysqldumpAvailable(),
            'diretorio_backup_existe' => file_exists(__DIR__ . '/../backups/'),
            'diretorio_backup_gravavel' => is_writable(__DIR__ . '/../backups/'),
            'diretorio_logs_existe' => file_exists(__DIR__ . '/../logs/'),
            'diretorio_logs_gravavel' => is_writable(__DIR__ . '/../logs/'),
            'extensao_gzip' => function_exists('gzopen'),
            'conexao_banco' => $this->testDatabaseConnection()
        ];
        
        $all_ok = array_reduce($checks, function($carry, $check) {
            return $carry && $check;
        }, true);
        
        return $this->jsonResponse([
            'success' => true,
            'sistema_ok' => $all_ok,
            'verificacoes' => $checks,
            'recomendacoes' => $this->getRecommendations($checks)
        ]);
    }
    
    /**
     * Criar backup
     */
    private function createBackup($data) {
        $type = $data['tipo'] ?? 'full';
        $custom_name = $data['nome_personalizado'] ?? null;
        
        if ($type === 'full') {
            $result = $this->backup_manager->createFullBackup($custom_name);
        } elseif ($type === 'incremental') {
            $result = $this->backup_manager->createIncrementalBackup();
        } else {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Tipo de backup inválido. Use "full" ou "incremental".'
            ], 400);
        }
        
        $status_code = $result['success'] ? 200 : 500;
        return $this->jsonResponse($result, $status_code);
    }
    
    /**
     * Restaurar backup
     */
    private function restoreBackup($data) {
        if (!isset($data['arquivo'])) {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Nome do arquivo de backup é obrigatório'
            ], 400);
        }
        
        // Confirmação adicional para restauração
        if (!isset($data['confirmacao']) || $data['confirmacao'] !== 'CONFIRMO_RESTAURACAO') {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Confirmação obrigatória. Envie "confirmacao": "CONFIRMO_RESTAURACAO" para prosseguir.'
            ], 400);
        }
        
        $result = $this->backup_manager->restoreBackup($data['arquivo']);
        
        $status_code = $result['success'] ? 200 : 500;
        return $this->jsonResponse($result, $status_code);
    }
    
    /**
     * Executar backups agendados
     */
    private function runScheduledBackups() {
        $result = $this->backup_scheduler->checkSchedule();
        
        return $this->jsonResponse($result);
    }
    
    /**
     * Atualizar configuração de agendamento
     */
    private function updateSchedule($data) {
        $result = $this->backup_scheduler->setSchedule($data);
        
        return $this->jsonResponse($result);
    }
    
    /**
     * Deletar backup específico
     */
    private function deleteBackup($data) {
        if (!isset($data['arquivo'])) {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Nome do arquivo é obrigatório'
            ], 400);
        }
        
        $filepath = __DIR__ . '/../backups/' . $data['arquivo'];
        
        if (!file_exists($filepath)) {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Arquivo de backup não encontrado'
            ], 404);
        }
        
        if (unlink($filepath)) {
            // Remover do banco de dados também
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "DELETE FROM backups WHERE nome_arquivo = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$data['arquivo']]);
                
                return $this->jsonResponse([
                    'success' => true,
                    'mensagem' => 'Backup deletado com success'
                ]);
                
            } catch (Exception $e) {
                return $this->jsonResponse([
                    'success' => true,
                    'mensagem' => 'Arquivo deletado, mas erro ao remover do banco: ' . $e->getMessage()
                ]);
            }
        } else {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Erro ao deletar arquivo'
            ], 500);
        }
    }
    
    /**
     * Limpeza de backups antigos
     */
    private function cleanupBackups($data) {
        $days = $data['dias'] ?? 30;
        $max_backups = $data['max_backups'] ?? 50;
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Obter backups antigos
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $query = "SELECT nome_arquivo FROM backups WHERE data_criacao < ? OR id NOT IN (SELECT id FROM backups ORDER BY data_criacao DESC LIMIT ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$cutoff_date, $max_backups]);
            
            $deleted_count = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $filepath = __DIR__ . '/../backups/' . $row['nome_arquivo'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                    $deleted_count++;
                }
                
                // Remover do banco
                $delete_query = "DELETE FROM backups WHERE nome_arquivo = ?";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->execute([$row['nome_arquivo']]);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'mensagem' => "Limpeza concluída com success. {$deleted_count} backups removidos.",
                'removidos' => $deleted_count
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'erro' => 'Erro na limpeza: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar autorização
     */
    private function isAuthorized() {
        // Em desenvolvimento, permitir acesso
        if (defined('AMBIENTE') && AMBIENTE === 'desenvolvimento') {
            return true;
        }
        
        // Em produção, verificar se é administrador
        $auth_result = $this->auth_middleware->verificarToken();
        if (!$auth_result['valido']) {
            return false;
        }
        
        return $auth_result['usuario']['tipo'] === 'admin';
    }
    
    /**
     * Testar conexão com banco de dados
     */
    private function testDatabaseConnection() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obter recomendações baseadas nas verificações
     */
    private function getRecommendations($checks) {
        $recommendations = [];
        
        if (!$checks['mysqldump_disponivel']) {
            $recommendations[] = 'Instale o MySQL Client para habilitar mysqldump';
        }
        
        if (!$checks['diretorio_backup_existe']) {
            $recommendations[] = 'Crie o diretório de backups: backend/backups/';
        }
        
        if (!$checks['diretorio_backup_gravavel']) {
            $recommendations[] = 'Configure permissões de escrita no diretório de backups';
        }
        
        if (!$checks['diretorio_logs_existe']) {
            $recommendations[] = 'Crie o diretório de logs: backend/logs/';
        }
        
        if (!$checks['diretorio_logs_gravavel']) {
            $recommendations[] = 'Configure permissões de escrita no diretório de logs';
        }
        
        if (!$checks['extensao_gzip']) {
            $recommendations[] = 'Habilite a extensão zlib do PHP para compressão de backups';
        }
        
        if (!$checks['conexao_banco']) {
            $recommendations[] = 'Verifique a configuração de conexão com o banco de dados';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Sistema de backup configurado corretamente!';
        }
        
        return $recommendations;
    }
    
    /**
     * Resposta JSON padronizada
     */
    private function jsonResponse($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}