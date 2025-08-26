-- =============================================
-- DADOS DE TESTE COMPLETOS - PORTAL DE NOTÍCIAS
-- Script para inserir dados simulados em todas as tabelas
-- =============================================

USE portal_noticias;

-- =============================================
-- USUÁRIOS DE TESTE
-- =============================================

INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, email_verificado, bio, data_nascimento, genero, cidade, estado, foto_perfil, ultimo_login) VALUES 
('João Silva', 'joao.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', 1, 1, 'Jornalista especializado em política e economia com 10 anos de experiência.', '1985-03-15', 'masculino', 'São Paulo', 'SP', '/uploads/avatars/joao.jpg', '2024-01-15 14:30:00'),
('Maria Santos', 'maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', 1, 1, 'Repórter esportiva com cobertura de grandes eventos nacionais e internacionais.', '1990-07-22', 'feminino', 'Rio de Janeiro', 'RJ', '/uploads/avatars/maria.jpg', '2024-01-15 16:45:00'),
('Carlos Oliveira', 'carlos.oliveira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor', 1, 1, 'Especialista em tecnologia e inovação, formado em Ciência da Computação.', '1988-11-08', 'masculino', 'Belo Horizonte', 'MG', '/uploads/avatars/carlos.jpg', '2024-01-15 09:20:00'),
('Ana Costa', 'ana.costa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'leitor', 1, 1, 'Leitora assídua interessada em saúde e bem-estar.', '1992-05-12', 'feminino', 'Porto Alegre', 'RS', NULL, '2024-01-14 20:15:00'),
('Pedro Ferreira', 'pedro.ferreira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'leitor', 1, 1, 'Empresário interessado em notícias de economia e negócios.', '1980-09-30', 'masculino', 'Brasília', 'DF', NULL, '2024-01-15 11:30:00'),
('Lucia Mendes', 'lucia.mendes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'leitor', 1, 1, 'Professora universitária com interesse em educação e cultura.', '1975-12-03', 'feminino', 'Salvador', 'BA', NULL, '2024-01-13 18:45:00'),
('Roberto Lima', 'roberto.lima@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'leitor', 1, 1, 'Aposentado que gosta de se manter informado sobre política.', '1955-04-18', 'masculino', 'Recife', 'PE', NULL, '2024-01-15 07:00:00'),
('Fernanda Rocha', 'fernanda.rocha@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'leitor', 1, 1, 'Estudante de jornalismo interessada em todas as áreas.', '1998-08-25', 'feminino', 'Fortaleza', 'CE', NULL, '2024-01-15 13:20:00');

-- =============================================
-- CATEGORIAS ADICIONAIS
-- =============================================

INSERT INTO categorias (nome, slug, descricao, cor, icone, ordem) VALUES 
('Cultura', 'cultura', 'Arte, literatura, música e manifestações culturais', '#e83e8c', 'fas fa-palette', 8),
('Educação', 'educacao', 'Notícias sobre ensino, universidades e educação', '#17a2b8', 'fas fa-graduation-cap', 9),
('Meio Ambiente', 'meio-ambiente', 'Sustentabilidade, ecologia e preservação ambiental', '#28a745', 'fas fa-leaf', 10);

-- =============================================
-- TAGS ADICIONAIS
-- =============================================

INSERT INTO tags (nome, slug, cor) VALUES 
('Breaking News', 'breaking-news', '#dc3545'),
('Eleições', 'eleicoes', '#007bff'),
('Mercado', 'mercado', '#28a745'),
('Futebol', 'futebol', '#ffc107'),
('Inteligência Artificial', 'inteligencia-artificial', '#6f42c1'),
('COVID-19', 'covid-19', '#dc3545'),
('Olimpíadas', 'olimpiadas', '#fd7e14'),
('Cinema', 'cinema', '#e83e8c'),
('Música', 'musica', '#20c997'),
('Sustentabilidade', 'sustentabilidade', '#28a745'),
('Inovação', 'inovacao', '#6f42c1'),
('Startups', 'startups', '#17a2b8'),
('Criptomoedas', 'criptomoedas', '#ffc107'),
('Educação Digital', 'educacao-digital', '#17a2b8'),
('Arte Contemporânea', 'arte-contemporanea', '#e83e8c');

-- =============================================
-- NOTÍCIAS DE TESTE
-- =============================================

INSERT INTO noticias (titulo, slug, subtitulo, conteudo, resumo, autor_id, categoria_id, status, destaque, data_publicacao, visualizacoes, curtidas, imagem_destaque, meta_title, meta_description) VALUES 

-- Política
('Nova Lei de Transparência é Aprovada no Congresso', 'nova-lei-transparencia-aprovada-congresso', 'Medida visa aumentar o acesso à informação pública', '<p>O Congresso Nacional aprovou hoje uma nova lei que amplia significativamente o acesso à informação pública no país. A medida, que tramitava há dois anos, estabelece novos prazos para resposta aos pedidos de informação e cria mecanismos mais eficazes de fiscalização.</p><p>Segundo o relator do projeto, a nova legislação representa um marco na transparência pública brasileira. "Estamos dando um passo importante para fortalecer a democracia e o controle social", declarou.</p><p>A lei entra em vigor em 90 dias e afeta todos os órgãos públicos federais, estaduais e municipais.</p>', 'Congresso aprova nova lei que amplia acesso à informação pública com prazos menores e fiscalização mais eficaz.', 2, 1, 'publicado', 1, '2024-01-15 10:30:00', 1250, 89, '/uploads/noticias/congresso-lei.jpg', 'Nova Lei de Transparência Aprovada - Portal Notícias', 'Congresso aprova lei que amplia acesso à informação pública com novos prazos e mecanismos de fiscalização'),

('Prefeito de São Paulo Anuncia Novo Plano de Mobilidade', 'prefeito-sp-anuncia-plano-mobilidade', 'Investimento de R$ 2 bilhões em transporte público', '<p>O prefeito de São Paulo anunciou hoje um ambicioso plano de mobilidade urbana que prevê investimentos de R$ 2 bilhões nos próximos quatro anos. O projeto inclui a expansão do metrô, criação de novas ciclovias e modernização da frota de ônibus.</p><p>"Nossa meta é reduzir em 30% o tempo de deslocamento na cidade até 2028", afirmou o prefeito durante coletiva de imprensa. O plano também prevê a integração total entre os diferentes modais de transporte.</p><p>As obras devem começar no segundo semestre deste ano, priorizando as regiões periféricas da cidade.</p>', 'Prefeito anuncia plano de R$ 2 bilhões para melhorar mobilidade urbana em São Paulo com expansão do metrô e ciclovias.', 2, 1, 'publicado', 1, '2024-01-15 14:15:00', 980, 67, '/uploads/noticias/mobilidade-sp.jpg', 'Plano de Mobilidade São Paulo - R$ 2 Bilhões', 'Prefeito anuncia investimento de R$ 2 bilhões em mobilidade urbana com expansão do metrô e ciclovias'),

-- Economia
('Inflação Fecha 2023 em 4,62%, Dentro da Meta', 'inflacao-fecha-2023-meta', 'IPCA fica abaixo do teto estabelecido pelo governo', '<p>A inflação oficial do país, medida pelo Índice Nacional de Preços ao Consumidor Amplo (IPCA), fechou 2023 em 4,62%, ficando dentro da meta estabelecida pelo Conselho Monetário Nacional, que era de 3,25% com tolerância de 1,5 ponto percentual para mais ou para menos.</p><p>O resultado representa uma desaceleração em relação ao ano anterior e reflete a eficácia das políticas monetárias adotadas pelo Banco Central. Os grupos que mais contribuíram para a inflação foram habitação e alimentação.</p><p>Economistas avaliam que o cenário é positivo para 2024, com expectativas de inflação ainda mais controlada.</p>', 'IPCA fecha 2023 em 4,62%, dentro da meta governamental, sinalizando controle da inflação e perspectivas positivas.', 2, 2, 'publicado', 1, '2024-01-15 08:45:00', 1450, 102, '/uploads/noticias/inflacao-2023.jpg', 'Inflação 2023: 4,62% Dentro da Meta', 'IPCA fecha 2023 em 4,62%, dentro da meta do governo, com perspectivas positivas para 2024'),

('Dólar Fecha em Queda Após Decisão do Fed', 'dolar-queda-decisao-fed', 'Moeda americana recua 1,2% frente ao real', '<p>O dólar fechou em queda de 1,2% nesta quarta-feira, cotado a R$ 4,95, após a decisão do Federal Reserve (Fed) de manter as taxas de juros inalteradas nos Estados Unidos. A decisão era esperada pelo mercado, mas os comentários do presidente do Fed sobre possíveis cortes futuros animaram os investidores.</p><p>No mercado doméstico, a melhora do cenário fiscal brasileiro também contribuiu para o fortalecimento do real. Analistas projetam que a moeda pode se manter estável nas próximas semanas.</p><p>O Ibovespa acompanhou o movimento positivo, fechando em alta de 0,8%.</p>', 'Dólar recua 1,2% após decisão do Fed de manter juros, com real se fortalecendo no cenário doméstico.', 2, 2, 'publicado', 0, '2024-01-15 16:20:00', 890, 45, '/uploads/noticias/dolar-queda.jpg', 'Dólar em Queda Após Decisão do Fed', 'Dólar recua 1,2% frente ao real após Fed manter juros, com perspectivas positivas para o mercado'),

-- Esportes
('Brasil Vence Argentina por 2x1 em Amistoso Emocionante', 'brasil-vence-argentina-amistoso', 'Seleção brasileira mostra evolução sob novo técnico', '<p>A Seleção Brasileira venceu a Argentina por 2 a 1 em amistoso disputado no Maracanã, diante de mais de 70 mil torcedores. Os gols brasileiros foram marcados por Vinícius Jr. e Rodrygo, enquanto Messi descontou para os argentinos.</p><p>O técnico da Seleção elogiou a performance da equipe: "Vejo uma evolução clara no nosso padrão de jogo. Os jogadores estão entendendo melhor a proposta tática". O resultado marca a terceira vitória consecutiva do Brasil sob o novo comando técnico.</p><p>A próxima partida da Seleção será contra o Uruguai, em Montevidéu, pelas Eliminatórias da Copa do Mundo.</p>', 'Brasil vence Argentina por 2x1 no Maracanã com gols de Vinícius Jr. e Rodrygo, mostrando evolução tática.', 3, 3, 'publicado', 1, '2024-01-14 22:30:00', 2100, 156, '/uploads/noticias/brasil-argentina.jpg', 'Brasil 2x1 Argentina - Amistoso no Maracanã', 'Brasil vence Argentina por 2x1 no Maracanã com gols de Vinícius Jr. e Rodrygo em grande partida'),

('Flamengo Contrata Novo Técnico Europeu', 'flamengo-contrata-tecnico-europeu', 'Clube carioca investe pesado para temporada 2024', '<p>O Flamengo oficializou hoje a contratação do técnico português Miguel Santos, de 45 anos, que comandava o Sporting Lisboa. O treinador assinou contrato até dezembro de 2025 e chega com uma comissão técnica de seis profissionais.</p><p>"Estou muito feliz por esta oportunidade. O Flamengo é um clube gigante e tenho certeza de que faremos um grande trabalho juntos", declarou o novo técnico em sua apresentação.</p><p>A diretoria rubro-negra também confirmou investimentos de R$ 80 milhões em reforços para a temporada, visando disputar Libertadores, Brasileirão e Copa do Brasil.</p>', 'Flamengo contrata técnico português Miguel Santos até 2025 e anuncia investimento de R$ 80 milhões em reforços.', 3, 3, 'publicado', 0, '2024-01-15 11:00:00', 1680, 134, '/uploads/noticias/flamengo-tecnico.jpg', 'Flamengo Contrata Técnico Português', 'Flamengo oficializa contratação do técnico Miguel Santos e anuncia investimento de R$ 80 milhões'),

-- Tecnologia
('Nova IA da Google Supera GPT-4 em Testes de Raciocínio', 'nova-ia-google-supera-gpt4', 'Gemini Ultra mostra avanços significativos em múltiplas áreas', '<p>O Google anunciou hoje que sua nova inteligência artificial, Gemini Ultra, superou o GPT-4 da OpenAI em diversos benchmarks de raciocínio e compreensão. Os testes incluíram matemática, programação, análise de texto e raciocínio lógico.</p><p>"Este é um marco importante para a IA", declarou o CEO do Google. "O Gemini Ultra representa um salto qualitativo na capacidade de processamento e compreensão de contexto". A nova IA será integrada aos produtos Google nos próximos meses.</p><p>Especialistas em IA consideram que essa evolução pode acelerar ainda mais o desenvolvimento de aplicações práticas da inteligência artificial.</p>', 'Google lança Gemini Ultra, IA que supera GPT-4 em testes de raciocínio e será integrada aos produtos da empresa.', 4, 4, 'publicado', 1, '2024-01-15 13:45:00', 1890, 201, '/uploads/noticias/gemini-ultra.jpg', 'Gemini Ultra Supera GPT-4 em Testes', 'Nova IA do Google supera GPT-4 em benchmarks de raciocínio e será integrada aos produtos da empresa'),

('Apple Anuncia iPhone 16 com Bateria que Dura 3 Dias', 'apple-iphone-16-bateria-3-dias', 'Novo smartphone promete revolucionar autonomia', '<p>A Apple surpreendeu o mercado ao anunciar que o iPhone 16, previsto para setembro, terá uma bateria capaz de durar até 3 dias com uso moderado. A conquista foi possível graças a um novo chip A18 mais eficiente e uma tecnologia de bateria desenvolvida em parceria com fornecedores asiáticos.</p><p>"Sabemos que a duração da bateria é uma das principais preocupações dos usuários", disse o vice-presidente de engenharia da Apple. O novo iPhone também terá carregamento sem fio mais rápido e suporte a 5G avançado.</p><p>O preço ainda não foi divulgado, mas especula-se que será similar ao modelo anterior.</p>', 'Apple anuncia iPhone 16 com bateria de 3 dias de duração, novo chip A18 e carregamento sem fio mais rápido.', 4, 4, 'publicado', 1, '2024-01-15 15:30:00', 1560, 178, '/uploads/noticias/iphone-16.jpg', 'iPhone 16: Bateria de 3 Dias da Apple', 'Apple anuncia iPhone 16 com bateria revolucionária de 3 dias e novo chip A18 mais eficiente'),

-- Saúde
('Novo Tratamento Reduz Câncer de Mama em 70%', 'novo-tratamento-cancer-mama-70', 'Terapia inovadora mostra resultados promissores', '<p>Pesquisadores brasileiros desenvolveram um novo tratamento para câncer de mama que reduziu tumores em 70% dos casos testados. A terapia combina imunoterapia com nanotecnologia, permitindo ataques mais precisos às células cancerígenas.</p><p>"Os resultados são extremamente promissores", afirmou a coordenadora da pesquisa. "Estamos vendo uma resposta muito melhor do que os tratamentos convencionais, com menos efeitos colaterais".</p><p>O estudo, realizado em parceria com universidades americanas, deve entrar na fase 3 de testes clínicos ainda este ano. Se aprovado, o tratamento pode estar disponível em 2026.</p>', 'Pesquisadores brasileiros desenvolvem tratamento que reduz câncer de mama em 70% com menos efeitos colaterais.', 1, 5, 'publicado', 1, '2024-01-15 09:15:00', 2200, 245, '/uploads/noticias/cancer-mama-tratamento.jpg', 'Novo Tratamento Reduz Câncer de Mama em 70%', 'Pesquisadores brasileiros desenvolvem terapia inovadora que reduz câncer de mama em 70% dos casos'),

('Ministério da Saúde Lança Campanha de Vacinação', 'ministerio-saude-campanha-vacinacao', 'Foco na imunização contra gripe e COVID-19', '<p>O Ministério da Saúde lançou hoje uma nova campanha nacional de vacinação que visa imunizar 80 milhões de brasileiros contra gripe e COVID-19. A campanha, que começa na próxima segunda-feira, priorizará idosos, crianças e profissionais de saúde.</p><p>"É fundamental que a população mantenha a carteira de vacinação em dia", alertou o ministro da Saúde. "As vacinas são nossa principal arma contra essas doenças".</p><p>Serão disponibilizadas 120 milhões de doses em todo o país, distribuídas em 40 mil postos de vacinação.</p>', 'Ministério da Saúde lança campanha para vacinar 80 milhões contra gripe e COVID-19 em 40 mil postos.', 1, 5, 'publicado', 0, '2024-01-15 12:00:00', 1340, 98, '/uploads/noticias/campanha-vacinacao.jpg', 'Campanha Nacional de Vacinação 2024', 'Ministério da Saúde lança campanha para vacinar 80 milhões contra gripe e COVID-19'),

-- Cultura
('Festival de Cinema de Gramado Anuncia Programação', 'festival-cinema-gramado-programacao', '120 filmes de 15 países serão exibidos', '<p>O Festival de Cinema de Gramado anunciou sua programação completa para 2024, com 120 filmes de 15 países diferentes. O evento, que acontece de 15 a 23 de agosto, terá como tema "Cinema sem Fronteiras" e contará com a presença de grandes nomes do cinema nacional e internacional.</p><p>"Este ano temos uma programação especialmente diversa", comentou o diretor do festival. "Queremos mostrar que o cinema é uma linguagem universal que une culturas".</p><p>Entre os destaques estão as estreias de três produções brasileiras e a retrospectiva completa de um renomado diretor argentino.</p>', 'Festival de Cinema de Gramado 2024 terá 120 filmes de 15 países com tema "Cinema sem Fronteiras".', 4, 9, 'publicado', 0, '2024-01-15 17:00:00', 780, 56, '/uploads/noticias/festival-gramado.jpg', 'Festival de Cinema de Gramado 2024', 'Festival de Gramado anuncia programação com 120 filmes de 15 países e tema "Cinema sem Fronteiras"');

-- =============================================
-- RELACIONAMENTOS NOTÍCIA-TAGS
-- =============================================

INSERT INTO noticia_tags (noticia_id, tag_id) VALUES 
-- Notícia 1 (Lei Transparência)
(1, 1), (1, 7), (1, 3),
-- Notícia 2 (Mobilidade SP)
(2, 8), (2, 11),
-- Notícia 3 (Inflação)
(3, 3), (3, 7), (3, 12),
-- Notícia 4 (Dólar)
(4, 12), (4, 7),
-- Notícia 5 (Brasil x Argentina)
(5, 1), (5, 14), (5, 7),
-- Notícia 6 (Flamengo)
(6, 14), (6, 2),
-- Notícia 7 (Gemini Ultra)
(7, 1), (7, 15), (7, 11),
-- Notícia 8 (iPhone 16)
(8, 1), (8, 11), (8, 2),
-- Notícia 9 (Câncer)
(9, 1), (9, 11), (9, 6),
-- Notícia 10 (Vacinação)
(10, 6), (10, 7),
-- Notícia 11 (Festival Gramado)
(11, 18), (11, 19);

-- =============================================
-- COMENTÁRIOS DE TESTE
-- =============================================

INSERT INTO comentarios (noticia_id, usuario_id, conteudo, aprovado, curtidas, data_criacao) VALUES 
-- Comentários na notícia da Lei de Transparência
(1, 5, 'Excelente notícia! Era hora de termos mais transparência no governo.', 1, 12, '2024-01-15 11:00:00'),
(1, 6, 'Espero que seja realmente implementada e não fique só no papel.', 1, 8, '2024-01-15 11:30:00'),
(1, 7, 'Muito importante para a democracia. Parabéns aos envolvidos!', 1, 5, '2024-01-15 12:15:00'),

-- Comentários na notícia do Plano de Mobilidade
(2, 4, 'São Paulo precisa mesmo de mais investimento em transporte público.', 1, 15, '2024-01-15 14:45:00'),
(2, 8, 'R$ 2 bilhões parece pouco para o tamanho do problema, mas é um começo.', 1, 9, '2024-01-15 15:20:00'),

-- Comentários na notícia da Inflação
(3, 5, 'Ótima notícia para a economia brasileira!', 1, 18, '2024-01-15 09:30:00'),
(3, 7, 'Agora é torcer para que continue controlada em 2024.', 1, 11, '2024-01-15 10:00:00'),

-- Comentários na notícia Brasil x Argentina
(5, 4, 'Que jogo fantástico! O Brasil está voltando ao seu melhor nível.', 1, 25, '2024-01-14 23:00:00'),
(5, 6, 'Vinícius Jr. está jogando muito! Craque demais!', 1, 22, '2024-01-14 23:15:00'),
(5, 8, 'Messi ainda é Messi, mas o Brasil mereceu a vitória.', 1, 14, '2024-01-14 23:30:00'),

-- Comentários na notícia do Gemini Ultra
(7, 4, 'A evolução da IA é impressionante. Mal posso esperar para testar.', 1, 16, '2024-01-15 14:00:00'),
(7, 5, 'Será que vai substituir o ChatGPT? A concorrência é boa para todos.', 1, 13, '2024-01-15 14:30:00'),

-- Comentários na notícia do iPhone 16
(8, 6, 'Finalmente! Bateria sempre foi o ponto fraco dos iPhones.', 1, 19, '2024-01-15 16:00:00'),
(8, 7, 'Curioso para saber o preço. Espero que não seja absurdo.', 1, 7, '2024-01-15 16:45:00'),

-- Comentários na notícia do tratamento de câncer
(9, 4, 'Que notícia maravilhosa! Esperança para milhões de pessoas.', 1, 35, '2024-01-15 10:00:00'),
(9, 5, 'Orgulho dos pesquisadores brasileiros. Ciência salvando vidas!', 1, 28, '2024-01-15 10:30:00'),
(9, 6, 'Minha mãe teve câncer de mama. Torço para que chegue logo ao mercado.', 1, 31, '2024-01-15 11:00:00');

-- =============================================
-- CURTIDAS EM NOTÍCIAS
-- =============================================

INSERT INTO curtidas_noticias (noticia_id, usuario_id, tipo, data_criacao) VALUES 
-- Curtidas na Lei de Transparência
(1, 4, 'curtida', '2024-01-15 11:00:00'),
(1, 5, 'curtida', '2024-01-15 11:15:00'),
(1, 6, 'curtida', '2024-01-15 11:30:00'),
(1, 7, 'curtida', '2024-01-15 12:00:00'),
(1, 8, 'curtida', '2024-01-15 12:30:00'),

-- Curtidas no Plano de Mobilidade
(2, 4, 'curtida', '2024-01-15 14:30:00'),
(2, 5, 'curtida', '2024-01-15 15:00:00'),
(2, 6, 'curtida', '2024-01-15 15:30:00'),

-- Curtidas na Inflação
(3, 4, 'curtida', '2024-01-15 09:00:00'),
(3, 5, 'curtida', '2024-01-15 09:30:00'),
(3, 6, 'curtida', '2024-01-15 10:00:00'),
(3, 7, 'curtida', '2024-01-15 10:30:00'),
(3, 8, 'curtida', '2024-01-15 11:00:00'),

-- Curtidas no Brasil x Argentina
(5, 4, 'curtida', '2024-01-14 23:00:00'),
(5, 5, 'curtida', '2024-01-14 23:15:00'),
(5, 6, 'curtida', '2024-01-14 23:30:00'),
(5, 7, 'curtida', '2024-01-14 23:45:00'),
(5, 8, 'curtida', '2024-01-15 00:00:00'),

-- Curtidas no Gemini Ultra
(7, 4, 'curtida', '2024-01-15 14:00:00'),
(7, 5, 'curtida', '2024-01-15 14:15:00'),
(7, 6, 'curtida', '2024-01-15 14:30:00'),
(7, 7, 'curtida', '2024-01-15 14:45:00'),

-- Curtidas no iPhone 16
(8, 4, 'curtida', '2024-01-15 16:00:00'),
(8, 5, 'curtida', '2024-01-15 16:15:00'),
(8, 6, 'curtida', '2024-01-15 16:30:00'),

-- Curtidas no tratamento de câncer
(9, 4, 'curtida', '2024-01-15 09:30:00'),
(9, 5, 'curtida', '2024-01-15 09:45:00'),
(9, 6, 'curtida', '2024-01-15 10:00:00'),
(9, 7, 'curtida', '2024-01-15 10:15:00'),
(9, 8, 'curtida', '2024-01-15 10:30:00');

-- =============================================
-- CURTIDAS EM COMENTÁRIOS
-- =============================================

INSERT INTO curtidas_comentarios (comentario_id, usuario_id, tipo, data_criacao) VALUES 
-- Curtidas nos comentários da Lei de Transparência
(1, 4, 'curtida', '2024-01-15 11:05:00'),
(1, 6, 'curtida', '2024-01-15 11:20:00'),
(1, 7, 'curtida', '2024-01-15 11:35:00'),
(2, 5, 'curtida', '2024-01-15 11:45:00'),
(2, 7, 'curtida', '2024-01-15 12:00:00'),

-- Curtidas nos comentários do Brasil x Argentina
(8, 5, 'curtida', '2024-01-14 23:05:00'),
(8, 6, 'curtida', '2024-01-14 23:10:00'),
(8, 7, 'curtida', '2024-01-14 23:20:00'),
(9, 4, 'curtida', '2024-01-14 23:20:00'),
(9, 5, 'curtida', '2024-01-14 23:25:00'),

-- Curtidas nos comentários do tratamento de câncer
(15, 5, 'curtida', '2024-01-15 10:05:00'),
(15, 6, 'curtida', '2024-01-15 10:15:00'),
(15, 7, 'curtida', '2024-01-15 10:25:00'),
(16, 4, 'curtida', '2024-01-15 10:35:00'),
(16, 6, 'curtida', '2024-01-15 10:45:00'),
(17, 4, 'curtida', '2024-01-15 11:05:00'),
(17, 5, 'curtida', '2024-01-15 11:15:00');

-- =============================================
-- ESTATÍSTICAS DE ACESSO
-- =============================================

INSERT INTO estatisticas_acesso (noticia_id, ip_address, user_agent, referer, data_acesso) VALUES 
-- Acessos variados nas notícias
(1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'https://google.com', '2024-01-15 10:35:00'),
(1, '192.168.1.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 'https://facebook.com', '2024-01-15 10:45:00'),
(1, '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'https://twitter.com', '2024-01-15 11:00:00'),

(2, '192.168.1.103', 'Mozilla/5.0 (Android 13; Mobile)', 'https://google.com', '2024-01-15 14:20:00'),
(2, '192.168.1.104', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'https://portal-noticias.com', '2024-01-15 14:30:00'),

(3, '192.168.1.105', 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)', 'https://google.com', '2024-01-15 08:50:00'),
(3, '192.168.1.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'https://yahoo.com', '2024-01-15 09:00:00'),

(5, '192.168.1.107', 'Mozilla/5.0 (Android 13; Mobile)', 'https://globoesporte.com', '2024-01-14 22:35:00'),
(5, '192.168.1.108', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 'https://espn.com.br', '2024-01-14 22:45:00'),
(5, '192.168.1.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'https://google.com', '2024-01-14 23:00:00'),

(7, '192.168.1.110', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'https://techcrunch.com', '2024-01-15 13:50:00'),
(7, '192.168.1.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'https://google.com', '2024-01-15 14:00:00'),

(9, '192.168.1.112', 'Mozilla/5.0 (Android 13; Mobile)', 'https://google.com', '2024-01-15 09:20:00'),
(9, '192.168.1.113', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 'https://facebook.com', '2024-01-15 09:30:00'),
(9, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'https://portal-noticias.com', '2024-01-15 09:45:00');

-- =============================================
-- NEWSLETTER
-- =============================================

INSERT INTO newsletter (email, nome, ativo, confirmado, data_inscricao, data_confirmacao, categorias_interesse) VALUES 
('newsletter1@email.com', 'Carlos Silva', 1, 1, '2024-01-10 10:00:00', '2024-01-10 10:30:00', '["politica", "economia"]'),
('newsletter2@email.com', 'Maria Oliveira', 1, 1, '2024-01-11 14:20:00', '2024-01-11 14:45:00', '["esportes", "tecnologia"]'),
('newsletter3@email.com', 'João Santos', 1, 1, '2024-01-12 09:15:00', '2024-01-12 09:40:00', '["saude", "cultura"]'),
('newsletter4@email.com', 'Ana Costa', 1, 1, '2024-01-13 16:30:00', '2024-01-13 17:00:00', '["politica", "mundo"]'),
('newsletter5@email.com', 'Pedro Lima', 1, 1, '2024-01-14 11:45:00', '2024-01-14 12:15:00', '["economia", "tecnologia"]'),
('newsletter6@email.com', 'Lucia Mendes', 1, 0, '2024-01-15 08:20:00', NULL, '["educacao", "cultura"]'),
('newsletter7@email.com', 'Roberto Ferreira', 1, 1, '2024-01-15 13:10:00', '2024-01-15 13:35:00', '["esportes", "entretenimento"]'),
('newsletter8@email.com', 'Fernanda Rocha', 1, 0, '2024-01-15 17:50:00', NULL, '["meio-ambiente", "saude"]');

-- =============================================
-- ANÚNCIOS
-- =============================================

INSERT INTO anuncios (titulo, imagem, link, posicao, ativo, data_inicio, data_fim, visualizacoes, cliques) VALUES 
('Curso de Jornalismo Online', '/uploads/anuncios/curso-jornalismo.jpg', 'https://cursojornalismo.com', 'sidebar', 1, '2024-01-01', '2024-03-31', 15420, 234),
('Seguro Auto com 30% de Desconto', '/uploads/anuncios/seguro-auto.jpg', 'https://seguradora.com/promo', 'header', 1, '2024-01-15', '2024-02-15', 8750, 156),
('Smartphone em Promoção', '/uploads/anuncios/smartphone-promo.jpg', 'https://loja.com/smartphones', 'conteudo', 1, '2024-01-10', '2024-01-25', 12300, 289),
('Investimentos em Renda Fixa', '/uploads/anuncios/investimentos.jpg', 'https://corretora.com/renda-fixa', 'footer', 1, '2024-01-05', '2024-02-05', 9870, 178),
('Curso de Inglês Online', '/uploads/anuncios/curso-ingles.jpg', 'https://escolaingles.com', 'sidebar', 0, '2023-12-01', '2024-01-01', 5420, 89);

-- =============================================
-- CONFIGURAÇÕES ADICIONAIS
-- =============================================

INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES 
('analytics_id', 'GA-123456789', 'ID do Google Analytics', 'string'),
('facebook_page', 'https://facebook.com/portalnoticias', 'Página do Facebook', 'string'),
('twitter_handle', '@portalnoticias', 'Handle do Twitter', 'string'),
('instagram_profile', 'portalnoticias', 'Perfil do Instagram', 'string'),
('youtube_channel', 'UCPortalNoticias', 'Canal do YouTube', 'string'),
('whatsapp_number', '5511999999999', 'Número do WhatsApp', 'string'),
('push_notifications', '1', 'Ativar notificações push', 'boolean'),
('cache_duration', '3600', 'Duração do cache em segundos', 'number'),
('max_upload_size', '10485760', 'Tamanho máximo de upload em bytes', 'number'),
('maintenance_mode', '0', 'Modo de manutenção', 'boolean');

-- =============================================
-- MÍDIAS DE EXEMPLO
-- =============================================

INSERT INTO midias (nome_original, nome_arquivo, caminho, tipo_mime, tamanho, tipo, alt_text, legenda, usuario_id) VALUES 
('congresso-brasilia.jpg', 'congresso-brasilia-20240115.jpg', '/uploads/noticias/congresso-brasilia-20240115.jpg', 'image/jpeg', 245760, 'imagem', 'Congresso Nacional em Brasília', 'Vista do Congresso Nacional durante sessão', 2),
('grafico-inflacao.png', 'grafico-inflacao-20240115.png', '/uploads/noticias/grafico-inflacao-20240115.png', 'image/png', 156890, 'imagem', 'Gráfico da inflação 2023', 'Evolução do IPCA ao longo de 2023', 2),
('maracana-jogo.jpg', 'maracana-jogo-20240114.jpg', '/uploads/noticias/maracana-jogo-20240114.jpg', 'image/jpeg', 389120, 'imagem', 'Maracanã lotado durante Brasil x Argentina', 'Torcida no Maracanã durante o amistoso', 3),
('laboratorio-pesquisa.jpg', 'laboratorio-pesquisa-20240115.jpg', '/uploads/noticias/laboratorio-pesquisa-20240115.jpg', 'image/jpeg', 298450, 'imagem', 'Laboratório de pesquisa médica', 'Pesquisadores trabalhando no desenvolvimento do tratamento', 1),
('smartphone-tecnologia.jpg', 'smartphone-tecnologia-20240115.jpg', '/uploads/noticias/smartphone-tecnologia-20240115.jpg', 'image/jpeg', 187630, 'imagem', 'Novo smartphone com tecnologia avançada', 'iPhone 16 com design renovado', 4);

-- =============================================
-- NOTIFICAÇÕES
-- =============================================

INSERT INTO notificacoes (titulo, mensagem, tipo, usuario_id, lida, data_criacao, url, icone) VALUES 
('Bem-vindo ao Portal!', 'Obrigado por se cadastrar em nosso portal de notícias. Explore nosso conteúdo!', 'sucesso', 4, 1, '2024-01-14 20:20:00', '/perfil', 'fas fa-user-check'),
('Nova notícia em Política', 'Nova Lei de Transparência é Aprovada no Congresso', 'info', 4, 0, '2024-01-15 10:35:00', '/noticia/nova-lei-transparencia-aprovada-congresso', 'fas fa-newspaper'),
('Seu comentário foi aprovado', 'Seu comentário na notícia sobre inflação foi aprovado e está visível.', 'sucesso', 5, 1, '2024-01-15 09:35:00', '/noticia/inflacao-fecha-2023-meta', 'fas fa-comment-check'),
('Breaking News!', 'Brasil vence Argentina por 2x1 em amistoso emocionante no Maracanã!', 'info', NULL, 0, '2024-01-14 22:35:00', '/noticia/brasil-vence-argentina-amistoso', 'fas fa-bolt'),
('Nova curtida no seu comentário', 'Alguém curtiu seu comentário sobre o jogo Brasil x Argentina.', 'info', 6, 0, '2024-01-14 23:25:00', '/noticia/brasil-vence-argentina-amistoso', 'fas fa-heart'),
('Manutenção programada', 'O sistema passará por manutenção amanhã das 2h às 4h.', 'aviso', NULL, 0, '2024-01-15 18:00:00', NULL, 'fas fa-tools'),
('Novo seguidor', 'Pedro Ferreira começou a seguir suas atividades.', 'info', 2, 0, '2024-01-15 11:35:00', '/perfil/pedro-ferreira', 'fas fa-user-plus'),
('Artigo em destaque', 'Seu artigo sobre tecnologia foi destacado na página inicial!', 'sucesso', 4, 1, '2024-01-15 13:50:00', '/noticia/nova-ia-google-supera-gpt4', 'fas fa-star');

-- =============================================
-- ATUALIZAR CONTADORES
-- =============================================

-- Atualizar visualizações das notícias baseado nas estatísticas
UPDATE noticias SET visualizacoes = (
    SELECT COUNT(*) FROM estatisticas_acesso WHERE noticia_id = noticias.id
) WHERE id IN (SELECT DISTINCT noticia_id FROM estatisticas_acesso);

-- Atualizar curtidas das notícias
UPDATE noticias SET curtidas = (
    SELECT COUNT(*) FROM curtidas_noticias 
    WHERE noticia_id = noticias.id AND tipo = 'curtida'
) WHERE id IN (SELECT DISTINCT noticia_id FROM curtidas_noticias);

-- Atualizar curtidas dos comentários
UPDATE comentarios SET curtidas = (
    SELECT COUNT(*) FROM curtidas_comentarios 
    WHERE comentario_id = comentarios.id AND tipo = 'curtida'
) WHERE id IN (SELECT DISTINCT comentario_id FROM curtidas_comentarios);

-- =============================================
-- VERIFICAÇÃO DOS DADOS INSERIDOS
-- =============================================

SELECT 'Dados inseridos com sucesso!' as status;
SELECT 
    'Usuários' as tabela, COUNT(*) as total FROM usuarios
UNION ALL
SELECT 'Categorias', COUNT(*) FROM categorias
UNION ALL
SELECT 'Tags', COUNT(*) FROM tags
UNION ALL
SELECT 'Notícias', COUNT(*) FROM noticias
UNION ALL
SELECT 'Comentários', COUNT(*) FROM comentarios
UNION ALL
SELECT 'Curtidas Notícias', COUNT(*) FROM curtidas_noticias
UNION ALL
SELECT 'Curtidas Comentários', COUNT(*) FROM curtidas_comentarios
UNION ALL
SELECT 'Estatísticas', COUNT(*) FROM estatisticas_acesso
UNION ALL
SELECT 'Newsletter', COUNT(*) FROM newsletter
UNION ALL
SELECT 'Anúncios', COUNT(*) FROM anuncios
UNION ALL
SELECT 'Configurações', COUNT(*) FROM configuracoes
UNION ALL
SELECT 'Mídias', COUNT(*) FROM midias
UNION ALL
SELECT 'Notificações', COUNT(*) FROM notificacoes;

-- =============================================
-- FIM DO SCRIPT DE DADOS DE TESTE
-- =============================================