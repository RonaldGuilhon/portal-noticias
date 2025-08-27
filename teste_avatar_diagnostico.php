<?php
// Script de diagnóstico para avatar padrão
// Portal de Notícias

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Avatar Padrão</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .avatar-test {
            display: inline-block;
            margin: 10px;
            text-align: center;
        }
        .avatar-test img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #ddd;
            object-fit: cover;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico do Avatar Padrão</h1>
        
        <div class="test-section">
            <h2>📁 Verificação de Arquivos</h2>
            <?php
            $frontendPath = __DIR__ . '/frontend';
            $assetsPath = $frontendPath . '/assets/img';
            
            $avatarFiles = [
                'default-avatar.svg' => $assetsPath . '/default-avatar.svg',
                'default-avatar.jpg' => $assetsPath . '/default-avatar.jpg',
                'default-avatar.png' => $assetsPath . '/default-avatar.png'
            ];
            
            foreach ($avatarFiles as $name => $path) {
                $exists = file_exists($path);
                $readable = $exists ? is_readable($path) : false;
                $size = $exists ? filesize($path) : 0;
                
                echo "<div class='debug-info'>";
                echo "<strong>{$name}:</strong><br>";
                echo "Caminho: {$path}<br>";
                echo "Existe: " . ($exists ? '✅ Sim' : '❌ Não') . "<br>";
                if ($exists) {
                    echo "Legível: " . ($readable ? '✅ Sim' : '❌ Não') . "<br>";
                    echo "Tamanho: " . number_format($size) . " bytes<br>";
                }
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>🖼️ Teste de Carregamento de Imagens</h2>
            <p>Testando diferentes caminhos para o avatar padrão:</p>
            
            <div class="avatar-test">
                <img src="frontend/assets/img/default-avatar.svg" alt="SVG Relativo" 
                     onerror="this.style.border='2px solid red'; this.alt='❌ Erro'">
                <br><small>frontend/assets/img/default-avatar.svg</small>
            </div>
            
            <div class="avatar-test">
                <img src="frontend/assets/img/default-avatar.jpg" alt="JPG Relativo" 
                     onerror="this.style.border='2px solid red'; this.alt='❌ Erro'">
                <br><small>frontend/assets/img/default-avatar.jpg</small>
            </div>
            
            <div class="avatar-test">
                <img src="http://localhost:8000/assets/img/default-avatar.svg" alt="SVG Absoluto" 
                     onerror="this.style.border='2px solid red'; this.alt='❌ Erro'">
                <br><small>http://localhost:8000/assets/img/default-avatar.svg</small>
            </div>
            
            <div class="avatar-test">
                <img src="assets/img/default-avatar.svg" alt="SVG Direto" 
                     onerror="this.style.border='2px solid red'; this.alt='❌ Erro'">
                <br><small>assets/img/default-avatar.svg</small>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🌐 Informações do Servidor</h2>
            <div class="debug-info">
                <strong>URL Atual:</strong> <?php echo $_SERVER['REQUEST_URI']; ?><br>
                <strong>Servidor:</strong> <?php echo $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']; ?><br>
                <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
                <strong>Script Path:</strong> <?php echo __FILE__; ?><br>
                <strong>Working Directory:</strong> <?php echo getcwd(); ?><br>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🔧 Simulação do Perfil</h2>
            <p>Simulando o comportamento do perfil.html:</p>
            
            <div id="profile-simulation">
                <div class="profile-avatar">
                    <img id="profile-avatar" src="assets/img/default-avatar.svg" alt="Avatar do usuário">
                </div>
            </div>
            
            <script>
                // Simular o comportamento do perfil.html
                const avatarElement = document.getElementById('profile-avatar');
                
                // Simular usuário sem foto
                const userData = {
                    nome: 'Usuário Teste',
                    foto_perfil: null // Sem foto
                };
                
                console.log('Dados do usuário:', userData);
                
                if (userData.foto_perfil) {
                    console.log('Usuário tem foto:', userData.foto_perfil);
                    avatarElement.src = userData.foto_perfil;
                } else {
                    console.log('Usuário sem foto, usando padrão');
                    avatarElement.src = 'assets/img/default-avatar.svg';
                }
                
                // Verificar se a imagem carregou
                avatarElement.onload = function() {
                    console.log('✅ Avatar carregado com sucesso:', this.src);
                    this.style.border = '2px solid green';
                };
                
                avatarElement.onerror = function() {
                    console.error('❌ Erro ao carregar avatar:', this.src);
                    this.style.border = '2px solid red';
                    this.alt = '❌ Erro no carregamento';
                };
            </script>
        </div>
        
        <div class="test-section">
            <h2>📋 Recomendações</h2>
            <div class="info status">
                <strong>Para resolver o problema:</strong><br>
                1. Verifique se o arquivo default-avatar.svg existe em frontend/assets/img/<br>
                2. Certifique-se de que o servidor frontend está rodando na porta 8000<br>
                3. Verifique as permissões de leitura dos arquivos<br>
                4. Teste o caminho completo: http://localhost:8000/assets/img/default-avatar.svg
            </div>
        </div>
    </div>
</body>
</html>