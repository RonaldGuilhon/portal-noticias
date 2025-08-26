<?php
/**
 * Teste de Conexão MySQL
 * Portal de Notícias
 */

require_once 'backend/config/database.php';

echo "<h1>🔍 Teste de Conexão MySQL</h1>";
echo "<hr>";

try {
    echo "<h2>📋 Informações do Sistema</h2>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
    echo "<p><strong>PDO Drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
    
    echo "<h2>🔌 Testando Conexão</h2>";
    
    // Criar instância do banco
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo instanceof MockPDO) {
        echo "<p style='color:orange;'>⚠️ <strong>Usando dados mockados</strong></p>";
        echo "<p>O sistema está funcionando com dados simulados do arquivo mock_data.json</p>";
    } else {
        echo "<p style='color:green;'>✅ <strong>Conexão MySQL estabelecida com sucesso!</strong></p>";
        
        // Testar query simples
        $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
        $info = $stmt->fetch();
        
        echo "<p><strong>Banco Atual:</strong> " . $info['current_db'] . "</p>";
        echo "<p><strong>Versão MySQL:</strong> " . $info['mysql_version'] . "</p>";
        
        // Verificar tabelas
        echo "<h3>📊 Tabelas no Banco</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li>" . $tableName . "</li>";
        }
        echo "</ul>";
        
        // Verificar dados dos usuários
        echo "<h3>👥 Usuários Cadastrados</h3>";
        $stmt = $pdo->query("SELECT id, nome, email, tipo_usuario FROM usuarios");
        $usuarios = $stmt->fetchAll();
        
        if (count($usuarios) > 0) {
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th></tr>";
            foreach ($usuarios as $usuario) {
                echo "<tr>";
                echo "<td>" . $usuario['id'] . "</td>";
                echo "<td>" . $usuario['nome'] . "</td>";
                echo "<td>" . $usuario['email'] . "</td>";
                echo "<td>" . $usuario['tipo_usuario'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Nenhum usuário encontrado.</p>";
        }
        
        // Verificar notícias
        echo "<h3>📰 Estatísticas</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM noticias");
        $noticias = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
        $categorias = $stmt->fetch();
        
        echo "<p><strong>Total de Notícias:</strong> " . $noticias['total'] . "</p>";
        echo "<p><strong>Total de Categorias:</strong> " . $categorias['total'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ <strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Próximos Passos</h3>";
echo "<p>Se a conexão MySQL foi estabelecida:</p>";
echo "<ol>";
echo "<li>✅ O backend agora está usando o banco MySQL real</li>";
echo "<li>✅ Os dados de teste foram inseridos</li>";
echo "<li>✅ Você pode fazer login com: <strong>admin@portal.com</strong> / <strong>password</strong></li>";
echo "<li>✅ Acesse: <a href='http://localhost:8000/admin/'>http://localhost:8000/admin/</a></li>";
echo "</ol>";

echo "<p>Se ainda estiver usando dados mockados:</p>";
echo "<ol>";
echo "<li>Verifique se o MySQL está rodando</li>";
echo "<li>Confirme as credenciais no arquivo .env</li>";
echo "<li>Instale os drivers PHP MySQL (pdo_mysql)</li>";
echo "</ol>";
?>