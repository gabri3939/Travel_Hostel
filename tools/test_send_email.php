<?php
// Script de teste para enviar um email usando as configurações do .env
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('ROOT', dirname(__DIR__));

// Carrega .env manualmente (similar a config/conexao.php)
$envPath = ROOT . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($name === '') continue;
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}

require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/controller/usuarioController.php';

session_start();

$controller = new usuarioController();

$rc = new ReflectionClass($controller);
if (!$rc->hasMethod('enviarCodigoVerificacao')) {
    echo "Metodo enviarCodigoVerificacao nao encontrado.\n";
    exit(1);
}

$m = $rc->getMethod('enviarCodigoVerificacao');
$m->setAccessible(true);

$testEmail = getenv('MAIL_USERNAME') ?: getenv('MAIL_FROM') ?: 'test@example.com';
$testName = 'Teste Local';
$testCode = '999999';

echo "Enviando para: {$testEmail}\n";

try {
    $ok = $m->invoke($controller, $testEmail, $testName, $testCode);
    echo $ok ? "RESULT: OK - envio bem-sucedido\n" : "RESULT: FAIL - envio falhou (PHPMailer::send retornou false)\n";
} catch (Throwable $e) {
    echo "RESULT: EXCEPTION - " . $e->getMessage() . "\n";
    if (method_exists($e, 'getTraceAsString')) echo $e->getTraceAsString() . "\n";
    exit(2);
}

exit($ok ? 0 : 1);
