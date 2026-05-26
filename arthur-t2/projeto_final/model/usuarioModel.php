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

        // Inicializa Cloudinary com credenciais do .env
        $this->inicializarCloudinary();
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

    // Valida credenciais do usuario e retorna informacoes da sessao.
    public function login(array $dados): array {
        $email = filter_var(trim($dados['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $dados['password'] ?? '';

        if (!$email || empty($password)) {
            return ['sucesso' => false, 'mensagem' => 'Preencha email e senha.'];
        }

        if ($this->conexao) {
            $stmt = $this->conexao->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($dados['password'], $usuario['senha'])) {
                return [
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso!',
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email']
                ];
            }
        }

        return ['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.'];
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
