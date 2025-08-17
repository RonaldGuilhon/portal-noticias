<?php
/**
 * Controlador de Tags
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../models/Usuario.php';

class TagController {
    private $db;
    private $tag;
    private $usuario;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->tag = new Tag($this->db);
        $this->usuario = new Usuario($this->db);
    }
    
    /**
     * Processar requisições
     */
    public function processarRequisicao() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';
        
        try {
            switch($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'POST':
                    $this->handlePost($action);
                    break;
                case 'PUT':
                    $this->handlePut($action);
                    break;
                case 'DELETE':
                    $this->handleDelete($action);
                    break;
                default:
                    jsonResponse(['erro' => 'Método não permitido'], 405);
            }
        } catch(Exception $e) {
            logError('Erro no TagController: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }
    
    /**
     * Lidar com requisições GET
     */
    private function handleGet($action) {
        switch($action) {
            case 'list':
                $this->listar();
                break;
            case 'get':
                $this->obter();
                break;
            case 'search':
                $this->buscar();
                break;
            case 'popular':
                $this->maisUsadas();
                break;
            case 'cloud':
                $this->nuvemTags();
                break;
            case 'related':
                $this->relacionadas();
                break;
            case 'stats':
                $this->estatisticas();
                break;
            default:
                jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Lidar com requisições POST
     */
    private function handlePost($action) {
        switch($action) {
            case 'create':
                $this->criar();
                break;
            case 'batch':
                $this->criarLote();
                break;
            default:
                jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Lidar com requisições PUT
     */
    private function handlePut($action) {
        switch($action) {
            case 'update':
                $this->atualizar();
                break;
            case 'status':
                $this->alterarStatus();
                break;
            default:
                jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Lidar com requisições DELETE
     */
    private function handleDelete($action) {
        switch($action) {
            case 'delete':
                $this->excluir();
                break;
            default:
                jsonResponse(['erro' => 'Ação não encontrada'], 404);
        }
    }
    
    /**
     * Listar tags
     */
    public function listar() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $order = $_GET['order'] ?? 'nome';
        $direction = $_GET['direction'] ?? 'ASC';
        
        $filtros = [
            'search' => $search,
            'status' => $status,
            'order' => $order,
            'direction' => $direction
        ];
        
        $tags = $this->tag->listar($page, $limit, $filtros);
        $total = $this->tag->contar($filtros);
        
        jsonResponse([
            'tags' => $tags,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Obter tag por ID ou slug
     */
    public function obter() {
        $id = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;
        
        if(!$id && !$slug) {
            jsonResponse(['erro' => 'ID ou slug da tag é obrigatório'], 400);
            return;
        }
        
        if($id) {
            $tag = $this->tag->obterPorId($id);
        } else {
            $tag = $this->tag->obterPorSlug($slug);
        }
        
        if(!$tag) {
            jsonResponse(['erro' => 'Tag não encontrada'], 404);
            return;
        }
        
        jsonResponse(['tag' => $tag]);
    }
    
    /**
     * Buscar tags por termo
     */
    public function buscar() {
        $termo = $_GET['q'] ?? $_GET['termo'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if(empty($termo)) {
            jsonResponse(['erro' => 'Termo de busca é obrigatório'], 400);
            return;
        }
        
        $tags = $this->tag->buscarPorTermo($termo, $limit);
        
        jsonResponse(['tags' => $tags]);
    }
    
    /**
     * Criar nova tag
     */
    public function criar() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        // Validar dados obrigatórios
        if(empty($dados['nome'])) {
            jsonResponse(['erro' => 'Nome da tag é obrigatório'], 400);
            return;
        }
        
        // Sanitizar dados
        $dados_limpos = [
            'nome' => sanitizeInput($dados['nome']),
            'descricao' => sanitizeInput($dados['descricao'] ?? ''),
            'cor' => sanitizeInput($dados['cor'] ?? '#6c757d'),
            'ativo' => isset($dados['ativo']) ? (bool)$dados['ativo'] : true
        ];
        
        // Validar dados
        $validacao = $this->tag->validarDados($dados_limpos);
        if(!$validacao['valido']) {
            jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
            return;
        }
        
        $tag_id = $this->tag->criar($dados_limpos);
        
        if($tag_id) {
            $tag = $this->tag->obterPorId($tag_id);
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Tag criada com sucesso',
                'tag' => $tag
            ], 201);
        } else {
            jsonResponse(['erro' => 'Erro ao criar tag'], 500);
        }
    }
    
    /**
     * Criar tags em lote
     */
    public function criarLote() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($dados['tags']) || !is_array($dados['tags'])) {
            jsonResponse(['erro' => 'Lista de tags é obrigatória'], 400);
            return;
        }
        
        $tags_criadas = [];
        $erros = [];
        
        foreach($dados['tags'] as $tag_data) {
            if(empty($tag_data['nome'])) {
                $erros[] = 'Nome da tag é obrigatório';
                continue;
            }
            
            $dados_limpos = [
                'nome' => sanitizeInput($tag_data['nome']),
                'descricao' => sanitizeInput($tag_data['descricao'] ?? ''),
                'cor' => sanitizeInput($tag_data['cor'] ?? '#6c757d'),
                'ativo' => isset($tag_data['ativo']) ? (bool)$tag_data['ativo'] : true
            ];
            
            $tag_id = $this->tag->criarOuObter($dados_limpos['nome']);
            
            if($tag_id) {
                $tags_criadas[] = $this->tag->obterPorId($tag_id);
            } else {
                $erros[] = "Erro ao criar tag: {$dados_limpos['nome']}";
            }
        }
        
        jsonResponse([
            'sucesso' => true,
            'tags_criadas' => count($tags_criadas),
            'tags' => $tags_criadas,
            'erros' => $erros
        ]);
    }
    
    /**
     * Atualizar tag
     */
    public function atualizar() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da tag é obrigatório'], 400);
            return;
        }
        
        // Verificar se tag existe
        $tag_existente = $this->tag->obterPorId($id);
        if(!$tag_existente) {
            jsonResponse(['erro' => 'Tag não encontrada'], 404);
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        // Sanitizar dados
        $dados_limpos = [];
        if(isset($dados['nome'])) $dados_limpos['nome'] = sanitizeInput($dados['nome']);
        if(isset($dados['descricao'])) $dados_limpos['descricao'] = sanitizeInput($dados['descricao']);
        if(isset($dados['cor'])) $dados_limpos['cor'] = sanitizeInput($dados['cor']);
        if(isset($dados['ativo'])) $dados_limpos['ativo'] = (bool)$dados['ativo'];
        
        // Validar dados
        $validacao = $this->tag->validarDados($dados_limpos, $id);
        if(!$validacao['valido']) {
            jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
            return;
        }
        
        $sucesso = $this->tag->atualizar($id, $dados_limpos);
        
        if($sucesso) {
            $tag = $this->tag->obterPorId($id);
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Tag atualizada com sucesso',
                'tag' => $tag
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar tag'], 500);
        }
    }
    
    /**
     * Excluir tag
     */
    public function excluir() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da tag é obrigatório'], 400);
            return;
        }
        
        // Verificar se tag existe
        $tag = $this->tag->obterPorId($id);
        if(!$tag) {
            jsonResponse(['erro' => 'Tag não encontrada'], 404);
            return;
        }
        
        $sucesso = $this->tag->excluir($id);
        
        if($sucesso) {
            jsonResponse([
                'sucesso' => true,
                'mensagem' => 'Tag excluída com sucesso'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao excluir tag'], 500);
        }
    }
    
    /**
     * Alterar status da tag
     */
    public function alterarStatus() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da tag é obrigatório'], 400);
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        $ativo = isset($dados['ativo']) ? (bool)$dados['ativo'] : null;
        
        if($ativo === null) {
            jsonResponse(['erro' => 'Status é obrigatório'], 400);
            return;
        }
        
        $sucesso = $this->tag->alterarStatus($id, $ativo);
        
        if($sucesso) {
            $status_texto = $ativo ? 'ativada' : 'desativada';
            jsonResponse([
                'sucesso' => true,
                'mensagem' => "Tag $status_texto com sucesso"
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao alterar status da tag'], 500);
        }
    }
    
    /**
     * Obter tags mais usadas
     */
    public function maisUsadas() {
        $limit = (int)($_GET['limit'] ?? 20);
        
        $tags = $this->tag->obterMaisUsadas($limit);
        
        jsonResponse(['tags' => $tags]);
    }
    
    /**
     * Gerar nuvem de tags
     */
    public function nuvemTags() {
        $limit = (int)($_GET['limit'] ?? 50);
        $min_uso = (int)($_GET['min_uso'] ?? 1);
        
        $nuvem = $this->tag->gerarNuvemTags($limit, $min_uso);
        
        jsonResponse(['nuvem_tags' => $nuvem]);
    }
    
    /**
     * Obter tags relacionadas
     */
    public function relacionadas() {
        $tag_id = $_GET['tag_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 10);
        
        if(!$tag_id) {
            jsonResponse(['erro' => 'ID da tag é obrigatório'], 400);
            return;
        }
        
        $tags = $this->tag->obterRelacionadas($tag_id, $limit);
        
        jsonResponse(['tags_relacionadas' => $tags]);
    }
    
    /**
     * Obter estatísticas das tags
     */
    public function estatisticas() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $stats = $this->tag->obterEstatisticas();
        
        jsonResponse(['estatisticas' => $stats]);
    }
    
    /**
     * Verificar se usuário tem permissão de administrador
     */
    private function verificarPermissaoAdmin() {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Acesso negado. Faça login primeiro.'], 401);
            return false;
        }
        
        $usuario = $this->usuario->obterPorId($_SESSION['usuario_id']);
        
        if(!$usuario || $usuario['tipo'] !== 'admin') {
            jsonResponse(['erro' => 'Acesso negado. Permissão de administrador necessária.'], 403);
            return false;
        }
        
        return true;
    }
}
?>