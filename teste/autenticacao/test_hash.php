<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$senhas = [
    'password',
    'secret', 
    'admin',
    '123456',
    'admin123',
    'portalnoticias',
    'test',
    'demo',
    'laravel',
    'bcrypt',
    'hello',
    'world'
];

foreach($senhas as $senha) {
    if (password_verify($senha, $hash)) {
        echo "ENCONTRADA! Senha: $senha\n";
        break;
    } else {
        echo "Testando $senha: NO MATCH\n";
    }
}

// Vamos também tentar gerar um novo hash para 'password'
echo "\nNovo hash para 'password': " . password_hash('password', PASSWORD_DEFAULT) . "\n";
?>