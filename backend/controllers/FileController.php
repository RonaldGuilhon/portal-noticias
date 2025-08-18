<?php
// Controller para servir arquivos de upload
// Portal de Notícias

require_once __DIR__ . '/../config/upload.php';

class FileController {
    
    public function __construct() {
        // Permitir CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Servir foto de perfil
     */
    public function serveProfilePhoto() {
        try {
            $filename = $_GET['file'] ?? '';
            
            if (empty($filename)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome do arquivo não fornecido']);
                return;
            }
            
            // Sanitizar nome do arquivo
            $filename = basename($filename);
            $filePath = UploadConfig::PROFILE_PHOTOS_DIR . '/' . $filename;
            
            // Verificar se o arquivo existe
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo json_encode(['error' => 'Arquivo não encontrado']);
                return;
            }
            
            // Verificar se é um arquivo de imagem válido
            $imageInfo = getimagesize($filePath);
            if ($imageInfo === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Arquivo não é uma imagem válida']);
                return;
            }
            
            // Definir headers apropriados
            $mimeType = $imageInfo['mime'];
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=31536000'); // Cache por 1 ano
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            // Servir o arquivo
            readfile($filePath);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }
}

// Processar requisição
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'serve_profile_photo';
    $controller = new FileController();
    
    switch ($action) {
        case 'serve_profile_photo':
            $controller->serveProfilePhoto();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Ação não encontrada']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
?>