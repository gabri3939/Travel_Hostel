<?php
// config/hostels.php
// Endpoint AJAX: retorna hostels em destaque como JSON

require_once __DIR__ . '/conexao.php';

// Define o header JSON para consistentemente retornar resposta em utf-8.
header('Content-Type: application/json; charset=utf-8');

// Tenta carregar os hostels em destaque do banco de dados.
try {
    $conexao = new Conexao();
    $pdo     = $conexao->conectar();

    if ($pdo === null) {
        echo json_encode(['erro' => 'Banco de dados indisponivel.']);
        exit;
    }

    $destaque = isset($_GET['destaque']) ? (int)$_GET['destaque'] : 1;
    $limite   = isset($_GET['limite'])   ? (int)$_GET['limite']   : 6;

    $stmt = $pdo->prepare(
        "SELECT id, nome, cidade, estado, pais, descricao,
                preco_diaria, avaliacao, total_avaliacoes,
                comodidades, camas, tipo, imagem_url
         FROM hostels
         WHERE destaque = :destaque
         ORDER BY avaliacao DESC
         LIMIT :limite"
    );
    $stmt->bindValue(':destaque', $destaque, PDO::PARAM_INT);
    $stmt->bindValue(':limite',   $limite,   PDO::PARAM_INT);
    $stmt->execute();

    $hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($hostels, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Em caso de erro, retorna mensagem de erro em JSON.
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
}
