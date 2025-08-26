<?php
/**
 * Script para verificar se os dados de teste foram inseridos corretamente
 * no banco de dados portal_noticias
 */

// Configuração do banco de dados
$config = [
    'host' => 'localhost',
    'database' => 'portal_noticias',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<h1>Verificação dos Dados de Teste - Portal de Notícias</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;} .success{color:green;} .warning{color:orange;} .error{color:red;}</style>";
    
    // Tabelas para verificar
    $tabelas = [
        'usuarios' => 'Usuários',
        'categorias' => 'Categorias', 
        'tags' => 'Tags',
        'noticias' => 'Notícias',
        'noticia_tags' => 'Relacionamentos Notícia-Tags',
        'comentarios' => 'Comentários',
        'curtidas_noticias' => 'Curtidas em Notícias',
        'curtidas_comentarios' => 'Curtidas em Comentários',
        'estatisticas_acesso' => 'Estatísticas de Acesso',
        'newsletter' => 'Newsletter',
        'anuncios' => 'Anúncios',
        'configuracoes' => 'Configurações',
        'midias' => 'Mídias',
        'notificacoes' => 'Notificações'
    ];
    
    echo "<h2>📊 Contagem de Registros por Tabela</h2>";
    echo "<table>";
    echo "<tr><th>Tabela</th><th>Total de Registros</th><th>Status</th></tr>";
    
    $totalGeral = 0;
    
    foreach ($tabelas as $tabela => $nome) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tabela}");
            $resultado = $stmt->fetch();
            $total = $resultado['total'];
            $totalGeral += $total;
            
            $status = $total > 0 ? "<span class='success'>✅ OK</span>" : "<span class='warning'>⚠️ Vazia</span>";
            
            echo "<tr>";
            echo "<td><strong>{$nome}</strong></td>";
            echo "<td>{$total}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
            
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>{$nome}</strong></td>";
            echo "<td>-</td>";
            echo "<td><span class='error'>❌ Erro: {$e->getMessage()}</span></td>";
            echo "</tr>";
        }
    }
    
    echo "<tr style='background-color:#e8f5e8;font-weight:bold;'>";
    echo "<td>TOTAL GERAL</td>";
    echo "<td>{$totalGeral}</td>";
    echo "<td><span class='success'>📈 Registros</span></td>";
    echo "</tr>";
    echo "</table>";
    
    // Verificações específicas
    echo "<h2>🔍 Verificações Específicas</h2>";
    
    // Verificar usuários de teste
    echo "<h3>👥 Usuários de Teste</h3>";
    $stmt = $pdo->query("SELECT nome, email, tipo_usuario FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll();
    
    if (count($usuarios) > 0) {
        echo "<table>";
        echo "<tr><th>Nome</th><th>Email</th><th>Tipo</th></tr>";
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>{$usuario['nome']}</td>";
            echo "<td>{$usuario['email']}</td>";
            echo "<td>{$usuario['tipo_usuario']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhum usuário encontrado</p>";
    }
    
    // Verificar categorias
    echo "<h3>📂 Categorias</h3>";
    $stmt = $pdo->query("SELECT nome, slug, cor FROM categorias ORDER BY ordem");
    $categorias = $stmt->fetchAll();
    
    if (count($categorias) > 0) {
        echo "<table>";
        echo "<tr><th>Nome</th><th>Slug</th><th>Cor</th></tr>";
        foreach ($categorias as $categoria) {
            echo "<tr>";
            echo "<td>{$categoria['nome']}</td>";
            echo "<td>{$categoria['slug']}</td>";
            echo "<td><span style='color:{$categoria['cor']};'>●</span> {$categoria['cor']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhuma categoria encontrada</p>";
    }
    
    // Verificar notícias
    echo "<h3>📰 Notícias</h3>";
    $stmt = $pdo->query("
        SELECT n.titulo, c.nome as categoria, n.visualizacoes, n.curtidas, n.status 
        FROM noticias n 
        LEFT JOIN categorias c ON n.categoria_id = c.id 
        ORDER BY n.data_publicacao DESC
    ");
    $noticias = $stmt->fetchAll();
    
    if (count($noticias) > 0) {
        echo "<table>";
        echo "<tr><th>Título</th><th>Categoria</th><th>Visualizações</th><th>Curtidas</th><th>Status</th></tr>";
        foreach ($noticias as $noticia) {
            $statusClass = $noticia['status'] == 'publicado' ? 'success' : 'warning';
            echo "<tr>";
            echo "<td>" . substr($noticia['titulo'], 0, 50) . "...</td>";
            echo "<td>{$noticia['categoria']}</td>";
            echo "<td>{$noticia['visualizacoes']}</td>";
            echo "<td>{$noticia['curtidas']}</td>";
            echo "<td><span class='{$statusClass}'>{$noticia['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhuma notícia encontrada</p>";
    }
    
    // Verificar comentários
    echo "<h3>💬 Comentários</h3>";
    $stmt = $pdo->query("
        SELECT c.conteudo, u.nome as usuario, n.titulo as noticia, c.curtidas, c.aprovado
        FROM comentarios c
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        LEFT JOIN noticias n ON c.noticia_id = n.id
        ORDER BY c.data_criacao DESC
        LIMIT 10
    ");
    $comentarios = $stmt->fetchAll();
    
    if (count($comentarios) > 0) {
        echo "<table>";
        echo "<tr><th>Comentário</th><th>Usuário</th><th>Notícia</th><th>Curtidas</th><th>Aprovado</th></tr>";
        foreach ($comentarios as $comentario) {
            $aprovadoStatus = $comentario['aprovado'] ? "<span class='success'>✅ Sim</span>" : "<span class='warning'>⏳ Pendente</span>";
            echo "<tr>";
            echo "<td>" . substr($comentario['conteudo'], 0, 50) . "...</td>";
            echo "<td>{$comentario['usuario']}</td>";
            echo "<td>" . substr($comentario['noticia'], 0, 30) . "...</td>";
            echo "<td>{$comentario['curtidas']}</td>";
            echo "<td>{$aprovadoStatus}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Nenhum comentário encontrado</p>";
    }
    
    // Resumo final
    echo "<h2>📋 Resumo da Verificação</h2>";
    echo "<div style='background-color:#e8f5e8;padding:15px;border-radius:5px;'>";
    echo "<p><strong>✅ Verificação concluída com sucesso!</strong></p>";
    echo "<p>📊 Total de registros no banco: <strong>{$totalGeral}</strong></p>";
    echo "<p>🗃️ Tabelas verificadas: <strong>" . count($tabelas) . "</strong></p>";
    
    if ($totalGeral > 0) {
        echo "<p class='success'>🎉 Os dados de teste foram inseridos corretamente no banco de dados!</p>";
    } else {
        echo "<p class='error'>❌ Nenhum dado de teste foi encontrado. Execute o script dados_teste_completos.sql</p>";
    }
    echo "</div>";
    
    echo "<hr>";
    echo "<p><small>Verificação realizada em: " . date('d/m/Y H:i:s') . "</small></p>";
    
} catch (Exception $e) {
    echo "<h1 style='color:red;'>❌ Erro de Conexão</h1>";
    echo "<p>Não foi possível conectar ao banco de dados:</p>";
    echo "<p style='color:red;'><strong>" . $e->getMessage() . "</strong></p>";
    echo "<p>Verifique se:</p>";
    echo "<ul>";
    echo "<li>O MySQL está rodando</li>";
    echo "<li>As credenciais no arquivo database.php estão corretas</li>";
    echo "<li>O banco de dados 'portal_noticias' existe</li>";
    echo "</ul>";
}
?>