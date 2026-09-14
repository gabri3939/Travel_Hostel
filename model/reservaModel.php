<?php

require_once ROOT . '/config/conexao.php';

// Modelo de reservas: criacao, consulta e atualizacao de status de pagamento.
class reservaModel {

    // Percentual retido pela plataforma em cada reserva paga (14% a 16% combinado; 15% e o valor padrao).
    public const TAXA_PLATAFORMA_PADRAO = 15.00;

    private $conexao;

    public function __construct() {
        $db = new Conexao();
        $this->conexao = $db->conectar();
        $this->garantirEstruturaReservas();
    }

    private function garantirEstruturaReservas(): void {
        if (!$this->conexao) {
            return;
        }

        try {
            $this->conexao->exec(
                "CREATE TABLE IF NOT EXISTS reservas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    hostel_id INT NOT NULL,
                    data_checkin DATE NOT NULL,
                    data_checkout DATE NOT NULL,
                    noites INT NOT NULL,
                    hospedes INT NOT NULL DEFAULT 1,
                    valor_total DECIMAL(10,2) NOT NULL,
                    taxa_plataforma DECIMAL(5,2) NOT NULL DEFAULT 15.00,
                    valor_plataforma DECIMAL(10,2) NOT NULL DEFAULT 0,
                    valor_anfitriao DECIMAL(10,2) NOT NULL DEFAULT 0,
                    status ENUM('pendente','em_analise','pago','recusado','cancelado') NOT NULL DEFAULT 'pendente',
                    forma_pagamento VARCHAR(20),
                    pagbank_order_id VARCHAR(60),
                    pagbank_charge_id VARCHAR(60),
                    pagbank_status VARCHAR(30),
                    anfitriao_notificado TINYINT(1) NOT NULL DEFAULT 0,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_reservas_usuario (usuario_id),
                    INDEX idx_reservas_order (pagbank_order_id),
                    CONSTRAINT fk_reserva_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                    CONSTRAINT fk_reserva_hostel FOREIGN KEY (hostel_id) REFERENCES hostels(id)
                )"
            );

            $colunas = [
                'forma_pagamento' => "ALTER TABLE reservas ADD COLUMN forma_pagamento VARCHAR(20) AFTER status",
                'anfitriao_notificado' => "ALTER TABLE reservas ADD COLUMN anfitriao_notificado TINYINT(1) NOT NULL DEFAULT 0 AFTER pagbank_status",
                'taxa_plataforma' => "ALTER TABLE reservas ADD COLUMN taxa_plataforma DECIMAL(5,2) NOT NULL DEFAULT 15.00 AFTER valor_total",
                'valor_plataforma' => "ALTER TABLE reservas ADD COLUMN valor_plataforma DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER taxa_plataforma",
                'valor_anfitriao' => "ALTER TABLE reservas ADD COLUMN valor_anfitriao DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER valor_plataforma",
            ];
            foreach ($colunas as $nome => $sql) {
                $stmt = $this->conexao->query("SHOW COLUMNS FROM reservas LIKE '{$nome}'");
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->conexao->exec($sql);
                }
            }
        } catch (Exception $e) {
            error_log('[DB ERROR] garantirEstruturaReservas: ' . $e->getMessage());
        }
    }

    // Cria a reserva com status pendente e calcula o valor total a partir do preco vigente do hostel.
    public function criarReserva(int $usuarioId, int $hostelId, string $checkin, string $checkout, int $hospedes): array {
        if (!$this->conexao) {
            return ['sucesso' => false, 'mensagem' => 'Banco de dados indisponivel.'];
        }

        try {
            $dtCheckin = new DateTimeImmutable($checkin);
            $dtCheckout = new DateTimeImmutable($checkout);
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Datas invalidas.'];
        }

        $hoje = new DateTimeImmutable('today');
        if ($dtCheckin < $hoje) {
            return ['sucesso' => false, 'mensagem' => 'A data de check-in nao pode ser no passado.'];
        }
        if ($dtCheckout <= $dtCheckin) {
            return ['sucesso' => false, 'mensagem' => 'A data de check-out deve ser depois do check-in.'];
        }

        $noites = $dtCheckout->diff($dtCheckin)->days;
        if ($noites > 60) {
            return ['sucesso' => false, 'mensagem' => 'O periodo maximo de reserva e 60 noites.'];
        }
        if ($hospedes < 1 || $hospedes > 20) {
            return ['sucesso' => false, 'mensagem' => 'Numero de hospedes invalido.'];
        }

        $stmtHostel = $this->conexao->prepare(
            "SELECT preco_diaria FROM hostels WHERE id = ? AND status_aprovacao = 'aprovado' AND ativo = 1"
        );
        $stmtHostel->execute([$hostelId]);
        $hostel = $stmtHostel->fetch(PDO::FETCH_ASSOC);
        if (!$hostel) {
            return ['sucesso' => false, 'mensagem' => 'Hostel nao encontrado ou indisponivel.'];
        }

        $valorTotal = round((float) $hostel['preco_diaria'] * $noites, 2);
        $taxaPlataforma = self::TAXA_PLATAFORMA_PADRAO;
        $valorPlataforma = round($valorTotal * $taxaPlataforma / 100, 2);
        $valorAnfitriao = round($valorTotal - $valorPlataforma, 2);

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO reservas (usuario_id, hostel_id, data_checkin, data_checkout, noites, hospedes, valor_total, taxa_plataforma, valor_plataforma, valor_anfitriao, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')"
            );
            $stmt->execute([
                $usuarioId,
                $hostelId,
                $dtCheckin->format('Y-m-d'),
                $dtCheckout->format('Y-m-d'),
                $noites,
                $hospedes,
                $valorTotal,
                $taxaPlataforma,
                $valorPlataforma,
                $valorAnfitriao,
            ]);

            return ['sucesso' => true, 'id' => (int) $this->conexao->lastInsertId()];
        } catch (Exception $e) {
            error_log('[DB ERROR] criarReserva: ' . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel criar a reserva.'];
        }
    }

    // Retorna a reserva com dados do hostel, garantindo que pertence ao usuario informado.
    public function getReservaCompleta(int $id, int $usuarioId): ?array {
        if (!$this->conexao || $id < 1) {
            return null;
        }

        $stmt = $this->conexao->prepare(
            "SELECT r.*, h.nome AS hostel_nome, h.slug AS hostel_slug, h.cidade, h.estado, h.imagem_url
             FROM reservas r
             JOIN hostels h ON h.id = r.hostel_id
             WHERE r.id = ? AND r.usuario_id = ?"
        );
        $stmt->execute([$id, $usuarioId]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reserva ?: null;
    }

    public function getReservaPorOrderId(string $orderId): ?array {
        if (!$this->conexao || $orderId === '') {
            return null;
        }

        $stmt = $this->conexao->prepare("SELECT * FROM reservas WHERE pagbank_order_id = ?");
        $stmt->execute([$orderId]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reserva ?: null;
    }

    public function atualizarPagamento(int $id, string $status, ?string $orderId = null, ?string $chargeId = null, ?string $pagbankStatus = null, ?string $formaPagamento = null): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE reservas
                 SET status = ?,
                     pagbank_order_id = COALESCE(?, pagbank_order_id),
                     pagbank_charge_id = COALESCE(?, pagbank_charge_id),
                     pagbank_status = COALESCE(?, pagbank_status),
                     forma_pagamento = COALESCE(?, forma_pagamento)
                 WHERE id = ?"
            );
            return $stmt->execute([$status, $orderId, $chargeId, $pagbankStatus, $formaPagamento, $id]);
        } catch (Exception $e) {
            error_log('[DB ERROR] atualizarPagamento: ' . $e->getMessage());
            return false;
        }
    }

    // Retorna true se conseguiu marcar como notificado (evita notificar o anfitriao duas vezes).
    public function marcarAnfitriaoNotificado(int $id): bool {
        if (!$this->conexao || $id < 1) {
            return false;
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE reservas SET anfitriao_notificado = 1 WHERE id = ? AND anfitriao_notificado = 0"
            );
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('[DB ERROR] marcarAnfitriaoNotificado: ' . $e->getMessage());
            return false;
        }
    }

    public function getReservaPorId(int $id): ?array {
        if (!$this->conexao || $id < 1) {
            return null;
        }

        $stmt = $this->conexao->prepare(
            "SELECT r.*, h.nome AS hostel_nome, h.slug AS hostel_slug, h.anfitriao_id,
                    u.nome AS cliente_nome, u.email AS cliente_email, u.telefone AS cliente_telefone,
                    a.nome AS anfitriao_nome, a.email AS anfitriao_email
             FROM reservas r
             JOIN hostels h ON h.id = r.hostel_id
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN usuarios a ON a.id = h.anfitriao_id
             WHERE r.id = ?"
        );
        $stmt->execute([$id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reserva ?: null;
    }

    // Reservas pagas/em analise dos recintos pertencentes a um anfitriao (para o painel dele).
    public function listarReservasDoAnfitriao(int $anfitriaoId): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT r.*, h.nome AS hostel_nome, u.nome AS cliente_nome, u.email AS cliente_email
                 FROM reservas r
                 JOIN hostels h ON h.id = r.hostel_id
                 JOIN usuarios u ON u.id = r.usuario_id
                 WHERE h.anfitriao_id = ? AND r.status IN ('pago', 'em_analise')
                 ORDER BY r.criado_em DESC"
            );
            $stmt->execute([$anfitriaoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarReservasDoAnfitriao: ' . $e->getMessage());
            return [];
        }
    }

    // Resumo financeiro da plataforma (somente reservas pagas), para o dashboard do admin.
    public function resumoFinanceiro(): array {
        if (!$this->conexao) {
            return ['total_bruto' => 0.0, 'total_plataforma' => 0.0, 'total_anfitrioes' => 0.0, 'reservas_pagas' => 0];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT
                    COALESCE(SUM(valor_total), 0) AS total_bruto,
                    COALESCE(SUM(valor_plataforma), 0) AS total_plataforma,
                    COALESCE(SUM(valor_anfitriao), 0) AS total_anfitrioes,
                    COUNT(*) AS reservas_pagas
                 FROM reservas WHERE status = 'pago'"
            );
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'total_bruto' => (float) $linha['total_bruto'],
                'total_plataforma' => (float) $linha['total_plataforma'],
                'total_anfitrioes' => (float) $linha['total_anfitrioes'],
                'reservas_pagas' => (int) $linha['reservas_pagas'],
            ];
        } catch (Exception $e) {
            error_log('[DB ERROR] resumoFinanceiro: ' . $e->getMessage());
            return ['total_bruto' => 0.0, 'total_plataforma' => 0.0, 'total_anfitrioes' => 0.0, 'reservas_pagas' => 0];
        }
    }

    // Todas as reservas da plataforma, para o admin acompanhar/auditar (nao apenas as de um usuario).
    public function listarTodasReservas(): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->query(
                "SELECT r.*, h.nome AS hostel_nome, u.nome AS cliente_nome, u.email AS cliente_email
                 FROM reservas r
                 JOIN hostels h ON h.id = r.hostel_id
                 JOIN usuarios u ON u.id = r.usuario_id
                 ORDER BY r.criado_em DESC
                 LIMIT 200"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarTodasReservas: ' . $e->getMessage());
            return [];
        }
    }

    public function listarReservasUsuario(int $usuarioId): array {
        if (!$this->conexao) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT r.*, h.nome AS hostel_nome, h.slug AS hostel_slug
                 FROM reservas r
                 JOIN hostels h ON h.id = r.hostel_id
                 WHERE r.usuario_id = ?
                 ORDER BY r.criado_em DESC"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DB ERROR] listarReservasUsuario: ' . $e->getMessage());
            return [];
        }
    }
}
