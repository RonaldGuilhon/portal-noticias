<?php
/**
 * Sistema de Backup Automático do Banco de Dados
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class BackupManager {
    private $db;
    private $backup_path;
    private $max_backups;
    private $compression_enabled;
    
    public function __construct($backup_path = null, $max_backups = 30, $compression = true) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->backup_path = $backup_path ?: __DIR__ . '/../backups/';
        $this->max_backups = $max_backups;
        $this->compression_enabled = $compression;
        
        // Criar diretório de backup se não existir
        if (!file_exists($this->backup_path)) {
            mkdir($this->backup_path, 0755, true);
        }
    }
    
    /**
     * Criar backup completo do banco de dados
     */
    public function createFullBackup($custom_name = null) {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = $custom_name ?: "backup_full_{$timestamp}.sql";
            $filepath = $this->backup_path . $filename;
            
            // Obter informações de conexão
            $host = DB_HOST;
            $username = DB_USER;
            $password = DB_PASS;
            $database = DB_NAME;
            
            // Comando mysqldump
            $command = "mysqldump --host={$host} --user={$username} --password={$password} ";
            $command .= "--single-transaction --routines --triggers --add-drop-table ";
            $command .= "--complete-insert --extended-insert --create-options ";
            $command .= "--quick --lock-tables=false {$database} > {$filepath}";
            
            // Executar backup
            $output = [];
            $return_code = 0;
            exec($command, $output, $return_code);
            
            if ($return_code !== 0) {
                throw new Exception('Erro ao executar mysqldump: ' . implode('\n', $output));
            }
            
            // Verificar se o arquivo foi criado
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception('Arquivo de backup não foi criado ou está vazio');
            }
            
            // Comprimir se habilitado
            if ($this->compression_enabled) {
                $compressed_file = $this->compressBackup($filepath);
                if ($compressed_file) {
                    unlink($filepath); // Remover arquivo não comprimido
                    $filepath = $compressed_file;
                    $filename = basename($compressed_file);
                }
            }
            
            // Registrar backup no banco
            $this->registerBackup($filename, filesize($filepath), 'full');
            
            // Limpar backups antigos
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'arquivo' => $filename,
                'caminho' => $filepath,
                'tamanho' => $this->formatBytes(filesize($filepath)),
                'timestamp' => $timestamp
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Criar backup incremental (apenas dados modificados)
     */
    public function createIncrementalBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_incremental_{$timestamp}.sql";
            $filepath = $this->backup_path . $filename;
            
            // Obter último backup
            $last_backup = $this->getLastBackupTime();
            
            $sql_content = "-- Backup Incremental - {$timestamp}\n";
            $sql_content .= "-- Dados modificados desde: {$last_backup}\n\n";
            
            // Tabelas para backup incremental
            $tables = ['noticias', 'comentarios', 'usuarios', 'categorias', 'tags'];
            
            foreach ($tables as $table) {
                $sql_content .= $this->getIncrementalTableData($table, $last_backup);
            }
            
            file_put_contents($filepath, $sql_content);
            
            // Comprimir se habilitado
            if ($this->compression_enabled) {
                $compressed_file = $this->compressBackup($filepath);
                if ($compressed_file) {
                    unlink($filepath);
                    $filepath = $compressed_file;
                    $filename = basename($compressed_file);
                }
            }
            
            // Registrar backup
            $this->registerBackup($filename, filesize($filepath), 'incremental');
            
            return [
                'success' => true,
                'arquivo' => $filename,
                'caminho' => $filepath,
                'tamanho' => $this->formatBytes(filesize($filepath)),
                'timestamp' => $timestamp
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restaurar backup
     */
    public function restoreBackup($filename) {
        try {
            $filepath = $this->backup_path . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('Arquivo de backup não encontrado');
            }
            
            // Descomprimir se necessário
            $temp_file = null;
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'gz') {
                $temp_file = $this->decompressBackup($filepath);
                $filepath = $temp_file;
            }
            
            // Obter informações de conexão
            $host = DB_HOST;
            $username = DB_USER;
            $password = DB_PASS;
            $database = DB_NAME;
            
            // Comando mysql para restaurar
            $command = "mysql --host={$host} --user={$username} --password={$password} {$database} < {$filepath}";
            
            $output = [];
            $return_code = 0;
            exec($command, $output, $return_code);
            
            // Limpar arquivo temporário
            if ($temp_file && file_exists($temp_file)) {
                unlink($temp_file);
            }
            
            if ($return_code !== 0) {
                throw new Exception('Erro ao restaurar backup: ' . implode('\n', $output));
            }
            
            return [
                'success' => true,
                'mensagem' => 'Backup restaurado com success',
                'arquivo' => $filename
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar backups disponíveis
     */
    public function listBackups() {
        try {
            $query = "SELECT * FROM backups ORDER BY data_criacao DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $backups = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $filepath = $this->backup_path . $row['nome_arquivo'];
                $row['existe'] = file_exists($filepath);
                $row['tamanho_formatado'] = $this->formatBytes($row['tamanho']);
                $backups[] = $row;
            }
            
            return $backups;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Comprimir arquivo de backup
     */
    private function compressBackup($filepath) {
        if (!function_exists('gzopen')) {
            return false;
        }
        
        $compressed_file = $filepath . '.gz';
        
        $file = fopen($filepath, 'rb');
        $gz_file = gzopen($compressed_file, 'wb9');
        
        if (!$file || !$gz_file) {
            return false;
        }
        
        while (!feof($file)) {
            gzwrite($gz_file, fread($file, 8192));
        }
        
        fclose($file);
        gzclose($gz_file);
        
        return $compressed_file;
    }
    
    /**
     * Descomprimir arquivo de backup
     */
    private function decompressBackup($compressed_file) {
        if (!function_exists('gzopen')) {
            return false;
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), 'backup_restore_');
        
        $gz_file = gzopen($compressed_file, 'rb');
        $file = fopen($temp_file, 'wb');
        
        if (!$gz_file || !$file) {
            return false;
        }
        
        while (!gzeof($gz_file)) {
            fwrite($file, gzread($gz_file, 8192));
        }
        
        gzclose($gz_file);
        fclose($file);
        
        return $temp_file;
    }
    
    /**
     * Obter dados incrementais de uma tabela
     */
    private function getIncrementalTableData($table, $since_date) {
        $sql_content = "\n-- Dados da tabela: {$table}\n";
        
        // Verificar se a tabela tem campo de timestamp
        $timestamp_fields = ['data_criacao', 'data_atualizacao', 'created_at', 'updated_at'];
        $timestamp_field = null;
        
        foreach ($timestamp_fields as $field) {
            $check_query = "SHOW COLUMNS FROM {$table} LIKE '{$field}'";
            $stmt = $this->db->prepare($check_query);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $timestamp_field = $field;
                break;
            }
        }
        
        if (!$timestamp_field) {
            return $sql_content . "-- Tabela {$table} não possui campo de timestamp para backup incremental\n";
        }
        
        // Obter registros modificados
        $query = "SELECT * FROM {$table} WHERE {$timestamp_field} >= ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$since_date]);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return $sql_content . "-- Nenhum registro modificado na tabela {$table}\n";
        }
        
        // Gerar comandos INSERT
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(function($value) {
                return $value === null ? 'NULL' : $this->db->quote($value);
            }, array_values($row));
            
            $sql_content .= "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ") ON DUPLICATE KEY UPDATE ";
            
            $updates = [];
            foreach ($columns as $column) {
                if ($column !== 'id') {
                    $updates[] = "{$column} = VALUES({$column})";
                }
            }
            
            $sql_content .= implode(', ', $updates) . ";\n";
        }
        
        return $sql_content;
    }
    
    /**
     * Registrar backup no banco de dados
     */
    private function registerBackup($filename, $size, $type) {
        try {
            // Criar tabela de backups se não existir
            $create_table = "
                CREATE TABLE IF NOT EXISTS backups (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome_arquivo VARCHAR(255) NOT NULL,
                    tamanho BIGINT NOT NULL,
                    tipo ENUM('full', 'incremental') NOT NULL,
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('success', 'erro') DEFAULT 'success'
                )
            ";
            
            $this->db->exec($create_table);
            
            // Inserir registro do backup
            $query = "INSERT INTO backups (nome_arquivo, tamanho, tipo) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$filename, $size, $type]);
            
        } catch (Exception $e) {
            // Log do erro, mas não interromper o processo
            error_log("Erro ao registrar backup: " . $e->getMessage());
        }
    }
    
    /**
     * Obter timestamp do último backup
     */
    private function getLastBackupTime() {
        try {
            $query = "SELECT MAX(data_criacao) as ultimo_backup FROM backups WHERE status = 'success'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['ultimo_backup'] ?: date('Y-m-d H:i:s', strtotime('-1 day'));
            
        } catch (Exception $e) {
            return date('Y-m-d H:i:s', strtotime('-1 day'));
        }
    }
    
    /**
     * Limpar backups antigos
     */
    private function cleanOldBackups() {
        try {
            // Obter backups antigos
            $query = "SELECT nome_arquivo FROM backups ORDER BY data_criacao DESC LIMIT {$this->max_backups}, 999999";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $filepath = $this->backup_path . $row['nome_arquivo'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // Remover do banco
                $delete_query = "DELETE FROM backups WHERE nome_arquivo = ?";
                $delete_stmt = $this->db->prepare($delete_query);
                $delete_stmt->execute([$row['nome_arquivo']]);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao limpar backups antigos: " . $e->getMessage());
        }
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
     * Verificar se mysqldump está disponível
     */
    public static function checkMysqldumpAvailable() {
        $output = [];
        $return_code = 0;
        exec('mysqldump --version 2>&1', $output, $return_code);
        
        return $return_code === 0;
    }
    
    /**
     * Obter estatísticas de backup
     */
    public function getBackupStats() {
        try {
            $stats = [];
            
            // Total de backups
            $query = "SELECT COUNT(*) as total, SUM(tamanho) as tamanho_total FROM backups";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['total_backups'] = $result['total'] ?: 0;
            $stats['tamanho_total'] = $this->formatBytes($result['tamanho_total'] ?: 0);
            
            // Último backup
            $query = "SELECT * FROM backups ORDER BY data_criacao DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['ultimo_backup'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Backups por tipo
            $query = "SELECT tipo, COUNT(*) as quantidade FROM backups GROUP BY tipo";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['por_tipo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'erro' => $e->getMessage()
            ];
        }
    }
}