<?php
require_once '../config-dev.php';
require_once 'utils/CacheManager.php';
require_once 'controllers/NoticiaController.php';
require_once 'models/Noticia.php';
require_once 'utils/functions.php';

echo "=== Teste de Cache para Notícias ===\n\n";

// Instanciar CacheManager
$cache = new CacheManager();

echo "1. Status do Cache: " . ($cache->isEnabled() ? "Habilitado" : "Desabilitado") . "\n";

// Limpar cache antes do teste
$cache->clear();
echo "2. Cache limpo para teste\n";

// Simular requisição GET para listar notícias
$_GET = [
    'page' => 1,
    'limit' => 5,
    'ordem' => 'recentes'
];

echo "\n3. Testando cache de listagem de notícias...\n";

// Primeira requisição (deve ir ao banco)
echo "   - Primeira requisição (sem cache): ";
$start_time = microtime(true);

// Simular o comportamento do controller
$filtros = [
    'page' => 1,
    'limit' => 5,
    'categoria_id' => null,
    'tag_id' => null,
    'autor_id' => null,
    'destaque' => null,
    'busca' => null,
    'ordem' => 'recentes'
];

$cache_key = 'noticias_list_' . md5(serialize($filtros));
$cached_result = $cache->get($cache_key);

if ($cached_result === null) {
    // Simular dados de notícias
    $mock_result = [
        'noticias' => [
            ['id' => 1, 'titulo' => 'Notícia Teste 1'],
            ['id' => 2, 'titulo' => 'Notícia Teste 2']
        ],
        'paginacao' => [
            'pagina_atual' => 1,
            'total_paginas' => 1,
            'total_itens' => 2,
            'itens_por_pagina' => 5
        ]
    ];
    
    $cache->set($cache_key, $mock_result, 300);
    echo "Dados salvos no cache\n";
} else {
    echo "Dados obtidos do cache\n";
}

$first_time = microtime(true) - $start_time;

// Segunda requisição (deve vir do cache)
echo "   - Segunda requisição (com cache): ";
$start_time = microtime(true);

$cached_result = $cache->get($cache_key);
if ($cached_result !== null) {
    echo "Dados obtidos do cache\n";
} else {
    echo "Cache não encontrado (erro!)\n";
}

$second_time = microtime(true) - $start_time;

echo "   - Tempo primeira requisição: " . number_format($first_time * 1000, 2) . "ms\n";
echo "   - Tempo segunda requisição: " . number_format($second_time * 1000, 2) . "ms\n";
echo "   - Melhoria de performance: " . number_format((($first_time - $second_time) / $first_time) * 100, 1) . "%\n";

echo "\n4. Testando cache de notícia individual...\n";

// Teste de cache para notícia individual
$noticia_id = 1;
$cache_key_individual = 'noticia_' . $noticia_id;

// Primeira requisição
echo "   - Primeira requisição (sem cache): ";
$cached_noticia = $cache->get($cache_key_individual);

if ($cached_noticia === null) {
    $mock_noticia = [
        'noticia' => [
            'id' => 1,
            'titulo' => 'Notícia Individual Teste',
            'conteudo' => 'Conteúdo da notícia...',
            'autor_nome' => 'Autor Teste'
        ]
    ];
    
    $cache->set($cache_key_individual, $mock_noticia, 600);
    echo "Dados salvos no cache\n";
} else {
    echo "Dados obtidos do cache\n";
}

// Segunda requisição
echo "   - Segunda requisição (com cache): ";
$cached_noticia = $cache->get($cache_key_individual);
if ($cached_noticia !== null) {
    echo "Dados obtidos do cache\n";
} else {
    echo "Cache não encontrado (erro!)\n";
}

echo "\n5. Estatísticas do cache:\n";
$stats = $cache->getStats();
echo "   - Total de arquivos: " . $stats['total_files'] . "\n";
echo "   - Tamanho total: " . number_format($stats['total_size'] / 1024, 2) . " KB\n";
echo "   - Arquivos expirados: " . $stats['expired_files'] . "\n";

echo "\n6. Testando invalidação de cache...\n";

// Simular atualização de notícia (deve invalidar cache)
echo "   - Removendo cache da notícia individual: ";
if ($cache->delete($cache_key_individual)) {
    echo "Sucesso\n";
} else {
    echo "Falha\n";
}

echo "   - Verificando se foi removido: ";
$cached_noticia = $cache->get($cache_key_individual);
if ($cached_noticia === null) {
    echo "Cache invalidado com sucesso\n";
} else {
    echo "Cache ainda existe (erro!)\n";
}

echo "\n=== Teste Concluído ===\n";
?>