<?php
// config/categorias.php
// Endpoint AJAX: retorna todas as categorias com contagem de hostels.

require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conexao = new Conexao();
    $pdo     = $conexao->conectar();

    if ($pdo === null) {
        echo json_encode(['erro' => 'Banco de dados indisponível.']);
        exit;
    }

    $stmt = $pdo->query("
        SELECT c.id, c.nome, c.slug, c.descricao, c.icone,
               COUNT(h.id) AS total_hostels
        FROM   categorias c
        LEFT JOIN hostels h ON h.categoria_id = c.id
        GROUP BY c.id
        ORDER BY c.nome ASC
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno.']);
}
