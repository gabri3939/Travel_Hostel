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
            return $pdo;
        } catch (PDOException $e) {
            // Se a conexao falhar, retorna null para que o sistema continue sem travar.
            return null;
        }
    }
}
