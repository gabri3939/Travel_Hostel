<?php

require_once ROOT . '/config/conexao.php';

// Avaliacoes de hostel e de anfitriao, sempre atreladas a uma reserva paga do proprio usuario.
class avaliacaoModel {

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
                "CREATE TABLE IF NOT EXISTS hostel_avaliacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reserva_id INT NOT NULL UNIQUE,
                    hostel_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    nota TINYINT NOT NULL,
                    comentario VARCHAR(500),
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_hostel_avaliacoes_hostel (hostel_id),
                    CONSTRAINT fk_avaliacao_hostel_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id),
                    CONSTRAINT fk_avaliacao_hostel_hostel FOREIGN KEY (hostel_id) REFERENCES hostels(id),
                    CONSTRAINT fk_avaliacao_hostel_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )"
            );
            $this->conexao->exec(
                "CREATE TABLE IF NOT EXISTS anfitriao_avaliacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reserva_id INT NOT NULL UNIQUE,
                    anfitriao_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    nota TINYINT NOT NULL,
                    comentario VARCHAR(500),
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_anfitriao_avaliacoes_anfitriao (anfitriao_id),
                    CONSTRAINT fk_avaliacao_anfitriao_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id),
                    CONSTRAINT fk_avaliacao_anfitriao_anfitriao FOREIGN KEY (anfitriao_id) REFERENCES usuarios(id),
                    CONSTRAINT fk_avaliacao_anfitriao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )"
            );
        } catch (Exception $e) {
            error_log('[DB ERROR] garantirEstrutura (avaliacoes): ' . $e->getMessage());
        }
    }

    public function jaAvaliouHostel(int $reservaId): bool {
        if (!$this->conexao) {
            return false;
        }
        $stmt = $this->conexao->prepare("SELECT id FROM hostel_avaliacoes WHERE reserva_id = ?");
        $stmt->execute([$reservaId]);
        return (bool) $stmt->fetch();
    }

    public function jaAvaliouAnfitriao(int $reservaId): bool {
        if (!$this->conexao) {
            return false;
        }
        $stmt = $this->conexao->prepare("SELECT id FROM anfitriao_avaliacoes WHERE reserva_id = ?");
        $stmt->execute([$reservaId]);
        return (bool) $stmt->fetch();
    }

    // Avalia o hostel e recalcula a media/total direto na tabela hostels.
    public function avaliarHostel(int $reservaId, int $usuarioId, int $hostelId, int $nota, string $comentario): array {
        if (!$this->conexao) {
            return ['sucesso' => false, 'mensagem' => 'Banco de dados indisponivel.'];
        }
        if ($nota < 1 || $nota > 5) {
            return ['sucesso' => false, 'mensagem' => 'Escolha uma nota de 1 a 5.'];
        }
        if ($this->jaAvaliouHostel($reservaId)) {
            return ['sucesso' => false, 'mensagem' => 'Voce ja avaliou este hostel para esta reserva.'];
        }

        $comentario = mb_substr(trim($comentario), 0, 500);

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO hostel_avaliacoes (reserva_id, hostel_id, usuario_id, nota, comentario)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$reservaId, $hostelId, $usuarioId, $nota, $comentario ?: null]);

            $this->recalcularMediaHostel($hostelId);

            return ['sucesso' => true, 'mensagem' => 'Avaliacao do hostel registrada. Obrigado!'];
        } catch (Exception $e) {
            error_log('[DB ERROR] avaliarHostel: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel registrar a avaliacao.'];
        }
    }

    public function avaliarAnfitriao(int $reservaId, int $usuarioId, int $anfitriaoId, int $nota, string $comentario): array {
        if (!$this->conexao) {
            return ['sucesso' => false, 'mensagem' => 'Banco de dados indisponivel.'];
        }
        if ($nota < 1 || $nota > 5) {
            return ['sucesso' => false, 'mensagem' => 'Escolha uma nota de 1 a 5.'];
        }
        if ($this->jaAvaliouAnfitriao($reservaId)) {
            return ['sucesso' => false, 'mensagem' => 'Voce ja avaliou este anfitriao para esta reserva.'];
        }

        $comentario = mb_substr(trim($comentario), 0, 500);

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO anfitriao_avaliacoes (reserva_id, anfitriao_id, usuario_id, nota, comentario)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$reservaId, $anfitriaoId, $usuarioId, $nota, $comentario ?: null]);

            return ['sucesso' => true, 'mensagem' => 'Avaliacao do anfitriao registrada. Obrigado!'];
        } catch (Exception $e) {
            error_log('[DB ERROR] avaliarAnfitriao: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel registrar a avaliacao.'];
        }
    }

    private function recalcularMediaHostel(int $hostelId): void {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT AVG(nota) AS media, COUNT(*) AS total FROM hostel_avaliacoes WHERE hostel_id = ?"
            );
            $stmt->execute([$hostelId]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            $media = round((float) ($resultado['media'] ?? 0), 1);
            $total = (int) ($resultado['total'] ?? 0);

            $update = $this->conexao->prepare("UPDATE hostels SET avaliacao = ?, total_avaliacoes = ? WHERE id = ?");
            $update->execute([$media, $total, $hostelId]);
        } catch (Exception $e) {
            error_log('[DB ERROR] recalcularMediaHostel: ' . $e->getMessage());
        }
    }

    public function listarAvaliacoesHostel(int $hostelId): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT a.nota, a.comentario, a.criado_em, u.nome AS usuario_nome
                 FROM hostel_avaliacoes a
                 JOIN usuarios u ON u.id = a.usuario_id
                 WHERE a.hostel_id = ?
                 ORDER BY a.criado_em DESC
                 LIMIT 20"
            );
            $stmt->execute([$hostelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarAvaliacoesHostel: ' . $e->getMessage());
            return [];
        }
    }

    // Media e total de avaliacoes recebidas por um anfitriao como anfitriao (nao confundir com a
    // autoavaliacao de perfil que ja existe na tabela usuarios).
    public function resumoAnfitriao(int $anfitriaoId): array {
        if (!$this->conexao) {
            return ['media' => 0.0, 'total' => 0];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT AVG(nota) AS media, COUNT(*) AS total FROM anfitriao_avaliacoes WHERE anfitriao_id = ?"
            );
            $stmt->execute([$anfitriaoId]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'media' => round((float) ($resultado['media'] ?? 0), 1),
                'total' => (int) ($resultado['total'] ?? 0),
            ];
        } catch (Exception $e) {
            error_log('[DB ERROR] resumoAnfitriao: ' . $e->getMessage());
            return ['media' => 0.0, 'total' => 0];
        }
    }
}
