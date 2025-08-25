<?php
/**
 * Script para atualizar código após renomeação de colunas
 * Portal de Notícias - Atualização de referências
 * Data: 2025-01-15
 */

// Este arquivo documenta as mudanças necessárias no código após a renomeação das colunas

/*
=== MAPEAMENTO DE COLUNAS RENOMEADAS ===

Antigas -> Novas:
bio -> biografia
show_images -> exibir_imagens
auto_play_videos -> reproduzir_videos_automaticamente
dark_mode -> modo_escuro
email_newsletter -> receber_newsletter
email_breaking -> notificacoes_email_urgentes
email_comments -> notificacoes_email_comentarios
email_marketing -> receber_promocoes
push_breaking -> notificacoes_push_urgentes
push_interests -> notificacoes_push_interesses
push_comments -> notificacoes_push_comentarios
profile_public -> perfil_publico
show_activity -> mostrar_atividade
allow_messages -> permitir_mensagens
favorite_categories -> categorias_favoritas
language_preference -> idioma_preferido

=== ARQUIVOS QUE PRECISAM SER ATUALIZADOS ===

1. backend/models/Usuario.php
   - Atualizar propriedades da classe
   - Atualizar queries SQL
   - Atualizar método atualizarPerfil()
   - Atualizar método criar()

2. backend/controllers/AuthController.php
   - Atualizar método registrar()
   - Atualizar método atualizarPerfil()
   - Atualizar mapeamento de campos

3. Formulários HTML
   - cadastro.html: atualizar nomes dos campos
   - perfil.html: atualizar nomes dos campos
   - Atualizar JavaScript de validação

4. Scripts de teste
   - Atualizar todos os scripts na pasta teste/
   - Atualizar referências às colunas antigas

=== EXEMPLO DE ATUALIZAÇÃO NO MODELO USUARIO ===
*/

// ANTES:
/*
class Usuario {
    public $bio;
    public $show_images;
    public $auto_play_videos;
    public $dark_mode;
    public $email_newsletter;
    // ... outras propriedades
}
*/

// DEPOIS:
/*
class Usuario {
    public $biografia;
    public $exibir_imagens;
    public $reproduzir_videos_automaticamente;
    public $modo_escuro;
    public $receber_newsletter;
    // ... outras propriedades
}
*/

/*
=== EXEMPLO DE ATUALIZAÇÃO NAS QUERIES ===
*/

// ANTES:
/*
$query = "UPDATE usuarios SET 
    bio=:bio, 
    show_images=:show_images, 
    dark_mode=:dark_mode,
    email_newsletter=:email_newsletter
    WHERE id=:id";
*/

// DEPOIS:
/*
$query = "UPDATE usuarios SET 
    biografia=:biografia, 
    exibir_imagens=:exibir_imagens, 
    modo_escuro=:modo_escuro,
    receber_newsletter=:receber_newsletter
    WHERE id=:id";
*/

/*
=== EXEMPLO DE ATUALIZAÇÃO NO CONTROLLER ===
*/

// ANTES:
/*
$this->usuario->bio = $dados['bio'] ?? '';
$this->usuario->show_images = isset($dados['show_images']) ? 1 : 0;
$this->usuario->dark_mode = isset($dados['dark_mode']) ? 1 : 0;
*/

// DEPOIS:
/*
$this->usuario->biografia = $dados['biografia'] ?? '';
$this->usuario->exibir_imagens = isset($dados['exibir_imagens']) ? 1 : 0;
$this->usuario->modo_escuro = isset($dados['modo_escuro']) ? 1 : 0;
*/

/*
=== EXEMPLO DE ATUALIZAÇÃO NO FRONTEND ===
*/

// ANTES (JavaScript):
/*
const dados = {
    bio: document.getElementById('bio').value,
    show_images: document.getElementById('show_images').checked,
    dark_mode: document.getElementById('dark_mode').checked
};
*/

// DEPOIS (JavaScript):
/*
const dados = {
    biografia: document.getElementById('biografia').value,
    exibir_imagens: document.getElementById('exibir_imagens').checked,
    modo_escuro: document.getElementById('modo_escuro').checked
};
*/

/*
=== CHECKLIST DE ATUALIZAÇÃO ===

□ 1. Executar script SQL de renomeação
□ 2. Atualizar modelo Usuario.php
□ 3. Atualizar AuthController.php
□ 4. Atualizar formulários HTML
□ 5. Atualizar JavaScript dos formulários
□ 6. Atualizar scripts de teste
□ 7. Atualizar documentação
□ 8. Testar funcionalidades
□ 9. Verificar logs de erro
□ 10. Deploy em produção

=== COMANDOS ÚTEIS PARA BUSCA E SUBSTITUIÇÃO ===

# Buscar referências às colunas antigas:
grep -r "bio" backend/
grep -r "show_images" backend/
grep -r "dark_mode" backend/
grep -r "email_newsletter" backend/

# Substituir em massa (exemplo):
sed -i 's/\$this->usuario->bio/\$this->usuario->biografia/g' backend/controllers/AuthController.php

*/

echo "Documentação de atualização criada com sucesso!\n";
echo "Consulte este arquivo antes de fazer as mudanças no código.\n";

?>