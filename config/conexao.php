<?php

// Classe de conexao com o banco de dados usando variaveis de ambiente.
class Conexao {
    private $host;
    private $db;
    private $user;
    private $password;

    public function __construct() {
        // Carrega .env se existir e configura as variaveis de ambiente.
        $this->loadEnv();
        $this->host     = getenv('DB_HOST') ?: 'localhost';
        $this->db       = getenv('DB_NAME') ?: 'travel_hostel';
        $this->user     = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
    }

    private function loadEnv() {
        // Tenta carregar arquivo .env na raiz do projeto.
        $envPath = defined('ROOT') ? ROOT . '/.env' : dirname(__DIR__) . '/.env';
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($name === '') {
                continue;
            }

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }

    public function conectar() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->password
            );
            // Habilita excecoes para erros do PDO.
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->garantirEstruturaAprovacao($pdo);
            $this->garantirEstruturaSolicitacaoAnfitriao($pdo);
            return $pdo;
        } catch (PDOException $e) {
            // Se a conexao falhar, retorna null para que o sistema continue sem travar.
            return null;
        }
    }

    private function garantirEstruturaAprovacao(PDO $pdo): void {
        try {
            $colunas = [
                'anfitriao_id' => "ALTER TABLE hostels ADD COLUMN anfitriao_id INT NULL AFTER palavras_chave",
                'status_aprovacao' => "ALTER TABLE hostels ADD COLUMN status_aprovacao ENUM('aprovado', 'pendente', 'rejeitado') NOT NULL DEFAULT 'aprovado' AFTER anfitriao_id",
                'motivo_rejeicao' => "ALTER TABLE hostels ADD COLUMN motivo_rejeicao VARCHAR(500) AFTER status_aprovacao",
                'ativo' => "ALTER TABLE hostels ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER motivo_rejeicao",
            ];
            foreach ($colunas as $nome => $sql) {
                $stmt = $pdo->query("SHOW COLUMNS FROM hostels LIKE '{$nome}'");
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pdo->exec($sql);
                }
            }
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS hostel_imagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hostel_id INT NOT NULL,
                    caminho VARCHAR(255) NOT NULL,
                    principal TINYINT(1) NOT NULL DEFAULT 0,
                    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            );
        } catch (PDOException $e) {
            error_log('[DB ERROR] Estrutura de aprovacao: ' . $e->getMessage());
        }
    }

    private function garantirEstruturaSolicitacaoAnfitriao(PDO $pdo): void {
        try {
            $colunas = [
                'solicitacao_anfitriao' => "ALTER TABLE usuarios ADD COLUMN solicitacao_anfitriao ENUM('nenhuma', 'pendente', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'nenhuma' AFTER ativo",
                'motivo_solicitacao' => "ALTER TABLE usuarios ADD COLUMN motivo_solicitacao VARCHAR(500) AFTER solicitacao_anfitriao",
            ];
            foreach ($colunas as $nome => $sql) {
                $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '{$nome}'");
                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pdo->exec($sql);
                }
            }
        } catch (PDOException $e) {
            error_log('[DB ERROR] Estrutura de solicitacao de anfitriao: ' . $e->getMessage());
        }
    }
}
