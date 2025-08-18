<?php
/**
 * Controlador de Categorias
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Usuario.php';

class CategoriaController {
    private $db;
    private $categoria;
    private $usuario;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->categoria = new Categoria($this->db);
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
            logError('Erro no CategoriaController: ' . $e->getMessage());
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
            case 'stats':
                $this->estatisticas();
                break;
            case 'popular':
                $this->maisUsadas();
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
            case 'reorder':
                $this->reordenar();
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
     * Listar categorias
     */
    public function listar() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
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
        
        $categorias = $this->categoria->listar($filtros);
        $total = $this->categoria->contar($filtros);
        
        jsonResponse([
            'categorias' => $categorias,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Obter categoria por ID ou slug
     */
    public function obter() {
        $id = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;
        
        if(!$id && !$slug) {
            jsonResponse(['erro' => 'ID ou slug da categoria é obrigatório'], 400);
            return;
        }
        
        if($id) {
            $categoria = $this->categoria->buscarPorId($id);
        } else {
            $categoria = $this->categoria->buscarPorSlug($slug);
        }
        
        if(!$categoria) {
            jsonResponse(['erro' => 'Categoria não encontrada'], 404);
            return;
        }
        
        jsonResponse(['categoria' => $categoria]);
    }
    
    /**
     * Criar nova categoria
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
        $campos_obrigatorios = ['nome'];
        foreach($campos_obrigatorios as $campo) {
            if(empty($dados[$campo])) {
                jsonResponse(['erro' => "Campo '$campo' é obrigatório"], 400);
                return;
            }
        }
        
        // Sanitizar dados
        $dados_limpos = [
            'nome' => sanitizeInput($dados['nome']),
            'descricao' => sanitizeInput($dados['descricao'] ?? ''),
            'cor' => sanitizeInput($dados['cor'] ?? '#007bff'),
            'icone' => sanitizeInput($dados['icone'] ?? ''),
            'meta_title' => sanitizeInput($dados['meta_title'] ?? ''),
            'meta_description' => sanitizeInput($dados['meta_description'] ?? ''),
            'ordem' => (int)($dados['ordem'] ?? 0),
            'ativo' => isset($dados['ativo']) ? (bool)$dados['ativo'] : true
        ];
        
        // Validar dados
        $validacao = $this->categoria->validarDados($dados_limpos);
        if(!$validacao['valido']) {
            jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
            return;
        }
        
        $categoria_id = $this->categoria->criar($dados_limpos);
        
        if($categoria_id) {
            $categoria = $this->categoria->buscarPorId($categoria_id);
            jsonResponse([
                'success' => true,
                'mensagem' => 'Categoria criada com success',
                'categoria' => $categoria
            ], 201);
        } else {
            jsonResponse(['erro' => 'Erro ao criar categoria'], 500);
        }
    }
    
    /**
     * Atualizar categoria
     */
    public function atualizar() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
            return;
        }
        
        // Verificar se categoria existe
        $categoria_existente = $this->categoria->buscarPorId($id);
        if(!$categoria_existente) {
            jsonResponse(['erro' => 'Categoria não encontrada'], 404);
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
        if(isset($dados['icone'])) $dados_limpos['icone'] = sanitizeInput($dados['icone']);
        if(isset($dados['meta_title'])) $dados_limpos['meta_title'] = sanitizeInput($dados['meta_title']);
        if(isset($dados['meta_description'])) $dados_limpos['meta_description'] = sanitizeInput($dados['meta_description']);
        if(isset($dados['ordem'])) $dados_limpos['ordem'] = (int)$dados['ordem'];
        if(isset($dados['ativo'])) $dados_limpos['ativo'] = (bool)$dados['ativo'];
        
        // Validar dados
        $validacao = $this->categoria->validarDados($dados_limpos, $id);
        if(!$validacao['valido']) {
            jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
            return;
        }
        
        $success = $this->categoria->atualizar($id, $dados_limpos);
            
            if($success) {
            $categoria = $this->categoria->buscarPorId($id);
            jsonResponse([
                'success' => true,
                'mensagem' => 'Categoria atualizada com success',
                'categoria' => $categoria
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar categoria'], 500);
        }
    }
    
    /**
     * Excluir categoria
     */
    public function excluir() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
            return;
        }
        
        // Verificar se categoria existe
        $categoria = $this->categoria->buscarPorId($id);
        if(!$categoria) {
            jsonResponse(['erro' => 'Categoria não encontrada'], 404);
            return;
        }
        
        $success = $this->categoria->excluir($id);
            
            if($success) {
            jsonResponse([
                'success' => true,
                'mensagem' => 'Categoria excluída com success'
            ]);
        } else {
            jsonResponse(['erro' => 'Não é possível excluir categoria com notícias associadas'], 400);
        }
    }
    
    /**
     * Alterar status da categoria
     */
    public function alterarStatus() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID da categoria é obrigatório'], 400);
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        $ativo = isset($dados['ativo']) ? (bool)$dados['ativo'] : null;
        
        if($ativo === null) {
            jsonResponse(['erro' => 'Status é obrigatório'], 400);
            return;
        }
        
        $success = $this->categoria->alterarStatus($id, $ativo);
        
        if($success) {
            $status_texto = $ativo ? 'ativada' : 'desativada';
            jsonResponse([
                'success' => true,
                'mensagem' => "Categoria $status_texto com success"
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao alterar status da categoria'], 500);
        }
    }
    
    /**
     * Reordenar categorias
     */
    public function reordenar() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($dados['categorias']) || !is_array($dados['categorias'])) {
            jsonResponse(['erro' => 'Lista de categorias é obrigatória'], 400);
            return;
        }
        
        $success = $this->categoria->reordenar($dados['categorias']);
        
        if($success) {
            jsonResponse([
                'success' => true,
                'mensagem' => 'Categorias reordenadas com success'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao reordenar categorias'], 500);
        }
    }
    
    /**
     * Obter categorias mais usadas
     */
    public function maisUsadas() {
        $limit = (int)($_GET['limit'] ?? 10);
        
        $categorias = $this->categoria->obterMaisUtilizadas($limit);
        
        jsonResponse(['categorias' => $categorias]);
    }
    
    /**
     * Obter estatísticas das categorias
     */
    public function estatisticas() {
        // Verificar autenticação e permissão de admin
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $stats = $this->categoria->obterEstatisticas();
        
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

// Processar requisição se chamado diretamente
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $controller = new CategoriaController();
    $controller->processarRequisicao();
}
?>