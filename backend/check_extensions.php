<?php
header('Content-Type: text/plain');

echo "=== VERIFICAÇÃO DE EXTENSÕES PHP ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Configuration File: " . php_ini_loaded_file() . "\n";
echo "\n";

echo "Extensões MySQL carregadas:\n";
echo "- mysqli: " . (extension_loaded('mysqli') ? 'SIM' : 'NÃO') . "\n";
echo "- pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'SIM' : 'NÃO') . "\n";
echo "- mysqlnd: " . (extension_loaded('mysqlnd') ? 'SIM' : 'NÃO') . "\n";
echo "- PDO: " . (extension_loaded('PDO') ? 'SIM' : 'NÃO') . "\n";

echo "\nTodas as extensões carregadas:\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- $ext\n";
}

echo "\n=== TESTE DE CONEXÃO PDO ===\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=portal_noticias', 'root', '');
    echo "Conexão PDO: SUCESSO\n";
} catch (Exception $e) {
    echo "Conexão PDO: ERRO - " . $e->getMessage() . "\n";
}

echo "\n=== TESTE DE CONEXÃO MYSQLI ===\n";
try {
    $mysqli = new mysqli('localhost', 'root', '', 'portal_noticias');
    if ($mysqli->connect_error) {
        echo "Conexão MySQLi: ERRO - " . $mysqli->connect_error . "\n";
    } else {
        echo "Conexão MySQLi: SUCESSO\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "Conexão MySQLi: ERRO - " . $e->getMessage() . "\n";
}
?>