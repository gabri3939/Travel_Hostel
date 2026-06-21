<?php
// config/sitemap.php
// Gera sitemap.xml dinâmico. Acessado via /sitemap.xml (rewrite no .htaccess).

define('ROOT', dirname(__DIR__));
require_once __DIR__ . '/conexao.php';

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script    = $_SERVER['SCRIPT_NAME'] ?? '';
$base      = rtrim(str_replace('/config/sitemap.php', '', $protocolo . '://' . $host . $script), '/');

header('Content-Type: application/xml; charset=utf-8');

$hostels    = [];
$categorias = [];

try {
    $conexao = new Conexao();
    $pdo     = $conexao->conectar();
    if ($pdo) {
        $hostels    = $pdo->query("SELECT slug, data_cadastro FROM hostels WHERE slug IS NOT NULL ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $categorias = $pdo->query("SELECT slug FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// Fallback estático
if (empty($hostels)) {
    $hoje = date('Y-m-d');
    $hostels = [
        ['slug'=>'morato-hostel-center',   'data_cadastro'=>$hoje],
        ['slug'=>'rocha-vibes-hostel',      'data_cadastro'=>$hoje],
        ['slug'=>'caieiras-eco-hostel',     'data_cadastro'=>$hoje],
        ['slug'=>'sao-paulo-downtown',      'data_cadastro'=>$hoje],
        ['slug'=>'rio-beach-hostel',        'data_cadastro'=>$hoje],
        ['slug'=>'curitiba-green-hostel',   'data_cadastro'=>$hoje],
        ['slug'=>'salvador-bahia-hostel',   'data_cadastro'=>$hoje],
        ['slug'=>'floripa-surf-hostel',     'data_cadastro'=>$hoje],
        ['slug'=>'belo-horizonte-hostel',   'data_cadastro'=>$hoje],
        ['slug'=>'brasilia-modern-hostel',  'data_cadastro'=>$hoje],
    ];
}
if (empty($categorias)) {
    $categorias = [
        ['slug'=>'praia'],['slug'=>'natureza'],['slug'=>'urbano'],
        ['slug'=>'cultural'],['slug'=>'surf'],['slug'=>'economico'],['slug'=>'boutique'],
    ];
}

$hoje = date('Y-m-d');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$estaticas = [
    [$base . '/',                     '1.0', 'daily'],
    [$base . '/hostels',              '0.9', 'daily'],
    [$base . '/login',                '0.5', 'monthly'],
    [$base . '/cadastro',             '0.5', 'monthly'],
    [$base . '/politica-privacidade', '0.3', 'yearly'],
    [$base . '/mapa-do-site',         '0.4', 'monthly'],
];
foreach ($estaticas as [$loc, $pri, $freq]) {
    echo "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$hoje}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$pri}</priority>\n  </url>\n";
}

foreach ($categorias as $c) {
    $loc = $base . '/hostels/categoria/' . htmlspecialchars($c['slug']);
    echo "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$hoje}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
}

foreach ($hostels as $h) {
    $loc     = $base . '/hostel/' . htmlspecialchars($h['slug']);
    $lastmod = substr($h['data_cadastro'] ?? $hoje, 0, 10);
    echo "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.7</priority>\n  </url>\n";
}

echo '</urlset>';
