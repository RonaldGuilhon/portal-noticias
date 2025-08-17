<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/UploadService.php';
require_once __DIR__ . '/../services/ImageService.php';

class UploadController {
    private $uploadService;
    private $imageService;
    
    public function __construct() {
        $this->uploadService = new UploadService();
        $this->imageService = new ImageService();
    }
    
    public function processarRequisicao() {
        $action = $_GET['action'] ?? '';
        
        switch($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                switch($action) {
                    case 'image':
                    case 'imagem':
                        $this->uploadImagem();
                        break;
                    case 'video':
                        $this->uploadVideo();
                        break;
                    case 'audio':
                        $this->uploadAudio();
                        break;
                    default:
                        jsonResponse(['erro' => 'Ação não encontrada'], 404);
                }
                break;
            default:
                jsonResponse(['erro' => 'Método não permitido'], 405);
        }
    }
    
    /**
     * Upload de imagem
     */
    private function uploadImagem() {
        try {
            if (!isset($_FILES['file'])) {
                jsonResponse(['erro' => 'Nenhum arquivo enviado'], 400);
                return;
            }
            
            $file = $_FILES['file'];
            
            // Validar tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                jsonResponse(['erro' => 'Tipo de arquivo não permitido'], 400);
                return;
            }
            
            // Validar tamanho (5MB máximo)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                jsonResponse(['erro' => 'Arquivo muito grande. Máximo 5MB'], 400);
                return;
            }
            
            $resultado = $this->uploadService->uploadImagem($file);
            
            if ($resultado['success']) {
                jsonResponse([
                    'success' => true,
                    'url' => $resultado['url'],
                    'nome_arquivo' => $resultado['nome_arquivo']
                ]);
            } else {
                jsonResponse(['erro' => $resultado['erro']], 500);
            }
            
        } catch (Exception $e) {
            logError('Erro no upload de imagem: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }
    
    /**
     * Upload de vídeo
     */
    private function uploadVideo() {
        try {
            if (!isset($_FILES['file'])) {
                jsonResponse(['erro' => 'Nenhum arquivo enviado'], 400);
                return;
            }
            
            $file = $_FILES['file'];
            
            // Validar tipo de arquivo
            $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
            if (!in_array($file['type'], $allowedTypes)) {
                jsonResponse(['erro' => 'Tipo de arquivo não permitido'], 400);
                return;
            }
            
            // Validar tamanho (50MB máximo)
            $maxSize = 50 * 1024 * 1024; // 50MB
            if ($file['size'] > $maxSize) {
                jsonResponse(['erro' => 'Arquivo muito grande. Máximo 50MB'], 400);
                return;
            }
            
            $resultado = $this->uploadService->uploadVideo($file);
            
            if ($resultado['success']) {
                jsonResponse([
                    'success' => true,
                    'url' => $resultado['url'],
                    'nome_arquivo' => $resultado['nome_arquivo']
                ]);
            } else {
                jsonResponse(['erro' => $resultado['erro']], 500);
            }
            
        } catch (Exception $e) {
            logError('Erro no upload de vídeo: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }
    
    /**
     * Upload de áudio
     */
    private function uploadAudio() {
        try {
            if (!isset($_FILES['file'])) {
                jsonResponse(['erro' => 'Nenhum arquivo enviado'], 400);
                return;
            }
            
            $file = $_FILES['file'];
            
            // Validar tipo de arquivo
            $allowedTypes = ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'];
            if (!in_array($file['type'], $allowedTypes)) {
                jsonResponse(['erro' => 'Tipo de arquivo não permitido'], 400);
                return;
            }
            
            // Validar tamanho (10MB máximo)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                jsonResponse(['erro' => 'Arquivo muito grande. Máximo 10MB'], 400);
                return;
            }
            
            $resultado = $this->uploadService->uploadAudio($file);
            
            if ($resultado['success']) {
                jsonResponse([
                    'success' => true,
                    'url' => $resultado['url'],
                    'nome_arquivo' => $resultado['nome_arquivo']
                ]);
            } else {
                jsonResponse(['erro' => $resultado['erro']], 500);
            }
            
        } catch (Exception $e) {
            logError('Erro no upload de áudio: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }
}