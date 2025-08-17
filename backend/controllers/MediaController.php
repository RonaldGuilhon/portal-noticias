<?php
/**
 * Controlador de Mídia
 * Portal de Notícias
 */

require_once __DIR__ . '/../services/UploadService.php';
require_once __DIR__ . '/../services/ImageService.php';

class MediaController {
    private $uploadService;
    private $imageService;
    
    public function __construct() {
        $this->uploadService = new UploadService();
        $this->imageService = new ImageService();
    }

    /**
     * Upload de arquivo
     */
    public function upload() {
        try {
            // Verificar autenticação
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            if (!isset($_FILES['arquivo'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nenhum arquivo enviado']);
                return;
            }

            $arquivo = $_FILES['arquivo'];
            $tipo = $_POST['tipo'] ?? 'geral'; // noticia, usuario, anuncio, geral
            $redimensionar = isset($_POST['redimensionar']) ? (bool)$_POST['redimensionar'] : false;
            $largura = isset($_POST['largura']) ? (int)$_POST['largura'] : null;
            $altura = isset($_POST['altura']) ? (int)$_POST['altura'] : null;

            // Definir diretório baseado no tipo
            $diretorios = [
                'noticia' => 'noticias',
                'usuario' => 'usuarios',
                'anuncio' => 'anuncios',
                'geral' => 'temp'
            ];

            $diretorio = $diretorios[$tipo] ?? 'temp';

            // Fazer upload
            $resultado = $this->uploadService->processarUpload($arquivo, $diretorio);

            if ($resultado['success']) {
                $caminhoCompleto = $resultado['caminho'];

                // Se for imagem e solicitado redimensionamento
                if ($redimensionar && $this->imageService->isImage($caminhoCompleto)) {
                    $redimensionado = $this->imageService->resize(
                        $caminhoCompleto,
                        $largura,
                        $altura
                    );

                    if (!$redimensionado) {
                        // Log do erro mas continua com a imagem original
                        error_log("Erro ao redimensionar imagem: {$caminhoCompleto}");
                    }
                }

                // Obter informações do arquivo
                $info = $this->obterInfoArquivo($caminhoCompleto);

                echo json_encode([
                    'success' => true,
                    'arquivo' => [
                        'nome' => $resultado['nome'],
                        'nome_original' => $arquivo['name'],
                        'caminho' => $resultado['caminho_relativo'],
                        'url' => $resultado['url'],
                        'tamanho' => $info['tamanho'],
                        'tipo' => $info['tipo'],
                        'dimensoes' => $info['dimensoes']
                    ]
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['erro' => $resultado['erro']]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro no upload: " . $e->getMessage());
        }
    }

    /**
     * Upload múltiplo
     */
    public function uploadMultiplo() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            if (!isset($_FILES['arquivos'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nenhum arquivo enviado']);
                return;
            }

            $arquivos = $_FILES['arquivos'];
            $tipo = $_POST['tipo'] ?? 'geral';
            $resultados = [];

            // Normalizar array de arquivos múltiplos
            $arquivosNormalizados = [];
            if (is_array($arquivos['name'])) {
                for ($i = 0; $i < count($arquivos['name']); $i++) {
                    $arquivosNormalizados[] = [
                        'name' => $arquivos['name'][$i],
                        'type' => $arquivos['type'][$i],
                        'tmp_name' => $arquivos['tmp_name'][$i],
                        'error' => $arquivos['error'][$i],
                        'size' => $arquivos['size'][$i]
                    ];
                }
            } else {
                $arquivosNormalizados[] = $arquivos;
            }

            foreach ($arquivosNormalizados as $arquivo) {
                if ($arquivo['error'] === UPLOAD_ERR_OK) {
                    $resultado = $this->uploadService->processarUpload($arquivo, $tipo);
                    
                    if ($resultado['success']) {
                        $info = $this->obterInfoArquivo($resultado['caminho']);
                        $resultados[] = [
                            'success' => true,
                            'arquivo' => [
                                'nome' => $resultado['nome'],
                                'nome_original' => $arquivo['name'],
                                'caminho' => $resultado['caminho_relativo'],
                                'url' => $resultado['url'],
                                'tamanho' => $info['tamanho'],
                                'tipo' => $info['tipo']
                            ]
                        ];
                    } else {
                        $resultados[] = [
                            'success' => false,
                            'erro' => $resultado['erro'],
                            'arquivo' => $arquivo['name']
                        ];
                    }
                } else {
                    $resultados[] = [
                        'success' => false,
                        'erro' => 'Erro no upload: ' . $arquivo['error'],
                        'arquivo' => $arquivo['name']
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'resultados' => $resultados
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro no upload múltiplo: " . $e->getMessage());
        }
    }

    /**
     * Listar arquivos
     */
    public function listar() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $tipo = $_GET['tipo'] ?? 'geral';
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
            $filtro = $_GET['filtro'] ?? '';

            $diretorios = [
                'noticia' => 'noticias',
                'usuario' => 'usuarios',
                'anuncio' => 'anuncios',
                'geral' => 'temp'
            ];

            $diretorio = $diretorios[$tipo] ?? 'temp';
            $caminhoCompleto = $this->uploadService->getUploadPath() . '/' . $diretorio;

            if (!is_dir($caminhoCompleto)) {
                echo json_encode([
                    'arquivos' => [],
                    'total' => 0,
                    'pagina' => $pagina,
                    'total_paginas' => 0
                ]);
                return;
            }

            $arquivos = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($caminhoCompleto)
            );

            foreach ($iterator as $arquivo) {
                if ($arquivo->isFile()) {
                    $nomeArquivo = $arquivo->getFilename();
                    
                    // Aplicar filtro se especificado
                    if (!empty($filtro) && stripos($nomeArquivo, $filtro) === false) {
                        continue;
                    }

                    $caminhoRelativo = str_replace(
                        $this->uploadService->getUploadPath() . '/',
                        '',
                        $arquivo->getPathname()
                    );

                    $info = $this->obterInfoArquivo($arquivo->getPathname());
                    
                    $arquivos[] = [
                        'nome' => $nomeArquivo,
                        'caminho' => $caminhoRelativo,
                        'url' => $this->uploadService->getUploadUrl() . '/' . $caminhoRelativo,
                        'tamanho' => $info['tamanho'],
                        'tipo' => $info['tipo'],
                        'data_modificacao' => date('Y-m-d H:i:s', $arquivo->getMTime()),
                        'dimensoes' => $info['dimensoes']
                    ];
                }
            }

            // Ordenar por data de modificação (mais recente primeiro)
            usort($arquivos, function($a, $b) {
                return strtotime($b['data_modificacao']) - strtotime($a['data_modificacao']);
            });

            // Paginação
            $total = count($arquivos);
            $totalPaginas = ceil($total / $limite);
            $offset = ($pagina - 1) * $limite;
            $arquivosPaginados = array_slice($arquivos, $offset, $limite);

            echo json_encode([
                'arquivos' => $arquivosPaginados,
                'total' => $total,
                'pagina' => $pagina,
                'total_paginas' => $totalPaginas
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro ao listar arquivos: " . $e->getMessage());
        }
    }

    /**
     * Deletar arquivo
     */
    public function deletar() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $caminho = $input['caminho'] ?? '';

            if (empty($caminho)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Caminho do arquivo não especificado']);
                return;
            }

            $resultado = $this->uploadService->delete($caminho);

            if ($resultado) {
                echo json_encode(['success' => true, 'mensagem' => 'Arquivo deletado com sucesso']);
            } else {
                http_response_code(400);
                echo json_encode(['erro' => 'Erro ao deletar arquivo']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro ao deletar arquivo: " . $e->getMessage());
        }
    }

    /**
     * Redimensionar imagem
     */
    public function redimensionar() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $caminho = $input['caminho'] ?? '';
            $largura = $input['largura'] ?? null;
            $altura = $input['altura'] ?? null;
            $manter_proporcao = $input['manter_proporcao'] ?? true;

            if (empty($caminho)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Caminho da imagem não especificado']);
                return;
            }

            $caminhoCompleto = $this->uploadService->getUploadPath() . '/' . $caminho;

            if (!file_exists($caminhoCompleto)) {
                http_response_code(404);
                echo json_encode(['erro' => 'Arquivo não encontrado']);
                return;
            }

            if (!$this->imageService->isImage($caminhoCompleto)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Arquivo não é uma imagem válida']);
                return;
            }

            $resultado = $this->imageService->resize(
                $caminhoCompleto,
                $largura,
                $altura,
                $manter_proporcao
            );

            if ($resultado) {
                $info = $this->obterInfoArquivo($caminhoCompleto);
                echo json_encode([
                    'success' => true,
                    'mensagem' => 'Imagem redimensionada com sucesso',
                    'dimensoes' => $info['dimensoes']
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['erro' => 'Erro ao redimensionar imagem']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro ao redimensionar imagem: " . $e->getMessage());
        }
    }

    /**
     * Obter informações de um arquivo
     */
    public function info() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $caminho = $_GET['caminho'] ?? '';

            if (empty($caminho)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Caminho do arquivo não especificado']);
                return;
            }

            $caminhoCompleto = $this->uploadService->getUploadPath() . '/' . $caminho;

            if (!file_exists($caminhoCompleto)) {
                http_response_code(404);
                echo json_encode(['erro' => 'Arquivo não encontrado']);
                return;
            }

            $info = $this->obterInfoArquivo($caminhoCompleto);
            $info['caminho'] = $caminho;
            $info['url'] = $this->uploadService->getUploadUrl() . '/' . $caminho;
            $info['data_modificacao'] = date('Y-m-d H:i:s', filemtime($caminhoCompleto));

            echo json_encode($info);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro ao obter informações do arquivo: " . $e->getMessage());
        }
    }

    /**
     * Limpar arquivos temporários antigos
     */
    public function limparTemp() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
            $resultado = $this->uploadService->cleanupTempFiles($dias);

            echo json_encode([
                'success' => true,
                'mensagem' => "Limpeza concluída. {$resultado} arquivos removidos.",
                'arquivos_removidos' => $resultado
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro na limpeza de arquivos temporários: " . $e->getMessage());
        }
    }

    /**
     * Obter estatísticas de uso de armazenamento
     */
    public function estatisticas() {
        try {
            if (!$this->verificarAutenticacao()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Acesso negado']);
                return;
            }

            $uploadPath = $this->uploadService->getUploadPath();
            $diretorios = ['noticias', 'usuarios', 'anuncios', 'temp'];
            $estatisticas = [];

            foreach ($diretorios as $dir) {
                $caminho = $uploadPath . '/' . $dir;
                $tamanho = 0;
                $arquivos = 0;

                if (is_dir($caminho)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($caminho)
                    );

                    foreach ($iterator as $arquivo) {
                        if ($arquivo->isFile()) {
                            $tamanho += $arquivo->getSize();
                            $arquivos++;
                        }
                    }
                }

                $estatisticas[$dir] = [
                    'arquivos' => $arquivos,
                    'tamanho' => $tamanho,
                    'tamanho_formatado' => $this->formatarTamanho($tamanho)
                ];
            }

            // Calcular total
            $totalArquivos = array_sum(array_column($estatisticas, 'arquivos'));
            $totalTamanho = array_sum(array_column($estatisticas, 'tamanho'));

            echo json_encode([
                'diretorios' => $estatisticas,
                'total' => [
                    'arquivos' => $totalArquivos,
                    'tamanho' => $totalTamanho,
                    'tamanho_formatado' => $this->formatarTamanho($totalTamanho)
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno do servidor']);
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
        }
    }

    /**
     * Verificar autenticação
     */
    private function verificarAutenticacao() {
        session_start();
        return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_tipo']);
    }

    /**
     * Obter informações detalhadas de um arquivo
     */
    private function obterInfoArquivo($caminho) {
        $info = [
            'nome' => basename($caminho),
            'tamanho' => filesize($caminho),
            'tamanho_formatado' => $this->formatarTamanho(filesize($caminho)),
            'tipo' => mime_content_type($caminho),
            'extensao' => strtolower(pathinfo($caminho, PATHINFO_EXTENSION)),
            'dimensoes' => null
        ];

        // Se for imagem, obter dimensões
        if ($this->imageService->isImage($caminho)) {
            $dimensoes = getimagesize($caminho);
            if ($dimensoes) {
                $info['dimensoes'] = [
                    'largura' => $dimensoes[0],
                    'altura' => $dimensoes[1]
                ];
            }
        }

        return $info;
    }

    /**
     * Formatar tamanho de arquivo
     */
    private function formatarTamanho($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($unidades) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $unidades[$pow];
    }
}

// Roteamento
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
} else {
    $action = $_POST['action'] ?? 'upload';
}

$controller = new MediaController();

switch ($action) {
    case 'upload':
        $controller->upload();
        break;
    case 'upload-multiplo':
        $controller->uploadMultiplo();
        break;
    case 'listar':
        $controller->listar();
        break;
    case 'deletar':
        $controller->deletar();
        break;
    case 'redimensionar':
        $controller->redimensionar();
        break;
    case 'info':
        $controller->info();
        break;
    case 'limpar-temp':
        $controller->limparTemp();
        break;
    case 'estatisticas':
        $controller->estatisticas();
        break;
    default:
        http_response_code(400);
        echo json_encode(['erro' => 'Ação não reconhecida']);
        break;
}
?>