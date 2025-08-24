<?php
echo "=== DRIVERS PDO DISPONÍVEIS ===\n";

if (extension_loaded('pdo')) {
    echo "Extensão PDO: CARREGADA\n";
    
    $drivers = PDO::getAvailableDrivers();
    echo "Drivers disponíveis: " . count($drivers) . "\n";
    
    foreach ($drivers as $driver) {
        echo "- $driver\n";
    }
    
    // Verificar drivers específicos
    echo "\n=== VERIFICAÇÃO ESPECÍFICA ===\n";
    echo "MySQL (mysql): " . (in_array('mysql', $drivers) ? 'SIM' : 'NÃO') . "\n";
    echo "SQLite (sqlite): " . (in_array('sqlite', $drivers) ? 'SIM' : 'NÃO') . "\n";
    
} else {
    echo "Extensão PDO: NÃO CARREGADA\n";
}

// Verificar extensões MySQL específicas
echo "\n=== EXTENSÕES MYSQL ===\n";
echo "mysqli: " . (extension_loaded('mysqli') ? 'SIM' : 'NÃO') . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'SIM' : 'NÃO') . "\n";
echo "mysql: " . (extension_loaded('mysql') ? 'SIM' : 'NÃO') . "\n";

// Verificar versão do PHP
echo "\n=== INFORMAÇÕES DO PHP ===\n";
echo "Versão do PHP: " . PHP_VERSION . "\n";
echo "Sistema: " . PHP_OS . "\n";
?>