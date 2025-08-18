<?php
// Configurações de upload de arquivos
// Portal de Notícias

class UploadConfig {
    // Diretório base para uploads no disco D
    const BASE_UPLOAD_DIR = 'D:/portal-noticias-uploads';
    
    // Diretório específico para fotos de perfil
    const PROFILE_PHOTOS_DIR = self::BASE_UPLOAD_DIR . '/profile-photos';
    
    // URL base para acessar as fotos através do FileController
    const PROFILE_PHOTOS_URL = 'http://localhost:8001/controllers/FileController.php?action=serve_profile_photo&file=';
    
    // Configurações de imagem
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp'
    ];
    
    // Dimensões para redimensionamento
    const PROFILE_PHOTO_WIDTH = 300;
    const PROFILE_PHOTO_HEIGHT = 300;
    const THUMBNAIL_WIDTH = 150;
    const THUMBNAIL_HEIGHT = 150;
    
    /**
     * Verificar se o diretório de upload existe e criar se necessário
     */
    public static function ensureUploadDirectoryExists() {
        $directories = [
            self::BASE_UPLOAD_DIR,
            self::PROFILE_PHOTOS_DIR
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Não foi possível criar o diretório: {$dir}");
                }
            }
            
            // Verificar permissões de escrita
            if (!is_writable($dir)) {
                throw new Exception("Diretório sem permissão de escrita: {$dir}");
            }
        }
    }
    
    /**
     * Gerar nome único para arquivo
     */
    public static function generateUniqueFileName($userId, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "profile_{$userId}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Validar tipo de arquivo
     */
    public static function validateFileType($mimeType, $extension) {
        return in_array(strtolower($mimeType), self::ALLOWED_MIME_TYPES) && 
               in_array(strtolower($extension), self::ALLOWED_EXTENSIONS);
    }
    
    /**
     * Validar tamanho do arquivo
     */
    public static function validateFileSize($size) {
        return $size <= self::MAX_FILE_SIZE;
    }
}
?>