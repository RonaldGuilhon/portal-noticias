<?php
/**
 * Script para Corrigir Problemas de Charset
 * Portal de Notícias
 */

require_once 'backend/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Correção de Charset</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo "        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }\n";
echo "        .success { background-color: #d4edda; border-color: #c3e6cb; }\n";
echo "        .error { background-color: #f8d7da; border-color: #f5c6cb; }\n";
echo "        .warning { background-color: #fff3cd; border-color: #ffeaa7; }\n";
echo "        pre { background-color: #f8f9fa; padding: 10px; border-radius: 3px; }\n";
echo "        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 3px; cursor: pointer; }\n";
echo "        .btn-primary { background-color: #007bff; color: white; }\n";
echo "        .btn-danger { background-color: #dc3545; color: white; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🔧 Correção de Problemas de Charset</h1>\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $action = $_GET['action'] ?? 'menu';
    
    if ($action === 'menu') {
        echo "<div class='step warning'>\n";
        echo "<h2>⚠️ ATENÇÃO - Faça Backup Antes de Continuar!</h2>\n";
        echo "<p>Este script irá modificar a estrutura do banco de dados. <strong>Faça um backup completo antes de prosseguir!</strong></p>\n";
        echo "<pre>mysqldump -u root -p portal_noticias > backup_antes_charset.sql</pre>\n";
        echo "</div>\n";
        
        echo "<div class='step'>\n";
        echo "<h2>Escolha a Ação:</h2>\n";
        echo "<p><a href='?action=check' class='btn btn-primary'>1. Verificar Status Atual</a></p>\n";
        echo "<p><a href='?action=fix_database' class='btn btn-primary'>2. Corrigir Charset do Banco</a></p>\n";
        echo "<p><a href='?action=fix_tables' class='btn btn-primary'>3. Corrigir Charset das Tabelas</a></p>\n";
        echo "<p><a href='?action=fix_data' class='btn btn-danger'>4. Corrigir Dados Corrompidos (CUIDADO!)</a></p>\n";
        echo "<p><a href='?action=test' class='btn btn-primary'>5. Testar Após Correção</a></p>\n";
        echo "</div>\n";
        
    } elseif ($action === 'check') {
        echo "<div class='step'>\n";
        echo "<h2>📊 Status Atual do Charset</h2>\n";
        
        // Verificar charset do banco
        $stmt = $pdo->query("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'portal_noticias'");
        $dbInfo = $stmt->fetch();
        
        echo "<h3>Banco de Dados:</h3>\n";
        echo "<p><strong>Charset:</strong> " . $dbInfo['DEFAULT_CHARACTER_SET_NAME'] . "</p>\n";
        echo "<p><strong>Collation:</strong> " . $dbInfo['DEFAULT_COLLATION_NAME'] . "</p>\n";
        
        if ($dbInfo['DEFAULT_CHARACTER_SET_NAME'] === 'utf8mb4') {
            echo "<p class='success'>✅ Banco usando UTF8MB4</p>\n";
        } else {
            echo "<p class='error'>❌ Banco NÃO está usando UTF8MB4</p>\n";
        }
        
        // Verificar tabelas
        echo "<h3>Tabelas:</h3>\n";
        $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'portal_noticias' AND TABLE_TYPE = 'BASE TABLE'");
        $tables = $stmt->fetchAll();
        
        foreach ($tables as $table) {
            $charset = explode('_', $table['TABLE_COLLATION'])[0];
            echo "<p><strong>{$table['TABLE_NAME']}:</strong> {$charset} ({$table['TABLE_COLLATION']})";
            if ($charset === 'utf8mb4') {
                echo " ✅</p>\n";
            } else {
                echo " ❌</p>\n";
            }
        }
        
        echo "<p><a href='?' class='btn btn-primary'>← Voltar ao Menu</a></p>\n";
        echo "</div>\n";
        
    } elseif ($action === 'fix_database') {
        echo "<div class='step'>\n";
        echo "<h2>🔧 Corrigindo Charset do Banco de Dados</h2>\n";
        
        try {
            $pdo->exec("ALTER DATABASE portal_noticias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p class='success'>✅ Charset do banco corrigido para UTF8MB4</p>\n";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao corrigir charset do banco: " . $e->getMessage() . "</p>\n";
        }
        
        echo "<p><a href='?' class='btn btn-primary'>← Voltar ao Menu</a></p>\n";
        echo "</div>\n";
        
    } elseif ($action === 'fix_tables') {
        echo "<div class='step'>\n";
        echo "<h2>🔧 Corrigindo Charset das Tabelas</h2>\n";
        
        $tables = ['usuarios', 'categorias', 'noticias', 'comentarios', 'tags', 'anuncios', 'configuracoes'];
        
        foreach ($tables as $table) {
            try {
                // Verificar se a tabela existe
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    echo "<p class='success'>✅ Tabela '$table' convertida para UTF8MB4</p>\n";
                } else {
                    echo "<p class='warning'>⚠️ Tabela '$table' não encontrada</p>\n";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao converter tabela '$table': " . $e->getMessage() . "</p>\n";
            }
        }
        
        echo "<p><a href='?' class='btn btn-primary'>← Voltar ao Menu</a></p>\n";
        echo "</div>\n";
        
    } elseif ($action === 'fix_data') {
        echo "<div class='step error'>\n";
        echo "<h2>⚠️ CORREÇÃO DE DADOS CORROMPIDOS</h2>\n";
        echo "<p><strong>ATENÇÃO:</strong> Esta operação é irreversível! Certifique-se de ter um backup!</p>\n";
        
        if (!isset($_GET['confirm'])) {
            echo "<p><a href='?action=fix_data&confirm=1' class='btn btn-danger'>Confirmar Correção de Dados</a></p>\n";
            echo "<p><a href='?' class='btn btn-primary'>← Cancelar e Voltar</a></p>\n";
        } else {
            echo "<h3>Corrigindo dados corrompidos...</h3>\n";
            
            // Correções comuns de caracteres corrompidos
            $corrections = [
                'ã' => ['????', 'Ã£', 'Ã¡'],
                'á' => ['????', 'Ã¡'],
                'à' => ['????', 'Ã '],
                'â' => ['????', 'Ã¢'],
                'é' => ['????', 'Ã©'],
                'ê' => ['????', 'Ãª'],
                'í' => ['????', 'Ã­'],
                'ó' => ['????', 'Ã³'],
                'ô' => ['????', 'Ã´'],
                'õ' => ['????', 'Ãµ'],
                'ú' => ['????', 'Ãº'],
                'ü' => ['????', 'Ã¼'],
                'ç' => ['????', 'Ã§'],
                'ñ' => ['????', 'Ã±']
            ];
            
            // Corrigir tabela de notícias
            try {
                $stmt = $pdo->query("SELECT id, titulo, subtitulo, conteudo FROM noticias WHERE titulo LIKE '%??%' OR subtitulo LIKE '%??%' OR conteudo LIKE '%??%'");
                $noticias = $stmt->fetchAll();
                
                $updateStmt = $pdo->prepare("UPDATE noticias SET titulo = ?, subtitulo = ?, conteudo = ? WHERE id = ?");
                
                foreach ($noticias as $noticia) {
                    $titulo = $noticia['titulo'];
                    $subtitulo = $noticia['subtitulo'];
                    $conteudo = $noticia['conteudo'];
                    
                    // Aplicar correções
                    foreach ($corrections as $correct => $corrupted) {
                        foreach ($corrupted as $corrupt) {
                            $titulo = str_replace($corrupt, $correct, $titulo);
                            $subtitulo = str_replace($corrupt, $correct, $subtitulo);
                            $conteudo = str_replace($corrupt, $correct, $conteudo);
                        }
                    }
                    
                    // Correção genérica para ??
                    $titulo = preg_replace('/\?\?/', '', $titulo);
                    $subtitulo = preg_replace('/\?\?/', '', $subtitulo);
                    $conteudo = preg_replace('/\?\?/', '', $conteudo);
                    
                    $updateStmt->execute([$titulo, $subtitulo, $conteudo, $noticia['id']]);
                }
                
                echo "<p class='success'>✅ " . count($noticias) . " notícias corrigidas</p>\n";
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao corrigir notícias: " . $e->getMessage() . "</p>\n";
            }
        }
        
        echo "<p><a href='?' class='btn btn-primary'>← Voltar ao Menu</a></p>\n";
        echo "</div>\n";
        
    } elseif ($action === 'test') {
        echo "<div class='step'>\n";
        echo "<h2>🧪 Teste Após Correção</h2>\n";
        
        // Inserir dados de teste
        $textoTeste = "Teste: ação, coração, não, são, informação";
        
        try {
            $pdo->exec("CREATE TEMPORARY TABLE teste_final (
                id INT AUTO_INCREMENT PRIMARY KEY,
                texto TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            )");
            
            $stmt = $pdo->prepare("INSERT INTO teste_final (texto) VALUES (?)");
            $stmt->execute([$textoTeste]);
            
            $stmt = $pdo->query("SELECT texto FROM teste_final");
            $resultado = $stmt->fetch();
            
            echo "<h3>Resultado do Teste:</h3>\n";
            echo "<p><strong>Texto Inserido:</strong> $textoTeste</p>\n";
            echo "<p><strong>Texto Recuperado:</strong> {$resultado['texto']}</p>\n";
            
            if ($resultado['texto'] === $textoTeste) {
                echo "<p class='success'>✅ Charset funcionando corretamente!</p>\n";
            } else {
                echo "<p class='error'>❌ Ainda há problemas de charset</p>\n";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro no teste: " . $e->getMessage() . "</p>\n";
        }
        
        echo "<p><a href='?' class='btn btn-primary'>← Voltar ao Menu</a></p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='step error'>\n";
    echo "<h2>❌ Erro</h2>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "</body>\n";
echo "</html>\n";
?>