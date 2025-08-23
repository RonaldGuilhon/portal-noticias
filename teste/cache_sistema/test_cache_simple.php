<?php
require_once '../config-dev.php';
require_once 'utils/CacheManager.php';

echo "=== Teste Simples de Cache ===\n\n";

try {
    // Instanciar CacheManager
    $cache = new CacheManager();
    echo "1. CacheManager instanciado com sucesso\n";
    
    echo "2. Status do Cache: " . ($cache->isEnabled() ? "Habilitado" : "Desabilitado") . "\n";
    
    // Limpar cache
    $cache->clear();
    echo "3. Cache limpo\n";
    
    // Teste básico de set/get
    $test_key = 'test_noticias_list';
    $test_data = [
        'noticias' => [
            ['id' => 1, 'titulo' => 'Teste 1'],
            ['id' => 2, 'titulo' => 'Teste 2']
        ],
        'total' => 2
    ];
    
    echo "4. Salvando dados no cache...\n";
    $result = $cache->set($test_key, $test_data, 300);
    echo "   Resultado: " . ($result ? "Sucesso" : "Falha") . "\n";
    
    echo "5. Recuperando dados do cache...\n";
    $cached_data = $cache->get($test_key);
    
    if ($cached_data !== null) {
        echo "   Dados recuperados com sucesso\n";
        echo "   Total de notícias: " . $cached_data['total'] . "\n";
        echo "   Primeira notícia: " . $cached_data['noticias'][0]['titulo'] . "\n";
    } else {
        echo "   Falha ao recuperar dados\n";
    }
    
    // Teste de chave individual
    echo "6. Testando cache de notícia individual...\n";
    $noticia_key = 'noticia_1';
    $noticia_data = [
        'noticia' => [
            'id' => 1,
            'titulo' => 'Notícia Individual',
            'conteudo' => 'Conteúdo da notícia...'
        ]
    ];
    
    $cache->set($noticia_key, $noticia_data, 600);
    $cached_noticia = $cache->get($noticia_key);
    
    if ($cached_noticia !== null) {
        echo "   Cache individual funcionando: " . $cached_noticia['noticia']['titulo'] . "\n";
    } else {
        echo "   Falha no cache individual\n";
    }
    
    // Estatísticas
    echo "7. Estatísticas do cache:\n";
    $stats = $cache->getStats();
    echo "   Total de arquivos: " . $stats['total_files'] . "\n";
    echo "   Tamanho total: " . number_format($stats['total_size'] / 1024, 2) . " KB\n";
    
    echo "\n=== Teste Concluído com Sucesso ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>