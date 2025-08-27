-- Importar dados em ordem correta

-- Primeiro os usuários
INSERT INTO usuarios (nome, email, senha, data_nascimento, genero, cidade, estado, foto_perfil) VALUES 
('Administrador', 'admin@portal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1985-03-15', 'masculino', 'São Paulo', 'SP', '/uploads/usuarios/admin.jpg'),
('João Silva', 'joao.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1990-07-22', 'masculino', 'Rio de Janeiro', 'RJ', '/uploads/usuarios/joao.jpg'),
('Maria Santos', 'maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1988-11-10', 'feminino', 'Belo Horizonte', 'MG', '/uploads/usuarios/maria.jpg'),
('Carlos Oliveira', 'carlos.oliveira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1992-04-18', 'masculino', 'Porto Alegre', 'RS', NULL),
('Ana Costa', 'ana.costa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-09-03', 'feminino', 'Salvador', 'BA', NULL),
('Pedro Ferreira', 'pedro.ferreira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1987-12-25', 'masculino', 'Recife', 'PE', NULL),
('Lucia Mendes', 'lucia.mendes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1993-06-14', 'feminino', 'Fortaleza', 'CE', NULL),
('Roberto Lima', 'roberto.lima@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1991-01-30', 'masculino', 'Brasília', 'DF', NULL),
('Fernanda Rocha', 'fernanda.rocha@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1989-08-07', 'feminino', 'Curitiba', 'PR', NULL);

-- Depois as categorias
INSERT INTO categorias (nome, slug, cor) VALUES 
('Política', 'politica', '#dc3545'),
('Economia', 'economia', '#28a745'),
('Esportes', 'esportes', '#007bff'),
('Tecnologia', 'tecnologia', '#6f42c1'),
('Saúde', 'saude', '#20c997'),
('Educação', 'educacao', '#fd7e14'),
('Meio Ambiente', 'meio-ambiente', '#198754'),
('Internacional', 'internacional', '#0dcaf0'),
('Cultura', 'cultura', '#d63384'),
('Ciência', 'ciencia', '#6610f2');

-- Depois as tags
INSERT INTO tags (nome, slug, cor) VALUES 
('Urgente', 'urgente', '#dc3545'),
('Destaque', 'destaque', '#ffc107'),
('Governo', 'governo', '#6c757d'),
('Mercado', 'mercado', '#28a745'),
('Investimento', 'investimento', '#17a2b8'),
('Saúde Pública', 'saude-publica', '#20c997'),
('Política Nacional', 'politica-nacional', '#dc3545'),
('São Paulo', 'sao-paulo', '#007bff'),
('Inovação', 'inovacao', '#6f42c1'),
('Pesquisa', 'pesquisa', '#fd7e14'),
('Tecnologia', 'tecnologia', '#6f42c1'),
('Economia', 'economia', '#28a745'),
('Copa do Mundo', 'copa-mundo', '#28a745'),
('Futebol', 'futebol', '#007bff'),
('Inteligência Artificial', 'inteligencia-artificial', '#6f42c1'),
('Smartphone', 'smartphone', '#17a2b8'),
('Apple', 'apple', '#6c757d'),
('Cinema', 'cinema', '#d63384'),
('Festival', 'festival', '#ffc107'),
('Gramado', 'gramado', '#198754');