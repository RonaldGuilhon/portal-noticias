<?php
/**
 * Serviço de Email
 * Portal de Notícias
 */

require_once __DIR__ . '/../../config-unified.php';

// Tentar carregar o autoload do Composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_secure;
    private $from_email;
    private $from_name;

    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_username = SMTP_USERNAME;
        $this->smtp_password = SMTP_PASSWORD;
        $this->smtp_secure = SMTP_SECURE;
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
    }

    /**
     * Enviar email genérico
     */
    public function enviar($para, $assunto, $conteudo, $nome_destinatario = '') {
        try {
            // Se PHPMailer estiver disponível, usar ele
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->enviarComPHPMailer($para, $assunto, $conteudo, $nome_destinatario);
            } else {
                // Fallback para mail() nativo do PHP
                return $this->enviarComMailNativo($para, $assunto, $conteudo, $nome_destinatario);
            }
        } catch (Exception $e) {
            logError('Erro ao enviar email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email usando PHPMailer
     */
    private function enviarComPHPMailer($para, $assunto, $conteudo, $nome_destinatario = '') {
        $mail = new PHPMailer(true);

        try {
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = $this->smtp_secure;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';

            // Remetente
            $mail->setFrom($this->from_email, $this->from_name);

            // Destinatário
            $mail->addAddress($para, $nome_destinatario);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $this->montarTemplateEmail($conteudo, $assunto);
            $mail->AltBody = strip_tags($conteudo);

            $mail->send();
            return true;
        } catch (Exception $e) {
            logError('Erro PHPMailer: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Enviar email usando mail() nativo
     */
    private function enviarComMailNativo($para, $assunto, $conteudo, $nome_destinatario = '') {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];

        $corpo_email = $this->montarTemplateEmail($conteudo, $assunto);

        return mail($para, $assunto, $corpo_email, implode("\r\n", $headers));
    }

    /**
     * Enviar email de newsletter
     */
    public function enviarNewsletter($para, $assunto, $conteudo, $nome_destinatario = '') {
        $conteudo_newsletter = $this->montarTemplateNewsletter($conteudo, $para);
        return $this->enviar($para, $assunto, $conteudo_newsletter, $nome_destinatario);
    }

    /**
     * Enviar email de confirmação de cadastro
     */
    public function enviarConfirmacaoCadastro($para, $nome, $token) {
        $link_confirmacao = BASE_URL . "/backend/auth/confirmar?token={$token}";
        
        $assunto = 'Confirme seu cadastro - ' . SITE_NAME;
        $conteudo = "
        <h2>Bem-vindo ao " . SITE_NAME . "!</h2>
        <p>Olá {$nome},</p>
        <p>Obrigado por se cadastrar em nosso portal de notícias!</p>
        <p>Para ativar sua conta, clique no link abaixo:</p>
        <p><a href='{$link_confirmacao}' style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>Confirmar Cadastro</a></p>
        <p>Se você não se cadastrou em nosso site, pode ignorar este email.</p>
        <p>Este link expira em 24 horas.</p>
        ";
        
        return $this->enviar($para, $assunto, $conteudo, $nome);
    }

    /**
     * Enviar email de recuperação de senha
     */
    public function enviarRecuperacaoSenha($para, $nome, $token) {
        $link_recuperacao = BASE_URL . "/frontend/recuperar-senha.html?token={$token}";
        
        $assunto = 'Recuperação de senha - ' . SITE_NAME;
        $conteudo = "
        <h2>Recuperação de senha</h2>
        <p>Olá {$nome},</p>
        <p>Você solicitou a recuperação de sua senha.</p>
        <p>Para criar uma nova senha, clique no link abaixo:</p>
        <p><a href='{$link_recuperacao}' style='background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>Recuperar Senha</a></p>
        <p>Se você não solicitou esta recuperação, pode ignorar este email.</p>
        <p>Este link expira em 1 hora por segurança.</p>
        ";
        
        return $this->enviar($para, $assunto, $conteudo, $nome);
    }

    /**
     * Enviar notificação de novo comentário
     */
    public function enviarNotificacaoComentario($para, $nome_autor, $titulo_noticia, $comentario, $link_noticia) {
        $assunto = 'Novo comentário em: ' . $titulo_noticia;
        $conteudo = "
        <h2>Novo comentário</h2>
        <p>Um novo comentário foi postado na notícia <strong>{$titulo_noticia}</strong>.</p>
        <p><strong>Autor:</strong> {$nome_autor}</p>
        <p><strong>Comentário:</strong></p>
        <blockquote style='border-left: 4px solid #e5e7eb; padding-left: 16px; margin: 16px 0; color: #6b7280;'>
            {$comentario}
        </blockquote>
        <p><a href='{$link_noticia}' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver Comentário</a></p>
        ";
        
        return $this->enviar($para, $assunto, $conteudo);
    }

    /**
     * Enviar email de contato
     */
    public function enviarContato($nome, $email, $assunto, $mensagem) {
        $assunto_email = 'Contato do site: ' . $assunto;
        $conteudo = "
        <h2>Nova mensagem de contato</h2>
        <p><strong>Nome:</strong> {$nome}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Assunto:</strong> {$assunto}</p>
        <p><strong>Mensagem:</strong></p>
        <div style='background: #f9fafb; padding: 16px; border-radius: 6px; margin: 16px 0;'>
            {$mensagem}
        </div>
        ";
        
        return $this->enviar($this->from_email, $assunto_email, $conteudo);
    }

    /**
     * Montar template base do email
     */
    private function montarTemplateEmail($conteudo, $assunto) {
        $template = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$assunto}</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f8fafc;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #e5e7eb;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    color: #2563eb;
                }
                .content {
                    margin-bottom: 30px;
                }
                .footer {
                    border-top: 1px solid #e5e7eb;
                    padding-top: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #6b7280;
                }
                a {
                    color: #2563eb;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>" . SITE_NAME . "</div>
                </div>
                <div class='content'>
                    {$conteudo}
                </div>
                <div class='footer'>
                    <p>Este email foi enviado por " . SITE_NAME . "</p>
                    <p>" . BASE_URL . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $template;
    }

    /**
     * Montar template específico para newsletter
     */
    private function montarTemplateNewsletter($conteudo, $email_destinatario) {
        $link_cancelamento = BASE_URL . "/backend/newsletter/cancelar?email=" . urlencode($email_destinatario);
        
        $conteudo_com_footer = $conteudo . "
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;'>
        <p style='font-size: 12px; color: #6b7280; text-align: center;'>
            Você está recebendo este email porque se inscreveu em nossa newsletter.<br>
            <a href='{$link_cancelamento}' style='color: #6b7280;'>Cancelar inscrição</a>
        </p>
        ";
        
        return $conteudo_com_footer;
    }

    /**
     * Validar configuração de email
     */
    public function validarConfiguracao() {
        $erros = [];
        
        if (empty($this->smtp_host)) {
            $erros[] = 'SMTP_HOST não configurado';
        }
        
        if (empty($this->smtp_username)) {
            $erros[] = 'SMTP_USERNAME não configurado';
        }
        
        if (empty($this->smtp_password)) {
            $erros[] = 'SMTP_PASSWORD não configurado';
        }
        
        if (empty($this->from_email)) {
            $erros[] = 'SMTP_FROM_EMAIL não configurado';
        }
        
        return empty($erros) ? true : $erros;
    }

    /**
     * Testar conexão SMTP
     */
    public function testarConexao() {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return ['erro' => 'PHPMailer não está instalado'];
        }
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = $this->smtp_secure;
            $mail->Port = $this->smtp_port;
            $mail->Timeout = 10;
            
            // Tentar conectar
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return ['success' => 'Conexão SMTP estabelecida com success'];
        } catch (Exception $e) {
            return ['erro' => 'Erro na conexão SMTP: ' . $e->getMessage()];
        }
    }

    /**
     * Enviar email de teste
     */
    public function enviarTeste($para) {
        $assunto = 'Teste de email - ' . SITE_NAME;
        $conteudo = "
        <h2>Teste de email</h2>
        <p>Este é um email de teste para verificar se o sistema de envio está funcionando corretamente.</p>
        <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
        <p><strong>Servidor:</strong> {$_SERVER['SERVER_NAME']}</p>
        <p>Se você recebeu este email, o sistema está funcionando perfeitamente!</p>
        ";
        
        return $this->enviar($para, $assunto, $conteudo);
    }
}
?>