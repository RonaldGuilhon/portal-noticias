<?php
/**
 * Controlador de Comentários
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';

class ComentarioController {
    private $db;
    private $comentario;
    private $usuario;
    private $noticia;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->comentario = new Comentario($this->db);
        $this->usuario = new Usuario($this->db);
        $this->noticia = new Noticia($this->db);
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
            logError('Erro no ComentarioController: ' . $e->getMessage());
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
            case 'by-news':
                $this->obterPorNoticia();
                break;
            case 'pending':
                $this->pendentes();
                break;
            case 'stats':
                $this->estatisticas();
                break;
            case 'export':
                $this->exportar();
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
            case 'like':
                $this->curtir();
                break;
            case 'dislike':
                $this->descurtir();
                break;
            case 'bulk-approve':
                $this->aprovarEmLote();
                break;
            case 'bulk-reject':
                $this->rejeitarEmLote();
                break;
            case 'bulk-spam':
                $this->marcarSpamEmLote();
                break;
            case 'bulk-delete':
                $this->excluirEmLote();
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
            case 'approve':
                $this->aprovar();
                break;
            case 'reject':
                $this->rejeitar();
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
     * Listar comentários
     */
    public function listar() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $noticia_id = $_GET['noticia_id'] ?? null;
        $usuario_id = $_GET['usuario_id'] ?? null;
        $data_inicio = $_GET['data_inicio'] ?? null;
        $data_fim = $_GET['data_fim'] ?? null;
        $order = $_GET['order'] ?? 'criado_em';
        $direction = $_GET['direction'] ?? 'DESC';
        
        $filtros = [
            'search' => $search,
            'status' => $status,
            'noticia_id' => $noticia_id,
            'usuario_id' => $usuario_id,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'order' => $order,
            'direction' => $direction
        ];
        
        $comentarios = $this->comentario->listar($page, $limit, $filtros);
        $total = $this->comentario->contar($filtros);
        
        jsonResponse([
            'comentarios' => $comentarios,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Obter comentário por ID
     */
    public function obter() {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        $comentario = $this->comentario->obterPorId($id);
        
        if(!$comentario) {
            jsonResponse(['erro' => 'Comentário não encontrado'], 404);
            return;
        }
        
        jsonResponse(['comentario' => $comentario]);
    }
    
    /**
     * Obter comentários por notícia
     */
    public function obterPorNoticia() {
        $noticia_id = $_GET['noticia_id'] ?? $_GET['id'] ?? null;
        $status = $_GET['status'] ?? 'aprovado';
        $limit = (int)($_GET['limit'] ?? 50);
        
        if(!$noticia_id) {
            jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            return;
        }
        
        // Verificar se notícia existe
        $noticia = $this->noticia->obterPorId($noticia_id);
        if(!$noticia) {
            jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            return;
        }
        
        $comentarios = $this->comentario->obterPorNoticia($noticia_id, $status, $limit);
        
        jsonResponse([
            'comentarios' => $comentarios,
            'total' => count($comentarios)
        ]);
    }
    
    /**
     * Criar novo comentário
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        // Verificar se notícia existe
        if(empty($dados['noticia_id'])) {
            jsonResponse(['erro' => 'ID da notícia é obrigatório'], 400);
            return;
        }
        
        $noticia = $this->noticia->obterPorId($dados['noticia_id']);
        if(!$noticia) {
            jsonResponse(['erro' => 'Notícia não encontrada'], 404);
            return;
        }
        
        // Verificar se comentários estão habilitados para a notícia
        if(!$noticia['comentarios_habilitados']) {
            jsonResponse(['erro' => 'Comentários desabilitados para esta notícia'], 403);
            return;
        }
        
        // Preparar dados do comentário
        $dados_comentario = [
            'noticia_id' => (int)$dados['noticia_id'],
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'comentario_pai_id' => !empty($dados['comentario_pai_id']) ? (int)$dados['comentario_pai_id'] : null,
            'conteudo' => sanitizeInput($dados['conteudo']),
            'autor_nome' => null,
            'autor_email' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Se usuário não está logado, pegar dados do autor
        if(!$dados_comentario['usuario_id']) {
            $dados_comentario['autor_nome'] = sanitizeInput($dados['autor_nome'] ?? '');
            $dados_comentario['autor_email'] = sanitizeInput($dados['autor_email'] ?? '');
        }
        
        // Validar dados
        $validacao = $this->comentario->validarDados($dados_comentario);
        if(!$validacao['valido']) {
            jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
            return;
        }
        
        // Verificar se é resposta a um comentário existente
        if($dados_comentario['comentario_pai_id']) {
            $comentario_pai = $this->comentario->obterPorId($dados_comentario['comentario_pai_id']);
            if(!$comentario_pai || $comentario_pai['noticia_id'] !== $dados_comentario['noticia_id']) {
                jsonResponse(['erro' => 'Comentário pai não encontrado ou inválido'], 400);
                return;
            }
        }
        
        // Verificar rate limiting (máximo 5 comentários por hora por IP)
        if(!$this->verificarRateLimit()) {
            jsonResponse(['erro' => 'Muitos comentários em pouco tempo. Tente novamente mais tarde.'], 429);
            return;
        }
        
        $comentario_id = $this->comentario->criar($dados_comentario);
        
        if($comentario_id) {
            $comentario = $this->comentario->obterPorId($comentario_id);
            jsonResponse([
                'success' => true,
                'mensagem' => 'Comentário criado com success. Aguardando moderação.',
                'comentario' => $comentario
            ], 201);
        } else {
            jsonResponse(['erro' => 'Erro ao criar comentário'], 500);
        }
    }
    
    /**
     * Atualizar comentário
     */
    public function atualizar() {
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        // Verificar se comentário existe
        $comentario_existente = $this->comentario->obterPorId($id);
        if(!$comentario_existente) {
            jsonResponse(['erro' => 'Comentário não encontrado'], 404);
            return;
        }
        
        // Verificar permissões
        if(!$this->verificarPermissaoEdicao($comentario_existente)) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        // Preparar dados para atualização
        $dados_atualizacao = [];
        if(isset($dados['conteudo'])) {
            $dados_atualizacao['conteudo'] = sanitizeInput($dados['conteudo']);
        }
        
        // Validar dados
        if(!empty($dados_atualizacao['conteudo'])) {
            $validacao = $this->comentario->validarDados(array_merge($comentario_existente, $dados_atualizacao), $id);
            if(!$validacao['valido']) {
                jsonResponse(['erro' => 'Dados inválidos', 'detalhes' => $validacao['erros']], 400);
                return;
            }
        }
        
        $success = $this->comentario->atualizar($id, $dados_atualizacao);
            
            if($success) {
            $comentario = $this->comentario->obterPorId($id);
            jsonResponse([
                'success' => true,
                'mensagem' => 'Comentário atualizado com success',
                'comentario' => $comentario
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar comentário'], 500);
        }
    }
    
    /**
     * Excluir comentário
     */
    public function excluir() {
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        // Verificar se comentário existe
        $comentario = $this->comentario->obterPorId($id);
        if(!$comentario) {
            jsonResponse(['erro' => 'Comentário não encontrado'], 404);
            return;
        }
        
        // Verificar permissões
        if(!$this->verificarPermissaoExclusao($comentario)) {
            return;
        }
        
        $success = $this->comentario->excluir($id);
            
            if($success) {
            jsonResponse([
                'success' => true,
                'mensagem' => 'Comentário excluído com success'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao excluir comentário'], 500);
        }
    }
    
    /**
     * Aprovar comentário
     */
    public function aprovar() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        $success = $this->comentario->moderar($id, 'aprovado', $_SESSION['usuario_id']);
            
            if($success) {
            jsonResponse([
                'success' => true,
                'mensagem' => 'Comentário aprovado com success'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao aprovar comentário'], 500);
        }
    }
    
    /**
     * Rejeitar comentário
     */
    public function rejeitar() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $id = $_GET['id'] ?? null;
        if(!$id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        $success = $this->comentario->moderar($id, 'rejeitado', $_SESSION['usuario_id']);
            
            if($success) {
            jsonResponse([
                'success' => true,
                'mensagem' => 'Comentário rejeitado com success'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao rejeitar comentário'], 500);
        }
    }
    
    /**
     * Curtir comentário
     */
    public function curtir() {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário para curtir comentários'], 401);
            return;
        }
        
        $comentario_id = $_GET['id'] ?? null;
        if(!$comentario_id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        $success = $this->comentario->curtir($comentario_id, $_SESSION['usuario_id'], 'like');
            
            if($success) {
                jsonResponse([
                    'success' => true,
                'mensagem' => 'Comentário curtido'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao curtir comentário'], 500);
        }
    }
    
    /**
     * Descurtir comentário
     */
    public function descurtir() {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário para descurtir comentários'], 401);
            return;
        }
        
        $comentario_id = $_GET['id'] ?? null;
        if(!$comentario_id) {
            jsonResponse(['erro' => 'ID do comentário é obrigatório'], 400);
            return;
        }
        
        $success = $this->comentario->curtir($comentario_id, $_SESSION['usuario_id'], 'dislike');
            
            if($success) {
                jsonResponse([
                    'success' => true,
                'mensagem' => 'Comentário descurtido'
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao descurtir comentário'], 500);
        }
    }
    
    /**
     * Listar comentários pendentes
     */
    public function pendentes() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        
        $filtros = ['status' => 'pendente'];
        
        $comentarios = $this->comentario->listar($page, $limit, $filtros);
        $total = $this->comentario->contar($filtros);
        
        jsonResponse([
            'comentarios' => $comentarios,
            'paginacao' => [
                'pagina_atual' => $page,
                'total_itens' => $total,
                'itens_por_pagina' => $limit,
                'total_paginas' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Obter estatísticas de comentários
     */
    public function estatisticas() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $stats = $this->comentario->obterEstatisticas();
        
        jsonResponse(['estatisticas' => $stats]);
    }
    
    /**
     * Aprovar comentários em lote
     */
    public function aprovarEmLote() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || !isset($dados['ids']) || !is_array($dados['ids'])) {
            jsonResponse(['erro' => 'IDs dos comentários são obrigatórios'], 400);
            return;
        }
        
        $ids = array_filter($dados['ids'], 'is_numeric');
        
        if(empty($ids)) {
            jsonResponse(['erro' => 'Nenhum ID válido fornecido'], 400);
            return;
        }
        
        $successes = 0;
        $erros = 0;
        
        foreach($ids as $id) {
            if($this->comentario->moderar($id, 'aprovado', $_SESSION['usuario_id'])) {
                $successes++;
            } else {
                $erros++;
            }
        }
        
        jsonResponse([
            'success' => true,
            'mensagem' => "$successes comentário(s) aprovado(s) com success",
            'detalhes' => [
                'aprovados' => $successes,
                'erros' => $erros,
                'total' => count($ids)
            ]
        ]);
    }
    
    /**
     * Rejeitar comentários em lote
     */
    public function rejeitarEmLote() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || !isset($dados['ids']) || !is_array($dados['ids'])) {
            jsonResponse(['erro' => 'IDs dos comentários são obrigatórios'], 400);
            return;
        }
        
        $ids = array_filter($dados['ids'], 'is_numeric');
        
        if(empty($ids)) {
            jsonResponse(['erro' => 'Nenhum ID válido fornecido'], 400);
            return;
        }
        
        $successes = 0;
            $erros = 0;
            
            foreach($ids as $id) {
                if($this->comentario->moderar($id, 'rejeitado', $_SESSION['usuario_id'])) {
                    $successes++;
            } else {
                $erros++;
            }
        }
        
        jsonResponse([
            'success' => true,
            'mensagem' => "$successes comentário(s) rejeitado(s) com success",
            'detalhes' => [
                'rejeitados' => $successes,
                'erros' => $erros,
                'total' => count($ids)
            ]
        ]);
    }
    
    /**
     * Marcar comentários como spam em lote
     */
    public function marcarSpamEmLote() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || !isset($dados['ids']) || !is_array($dados['ids'])) {
            jsonResponse(['erro' => 'IDs dos comentários são obrigatórios'], 400);
            return;
        }
        
        $ids = array_filter($dados['ids'], 'is_numeric');
        
        if(empty($ids)) {
            jsonResponse(['erro' => 'Nenhum ID válido fornecido'], 400);
            return;
        }
        
        $success = 0;
        $erros = 0;
        
        foreach($ids as $id) {
            if($this->comentario->moderar($id, 'spam', $_SESSION['usuario_id'])) {
                $success++;
            } else {
                $erros++;
            }
        }
        
        jsonResponse([
            'success' => true,
            'mensagem' => "$success comentário(s) marcado(s) como spam",
            'detalhes' => [
                'spam' => $success,
                'erros' => $erros,
                'total' => count($ids)
            ]
        ]);
    }
    
    /**
     * Excluir comentários em lote
     */
    public function excluirEmLote() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || !isset($dados['ids']) || !is_array($dados['ids'])) {
            jsonResponse(['erro' => 'IDs dos comentários são obrigatórios'], 400);
            return;
        }
        
        $ids = array_filter($dados['ids'], 'is_numeric');
        
        if(empty($ids)) {
            jsonResponse(['erro' => 'Nenhum ID válido fornecido'], 400);
            return;
        }
        
        $successes = 0;
        $erros = 0;
        
        foreach($ids as $id) {
            if($this->comentario->excluir($id)) {
                $successes++;
            } else {
                $erros++;
            }
        }
        
        jsonResponse([
            'success' => true,
            'mensagem' => "$successes comentário(s) excluído(s) com success",
            'detalhes' => [
                'excluidos' => $successes,
                'erros' => $erros,
                'total' => count($ids)
            ]
        ]);
    }
    
    /**
     * Verificar rate limit para comentários
     */
    private function verificarRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $cache_key = "comment_rate_limit_$ip";
        
        // Implementação simples usando sessão (em produção, usar Redis ou Memcached)
        if(!isset($_SESSION['comment_timestamps'])) {
            $_SESSION['comment_timestamps'] = [];
        }
        
        $agora = time();
        $uma_hora_atras = $agora - 3600;
        
        // Remover timestamps antigos
        $_SESSION['comment_timestamps'] = array_filter(
            $_SESSION['comment_timestamps'], 
            function($timestamp) use ($uma_hora_atras) {
                return $timestamp > $uma_hora_atras;
            }
        );
        
        // Verificar limite
        if(count($_SESSION['comment_timestamps']) >= 5) {
            return false;
        }
        
        // Adicionar timestamp atual
        $_SESSION['comment_timestamps'][] = $agora;
        
        return true;
    }
    
    /**
     * Verificar permissão de edição
     */
    private function verificarPermissaoEdicao($comentario) {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário'], 401);
            return false;
        }
        
        $usuario = $this->usuario->obterPorId($_SESSION['usuario_id']);
        
        // Admin ou moderador pode editar qualquer comentário
        if($usuario && in_array($usuario['tipo'], ['admin', 'moderador'])) {
            return true;
        }
        
        // Usuário pode editar apenas seus próprios comentários
        if($comentario['usuario_id'] && $comentario['usuario_id'] == $_SESSION['usuario_id']) {
            // Verificar se comentário não foi moderado ainda
            if($comentario['status'] === 'pendente') {
                return true;
            }
        }
        
        jsonResponse(['erro' => 'Sem permissão para editar este comentário'], 403);
        return false;
    }
    
    /**
     * Verificar permissão de exclusão
     */
    private function verificarPermissaoExclusao($comentario) {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário'], 401);
            return false;
        }
        
        $usuario = $this->usuario->obterPorId($_SESSION['usuario_id']);
        
        // Admin ou moderador pode excluir qualquer comentário
        if($usuario && in_array($usuario['tipo'], ['admin', 'moderador'])) {
            return true;
        }
        
        // Usuário pode excluir apenas seus próprios comentários
        if($comentario['usuario_id'] && $comentario['usuario_id'] == $_SESSION['usuario_id']) {
            return true;
        }
        
        jsonResponse(['erro' => 'Sem permissão para excluir este comentário'], 403);
        return false;
    }
    
    /**
     * Exportar comentários
     */
    public function exportar() {
        if(!$this->verificarPermissaoModerador()) {
            return;
        }
        
        $formato = $_GET['format'] ?? 'csv';
        $filtros = [];
        
        // Aplicar filtros da query string
        if(!empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        
        if(!empty($_GET['noticia_id'])) {
            $filtros['noticia_id'] = $_GET['noticia_id'];
        }
        
        if(!empty($_GET['search'])) {
            $filtros['search'] = $_GET['search'];
        }
        
        if(!empty($_GET['data_inicio'])) {
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }
        
        if(!empty($_GET['data_fim'])) {
            $filtros['data_fim'] = $_GET['data_fim'];
        }
        
        try {
            if($formato === 'json') {
                $comentarios = $this->comentario->exportarParaJSON($filtros);
                
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="comentarios_' . date('Y-m-d_H-i-s') . '.json"');
                
                echo json_encode([
                    'exportado_em' => date('Y-m-d H:i:s'),
                    'total_comentarios' => count($comentarios),
                    'filtros_aplicados' => $filtros,
                    'comentarios' => $comentarios
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                // Formato CSV (padrão)
                $comentarios = $this->comentario->exportarParaCSV($filtros);
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="comentarios_' . date('Y-m-d_H-i-s') . '.csv"');
                
                // BOM para UTF-8
                echo "\xEF\xBB\xBF";
                
                // Cabeçalhos CSV
                $cabecalhos = [
                    'ID',
                    'Notícia ID',
                    'Título da Notícia',
                    'Autor',
                    'Email',
                    'Conteúdo',
                    'Tipo',
                    'Status',
                    'Likes',
                    'Dislikes',
                    'IP Address',
                    'Data Criação',
                    'Data Atualização',
                    'Data Moderação'
                ];
                
                echo implode(',', array_map(function($header) {
                    return '"' . str_replace('"', '""', $header) . '"';
                }, $cabecalhos)) . "\n";
                
                // Dados dos comentários
                foreach($comentarios as $comentario) {
                    $linha = [
                        $comentario['id'],
                        $comentario['noticia_id'],
                        $comentario['noticia_titulo'] ?? '',
                        $comentario['autor_nome'] ?? '',
                        $comentario['autor_email'] ?? '',
                        $comentario['conteudo'],
                        $comentario['tipo'],
                        $comentario['status'],
                        $comentario['likes'],
                        $comentario['dislikes'],
                        $comentario['ip_address'] ?? '',
                        $comentario['criado_em'],
                        $comentario['atualizado_em'] ?? '',
                        $comentario['moderado_em'] ?? ''
                    ];
                    
                    echo implode(',', array_map(function($campo) {
                        return '"' . str_replace('"', '""', $campo) . '"';
                    }, $linha)) . "\n";
                }
            }
        } catch(Exception $e) {
            logError('Erro ao exportar comentários: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro ao exportar comentários'], 500);
        }
    }
    
    /**
     * Verificar permissão de moderador
     */
    private function verificarPermissaoModerador() {
        if(!isset($_SESSION['usuario_id'])) {
            jsonResponse(['erro' => 'Login necessário'], 401);
            return false;
        }
        
        $usuario = $this->usuario->obterPorId($_SESSION['usuario_id']);
        
        if(!$usuario || !in_array($usuario['tipo'], ['admin', 'moderador'])) {
            jsonResponse(['erro' => 'Permissão de moderador necessária'], 403);
            return false;
        }
        
        return true;
    }
}
?>