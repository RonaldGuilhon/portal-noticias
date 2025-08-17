<?php
/**
 * Controlador de Newsletter
 * Portal de Notícias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Newsletter.php';
require_once __DIR__ . '/../services/EmailService.php';

class NewsletterController {
    private $db;
    private $newsletter;
    private $emailService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->newsletter = new Newsletter($this->db);
        $this->emailService = new EmailService();
        
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
                        if($segmentos[2] === 'confirmar') {
                            $this->confirmarInscricao();
                        } elseif($segmentos[2] === 'cancelar') {
                            $this->cancelarInscricao();
                        } else {
                            $this->obterAssinante($segmentos[2]);
                        }
                    } else {
                        $this->listarAssinantes();
                    }
                    break;
                    
                case 'POST':
                    if(isset($segmentos[2]) && $segmentos[2] === 'campanha') {
                        $this->enviarCampanha();
                    } else {
                        $this->inscrever();
                    }
                    break;
                    
                case 'PUT':
                    $this->atualizarAssinante($segmentos[2]);
                    break;
                    
                case 'DELETE':
                    $this->removerAssinante($segmentos[2]);
                    break;
                    
                default:
                    jsonResponse(['erro' => 'Método não permitido'], 405);
            }
        } catch(Exception $e) {
            logError('Erro no NewsletterController: ' . $e->getMessage());
            jsonResponse(['erro' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Inscrever na newsletter
     */
    public function inscrever() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            $dados = $_POST;
        }
        
        // Validar dados
        if(empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['erro' => 'Email inválido'], 400);
            return;
        }
        
        // Verificar se já está inscrito
        $assinante_existente = $this->newsletter->obterPorEmail($dados['email']);
        if($assinante_existente) {
            if($assinante_existente['ativo']) {
                jsonResponse(['erro' => 'Email já está inscrito na newsletter'], 400);
                return;
            } else {
                // Reativar inscrição
                $this->newsletter->reativar($assinante_existente['id']);
                jsonResponse(['success' => 'Inscrição reativada com success']);
                return;
            }
        }
        
        $dados_inscricao = [
            'email' => sanitizeInput($dados['email']),
            'nome' => sanitizeInput($dados['nome'] ?? ''),
            'categorias_interesse' => $dados['categorias'] ?? null,
            'token_confirmacao' => bin2hex(random_bytes(32))
        ];
        
        $id = $this->newsletter->criar($dados_inscricao);
        
        if($id) {
            // Enviar email de confirmação
            $this->enviarEmailConfirmacao($dados_inscricao['email'], $dados_inscricao['token_confirmacao']);
            
            jsonResponse([
                'success' => 'Inscrição realizada com success. Verifique seu email para confirmar.',
                'id' => $id
            ]);
        } else {
            jsonResponse(['erro' => 'Erro ao realizar inscrição'], 500);
        }
    }

    /**
     * Confirmar inscrição
     */
    public function confirmarInscricao() {
        $token = $_GET['token'] ?? '';
        
        if(empty($token)) {
            jsonResponse(['erro' => 'Token não fornecido'], 400);
            return;
        }
        
        $assinante = $this->newsletter->obterPorToken($token);
        
        if(!$assinante) {
            jsonResponse(['erro' => 'Token inválido'], 400);
            return;
        }
        
        if($this->newsletter->confirmar($assinante['id'])) {
            jsonResponse(['success' => 'Email confirmado com success']);
        } else {
            jsonResponse(['erro' => 'Erro ao confirmar email'], 500);
        }
    }

    /**
     * Cancelar inscrição
     */
    public function cancelarInscricao() {
        $email = $_GET['email'] ?? '';
        $token = $_GET['token'] ?? '';
        
        if(empty($email) || empty($token)) {
            jsonResponse(['erro' => 'Dados insuficientes'], 400);
            return;
        }
        
        $assinante = $this->newsletter->obterPorEmail($email);
        
        if(!$assinante || $assinante['token_confirmacao'] !== $token) {
            jsonResponse(['erro' => 'Dados inválidos'], 400);
            return;
        }
        
        if($this->newsletter->desativar($assinante['id'])) {
            jsonResponse(['success' => 'Inscrição cancelada com success']);
        } else {
            jsonResponse(['erro' => 'Erro ao cancelar inscrição'], 500);
        }
    }

    /**
     * Listar assinantes (admin)
     */
    public function listarAssinantes() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $pagina = (int)($_GET['pagina'] ?? 1);
        $limite = min((int)($_GET['limite'] ?? ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);
        $filtros = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
            'periodo' => $_GET['periodo'] ?? ''
        ];
        
        $resultado = $this->newsletter->listar($pagina, $limite, $filtros);
        
        jsonResponse($resultado);
    }

    /**
     * Obter assinante específico (admin)
     */
    public function obterAssinante($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $assinante = $this->newsletter->obterPorId($id);
        
        if($assinante) {
            jsonResponse($assinante);
        } else {
            jsonResponse(['erro' => 'Assinante não encontrado'], 404);
        }
    }

    /**
     * Atualizar assinante (admin)
     */
    public function atualizarAssinante($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados) {
            jsonResponse(['erro' => 'Dados não fornecidos'], 400);
            return;
        }
        
        $dados_atualizacao = [];
        
        if(isset($dados['nome'])) {
            $dados_atualizacao['nome'] = sanitizeInput($dados['nome']);
        }
        
        if(isset($dados['ativo'])) {
            $dados_atualizacao['ativo'] = (bool)$dados['ativo'];
        }
        
        if(isset($dados['categorias_interesse'])) {
            $dados_atualizacao['categorias_interesse'] = $dados['categorias_interesse'];
        }
        
        if($this->newsletter->atualizar($id, $dados_atualizacao)) {
            jsonResponse(['success' => 'Assinante atualizado com success']);
        } else {
            jsonResponse(['erro' => 'Erro ao atualizar assinante'], 500);
        }
    }

    /**
     * Remover assinante (admin)
     */
    public function removerAssinante($id) {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        if($this->newsletter->deletar($id)) {
            jsonResponse(['success' => 'Assinante removido com success']);
        } else {
            jsonResponse(['erro' => 'Erro ao remover assinante'], 500);
        }
    }

    /**
     * Enviar campanha de email (admin)
     */
    public function enviarCampanha() {
        if(!$this->verificarPermissaoAdmin()) {
            return;
        }
        
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if(!$dados || empty($dados['assunto']) || empty($dados['conteudo'])) {
            jsonResponse(['erro' => 'Dados insuficientes'], 400);
            return;
        }
        
        $destinatarios = [];
        
        if($dados['destinatarios'] === 'all') {
            $destinatarios = $this->newsletter->obterTodosAtivos();
        } elseif($dados['destinatarios'] === 'selected' && !empty($dados['ids'])) {
            $destinatarios = $this->newsletter->obterPorIds($dados['ids']);
        }
        
        if(empty($destinatarios)) {
            jsonResponse(['erro' => 'Nenhum destinatário encontrado'], 400);
            return;
        }
        
        $enviados = 0;
        $erros = 0;
        
        foreach($destinatarios as $destinatario) {
            try {
                $conteudo_personalizado = str_replace(
                    ['{{nome}}', '{{email}}'],
                    [$destinatario['nome'] ?: 'Assinante', $destinatario['email']],
                    $dados['conteudo']
                );
                
                $this->emailService->enviarNewsletter(
                    $destinatario['email'],
                    $dados['assunto'],
                    $conteudo_personalizado,
                    $destinatario['nome']
                );
                
                $enviados++;
            } catch(Exception $e) {
                logError('Erro ao enviar newsletter para ' . $destinatario['email'] . ': ' . $e->getMessage());
                $erros++;
            }
        }
        
        jsonResponse([
            'success' => 'Campanha processada',
            'enviados' => $enviados,
            'erros' => $erros,
            'total' => count($destinatarios)
        ]);
    }

    /**
     * Enviar email de confirmação
     */
    private function enviarEmailConfirmacao($email, $token) {
        $link_confirmacao = BASE_URL . "/backend/newsletter/confirmar?token={$token}";
        $link_cancelamento = BASE_URL . "/backend/newsletter/cancelar?email={$email}&token={$token}";
        
        $assunto = 'Confirme sua inscrição na newsletter';
        $conteudo = "
        <h2>Confirme sua inscrição</h2>
        <p>Obrigado por se inscrever em nossa newsletter!</p>
        <p>Para confirmar sua inscrição, clique no link abaixo:</p>
        <p><a href='{$link_confirmacao}' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar Inscrição</a></p>
        <p>Se você não se inscreveu, pode ignorar este email ou <a href='{$link_cancelamento}'>cancelar a inscrição</a>.</p>
        ";
        
        $this->emailService->enviar($email, $assunto, $conteudo);
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
    $controller = new NewsletterController();
    $controller->processarRequisicao();
}
?>