<?php
// config/buscar.php
// Endpoint AJAX de busca e filtragem com suporte à taxonomia.
// Parâmetros GET: q, categoria, cidade, estado, preco_min, preco_max,
//                 avaliacao_min, ordenar, pagina, por_pagina

require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$busca        = trim($_GET['q']           ?? '');
$categoria    = trim($_GET['categoria']   ?? '');
$cidade       = trim($_GET['cidade']      ?? '');
$estado       = trim($_GET['estado']      ?? '');
$precoMin     = isset($_GET['preco_min'])     ? (float) $_GET['preco_min']     : null;
$precoMax     = isset($_GET['preco_max'])     ? (float) $_GET['preco_max']     : null;
$avaliacaoMin = isset($_GET['avaliacao_min']) ? (float) $_GET['avaliacao_min'] : null;
$ordenar      = trim($_GET['ordenar']     ?? 'avaliacao');
$pagina       = max(1, (int) ($_GET['pagina']     ?? 1));
$porPagina    = min(50, max(1, (int) ($_GET['por_pagina'] ?? 9)));

$ordens = [
    'avaliacao'  => 'h.avaliacao DESC',
    'preco_asc'  => 'h.preco_diaria ASC',
    'preco_desc' => 'h.preco_diaria DESC',
    'nome'       => 'h.nome ASC',
];
$orderSql = $ordens[$ordenar] ?? $ordens['avaliacao'];

try {
    $conexao = new Conexao();
    $pdo     = $conexao->conectar();

    if ($pdo === null) {
        echo json_encode(['erro' => 'Banco de dados indisponível.', 'hostels' => [], 'total' => 0]);
        exit;
    }

    $where  = ['1=1'];
    $params = [];

    if ($busca !== '') {
        $where[]             = '(MATCH(h.nome, h.cidade, h.descricao, h.palavras_chave) AGAINST(:busca IN BOOLEAN MODE)
                                 OR h.nome          LIKE :busca_like
                                 OR h.cidade        LIKE :busca_like
                                 OR h.palavras_chave LIKE :busca_like)';
        $params[':busca']      = $busca . '*';
        $params[':busca_like'] = '%' . $busca . '%';
    }

    if ($categoria !== '') {
        $where[]              = 'c.slug = :categoria';
        $params[':categoria'] = $categoria;
    }

    if ($cidade !== '') {
        $where[]           = 'h.cidade LIKE :cidade';
        $params[':cidade'] = '%' . $cidade . '%';
    }

    if ($estado !== '') {
        $where[]           = 'h.estado = :estado';
        $params[':estado'] = strtoupper($estado);
    }

    if ($precoMin !== null) {
        $where[]              = 'h.preco_diaria >= :preco_min';
        $params[':preco_min'] = $precoMin;
    }

    if ($precoMax !== null) {
        $where[]              = 'h.preco_diaria <= :preco_max';
        $params[':preco_max'] = $precoMax;
    }

    if ($avaliacaoMin !== null) {
        $where[]                  = 'h.avaliacao >= :avaliacao_min';
        $params[':avaliacao_min'] = $avaliacaoMin;
    }

    $whereSql = implode(' AND ', $where);
    $offset   = ($pagina - 1) * $porPagina;

    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM hostels h
        LEFT JOIN categorias c ON c.id = h.categoria_id
        WHERE {$whereSql}
    ");
    $stmtCount->execute($params);
    $total = (int) $stmtCount->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT h.id, h.nome, h.slug, h.cidade, h.estado, h.pais,
               h.descricao, h.preco_diaria, h.avaliacao, h.total_avaliacoes,
               h.comodidades, h.camas, h.tipo, h.imagem_url, h.palavras_chave,
               c.id AS categoria_id, c.nome AS categoria_nome,
               c.slug AS categoria_slug, c.icone AS categoria_icone
        FROM   hostels h
        LEFT JOIN categorias c ON c.id = h.categoria_id
        WHERE  {$whereSql}
        ORDER BY {$orderSql}
        LIMIT  :limite OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'hostels'    => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $porPagina,
        'paginas'    => (int) ceil($total / max(1, $porPagina)),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno.', 'hostels' => [], 'total' => 0]);
}
