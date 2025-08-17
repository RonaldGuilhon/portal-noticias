<?php
/**
 * Serviço de Upload de Arquivos
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';

class UploadService {
    private $uploadPath;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct() {
        $this->uploadPath = UPLOAD_PATH;
        $this->allowedTypes = ALLOWED_TYPES;
        $this->maxFileSize = MAX_FILE_SIZE;
        
        // Criar diretórios se não existirem
        $this->criarDiretorios();
    }
    
    /**
     * Obter caminho de upload
     */
    public function getUploadPath() {
        return $this->uploadPath;
    }
    
    /**
     * Obter URL de upload
     */
    public function getUploadUrl() {
        return 'uploads';
    }
    
    /**
     * Deletar arquivo
     */
    public function delete($caminho) {
        try {
            // Construir caminho completo
            $caminhoCompleto = $this->uploadPath . '/' . $caminho;
            
            // Verificar se o arquivo existe
            if (!file_exists($caminhoCompleto)) {
                return false;
            }
            
            // Deletar o arquivo
            return unlink($caminhoCompleto);
            
        } catch (Exception $e) {
            logError('Erro ao deletar arquivo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpar arquivos temporários antigos
     */
    public function cleanupTempFiles($dias = 7) {
        try {
            $arquivosRemovidos = 0;
            $tempoLimite = time() - ($dias * 24 * 60 * 60);
            
            // Diretório temporário
            $tempDir = $this->uploadPath . '/temp';
            
            if (!is_dir($tempDir)) {
                return 0;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() < $tempoLimite) {
                    if (unlink($file->getRealPath())) {
                        $arquivosRemovidos++;
                    }
                }
            }
            
            return $arquivosRemovidos;
            
        } catch (Exception $e) {
            logError('Erro na limpeza de arquivos temporários: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Processar upload de arquivo
     */
    public function processarUpload($arquivo, $tipo = 'images', $redimensionar = true) {
        try {
            // Validar arquivo
            $validacao = $this->validarArquivo($arquivo);
            if($validacao !== true) {
                throw new Exception($validacao);
            }
            
            // Gerar nome único
            $nomeArquivo = $this->gerarNomeUnico($arquivo['name']);
            
            // Definir caminho de destino
            $caminhoDestino = $this->uploadPath . '/' . $tipo . '/' . $nomeArquivo;
            
            // Mover arquivo
            if(!move_uploaded_file($arquivo['tmp_name'], $caminhoDestino)) {
                throw new Exception('Erro ao mover arquivo');
            }
            
            // Redimensionar imagem se necessário
            if($tipo === 'images' && $redimensionar) {
                $this->redimensionarImagem($caminhoDestino);
            }
            
            // Retornar caminho relativo
            return 'uploads/' . $tipo . '/' . $nomeArquivo;
            
        } catch(Exception $e) {
            logError('Erro no upload: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Processar upload múltiplo
     */
    public function processarUploadMultiplo($arquivos, $tipo = 'images') {
        $resultados = [];
        
        foreach($arquivos['name'] as $key => $nome) {
            $arquivo = [
                'name' => $arquivos['name'][$key],
                'type' => $arquivos['type'][$key],
                'tmp_name' => $arquivos['tmp_name'][$key],
                'error' => $arquivos['error'][$key],
                'size' => $arquivos['size'][$key]
            ];
            
            try {
                $resultado = $this->processarUpload($arquivo, $tipo);
                $resultados[] = [
                    'sucesso' => true,
                    'arquivo' => $resultado,
                    'nome_original' => $nome
                ];
            } catch(Exception $e) {
                $resultados[] = [
                    'sucesso' => false,
                    'erro' => $e->getMessage(),
                    'nome_original' => $nome
                ];
            }
        }
        
        return $resultados;
    }
    
    /**
     * Processar upload via base64
     */
    public function processarUploadBase64($dadosBase64, $tipo = 'images', $extensao = 'jpg') {
        try {
            // Decodificar base64
            $dados = base64_decode($dadosBase64);
            if($dados === false) {
                throw new Exception('Dados base64 inválidos');
            }
            
            // Gerar nome único
            $nomeArquivo = $this->gerarNomeUnico('upload.' . $extensao);
            
            // Definir caminho de destino
            $caminhoDestino = $this->uploadPath . '/' . $tipo . '/' . $nomeArquivo;
            
            // Salvar arquivo
            if(file_put_contents($caminhoDestino, $dados) === false) {
                throw new Exception('Erro ao salvar arquivo');
            }
            
            // Redimensionar imagem se necessário
            if($tipo === 'images') {
                $this->redimensionarImagem($caminhoDestino);
            }
            
            // Retornar caminho relativo
            return 'uploads/' . $tipo . '/' . $nomeArquivo;
            
        } catch(Exception $e) {
            logError('Erro no upload base64: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Upload de imagem
     */
    public function uploadImagem($file) {
        try {
            $resultado = $this->processarUpload($file, 'images', true);
            
            return [
                'sucesso' => true,
                'url' => $resultado,
                'nome_arquivo' => basename($resultado)
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload de vídeo
     */
    public function uploadVideo($file) {
        try {
            $resultado = $this->processarUpload($file, 'videos', false);
            
            return [
                'sucesso' => true,
                'url' => $resultado,
                'nome_arquivo' => basename($resultado)
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload de áudio
     */
    public function uploadAudio($file) {
        try {
            $resultado = $this->processarUpload($file, 'audios', false);
            
            return [
                'sucesso' => true,
                'url' => $resultado,
                'nome_arquivo' => basename($resultado)
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar arquivo
     */
    private function validarArquivo($arquivo) {
        // Verificar se houve erro no upload
        if($arquivo['error'] !== UPLOAD_ERR_OK) {
            return $this->obterMensagemErroUpload($arquivo['error']);
        }
        
        // Verificar tamanho
        if($arquivo['size'] > $this->maxFileSize) {
            return 'Arquivo muito grande. Tamanho máximo: ' . formatBytes($this->maxFileSize);
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);
        
        if(!in_array($mimeType, $this->allowedTypes)) {
            return 'Tipo de arquivo não permitido: ' . $mimeType;
        }
        
        // Verificar extensão
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mov', 'mp3', 'wav', 'pdf', 'doc', 'docx'];
        
        if(!in_array($extensao, $extensoesPermitidas)) {
            return 'Extensão não permitida: ' . $extensao;
        }
        
        return true;
    }
    
    /**
     * Gerar nome único para arquivo
     */
    private function gerarNomeUnico($nomeOriginal) {
        $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $nome = pathinfo($nomeOriginal, PATHINFO_FILENAME);
        
        // Limpar nome
        $nome = preg_replace('/[^a-zA-Z0-9_-]/', '', $nome);
        $nome = substr($nome, 0, 50); // Limitar tamanho
        
        // Adicionar timestamp e hash
        $timestamp = time();
        $hash = substr(md5(uniqid()), 0, 8);
        
        return $nome . '_' . $timestamp . '_' . $hash . '.' . $extensao;
    }
    
    /**
     * Redimensionar imagem
     */
    private function redimensionarImagem($caminhoArquivo) {
        try {
            $info = getimagesize($caminhoArquivo);
            if($info === false) {
                return false;
            }
            
            $larguraOriginal = $info[0];
            $alturaOriginal = $info[1];
            $tipo = $info[2];
            
            // Definir tamanhos máximos
            $larguraMax = 1200;
            $alturaMax = 800;
            
            // Calcular novas dimensões
            if($larguraOriginal <= $larguraMax && $alturaOriginal <= $alturaMax) {
                return true; // Não precisa redimensionar
            }
            
            $ratio = min($larguraMax / $larguraOriginal, $alturaMax / $alturaOriginal);
            $novaLargura = round($larguraOriginal * $ratio);
            $novaAltura = round($alturaOriginal * $ratio);
            
            // Criar imagem original
            switch($tipo) {
                case IMAGETYPE_JPEG:
                    $imagemOriginal = imagecreatefromjpeg($caminhoArquivo);
                    break;
                case IMAGETYPE_PNG:
                    $imagemOriginal = imagecreatefrompng($caminhoArquivo);
                    break;
                case IMAGETYPE_GIF:
                    $imagemOriginal = imagecreatefromgif($caminhoArquivo);
                    break;
                case IMAGETYPE_WEBP:
                    $imagemOriginal = imagecreatefromwebp($caminhoArquivo);
                    break;
                default:
                    return false;
            }
            
            if(!$imagemOriginal) {
                return false;
            }
            
            // Criar nova imagem
            $novaImagem = imagecreatetruecolor($novaLargura, $novaAltura);
            
            // Preservar transparência para PNG e GIF
            if($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                imagealphablending($novaImagem, false);
                imagesavealpha($novaImagem, true);
                $transparente = imagecolorallocatealpha($novaImagem, 255, 255, 255, 127);
                imagefill($novaImagem, 0, 0, $transparente);
            }
            
            // Redimensionar
            imagecopyresampled(
                $novaImagem, $imagemOriginal,
                0, 0, 0, 0,
                $novaLargura, $novaAltura,
                $larguraOriginal, $alturaOriginal
            );
            
            // Salvar imagem redimensionada
            switch($tipo) {
                case IMAGETYPE_JPEG:
                    imagejpeg($novaImagem, $caminhoArquivo, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($novaImagem, $caminhoArquivo, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($novaImagem, $caminhoArquivo);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($novaImagem, $caminhoArquivo, 85);
                    break;
            }
            
            // Limpar memória
            imagedestroy($imagemOriginal);
            imagedestroy($novaImagem);
            
            return true;
            
        } catch(Exception $e) {
            logError('Erro ao redimensionar imagem: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar miniaturas
     */
    public function criarMiniatura($caminhoOriginal, $largura = 300, $altura = 200) {
        try {
            $info = getimagesize($caminhoOriginal);
            if($info === false) {
                return false;
            }
            
            $larguraOriginal = $info[0];
            $alturaOriginal = $info[1];
            $tipo = $info[2];
            
            // Criar imagem original
            switch($tipo) {
                case IMAGETYPE_JPEG:
                    $imagemOriginal = imagecreatefromjpeg($caminhoOriginal);
                    break;
                case IMAGETYPE_PNG:
                    $imagemOriginal = imagecreatefrompng($caminhoOriginal);
                    break;
                case IMAGETYPE_GIF:
                    $imagemOriginal = imagecreatefromgif($caminhoOriginal);
                    break;
                case IMAGETYPE_WEBP:
                    $imagemOriginal = imagecreatefromwebp($caminhoOriginal);
                    break;
                default:
                    return false;
            }
            
            if(!$imagemOriginal) {
                return false;
            }
            
            // Criar miniatura
            $miniatura = imagecreatetruecolor($largura, $altura);
            
            // Preservar transparência
            if($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                imagealphablending($miniatura, false);
                imagesavealpha($miniatura, true);
                $transparente = imagecolorallocatealpha($miniatura, 255, 255, 255, 127);
                imagefill($miniatura, 0, 0, $transparente);
            }
            
            // Redimensionar mantendo proporção (crop)
            $ratioOriginal = $larguraOriginal / $alturaOriginal;
            $ratioMiniatura = $largura / $altura;
            
            if($ratioOriginal > $ratioMiniatura) {
                // Imagem mais larga
                $novaAltura = $alturaOriginal;
                $novaLargura = $alturaOriginal * $ratioMiniatura;
                $x = ($larguraOriginal - $novaLargura) / 2;
                $y = 0;
            } else {
                // Imagem mais alta
                $novaLargura = $larguraOriginal;
                $novaAltura = $larguraOriginal / $ratioMiniatura;
                $x = 0;
                $y = ($alturaOriginal - $novaAltura) / 2;
            }
            
            imagecopyresampled(
                $miniatura, $imagemOriginal,
                0, 0, $x, $y,
                $largura, $altura,
                $novaLargura, $novaAltura
            );
            
            // Gerar nome da miniatura
            $pathInfo = pathinfo($caminhoOriginal);
            $caminhoMiniatura = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
            
            // Salvar miniatura
            switch($tipo) {
                case IMAGETYPE_JPEG:
                    imagejpeg($miniatura, $caminhoMiniatura, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($miniatura, $caminhoMiniatura, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($miniatura, $caminhoMiniatura);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($miniatura, $caminhoMiniatura, 85);
                    break;
            }
            
            // Limpar memória
            imagedestroy($imagemOriginal);
            imagedestroy($miniatura);
            
            return str_replace($this->uploadPath . '/', 'uploads/', $caminhoMiniatura);
            
        } catch(Exception $e) {
            logError('Erro ao criar miniatura: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Excluir arquivo
     */
    public function excluirArquivo($caminhoArquivo) {
        try {
            $caminhoCompleto = str_replace('uploads/', $this->uploadPath . '/', $caminhoArquivo);
            
            if(file_exists($caminhoCompleto)) {
                unlink($caminhoCompleto);
                
                // Excluir miniatura se existir
                $pathInfo = pathinfo($caminhoCompleto);
                $caminhoMiniatura = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
                if(file_exists($caminhoMiniatura)) {
                    unlink($caminhoMiniatura);
                }
                
                return true;
            }
            
            return false;
            
        } catch(Exception $e) {
            logError('Erro ao excluir arquivo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter informações do arquivo
     */
    public function obterInfoArquivo($caminhoArquivo) {
        try {
            $caminhoCompleto = str_replace('uploads/', $this->uploadPath . '/', $caminhoArquivo);
            
            if(!file_exists($caminhoCompleto)) {
                return false;
            }
            
            $info = [
                'nome' => basename($caminhoCompleto),
                'tamanho' => filesize($caminhoCompleto),
                'tamanho_formatado' => formatBytes(filesize($caminhoCompleto)),
                'tipo' => mime_content_type($caminhoCompleto),
                'data_modificacao' => filemtime($caminhoCompleto),
                'caminho' => $caminhoArquivo
            ];
            
            // Informações adicionais para imagens
            if(strpos($info['tipo'], 'image/') === 0) {
                $dimensoes = getimagesize($caminhoCompleto);
                if($dimensoes) {
                    $info['largura'] = $dimensoes[0];
                    $info['altura'] = $dimensoes[1];
                    $info['dimensoes'] = $dimensoes[0] . 'x' . $dimensoes[1];
                }
            }
            
            return $info;
            
        } catch(Exception $e) {
            logError('Erro ao obter info do arquivo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar arquivos de um diretório
     */
    public function listarArquivos($tipo = 'images', $limite = 50, $offset = 0) {
        try {
            $diretorio = $this->uploadPath . '/' . $tipo;
            
            if(!is_dir($diretorio)) {
                return [];
            }
            
            $arquivos = [];
            $iterator = new DirectoryIterator($diretorio);
            
            foreach($iterator as $arquivo) {
                if($arquivo->isDot() || $arquivo->isDir()) {
                    continue;
                }
                
                // Pular miniaturas
                if(strpos($arquivo->getFilename(), 'thumb_') === 0) {
                    continue;
                }
                
                $arquivos[] = [
                    'nome' => $arquivo->getFilename(),
                    'caminho' => 'uploads/' . $tipo . '/' . $arquivo->getFilename(),
                    'tamanho' => $arquivo->getSize(),
                    'data_modificacao' => $arquivo->getMTime()
                ];
            }
            
            // Ordenar por data de modificação (mais recente primeiro)
            usort($arquivos, function($a, $b) {
                return $b['data_modificacao'] - $a['data_modificacao'];
            });
            
            // Aplicar limite e offset
            return array_slice($arquivos, $offset, $limite);
            
        } catch(Exception $e) {
            logError('Erro ao listar arquivos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Criar diretórios necessários
     */
    private function criarDiretorios() {
        $diretorios = [
            $this->uploadPath,
            $this->uploadPath . '/images',
            $this->uploadPath . '/videos',
            $this->uploadPath . '/audios',
            $this->uploadPath . '/documents'
        ];
        
        foreach($diretorios as $diretorio) {
            if(!is_dir($diretorio)) {
                mkdir($diretorio, 0755, true);
            }
        }
    }
    
    /**
     * Obter mensagem de erro do upload
     */
    private function obterMensagemErroUpload($codigo) {
        switch($codigo) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Arquivo muito grande (limite do servidor)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo muito grande (limite do formulário)';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incompleto';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Erro ao escrever arquivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bloqueado por extensão';
            default:
                return 'Erro desconhecido no upload';
        }
    }
}

/**
 * Função auxiliar para formatar bytes
 */
if(!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>