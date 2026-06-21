<?php

session_start();

define('ROOT', dirname(__DIR__));

function carregarEnv() {
    $envPath = ROOT . '/.env';
    if (!file_exists($envPath)) return;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($name === '') continue;
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}

carregarEnv();

if (file_exists(ROOT . '/vendor/autoload.php')) {
    require_once ROOT . '/vendor/autoload.php';
}

$scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$urlBase    = rtrim(dirname($scriptDir), '/');
define('URL_BASE',   $urlBase);
define('URL_PUBLIC', $urlBase . '/public');

function routeUrl(string $pagina, array $params = []): string {
    $query = http_build_query(array_merge(['pagina' => $pagina], $params));
    return URL_BASE . '/controller/router.php?' . $query;
}

function normalizeAvatarUrl(?string $avatarPath): string {
    if (empty($avatarPath)) {
        return '';
    }

    if (strpos($avatarPath, 'http://') === 0 || strpos($avatarPath, 'https://') === 0) {
        return $avatarPath;
    }

    return URL_BASE . '/' . ltrim($avatarPath, '/');
}

require_once 'usuarioController.php';

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'home';

// Sanitiza slug e categoria recebidos via rewrite
$slug      = isset($_GET['slug'])      ? preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug']))      : '';
$categoria = isset($_GET['categoria']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['categoria'])) : '';
if ($categoria) $_GET['categoria'] = $categoria;

$controller = new usuarioController();

switch ($pagina) {
    case 'home':
        $controller->home();
        break;
    case 'hostels':
        $controller->hostels();
        break;
    case 'hostel':
        $controller->hostelDetalhe($slug);
        break;
    case 'mapa-do-site':
        $controller->mapaSite();
        break;
    case 'cadastro':
        $controller->cadastro();
        break;
    case 'verificar':
        $controller->verificar();
        break;
    case 'perfil':
        $controller->perfil();
        break;
    case 'login':
        $controller->login();
        break;
    case 'logout':
        $controller->logout();
        break;
    case 'politica':
        $controller->politica();
        break;
    // ── Endpoint de API ─────────────────────────────────────────────────
    case 'api/upload-foto':
        $controller->uploadFotoCloudinary();
        break;
    default:
        $controller->home();
        break;
}
