<?php
/**
 * Teste da API de notícias populares
 */

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/controllers/NoticiaController.php';

// Simular requisição GET
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'popular';
$_GET['limit'] = '5';

try {
    $controller = new NoticiaController();
    $controller->processarRequisicao();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}