<?php
// Verificar dados do usuário no banco de dados
require_once 'backend/config/database.php';

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4", 
                   $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Verificação de Dados do Usuário</h1>";
    echo "<h2>Conectado ao banco de dados com sucesso!</h2>";
    
    // Buscar usuário específico
    $email = 'ronaldguilhon@gmail.com';
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "<h3>Dados do Usuário: {$email}</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        
        foreach ($usuario as $campo => $valor) {
            // Não mostrar senha por segurança
            if ($campo === 'senha') {
                $valor = '[HASH PROTEGIDO]';
            }
            
            echo "<tr><td><strong>{$campo}</strong></td><td>{$valor}</td></tr>";
        }
        
        echo "</table>";
        
        // Verificar campos específicos importantes
        echo "<h3>Verificações Específicas:</h3>";
        echo "<ul>";
        echo "<li><strong>Nome:</strong> " . ($usuario['nome'] ? $usuario['nome'] : 'VAZIO') . "</li>";
        echo "<li><strong>Email:</strong> " . ($usuario['email'] ? $usuario['email'] : 'VAZIO') . "</li>";
        echo "<li><strong>Biografia:</strong> " . ($usuario['biografia'] ? $usuario['biografia'] : 'VAZIO') . "</li>";
        echo "<li><strong>Telefone:</strong> " . ($usuario['telefone'] ? $usuario['telefone'] : 'VAZIO') . "</li>";
        echo "<li><strong>Data Nascimento:</strong> " . ($usuario['data_nascimento'] ? $usuario['data_nascimento'] : 'VAZIO') . "</li>";
        echo "<li><strong>Gênero:</strong> " . ($usuario['genero'] ? $usuario['genero'] : 'VAZIO') . "</li>";
        echo "<li><strong>Estado:</strong> " . ($usuario['estado'] ? $usuario['estado'] : 'VAZIO') . "</li>";
        echo "<li><strong>Cidade:</strong> " . ($usuario['cidade'] ? $usuario['cidade'] : 'VAZIO') . "</li>";
        echo "<li><strong>Avatar:</strong> " . ($usuario['avatar'] ? $usuario['avatar'] : 'VAZIO') . "</li>";
        echo "</ul>";
        
        // Verificar preferências
        echo "<h3>Preferências:</h3>";
        echo "<ul>";
        echo "<li><strong>Tema:</strong> " . ($usuario['tema'] ? $usuario['tema'] : 'VAZIO') . "</li>";
        echo "<li><strong>Idioma:</strong> " . ($usuario['idioma'] ? $usuario['idioma'] : 'VAZIO') . "</li>";
        echo "<li><strong>Notificações Email:</strong> " . ($usuario['notificacoes_email'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li><strong>Notificações Push:</strong> " . ($usuario['notificacoes_push'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li><strong>Newsletter:</strong> " . ($usuario['newsletter'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li><strong>Perfil Público:</strong> " . ($usuario['perfil_publico'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li><strong>Mostrar Email:</strong> " . ($usuario['mostrar_email'] ? 'SIM' : 'NÃO') . "</li>";
        echo "<li><strong>Categorias Favoritas:</strong> " . ($usuario['categorias_favoritas'] ? $usuario['categorias_favoritas'] : 'VAZIO') . "</li>";
        echo "</ul>";
        
        // Verificar timestamps
        echo "<h3>Timestamps:</h3>";
        echo "<ul>";
        echo "<li><strong>Criado em:</strong> " . ($usuario['created_at'] ? $usuario['created_at'] : 'VAZIO') . "</li>";
        echo "<li><strong>Atualizado em:</strong> " . ($usuario['updated_at'] ? $usuario['updated_at'] : 'VAZIO') . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>Usuário não encontrado!</p>";
    }
    
    // Verificar estrutura da tabela
    echo "<h3>Estrutura da Tabela 'usuarios':</h3>";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>{$coluna['Field']}</td>";
        echo "<td>{$coluna['Type']}</td>";
        echo "<td>{$coluna['Null']}</td>";
        echo "<td>{$coluna['Key']}</td>";
        echo "<td>{$coluna['Default']}</td>";
        echo "<td>{$coluna['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro de conexão: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>