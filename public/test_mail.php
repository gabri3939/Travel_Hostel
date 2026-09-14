<?php
// Endpoint de debug: tenta enviar codigo via enviarCodigoVerificacao usando o SAPI do Apache.
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('ROOT', dirname(__DIR__));

// Carrega .env (router.php faz algo parecido, mas garantimos aqui)
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

$controller = new usuarioController();
$rc = new ReflectionClass($controller);
$m = $rc->getMethod('enviarCodigoVerificacao');
$m->setAccessible(true);

$testEmail = getenv('MAIL_USERNAME') ?: getenv('MAIL_FROM') ?: 'test@example.com';
$testName = 'Teste Web';
$testCode = '123456';

header('Content-Type: text/plain; charset=utf-8');
echo "Enviando para: {$testEmail}\n";

try {
    $ok = $m->invoke($controller, $testEmail, $testName, $testCode);
    echo $ok ? "RESULT: OK - envio bem-sucedido\n" : "RESULT: FAIL - envio falhou (PHPMailer::send retornou false)\n";
} catch (Throwable $e) {
    echo "RESULT: EXCEPTION - " . $e->getMessage() . "\n";
}

// Sugere checar os logs do Apache para detalhes (ErrorInfo)
echo "Verifique os logs do Apache para mensagens '[EMAIL' se houver falha.\n";
