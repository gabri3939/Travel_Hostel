<?php

require_once ROOT . '/config/conexao.php';

// Mensagens enviadas pela pagina de contato, direcionadas ao painel do admin.
class contatoModel {

    private $conexao;

    public function __construct() {
        $db = new Conexao();
        $this->conexao = $db->conectar();
        $this->garantirEstrutura();
    }

    private function garantirEstrutura(): void {
        if (!$this->conexao) {
            return;
        }

        try {
            $this->conexao->exec(
                "CREATE TABLE IF NOT EXISTS mensagens_contato (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NULL,
                    nome VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    assunto VARCHAR(150) NOT NULL,
                    mensagem VARCHAR(1000) NOT NULL,
                    status ENUM('nova', 'lida', 'respondida') NOT NULL DEFAULT 'nova',
                    resposta VARCHAR(2000),
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    respondido_em TIMESTAMP NULL,
                    INDEX idx_mensagens_status (status),
                    CONSTRAINT fk_mensagem_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
                )"
            );
        } catch (Exception $e) {
            error_log('[DB ERROR] garantirEstrutura (contato): ' . $e->getMessage());
        }
    }

    public function enviarMensagem(?int $usuarioId, string $nome, string $email, string $assunto, string $mensagem): array {
        if (!$this->conexao) {
            return ['sucesso' => false, 'mensagem' => 'Banco de dados indisponivel.'];
        }

        $nome = trim($nome);
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        $assunto = trim($assunto);
        $mensagem = trim($mensagem);

        if ($nome === '' || !$email || $assunto === '' || $mensagem === '') {
            return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos corretamente.'];
        }
        if (strlen($mensagem) < 10) {
            return ['sucesso' => false, 'mensagem' => 'Conte um pouco mais na mensagem (minimo 10 caracteres).'];
        }

        // Limite simples anti-spam: uma mensagem a cada 60 segundos por sessao.
        if (!empty($_SESSION['ultima_mensagem_contato']) && time() - $_SESSION['ultima_mensagem_contato'] < 60) {
            return ['sucesso' => false, 'mensagem' => 'Aguarde um instante antes de enviar outra mensagem.'];
        }

        $nome = mb_substr($nome, 0, 100);
        $assunto = mb_substr($assunto, 0, 150);
        $mensagem = mb_substr($mensagem, 0, 1000);

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO mensagens_contato (usuario_id, nome, email, assunto, mensagem)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$usuarioId, $nome, $email, $assunto, $mensagem]);
            $_SESSION['ultima_mensagem_contato'] = time();

            return ['sucesso' => true, 'mensagem' => 'Mensagem enviada! A administração vai analisar e pode responder no seu e-mail.'];
        } catch (Exception $e) {
            error_log('[DB ERROR] enviarMensagem: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel enviar sua mensagem. Tente novamente.'];
        }
    }

    public function listarMensagens(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            return $this->conexao->query(
                "SELECT * FROM mensagens_contato ORDER BY (status = 'nova') DESC, criado_em DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarMensagens: ' . $e->getMessage());
            return [];
        }
    }

    public function contarNaoLidas(): int {
        if (!$this->conexao) {
            return 0;
        }

        try {
            return (int) $this->conexao->query("SELECT COUNT(*) FROM mensagens_contato WHERE status = 'nova'")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    // Mensagens enviadas pelo proprio usuario logado, para ele acompanhar se ja foi respondido.
    public function listarMensagensDoUsuario(int $usuarioId): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT * FROM mensagens_contato WHERE usuario_id = ? ORDER BY criado_em DESC"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarMensagensDoUsuario: ' . $e->getMessage());
            return [];
        }
    }

    public function getMensagem(int $id): ?array {
        if (!$this->conexao || $id < 1) {
            return null;
        }

        $stmt = $this->conexao->prepare("SELECT * FROM mensagens_contato WHERE id = ?");
        $stmt->execute([$id]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        return $msg ?: null;
    }

    public function marcarComoLida(int $id): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare("UPDATE mensagens_contato SET status = 'lida' WHERE id = ? AND status = 'nova'");
            $stmt->execute([$id]);
            return true;
        } catch (Exception $e) {
            error_log('[DB ERROR] marcarComoLida: ' . $e->getMessage());
            return false;
        }
    }

    public function responderMensagem(int $id, string $resposta): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        $resposta = mb_substr(trim($resposta), 0, 2000);
        if ($resposta === '') {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE mensagens_contato SET status = 'respondida', resposta = ?, respondido_em = NOW() WHERE id = ?"
            );
            $stmt->execute([$resposta, $id]);
            return true;
        } catch (Exception $e) {
            error_log('[DB ERROR] responderMensagem: ' . $e->getMessage());
            return false;
        }
    }
}
