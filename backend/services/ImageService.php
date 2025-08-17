<?php
/**
 * Serviço de Manipulação de Imagens
 * Portal de Notícias
 */

class ImageService {
    private $tipos_suportados = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $qualidade_jpeg = 85;
    private $qualidade_webp = 80;
    
    /**
     * Verificar se o arquivo é uma imagem
     */
    public function isImage($caminho) {
        if (!file_exists($caminho)) {
            return false;
        }
        
        $info = getimagesize($caminho);
        return $info !== false;
    }
    
    /**
     * Redimensionar imagem
     */
    public function resize($caminho, $nova_largura = null, $nova_altura = null, $manter_proporcao = true) {
        try {
            if (!$this->isImage($caminho)) {
                return false;
            }
            
            $info = getimagesize($caminho);
            $largura_original = $info[0];
            $altura_original = $info[1];
            $tipo = $info[2];
            
            // Se não especificou dimensões, manter originais
            if ($nova_largura === null && $nova_altura === null) {
                return true;
            }
            
            // Calcular dimensões finais
            if ($manter_proporcao) {
                $dimensoes = $this->calcularDimensoesProporcional(
                    $largura_original, 
                    $altura_original, 
                    $nova_largura, 
                    $nova_altura
                );
                $nova_largura = $dimensoes['largura'];
                $nova_altura = $dimensoes['altura'];
            } else {
                $nova_largura = $nova_largura ?: $largura_original;
                $nova_altura = $nova_altura ?: $altura_original;
            }
            
            // Criar imagem original
            $imagem_original = $this->criarImagemPorTipo($caminho, $tipo);
            if (!$imagem_original) {
                return false;
            }
            
            // Criar nova imagem
            $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);
            
            // Preservar transparência para PNG e GIF
            if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                imagealphablending($nova_imagem, false);
                imagesavealpha($nova_imagem, true);
                $transparente = imagecolorallocatealpha($nova_imagem, 255, 255, 255, 127);
                imagefill($nova_imagem, 0, 0, $transparente);
            }
            
            // Redimensionar
            imagecopyresampled(
                $nova_imagem, 
                $imagem_original, 
                0, 0, 0, 0, 
                $nova_largura, 
                $nova_altura, 
                $largura_original, 
                $altura_original
            );
            
            // Salvar imagem
            $resultado = $this->salvarImagemPorTipo($nova_imagem, $caminho, $tipo);
            
            // Limpar memória
            imagedestroy($imagem_original);
            imagedestroy($nova_imagem);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao redimensionar imagem: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar thumbnail
     */
    public function createThumbnail($caminho_origem, $caminho_destino, $largura = 150, $altura = 150) {
        try {
            if (!$this->isImage($caminho_origem)) {
                return false;
            }
            
            $info = getimagesize($caminho_origem);
            $largura_original = $info[0];
            $altura_original = $info[1];
            $tipo = $info[2];
            
            // Calcular dimensões do thumbnail (crop centralizado)
            $ratio_original = $largura_original / $altura_original;
            $ratio_thumb = $largura / $altura;
            
            if ($ratio_original > $ratio_thumb) {
                // Imagem mais larga - cortar laterais
                $nova_largura_temp = $altura_original * $ratio_thumb;
                $nova_altura_temp = $altura_original;
                $x = ($largura_original - $nova_largura_temp) / 2;
                $y = 0;
            } else {
                // Imagem mais alta - cortar topo/base
                $nova_largura_temp = $largura_original;
                $nova_altura_temp = $largura_original / $ratio_thumb;
                $x = 0;
                $y = ($altura_original - $nova_altura_temp) / 2;
            }
            
            // Criar imagem original
            $imagem_original = $this->criarImagemPorTipo($caminho_origem, $tipo);
            if (!$imagem_original) {
                return false;
            }
            
            // Criar thumbnail
            $thumbnail = imagecreatetruecolor($largura, $altura);
            
            // Preservar transparência
            if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparente = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparente);
            }
            
            // Redimensionar e cortar
            imagecopyresampled(
                $thumbnail, 
                $imagem_original, 
                0, 0, 
                $x, $y, 
                $largura, 
                $altura, 
                $nova_largura_temp, 
                $nova_altura_temp
            );
            
            // Salvar thumbnail
            $resultado = $this->salvarImagemPorTipo($thumbnail, $caminho_destino, $tipo);
            
            // Limpar memória
            imagedestroy($imagem_original);
            imagedestroy($thumbnail);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao criar thumbnail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Converter imagem para WebP
     */
    public function convertToWebP($caminho_origem, $caminho_destino = null) {
        try {
            if (!$this->isImage($caminho_origem)) {
                return false;
            }
            
            if (!function_exists('imagewebp')) {
                return false; // WebP não suportado
            }
            
            $info = getimagesize($caminho_origem);
            $tipo = $info[2];
            
            // Criar imagem original
            $imagem = $this->criarImagemPorTipo($caminho_origem, $tipo);
            if (!$imagem) {
                return false;
            }
            
            // Definir caminho de destino
            if ($caminho_destino === null) {
                $caminho_destino = preg_replace('/\.[^.]+$/', '.webp', $caminho_origem);
            }
            
            // Salvar como WebP
            $resultado = imagewebp($imagem, $caminho_destino, $this->qualidade_webp);
            
            // Limpar memória
            imagedestroy($imagem);
            
            return $resultado ? $caminho_destino : false;
            
        } catch (Exception $e) {
            error_log("Erro ao converter para WebP: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Otimizar imagem (reduzir tamanho do arquivo)
     */
    public function optimize($caminho, $qualidade = null) {
        try {
            if (!$this->isImage($caminho)) {
                return false;
            }
            
            $info = getimagesize($caminho);
            $tipo = $info[2];
            
            // Definir qualidade baseada no tipo
            if ($qualidade === null) {
                switch ($tipo) {
                    case IMAGETYPE_JPEG:
                        $qualidade = $this->qualidade_jpeg;
                        break;
                    case IMAGETYPE_WEBP:
                        $qualidade = $this->qualidade_webp;
                        break;
                    default:
                        $qualidade = 85;
                }
            }
            
            // Criar imagem
            $imagem = $this->criarImagemPorTipo($caminho, $tipo);
            if (!$imagem) {
                return false;
            }
            
            // Salvar com nova qualidade
            $resultado = $this->salvarImagemPorTipo($imagem, $caminho, $tipo, $qualidade);
            
            // Limpar memória
            imagedestroy($imagem);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao otimizar imagem: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adicionar marca d'água
     */
    public function addWatermark($caminho_imagem, $caminho_marca, $posicao = 'bottom-right', $opacidade = 50) {
        try {
            if (!$this->isImage($caminho_imagem) || !$this->isImage($caminho_marca)) {
                return false;
            }
            
            $info_imagem = getimagesize($caminho_imagem);
            $info_marca = getimagesize($caminho_marca);
            
            // Criar imagens
            $imagem = $this->criarImagemPorTipo($caminho_imagem, $info_imagem[2]);
            $marca = $this->criarImagemPorTipo($caminho_marca, $info_marca[2]);
            
            if (!$imagem || !$marca) {
                return false;
            }
            
            // Calcular posição
            $posicoes = $this->calcularPosicaoMarca(
                $info_imagem[0], $info_imagem[1],
                $info_marca[0], $info_marca[1],
                $posicao
            );
            
            // Aplicar marca d'água
            imagecopymerge(
                $imagem, $marca,
                $posicoes['x'], $posicoes['y'],
                0, 0,
                $info_marca[0], $info_marca[1],
                $opacidade
            );
            
            // Salvar imagem
            $resultado = $this->salvarImagemPorTipo($imagem, $caminho_imagem, $info_imagem[2]);
            
            // Limpar memória
            imagedestroy($imagem);
            imagedestroy($marca);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao adicionar marca d'água: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter informações da imagem
     */
    public function getImageInfo($caminho) {
        if (!$this->isImage($caminho)) {
            return false;
        }
        
        $info = getimagesize($caminho);
        $tamanho = filesize($caminho);
        
        return [
            'largura' => $info[0],
            'altura' => $info[1],
            'tipo' => $info[2],
            'tipo_nome' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
            'tamanho' => $tamanho,
            'tamanho_formatado' => $this->formatarTamanho($tamanho),
            'ratio' => round($info[0] / $info[1], 2)
        ];
    }
    
    /**
     * Criar imagem baseada no tipo
     */
    private function criarImagemPorTipo($caminho, $tipo) {
        switch ($tipo) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($caminho);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($caminho);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($caminho);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($caminho) : false;
            default:
                return false;
        }
    }
    
    /**
     * Salvar imagem baseada no tipo
     */
    private function salvarImagemPorTipo($imagem, $caminho, $tipo, $qualidade = null) {
        switch ($tipo) {
            case IMAGETYPE_JPEG:
                return imagejpeg($imagem, $caminho, $qualidade ?: $this->qualidade_jpeg);
            case IMAGETYPE_PNG:
                return imagepng($imagem, $caminho);
            case IMAGETYPE_GIF:
                return imagegif($imagem, $caminho);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp') ? imagewebp($imagem, $caminho, $qualidade ?: $this->qualidade_webp) : false;
            default:
                return false;
        }
    }
    
    /**
     * Calcular dimensões proporcionais
     */
    private function calcularDimensoesProporcional($largura_original, $altura_original, $nova_largura, $nova_altura) {
        $ratio_original = $largura_original / $altura_original;
        
        if ($nova_largura && $nova_altura) {
            // Ambas especificadas - usar a menor proporção
            $ratio_largura = $nova_largura / $largura_original;
            $ratio_altura = $nova_altura / $altura_original;
            $ratio = min($ratio_largura, $ratio_altura);
            
            return [
                'largura' => (int)($largura_original * $ratio),
                'altura' => (int)($altura_original * $ratio)
            ];
        } elseif ($nova_largura) {
            // Apenas largura especificada
            return [
                'largura' => $nova_largura,
                'altura' => (int)($nova_largura / $ratio_original)
            ];
        } elseif ($nova_altura) {
            // Apenas altura especificada
            return [
                'largura' => (int)($nova_altura * $ratio_original),
                'altura' => $nova_altura
            ];
        }
        
        // Nenhuma especificada - manter originais
        return [
            'largura' => $largura_original,
            'altura' => $altura_original
        ];
    }
    
    /**
     * Calcular posição da marca d'água
     */
    private function calcularPosicaoMarca($img_largura, $img_altura, $marca_largura, $marca_altura, $posicao) {
        $margem = 10;
        
        switch ($posicao) {
            case 'top-left':
                return ['x' => $margem, 'y' => $margem];
            case 'top-right':
                return ['x' => $img_largura - $marca_largura - $margem, 'y' => $margem];
            case 'bottom-left':
                return ['x' => $margem, 'y' => $img_altura - $marca_altura - $margem];
            case 'bottom-right':
            default:
                return ['x' => $img_largura - $marca_largura - $margem, 'y' => $img_altura - $marca_altura - $margem];
            case 'center':
                return [
                    'x' => ($img_largura - $marca_largura) / 2,
                    'y' => ($img_altura - $marca_altura) / 2
                ];
        }
    }
    
    /**
     * Formatar tamanho de arquivo
     */
    private function formatarTamanho($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($unidades) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $unidades[$pow];
    }
}
?>