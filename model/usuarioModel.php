<?php

require_once ROOT . '/config/conexao.php';

// Modelo de usuario que faz operacoes de banco e validacoes basicas.
class usuarioModel {

    private $conexao;
    private $cloudinary;

    public function __construct() {
        // Conecta ao banco ao criar o modelo.
        $db = new Conexao();
        $this->conexao = $db->conectar();
        $this->garantirColunaAtivo();
        $this->garantirEstruturaHostels();

        // Inicializa Cloudinary com credenciais do .env
        $this->inicializarCloudinary();
    }

    private function garantirColunaAtivo(): void {
        if (!$this->conexao) {
            return;
        }

        try {
            $stmt = $this->conexao->query("SHOW COLUMNS FROM usuarios LIKE 'ativo'");
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conexao->exec("ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER nivel");
            }
        } catch (Exception $e) {
            error_log('[DB ERROR] garantirColunaAtivo: ' . $e->getMessage());
        }
    }

    private function garantirEstruturaHostels(): void {
        if (!$this->conexao) {
            return;
        }

        try {
            $colunas = [
                'anfitriao_id' => "ALTER TABLE hostels ADD COLUMN anfitriao_id INT NULL AFTER palavras_chave",
                'status_aprovacao' => "ALTER TABLE hostels ADD COLUMN status_aprovacao ENUM('aprovado', 'pendente', 'rejeitado') NOT NULL DEFAULT 'aprovado' AFTER anfitriao_id",
                'motivo_rejeicao' => "ALTER TABLE hostels ADD COLUMN motivo_rejeicao VARCHAR(500) AFTER status_aprovacao",
                'ativo' => "ALTER TABLE hostels ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER motivo_rejeicao",
            ];
            foreach ($colunas as $nome => $sql) {
                $stmt = $this->conexao->query("SHOW COLUMNS FROM hostels LIKE '{$nome}'");
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->conexao->exec($sql);
                }
            }
            $this->conexao->exec(
                "CREATE TABLE IF NOT EXISTS hostel_imagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hostel_id INT NOT NULL,
                    caminho VARCHAR(255) NOT NULL,
                    principal TINYINT(1) NOT NULL DEFAULT 0,
                    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            );
        } catch (Exception $e) {
            error_log('[DB ERROR] garantirEstruturaHostels: ' . $e->getMessage());
        }
    }

    // Inicializa configuracao do Cloudinary
    private function inicializarCloudinary() {
        try {
            if (!class_exists('Cloudinary\\Configuration\\Configuration') || !class_exists('Cloudinary\\Api\\Upload\\UploadApi')) {
                error_log('[CLOUDINARY ERROR] SDK do Cloudinary nao encontrado em vendor/.');
                $this->cloudinary = null;
                return;
            }

            $cloudinaryUrl = getenv('CLOUDINARY_URL');
            $cloudinarySecret = getenv('CLOUDINARY_SECRET');
            $cloudinaryCloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: 'arthur-t2';

            \Cloudinary\Configuration\Configuration::instance("cloudinary://{$cloudinaryUrl}:{$cloudinarySecret}@{$cloudinaryCloudName}");
            $this->cloudinary = new \Cloudinary\Api\Upload\UploadApi();
        } catch (Exception $e) {
            error_log('[CLOUDINARY ERROR] Falha ao inicializar: ' . $e->getMessage());
        }
    }
public function atualizarSenha(string $email, string $novaSenha): bool {
    if (!$this->conexao) {
        return false;
    }

    try {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->conexao->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        return $stmt->execute([$hash, $email]);
    } catch (Exception $e) {
        error_log('[DB ERROR] atualizarSenha: ' . $e->getMessage());
        return false;
    }
}
    // Upload de foto do usuario para Cloudinary
    public function uploadFotoUsuario(string $email, string $caminhoArquivo): array {
        if (!file_exists($caminhoArquivo)) {
            return ['sucesso' => false, 'mensagem' => 'Arquivo nao encontrado.'];
        }

        try {
            if (!$this->cloudinary) {
                return ['sucesso' => false, 'mensagem' => 'Cloudinary nao configurado.'];
            }

            // Extrai informacoes do usuario para usar como pasta no Cloudinary
            $usuario = $this->getUsuarioByEmail($email);
            if (!$usuario) {
                return ['sucesso' => false, 'mensagem' => 'Usuario nao encontrado.'];
            }

            $usuarioId = $usuario['id'];
            $publicId = "usuarios/usuario_{$usuarioId}_foto";

            // Faz upload para Cloudinary com transformacoes
            $response = $this->cloudinary->upload($caminhoArquivo, [
                'public_id' => $publicId,
                'folder' => 'travel-hostel/usuarios',
                'resource_type' => 'auto',
                'quality' => 'auto',
                'transformation' => [
                    ['width' => 500, 'height' => 500, 'crop' => 'fill', 'gravity' => 'face']
                ]
            ]);

            $fotoUrl = $response['secure_url'];

            // Atualiza URL da foto no banco de dados
            if ($this->atualizarAvatar($email, $fotoUrl)) {
                return [
                    'sucesso' => true,
                    'mensagem' => 'Foto enviada com sucesso!',
                    'url' => $fotoUrl,
                    'public_id' => $response['public_id']
                ];
            }

            return ['sucesso' => false, 'mensagem' => 'Falha ao salvar URL da foto no banco.'];
        } catch (Exception $e) {
            error_log('[CLOUDINARY UPLOAD ERROR] ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Erro ao fazer upload: ' . $e->getMessage()];
        }
    }

    // Insere novo usuario com validacoes basicas.
    public function cadastrar(array $dados): array {
        $nome = trim($dados['name'] ?? '');
        $email = filter_var(trim($dados['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $dados['password'] ?? '';
        $confirmPassword = $dados['confirmPassword'] ?? $password;

        if (empty($nome) || empty($email) || empty($password)) {
            return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos obrigatorios.'];
        }
        if (!$email) {
            return ['sucesso' => false, 'mensagem' => 'Informe um email valido.'];
        }
        if ($password !== $confirmPassword) {
            return ['sucesso' => false, 'mensagem' => 'As senhas nao coincidem.'];
        }
        if (strlen($password) < 8) {
            return ['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 8 caracteres.'];
        }

        if ($this->conexao) {
            // Verifica se o email ja existe no banco.
            $stmt = $this->conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['sucesso' => false, 'mensagem' => 'Este email ja esta cadastrado.'];
            }

            $hash = password_hash($dados['password'], PASSWORD_DEFAULT);
            $stmt = $this->conexao->prepare(
                "INSERT INTO usuarios (nome, email, cpf, telefone, data_nascimento, senha)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $dados['name'],
                $dados['email'],
                $dados['cpf'] ?? null,
                $dados['phone'] ?? null,
                $dados['birthDate'] ?? null,
                $hash
            ]);
        }

        return [
            'sucesso' => true,
            'mensagem' => 'Cadastro realizado! Bem-vindo(a), ' . htmlspecialchars($dados['name']) . '!',
            'nome' => htmlspecialchars($dados['name']),
            'email' => htmlspecialchars($dados['email'])
        ];
    }

    // Verifica no banco se o email informado ja esta registrado.
    public function emailExiste(string $email): bool {
        if (!$this->conexao) {
            return false;
        }

        $stmt = $this->conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        return (bool) $stmt->fetch();
    }

    private function getLoginAttemptKey(string $email): string {
        return 'login_attempts_' . md5(strtolower(trim($email)));
    }

    private function getLoginBlockedKey(string $email): string {
        return 'login_block_' . md5(strtolower(trim($email)));
    }

    // Valida credenciais do usuario e retorna informacoes da sessao.
    public function login(array $dados): array {
        $email = filter_var(trim((string) ($dados['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($dados['password'] ?? '');

        if (!$email || $password === '') {
            return ['sucesso' => false, 'mensagem' => 'Preencha email e senha.'];
        }

        $attemptKey = $this->getLoginAttemptKey($email);
        $blockKey = $this->getLoginBlockedKey($email);

        if (!empty($_SESSION[$blockKey]) && (int) $_SESSION[$blockKey] > time()) {
            return ['sucesso' => false, 'mensagem' => 'Conta temporariamente bloqueada apos 5 tentativas falhas. Tente novamente em 15 minutos.'];
        }

        if (!empty($_SESSION[$blockKey]) && (int) $_SESSION[$blockKey] <= time()) {
            unset($_SESSION[$blockKey], $_SESSION[$attemptKey]);
        }

        if ($this->conexao) {
            $stmt = $this->conexao->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($password, $usuario['senha'])) {
                if (array_key_exists('ativo', $usuario) && !(int) $usuario['ativo']) {
                    return ['sucesso' => false, 'mensagem' => 'Esta conta esta inativa. Entre em contato com o administrador.'];
                }

                unset($_SESSION[$attemptKey], $_SESSION[$blockKey]);

                return [
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso!',
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email']
                ];
            }
        }

        $attempts = (int) ($_SESSION[$attemptKey] ?? 0) + 1;
        $_SESSION[$attemptKey] = $attempts;

        if ($attempts >= 5) {
            $_SESSION[$blockKey] = time() + 900;
            unset($_SESSION[$attemptKey]);
            return ['sucesso' => false, 'mensagem' => 'Conta temporariamente bloqueada apos 5 tentativas falhas. Tente novamente em 15 minutos.'];
        }

        return ['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.'];
    }

    public function listarUsuarios(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT id, nome, email, cpf, telefone, nivel, ativo, data_cadastro
                 FROM usuarios ORDER BY data_cadastro DESC, id DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarUsuarios: ' . $e->getMessage());
            return [];
        }
    }

    public function atualizarUsuario(int $id, array $dados): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        $nome = trim($dados['nome'] ?? '');
        $email = filter_var(trim($dados['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $nivel = $dados['nivel'] ?? 'usuario';

        if ($nome === '' || !$email || !in_array($nivel, ['usuario', 'anfitriao', 'admin'], true)) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, cpf = ?, telefone = ?, nivel = ? WHERE id = ?"
            );
            return $stmt->execute([
                $nome,
                $email,
                trim($dados['cpf'] ?? '') ?: null,
                trim($dados['telefone'] ?? '') ?: null,
                $nivel,
                $id
            ]);
        } catch (Exception $e) {
            error_log('[DB ERROR] atualizarUsuario: ' . $e->getMessage());
            return false;
        }
    }

    public function alterarStatusUsuario(int $id, bool $ativo): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
            return $stmt->execute([$ativo ? 1 : 0, $id]);
        } catch (Exception $e) {
            error_log('[DB ERROR] alterarStatusUsuario: ' . $e->getMessage());
            return false;
        }
    }

    public function solicitarAnfitriao(int $usuarioId): bool {
        if (!$this->conexao || $usuarioId < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE usuarios
                 SET solicitacao_anfitriao = 'pendente', motivo_solicitacao = NULL
                 WHERE id = ? AND nivel = 'usuario' AND ativo = 1
                   AND solicitacao_anfitriao <> 'pendente'"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[DB ERROR] solicitarAnfitriao: ' . $e->getMessage());
            return false;
        }
    }

    public function listarSolicitacoesAnfitriao(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT id, nome, email, cpf, telefone, data_cadastro
                 FROM usuarios
                 WHERE nivel = 'usuario' AND ativo = 1 AND solicitacao_anfitriao = 'pendente'
                 ORDER BY data_cadastro ASC, id ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarSolicitacoesAnfitriao: ' . $e->getMessage());
            return [];
        }
    }

    public function decidirSolicitacaoAnfitriao(int $usuarioId, string $decisao, string $motivo = ''): bool {
        if (!$this->conexao || $usuarioId < 1 || !in_array($decisao, ['aprovada', 'rejeitada'], true)) {
            return false;
        }

        try {
            $nivel = $decisao === 'aprovada' ? 'anfitriao' : 'usuario';
            $stmt = $this->conexao->prepare(
                "UPDATE usuarios
                 SET nivel = ?, solicitacao_anfitriao = ?, motivo_solicitacao = ?
                 WHERE id = ? AND solicitacao_anfitriao = 'pendente'"
            );
            $stmt->execute([$nivel, $decisao, $decisao === 'rejeitada' ? trim($motivo) : null, $usuarioId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[DB ERROR] decidirSolicitacaoAnfitriao: ' . $e->getMessage());
            return false;
        }
    }

    public function listarCategorias(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            return $this->conexao->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarCategorias: ' . $e->getMessage());
            return [];
        }
    }

    public function cadastrarSolicitacaoHostel(int $anfitriaoId, array $dados): ?int {
        if (!$this->conexao || $anfitriaoId < 1) {
            return null;
        }

        $nome = trim($dados['nome'] ?? '');
        $cidade = trim($dados['cidade'] ?? '');
        $descricao = trim($dados['descricao'] ?? '');
        $descricao = function_exists('mb_substr') ? mb_substr($descricao, 0, 500) : substr($descricao, 0, 500);
        $preco = (float) str_replace(',', '.', trim($dados['preco_diaria'] ?? '0'));
        if ($nome === '' || $cidade === '' || $preco <= 0) {
            return null;
        }

        $slugBase = trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nome)), '-');
        $slug = strtolower($slugBase ?: 'recinto');
        $slug .= '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO hostels
                 (nome, slug, cidade, estado, pais, descricao, preco_diaria, comodidades, camas, tipo, categoria_id, palavras_chave, anfitriao_id, status_aprovacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')"
            );
            $stmt->execute([
                $nome,
                $slug,
                $cidade,
                trim($dados['estado'] ?? '') ?: null,
                trim($dados['pais'] ?? 'Brasil') ?: 'Brasil',
                $descricao,
                $preco,
                trim($dados['comodidades'] ?? ''),
                max(0, (int) ($dados['camas'] ?? 0)),
                trim($dados['tipo'] ?? 'Dormitorio') ?: 'Dormitorio',
                (int) ($dados['categoria_id'] ?? 0) ?: null,
                trim($dados['palavras_chave'] ?? ''),
                $anfitriaoId,
            ]);
            return (int) $this->conexao->lastInsertId();
        } catch (Exception $e) {
            error_log('[DB ERROR] cadastrarSolicitacaoHostel: ' . $e->getMessage());
            return null;
        }
    }

    public function salvarImagemHostel(int $hostelId, string $caminho, bool $principal = false): bool {
        if (!$this->conexao || $hostelId < 1 || $caminho === '') {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO hostel_imagens (hostel_id, caminho, principal) VALUES (?, ?, ?)"
            );
            $stmt->execute([$hostelId, $caminho, $principal ? 1 : 0]);
            if ($principal) {
                $stmt = $this->conexao->prepare("UPDATE hostels SET imagem_url = ? WHERE id = ?");
                $stmt->execute([$caminho, $hostelId]);
            }
            return true;
        } catch (Exception $e) {
            error_log('[DB ERROR] salvarImagemHostel: ' . $e->getMessage());
            return false;
        }
    }

    public function listarSolicitacoesHostel(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT h.*, u.nome AS anfitriao_nome, u.email AS anfitriao_email, c.nome AS categoria_nome
                 FROM hostels h
                 LEFT JOIN usuarios u ON u.id = h.anfitriao_id
                 LEFT JOIN categorias c ON c.id = h.categoria_id
                 WHERE h.status_aprovacao = 'pendente'
                 ORDER BY h.data_cadastro ASC, h.id ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarSolicitacoesHostel: ' . $e->getMessage());
            return [];
        }
    }

    public function listarImagensHostel(int $hostelId): array {
        if (!$this->conexao || $hostelId < 1) {
            return [];
        }

        $stmt = $this->conexao->prepare("SELECT caminho FROM hostel_imagens WHERE hostel_id = ? ORDER BY principal DESC, id ASC");
        $stmt->execute([$hostelId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function atualizarAprovacaoHostel(int $hostelId, string $status, string $motivo = ''): bool {
        if (!$this->conexao || !in_array($status, ['aprovado', 'rejeitado'], true)) {
            return false;
        }

        $stmt = $this->conexao->prepare(
            "UPDATE hostels SET status_aprovacao = ?, motivo_rejeicao = ? WHERE id = ? AND status_aprovacao = 'pendente'"
        );
        return $stmt->execute([$status, $status === 'rejeitado' ? trim($motivo) : null, $hostelId]);
    }

    // Lista os recintos que pertencem a um anfitriao, para o painel dele gerenciar.
    public function listarHostelsDoAnfitriao(int $anfitriaoId): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT h.*, c.nome AS categoria_nome
                 FROM hostels h
                 LEFT JOIN categorias c ON c.id = h.categoria_id
                 WHERE h.anfitriao_id = ?
                 ORDER BY h.data_cadastro DESC"
            );
            $stmt->execute([$anfitriaoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarHostelsDoAnfitriao: ' . $e->getMessage());
            return [];
        }
    }

    // Atualiza um recinto, restrito ao proprio anfitriao dono dele.
    // Se o recinto havia sido rejeitado, a edicao o reenvia para analise do admin.
    public function atualizarHostelAnfitriao(int $hostelId, int $anfitriaoId, array $dados): bool {
        if (!$this->conexao || $hostelId < 1) {
            return false;
        }

        $nome = trim($dados['nome'] ?? '');
        $cidade = trim($dados['cidade'] ?? '');
        $preco = (float) str_replace(',', '.', trim($dados['preco_diaria'] ?? '0'));

        if ($nome === '' || $cidade === '' || $preco <= 0) {
            return false;
        }

        $descricao = trim($dados['descricao'] ?? '');
        $descricao = function_exists('mb_substr') ? mb_substr($descricao, 0, 500) : substr($descricao, 0, 500);

        // Confirma a posse antes de atualizar. Feito separado do UPDATE porque rowCount() do
        // MySQL conta linhas alteradas, nao linhas encontradas: salvar sem mudar nada retornaria
        // 0 e seria confundido com "recinto nao encontrado".
        $verifica = $this->conexao->prepare("SELECT id FROM hostels WHERE id = ? AND anfitriao_id = ?");
        $verifica->execute([$hostelId, $anfitriaoId]);
        if (!$verifica->fetch()) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE hostels
                 SET nome = ?, cidade = ?, estado = ?, pais = ?, descricao = ?, preco_diaria = ?,
                     comodidades = ?, camas = ?, tipo = ?, categoria_id = ?, palavras_chave = ?,
                     status_aprovacao = CASE WHEN status_aprovacao = 'rejeitado' THEN 'pendente' ELSE status_aprovacao END,
                     motivo_rejeicao = CASE WHEN status_aprovacao = 'rejeitado' THEN NULL ELSE motivo_rejeicao END
                 WHERE id = ? AND anfitriao_id = ?"
            );
            $stmt->execute([
                $nome,
                $cidade,
                trim($dados['estado'] ?? '') ?: null,
                trim($dados['pais'] ?? 'Brasil') ?: 'Brasil',
                $descricao,
                $preco,
                trim($dados['comodidades'] ?? ''),
                max(0, (int) ($dados['camas'] ?? 0)),
                trim($dados['tipo'] ?? 'Dormitorio') ?: 'Dormitorio',
                (int) ($dados['categoria_id'] ?? 0) ?: null,
                trim($dados['palavras_chave'] ?? ''),
                $hostelId,
                $anfitriaoId,
            ]);
            return true;
        } catch (Exception $e) {
            error_log('[DB ERROR] atualizarHostelAnfitriao: ' . $e->getMessage());
            return false;
        }
    }

    // Ativa/desativa um recinto do proprio anfitriao (remocao logica, preserva reservas e avaliacoes ja feitas).
    public function alternarStatusHostelAnfitriao(int $hostelId, int $anfitriaoId, bool $ativo): bool {
        if (!$this->conexao || $hostelId < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE hostels SET ativo = ? WHERE id = ? AND anfitriao_id = ?"
            );
            $stmt->execute([$ativo ? 1 : 0, $hostelId, $anfitriaoId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[DB ERROR] alternarStatusHostelAnfitriao: ' . $e->getMessage());
            return false;
        }
    }

    // Lista todos os recintos da plataforma (qualquer status/anfitriao), para o admin.
    public function listarTodosHostels(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT h.*, c.nome AS categoria_nome, a.nome AS anfitriao_nome, a.email AS anfitriao_email
                 FROM hostels h
                 LEFT JOIN categorias c ON c.id = h.categoria_id
                 LEFT JOIN usuarios a ON a.id = h.anfitriao_id
                 ORDER BY h.data_cadastro DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarTodosHostels: ' . $e->getMessage());
            return [];
        }
    }

    // Edicao de qualquer recinto pelo admin (sem restricao de dono).
    public function atualizarHostelAdmin(int $hostelId, array $dados): bool {
        if (!$this->conexao || $hostelId < 1) {
            return false;
        }

        $nome = trim($dados['nome'] ?? '');
        $cidade = trim($dados['cidade'] ?? '');
        $preco = (float) str_replace(',', '.', trim($dados['preco_diaria'] ?? '0'));

        if ($nome === '' || $cidade === '' || $preco <= 0) {
            return false;
        }

        $descricao = trim($dados['descricao'] ?? '');
        $descricao = function_exists('mb_substr') ? mb_substr($descricao, 0, 500) : substr($descricao, 0, 500);

        $verifica = $this->conexao->prepare("SELECT id FROM hostels WHERE id = ?");
        $verifica->execute([$hostelId]);
        if (!$verifica->fetch()) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE hostels
                 SET nome = ?, cidade = ?, estado = ?, pais = ?, descricao = ?, preco_diaria = ?,
                     comodidades = ?, camas = ?, tipo = ?, categoria_id = ?, palavras_chave = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $nome,
                $cidade,
                trim($dados['estado'] ?? '') ?: null,
                trim($dados['pais'] ?? 'Brasil') ?: 'Brasil',
                $descricao,
                $preco,
                trim($dados['comodidades'] ?? ''),
                max(0, (int) ($dados['camas'] ?? 0)),
                trim($dados['tipo'] ?? 'Dormitorio') ?: 'Dormitorio',
                (int) ($dados['categoria_id'] ?? 0) ?: null,
                trim($dados['palavras_chave'] ?? ''),
                $hostelId,
            ]);
            return true;
        } catch (Exception $e) {
            error_log('[DB ERROR] atualizarHostelAdmin: ' . $e->getMessage());
            return false;
        }
    }

    public function alternarStatusHostelAdmin(int $hostelId, bool $ativo): bool {
        if (!$this->conexao || $hostelId < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare("UPDATE hostels SET ativo = ? WHERE id = ?");
            $stmt->execute([$ativo ? 1 : 0, $hostelId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[DB ERROR] alternarStatusHostelAdmin: ' . $e->getMessage());
            return false;
        }
    }

    // Exclui o recinto de vez. So permite quando nao ha reserva ou avaliacao vinculada a ele,
    // para nunca apagar historico financeiro; nesses casos o admin deve desativar em vez de excluir.
    public function excluirHostel(int $hostelId): array {
        if (!$this->conexao || $hostelId < 1) {
            return ['sucesso' => false, 'mensagem' => 'Recinto invalido.'];
        }

        try {
            $stmtReservas = $this->conexao->prepare("SELECT COUNT(*) FROM reservas WHERE hostel_id = ?");
            $stmtReservas->execute([$hostelId]);
            if ((int) $stmtReservas->fetchColumn() > 0) {
                return ['sucesso' => false, 'mensagem' => 'Este recinto tem reservas no historico e nao pode ser excluido. Desative-o em vez de excluir.'];
            }

            $stmtAvaliacoes = $this->conexao->prepare("SELECT COUNT(*) FROM hostel_avaliacoes WHERE hostel_id = ?");
            $stmtAvaliacoes->execute([$hostelId]);
            if ((int) $stmtAvaliacoes->fetchColumn() > 0) {
                return ['sucesso' => false, 'mensagem' => 'Este recinto tem avaliacoes no historico e nao pode ser excluido. Desative-o em vez de excluir.'];
            }

            $imagens = $this->listarImagensHostel($hostelId);

            $this->conexao->beginTransaction();
            $this->conexao->prepare("DELETE FROM hostel_imagens WHERE hostel_id = ?")->execute([$hostelId]);
            $stmtDel = $this->conexao->prepare("DELETE FROM hostels WHERE id = ?");
            $stmtDel->execute([$hostelId]);
            $excluiu = $stmtDel->rowCount() > 0;
            $this->conexao->commit();

            if ($excluiu) {
                foreach ($imagens as $caminho) {
                    $caminhoFisico = ROOT . '/' . ltrim($caminho, '/');
                    if (is_file($caminhoFisico)) {
                        @unlink($caminhoFisico);
                    }
                }
                return ['sucesso' => true, 'mensagem' => 'Recinto excluido permanentemente.'];
            }

            return ['sucesso' => false, 'mensagem' => 'Recinto nao encontrado.'];
        } catch (Exception $e) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            error_log('[DB ERROR] excluirHostel: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel excluir o recinto.'];
        }
    }

    // Retorna os dados completos do usuario por email
    public function getUsuarioByEmail(string $email): ?array {
        if (!$this->conexao) {
            return null;
        }

        $stmt = $this->conexao->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario ?: null;
    }

    // Atualiza o caminho do avatar do usuario
    public function atualizarAvatar(string $email, string $caminho): bool {
        if (!$this->conexao) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare("UPDATE usuarios SET avatar = ? WHERE email = ?");
            return $stmt->execute([$caminho, $email]);
        } catch (Exception $e) {
            error_log('[DB ERROR] atualizarAvatar: ' . $e->getMessage());
            return false;
        }
    }

    // Registra uma avaliacao de usuario e atualiza a nota media.
    public function registrarAvaliacao(string $email, int $nota): bool {
        if (!$this->conexao) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare("SELECT avaliacao, total_avaliacoes FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                return false;
            }

            $totalAtual = (int) $usuario['total_avaliacoes'];
            $mediaAtual = (float) $usuario['avaliacao'];
            $novoTotal = $totalAtual + 1;
            $novaMedia = $novoTotal > 0 ? round((($mediaAtual * $totalAtual) + $nota) / $novoTotal, 1) : $nota;

            $stmt = $this->conexao->prepare("UPDATE usuarios SET avaliacao = ?, total_avaliacoes = ? WHERE email = ?");
            return $stmt->execute([$novaMedia, $novoTotal, $email]);
        } catch (Exception $e) {
            error_log('[DB ERROR] registrarAvaliacao: ' . $e->getMessage());
            return false;
        }
    }
}
