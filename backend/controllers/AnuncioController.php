<?php
/**
 * Controlador de Anúncios
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Anuncio.php';
require_once __DIR__ . '/../services/UploadService.php';

class AnuncioController {
    private $db;
    private $anuncio;
    private $uploadService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->anuncio = new Anuncio($this->db);
        $this->uploadService = new UploadService();
        
        // Iniciar sessão se não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Processar requisições
     */
    public function processarRequisicao() {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $segmentos = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        
        try {
            switch($metodo) {
                case 'GET':
                    if(isset($segmentos[2])) {
                        if($segmentos[2] === 'publicos') {
                            $this->listarAnunciosPublicos();
                        } elseif($segmentos[2] === 'estatisticas') {
                            $this->obterEstatisticas();
                        } elseif(is_numeric($segmentos[2])) {
                            $this->obterAnuncio($segmentos[2]);
                        }
                    } else {
                        $this->listarAnuncios();
                    }
                    break;
                    
                case 'POST':
                    if(isset($segmentos[2]) && $segmentos[2] === 'clique') {
                        $this->registrarClique();
                    } else {
                        $this->criarAnuncio();
                    }
                    break;
                    
                case 'PUT':
                    $this->atualizarAnuncio($segmentos[2]);
                    break;
                    
                case 'DELETE':
                    $this->deletarAnuncio($segmentos[2]);
                    break;
                    
                default:
                    jsonResponse(['erro' => 'Método não permitido'], 405);
            }
        } catch(Exception $e) {
            logError('Erro no AnuncioController: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Listar anúncios (admin)
     */
    public function listarAnuncios() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $pagina = (int)($_GET['pagina'] ?? 1);
        $limite = min((int)($_GET['limite'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'posicao' => $_GET['posicao'] ?? '',
            'search' => $_GET['search'] ?? '',
            'periodo' => $_GET['periodo'] ?? ''
        ];
        
        $resultado = $this->anuncio->listar($pagina, $limite, $filtros);
        
        jsonResponse($resultado);
    }

    /**
     * Listar anúncios públicos (para exibição no site)
     */
    public function listarAnunciosPublicos() {
        $posicao = $_GET['posicao'] ?? '';
        $limite = min((int)($_GET['limite'] ?? 10), 20);
        
        $anuncios = $this->anuncio->obterAtivos($posicao, $limite);
        
        // Registrar impressões
        foreach($anuncios as $anuncio) {
            $this->anuncio->registrarImpressao($anuncio['id']);
        }
        
        jsonResponse(['anuncios' => $anuncios]);
    }

    /**
     * Obter anúncio específico
     */
    public function obterAnuncio($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $anuncio = $this->anuncio->obterPorId($id);
        
        if($anuncio) {
            jsonResponse($anuncio);
        } else {
            jsonResponse(['erro' => 'Anúncio não encontrado'], 404);
        }
    }

    /**
     * Criar novo anúncio
     */
    public function criarAnuncio() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            $dados = $_POST;
        }
        
        // Validar dados obrigatórios
        if(empty($dados['titulo']) || empty($dados['posicao']) || empty($dados['tipo'])) {
            jsonResponse(['erro' => 'Dados obrigatórios não fornecidos'], 400);
            return;
        }
        
        // Processar upload de imagem se fornecida
        $imagem_url = null;
        if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = $this->uploadService->uploadImagem($_FILES['imagem']);
            if($resultado_upload['success']) {
                $imagem_url = $resultado_upload['url'];
            } else {
                jsonResponse(['erro' => $resultado_upload['erro']], 400);
                return;
            }
        }
        
        $dados_anuncio = [
            'titulo' => sanitizeInput($dados['titulo']),
            'descricao' => sanitizeInput($dados['descricao'] ?? ''),
            'tipo' => $dados['tipo'], // banner, texto, video
            'posicao' => $dados['posicao'], // header, sidebar, footer, content
            'conteudo' => $dados['conteudo'] ?? '',
            'link_destino' => filter_var($dados['link_destino'] ?? '', FILTER_SANITIZE_URL),
            'imagem_url' => $imagem_url,
            'data_inicio' => $dados['data_inicio'] ?? date('Y-m-d'),
            'data_fim' => $dados['data_fim'] ?? null,
            'ativo' => isset($dados['ativo']) ? (bool)$dados['ativo'] : true,
            'prioridade' => (int)($dados['prioridade'] ?? 1),
            'target_blank' => isset($dados['target_blank']) ? (bool)$dados['target_blank'] : true,
            'usuario_id' => $_SESSION['usuario_id']
        ];
        
        $id = $this->anuncio->criar($dados_anuncio);
        
        if($id) {
            jsonResponse([
                'success' => 'Anúncio criado com sucesso',
                'id' => $id
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao criar anúncio'], 500);
        }
    }

    /**
     * Atualizar anúncio
     */
    public function atualizarAnuncio($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados não fornecidos'], 400);
            return;
        }
        
        // Verificar se anúncio existe
        $anuncio_existente = $this->anuncio->obterPorId($id);
        if(!$anuncio_existente) {
            jsonResponse(['erro' => 'Anúncio não encontrado'], 404);
            return;
        }
        
        $dados_atualizacao = [];
        
        if(isset($dados['titulo'])) {
            $dados_atualizacao['titulo'] = sanitizeInput($dados['titulo']);
        }
        
        if(isset($dados['descricao'])) {
            $dados_atualizacao['descricao'] = sanitizeInput($dados['descricao']);
        }
        
        if(isset($dados['tipo'])) {
            $dados_atualizacao['tipo'] = $dados['tipo'];
        }
        
        if(isset($dados['posicao'])) {
            $dados_atualizacao['posicao'] = $dados['posicao'];
        }
        
        if(isset($dados['conteudo'])) {
            $dados_atualizacao['conteudo'] = $dados['conteudo'];
        }
        
        if(isset($dados['link_destino'])) {
            $dados_atualizacao['link_destino'] = filter_var($dados['link_destino'], FILTER_SANITIZE_URL);
        }
        
        if(isset($dados['data_inicio'])) {
            $dados_atualizacao['data_inicio'] = $dados['data_inicio'];
        }
        
        if(isset($dados['data_fim'])) {
            $dados_atualizacao['data_fim'] = $dados['data_fim'];
        }
        
        if(isset($dados['ativo'])) {
            $dados_atualizacao['ativo'] = (bool)$dados['ativo'];
        }
        
        if(isset($dados['prioridade'])) {
            $dados_atualizacao['prioridade'] = (int)$dados['prioridade'];
        }
        
        if(isset($dados['target_blank'])) {
            $dados_atualizacao['target_blank'] = (bool)$dados['target_blank'];
        }
        
        // Processar nova imagem se fornecida
        if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = $this->uploadService->uploadImagem($_FILES['imagem']);
            if($resultado_upload['success']) {
                // Remover imagem antiga se existir
                if($anuncio_existente['imagem_url']) {
                    $this->uploadService->excluirArquivo($anuncio_existente['imagem_url']);
                }
                $dados_atualizacao['imagem_url'] = $resultado_upload['url'];
            } else {
                jsonResponse(['erro' => $resultado_upload['erro']], 400);
                return;
            }
        }
        
        if($this->anuncio->atualizar($id, $dados_atualizacao)) {
            jsonResponse(['success' => 'Anúncio atualizado com sucesso']);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar anúncio'], 500);
        }
    }

    /**
     * Deletar anúncio
     */
    public function deletarAnuncio($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        // Obter dados do anúncio para remover imagem
        $anuncio = $this->anuncio->obterPorId($id);
        
        if(!$anuncio) {
            jsonResponse(['erro' => 'Anúncio não encontrado'], 404);
            return;
        }
        
        if($this->anuncio->deletar($id)) {
            // Remover imagem se existir
            if($anuncio['imagem_url']) {
                $this->uploadService->excluirArquivo($anuncio['imagem_url']);
            }
            
            jsonResponse(['success' => 'Anúncio removido com sucesso']);
        } else {
            jsonResponse(['erro' => 'Erro ao remover anúncio'], 500);
        }
    }

    /**
     * Registrar clique em anúncio
     */
    public function registrarClique() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || empty($dados['anuncio_id'])) {
            jsonResponse(['erro' => 'ID do anúncio não fornecido'], 400);
            return;
        }
        
        $anuncio_id = (int)$dados['anuncio_id'];
        $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if($this->anuncio->registrarClique($anuncio_id, $ip_usuario, $user_agent)) {
            jsonResponse(['success' => 'Clique registrado']);
        } else {
            jsonResponse(['erro' => 'Erro ao registrar clique'], 500);
        }
    }

    /**
     * Obter estatísticas de anúncios
     */
    public function obterEstatisticas() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $periodo = $_GET['periodo'] ?? 'mes';
        $anuncio_id = $_GET['anuncio_id'] ?? null;
        
        $estatisticas = $this->anuncio->obterEstatisticas($periodo, $anuncio_id);
        
        jsonResponse($estatisticas);
    }

    /**
     * Verificar permissão de administrador
     */
    private function verificarPermissaoAdmin() {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário'], 401);
            return false;
        }
        
        if(!in_array($_SESSION['usuario_tipo'], ['admin', 'editor'])) {
            jsonResponse(['erro' => 'Permissão de administrador necessária'], 403);
            return false;
        }
        
        return true;
    }
}

// Processar requisição se chamado diretamente
if(basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new AnuncioController();
    $controller->processarRequisicao();
}
?>