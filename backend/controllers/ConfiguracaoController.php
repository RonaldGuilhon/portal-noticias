<?php
/**
 * Controlador de Configurações do Sistema
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Configuracao.php';

class ConfiguracaoController {
    private $db;
    private $configuracao;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->configuracao = new Configuracao($this->db);
        
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
                        if($segmentos[2] === 'publicas') {
                            $this->obterConfiguracaoPublicas();
                        } elseif($segmentos[2] === 'sistema') {
                            $this->obterConfiguracoesSistema();
                        } else {
                            $this->obterConfiguracao($segmentos[2]);
                        }
                    } else {
                        $this->listarConfiguracoes();
                    }
                    break;
                    
                case 'POST':
                    $this->criarConfiguracao();
                    break;
                    
                case 'PUT':
                    if(isset($segmentos[2]) && $segmentos[2] === 'lote') {
                        $this->atualizarConfiguracaoLote();
                    } else {
                        $this->atualizarConfiguracao($segmentos[2]);
                    }
                    break;
                    
                case 'DELETE':
                    $this->deletarConfiguracao($segmentos[2]);
                    break;
                    
                default:
                    jsonResponse(['erro' => 'Método não permitido'], 405);
            }
        } catch(Exception $e) {
            logError('Erro no ConfiguracaoController: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Listar todas as configurações (admin)
     */
    public function listarConfiguracoes() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $categoria = $_GET['categoria'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $configuracoes = $this->configuracao->listar($categoria, $search);
        
        jsonResponse(['configuracoes' => $configuracoes]);
    }

    /**
     * Obter configurações públicas (sem autenticação)
     */
    public function obterConfiguracaoPublicas() {
        $configuracoes = $this->configuracao->obterPublicas();
        
        jsonResponse(['configuracoes' => $configuracoes]);
    }

    /**
     * Obter configurações do sistema (admin)
     */
    public function obterConfiguracoesSistema() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $configuracoes = $this->configuracao->obterSistema();
        
        jsonResponse(['configuracoes' => $configuracoes]);
    }

    /**
     * Obter configuração específica
     */
    public function obterConfiguracao($chave) {
        $configuracao = $this->configuracao->obterPorChave($chave);
        
        if(!$configuracao) {
            jsonResponse(['erro' => 'Configuração não encontrada'], 404);
            return;
        }
        
        // Verificar se é pública ou se usuário tem permissão
        if(!$configuracao['publica'] && !$this->verificarPermissaoAdmin(false)) {
            jsonResponse(['erro' => 'Acesso negado'], 403);
            return;
        }
        
        jsonResponse($configuracao);
    }

    /**
     * Criar nova configuração
     */
    public function criarConfiguracao() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            $dados = $_POST;
        }
        
        // Validar dados obrigatórios
        if(empty($dados['chave']) || !isset($dados['valor'])) {
            jsonResponse(['erro' => 'Chave e valor são obrigatórios'], 400);
            return;
        }
        
        // Verificar se chave já existe
        if($this->configuracao->obterPorChave($dados['chave'])) {
            jsonResponse(['erro' => 'Chave já existe'], 400);
            return;
        }
        
        $dados_configuracao = [
            'chave' => sanitizeInput($dados['chave']),
            'valor' => $dados['valor'],
            'descricao' => sanitizeInput($dados['descricao'] ?? ''),
            'tipo' => $dados['tipo'] ?? 'string',
            'categoria' => sanitizeInput($dados['categoria'] ?? 'geral'),
            'publica' => isset($dados['publica']) ? (bool)$dados['publica'] : false,
            'editavel' => isset($dados['editavel']) ? (bool)$dados['editavel'] : true,
            'usuario_id' => $_SESSION['usuario_id']
        ];
        
        $id = $this->configuracao->criar($dados_configuracao);
        
        if($id) {
            jsonResponse([
                'sucesso' => 'Configuração criada com sucesso',
                'id' => $id
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao criar configuração'], 500);
        }
    }

    /**
     * Atualizar configuração
     */
    public function atualizarConfiguracao($chave) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados não fornecidos'], 400);
            return;
        }
        
        // Verificar se configuração existe
        $configuracao_existente = $this->configuracao->obterPorChave($chave);
        if(!$configuracao_existente) {
            jsonResponse(['erro' => 'Configuração não encontrada'], 404);
            return;
        }
        
        // Verificar se é editável
        if(!$configuracao_existente['editavel']) {
            jsonResponse(['erro' => 'Esta configuração não pode ser editada'], 403);
            return;
        }
        
        $dados_atualizacao = [];
        
        if(isset($dados['valor'])) {
            $dados_atualizacao['valor'] = $dados['valor'];
        }
        
        if(isset($dados['descricao'])) {
            $dados_atualizacao['descricao'] = sanitizeInput($dados['descricao']);
        }
        
        if(isset($dados['tipo'])) {
            $dados_atualizacao['tipo'] = $dados['tipo'];
        }
        
        if(isset($dados['categoria'])) {
            $dados_atualizacao['categoria'] = sanitizeInput($dados['categoria']);
        }
        
        if(isset($dados['publica'])) {
            $dados_atualizacao['publica'] = (bool)$dados['publica'];
        }
        
        if(isset($dados['editavel'])) {
            $dados_atualizacao['editavel'] = (bool)$dados['editavel'];
        }
        
        $dados_atualizacao['usuario_atualizacao_id'] = $_SESSION['usuario_id'];
        
        if($this->configuracao->atualizar($chave, $dados_atualizacao)) {
            jsonResponse(['sucesso' => 'Configuração atualizada com sucesso']);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar configuração'], 500);
        }
    }

    /**
     * Atualizar múltiplas configurações em lote
     */
    public function atualizarConfiguracaoLote() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || !isset($dados['configuracoes']) || !is_array($dados['configuracoes'])) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        $sucessos = 0;
        $erros = 0;
        $detalhes = [];
        
        foreach($dados['configuracoes'] as $config) {
            if(!isset($config['chave']) || !isset($config['valor'])) {
                $erros++;
                $detalhes[] = ['chave' => $config['chave'] ?? 'indefinida', 'erro' => 'Chave ou valor não fornecido'];
                continue;
            }
            
            $configuracao_existente = $this->configuracao->obterPorChave($config['chave']);
            if(!$configuracao_existente) {
                $erros++;
                $detalhes[] = ['chave' => $config['chave'], 'erro' => 'Configuração não encontrada'];
                continue;
            }
            
            if(!$configuracao_existente['editavel']) {
                $erros++;
                $detalhes[] = ['chave' => $config['chave'], 'erro' => 'Configuração não editável'];
                continue;
            }
            
            $dados_atualizacao = [
                'valor' => $config['valor'],
                'usuario_atualizacao_id' => $_SESSION['usuario_id']
            ];
            
            if($this->configuracao->atualizar($config['chave'], $dados_atualizacao)) {
                $sucessos++;
                $detalhes[] = ['chave' => $config['chave'], 'sucesso' => true];
            } else {
                $erros++;
                $detalhes[] = ['chave' => $config['chave'], 'erro' => 'Erro ao atualizar'];
            }
        }
        
        jsonResponse([
            'sucesso' => 'Processamento concluído',
            'sucessos' => $sucessos,
            'erros' => $erros,
            'detalhes' => $detalhes
        ]);
    }

    /**
     * Deletar configuração
     */
    public function deletarConfiguracao($chave) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        // Verificar se configuração existe
        $configuracao = $this->configuracao->obterPorChave($chave);
        if(!$configuracao) {
            jsonResponse(['erro' => 'Configuração não encontrada'], 404);
            return;
        }
        
        // Verificar se é editável (configurações não editáveis não podem ser deletadas)
        if(!$configuracao['editavel']) {
            jsonResponse(['erro' => 'Esta configuração não pode ser deletada'], 403);
            return;
        }
        
        if($this->configuracao->deletar($chave)) {
            jsonResponse(['sucesso' => 'Configuração removida com sucesso']);
        } else {
            jsonResponse(['erro' => 'Erro ao remover configuração'], 500);
        }
    }

    /**
     * Resetar configurações para valores padrão
     */
    public function resetarConfiguracoes() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $categoria = $_GET['categoria'] ?? '';
        
        if($this->configuracao->resetarPadrao($categoria)) {
            jsonResponse(['sucesso' => 'Configurações resetadas com sucesso']);
        } else {
            jsonResponse(['erro' => 'Erro ao resetar configurações'], 500);
        }
    }

    /**
     * Exportar configurações
     */
    public function exportarConfiguracoes() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $configuracoes = $this->configuracao->listar();
        
        $dados_exportacao = [
            'data_exportacao' => date('Y-m-d H:i:s'),
            'versao' => '1.0',
            'configuracoes' => $configuracoes
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="configuracoes_' . date('Y-m-d_H-i-s') . '.json"');
        
        echo json_encode($dados_exportacao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Importar configurações
     */
    public function importarConfiguracoes() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        if(!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['erro' => 'Arquivo não fornecido ou com erro'], 400);
            return;
        }
        
        $conteudo = file_get_contents($_FILES['arquivo']['tmp_name']);
        $dados = json_decode($conteudo, true);
        
        if(!$dados || !isset($dados['configuracoes'])) {
            jsonResponse(['erro' => 'Arquivo inválido'], 400);
            return;
        }
        
        $sucessos = 0;
        $erros = 0;
        $detalhes = [];
        
        foreach($dados['configuracoes'] as $config) {
            if(!isset($config['chave']) || !isset($config['valor'])) {
                $erros++;
                continue;
            }
            
            $configuracao_existente = $this->configuracao->obterPorChave($config['chave']);
            
            if($configuracao_existente) {
                // Atualizar existente
                $dados_atualizacao = [
                    'valor' => $config['valor'],
                    'descricao' => $config['descricao'] ?? $configuracao_existente['descricao'],
                    'usuario_atualizacao_id' => $_SESSION['usuario_id']
                ];
                
                if($this->configuracao->atualizar($config['chave'], $dados_atualizacao)) {
                    $sucessos++;
                    $detalhes[] = ['chave' => $config['chave'], 'acao' => 'atualizada'];
                } else {
                    $erros++;
                }
            } else {
                // Criar nova
                $dados_configuracao = [
                    'chave' => $config['chave'],
                    'valor' => $config['valor'],
                    'descricao' => $config['descricao'] ?? '',
                    'tipo' => $config['tipo'] ?? 'string',
                    'categoria' => $config['categoria'] ?? 'geral',
                    'publica' => $config['publica'] ?? false,
                    'editavel' => $config['editavel'] ?? true,
                    'usuario_id' => $_SESSION['usuario_id']
                ];
                
                if($this->configuracao->criar($dados_configuracao)) {
                    $sucessos++;
                    $detalhes[] = ['chave' => $config['chave'], 'acao' => 'criada'];
                } else {
                    $erros++;
                }
            }
        }
        
        jsonResponse([
            'sucesso' => 'Importação concluída',
            'sucessos' => $sucessos,
            'erros' => $erros,
            'detalhes' => $detalhes
        ]);
    }

    /**
     * Verificar permissão de administrador
     */
    private function verificarPermissaoAdmin($retornar_erro = true) {
        if(!isset($_SESSION['usuario_id'])) {
            if($retornar_erro) {
                jsonResponse(['erro' => 'Login necessário'], 401);
            }
            return false;
        }
        
        if($_SESSION['usuario_tipo'] !== 'admin') {
            if($retornar_erro) {
                jsonResponse(['erro' => 'Permissão de administrador necessária'], 403);
            }
            return false;
        }
        
        return true;
    }
}

// Processar requisição se chamado diretamente
if(basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new ConfiguracaoController();
    $controller->processarRequisicao();
}
?>