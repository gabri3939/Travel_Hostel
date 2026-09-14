<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once ROOT . '/model/reservaModel.php';
require_once ROOT . '/model/usuarioModel.php';
require_once ROOT . '/model/pagbankService.php';
require_once ROOT . '/model/avaliacaoModel.php';

class reservaController {

    private function redirect(string $pagina, array $params = []): void {
        header('Location: ' . routeUrl($pagina, $params));
        exit;
    }

    // Garante usuario autenticado e retorna os dados completos dele.
    private function exigirLogin(): array {
        if (empty($_SESSION['usuario_email'])) {
            $this->redirect('login');
        }

        $usuarioModel = new usuarioModel();
        $usuario = $usuarioModel->getUsuarioByEmail($_SESSION['usuario_email']);
        if (!$usuario) {
            session_unset();
            session_destroy();
            $this->redirect('login');
        }

        return $usuario;
    }

    // Cria a reserva a partir do formulario de datas na pagina do hostel e segue para o checkout.
    public function reservar(): void {
        $usuario = $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('hostels');
        }

        $slugVolta = trim($_POST['slug'] ?? '');

        if (!csrfValido($_POST['csrf_token'] ?? null)) {
            $_SESSION['reserva_erro'] = 'Sessao expirada. Tente novamente.';
            $this->redirect($slugVolta !== '' ? 'hostel' : 'hostels', $slugVolta !== '' ? ['slug' => $slugVolta] : []);
        }

        $hostelId = (int) ($_POST['hostel_id'] ?? 0);
        $checkin = trim($_POST['checkin'] ?? '');
        $checkout = trim($_POST['checkout'] ?? '');
        $hospedes = (int) ($_POST['hospedes'] ?? 1);

        $model = new reservaModel();
        $resultado = $model->criarReserva((int) $usuario['id'], $hostelId, $checkin, $checkout, $hospedes);

        if (!$resultado['sucesso']) {
            $_SESSION['reserva_erro'] = $resultado['mensagem'];
            $this->redirect($slugVolta !== '' ? 'hostel' : 'hostels', $slugVolta !== '' ? ['slug' => $slugVolta] : []);
        }

        $this->redirect('checkout', ['id' => $resultado['id']]);
    }

    // Exibe a pagina de checkout com o resumo da reserva e as opcoes de pagamento.
    public function checkout(): void {
        $usuario = $this->exigirLogin();
        $id = (int) ($_GET['id'] ?? 0);

        $model = new reservaModel();
        $reserva = $model->getReservaCompleta($id, (int) $usuario['id']);

        if (!$reserva) {
            $this->redirect('hostels');
        }

        $publicKey = getenv('PAGBANK_PUBLIC_KEY') ?: '';
        $titulo = 'Pagamento da reserva - Travel Hostel';
        $metaRobots = 'noindex, nofollow';

        require_once ROOT . '/view/checkout/index.php';
    }

    // Valida a sessao/CSRF e retorna [usuario, reservaModel, reserva] ou responde JSON de erro e encerra.
    private function validarRequisicaoPagamento(int $reservaId): array {
        if (empty($_SESSION['usuario_email'])) {
            http_response_code(401);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessao expirada. Faca login novamente.']);
            exit;
        }

        if (!csrfValido($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessao expirada. Recarregue a pagina e tente novamente.']);
            exit;
        }

        $usuarioModel = new usuarioModel();
        $usuario = $usuarioModel->getUsuarioByEmail($_SESSION['usuario_email']);
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Sessao invalida.']);
            exit;
        }

        $model = new reservaModel();
        $reserva = $model->getReservaCompleta($reservaId, (int) $usuario['id']);
        if (!$reserva) {
            http_response_code(404);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Reserva nao encontrada.']);
            exit;
        }

        if ($reserva['status'] !== 'pendente') {
            http_response_code(409);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Esta reserva ja foi processada.']);
            exit;
        }

        return [$usuario, $model, $reserva];
    }

    // Endpoint AJAX: recebe o cartao ja criptografado no navegador e cria o pedido de credito no PagBank.
    public function processarPagamento(): void {
        header('Content-Type: application/json');

        $reservaId = (int) ($_POST['reserva_id'] ?? 0);
        [$usuario, $model, $reserva] = $this->validarRequisicaoPagamento($reservaId);

        $encrypted = trim($_POST['card_encrypted'] ?? '');
        $expMonth = trim($_POST['exp_month'] ?? '');
        $expYear = trim($_POST['exp_year'] ?? '');
        $securityCode = trim($_POST['security_code'] ?? '');
        $holderName = trim($_POST['holder_name'] ?? '');
        $holderCpf = preg_replace('/\D/', '', $_POST['holder_cpf'] ?? '');
        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? ($usuario['telefone'] ?? ''));
        $parcelas = (int) ($_POST['installments'] ?? 1);
        if ($parcelas < 1 || $parcelas > 12) {
            $parcelas = 1;
        }

        if ($encrypted === '' || $expMonth === '' || $expYear === '' || $securityCode === '' || $holderName === '') {
            http_response_code(422);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os dados do cartao.']);
            exit;
        }

        if (strlen($holderCpf) !== 11) {
            http_response_code(422);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Informe um CPF valido do titular do cartao.']);
            exit;
        }

        if (strlen($telefone) < 10) {
            http_response_code(422);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Informe um telefone valido para contato.']);
            exit;
        }

        $pagbank = new PagBankService();
        if (!$pagbank->configurado()) {
            error_log('[PAGBANK] Tentativa de pagamento sem PAGBANK_TOKEN configurado.');
            http_response_code(503);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Pagamento indisponivel no momento. Tente novamente mais tarde.']);
            exit;
        }

        $referencia = 'reserva-' . $reserva['id'];
        $valorCentavos = (int) round(((float) $reserva['valor_total']) * 100);

        $payload = [
            'reference_id' => $referencia,
            'customer' => [
                'name' => $usuario['nome'],
                'email' => $usuario['email'],
                'tax_id' => $holderCpf,
                'phones' => [[
                    'country' => '55',
                    'area' => substr($telefone, 0, 2),
                    'number' => substr($telefone, 2),
                    'type' => 'MOBILE',
                ]],
            ],
            'items' => [[
                'reference_id' => 'hostel-' . $reserva['hostel_id'],
                'name' => mb_substr($reserva['hostel_nome'], 0, 100),
                'quantity' => 1,
                'unit_amount' => $valorCentavos,
            ]],
            'charges' => [[
                'reference_id' => $referencia,
                'description' => mb_substr('Reserva - ' . $reserva['hostel_nome'], 0, 100),
                'amount' => [
                    'value' => $valorCentavos,
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'CREDIT_CARD',
                    'installments' => $parcelas,
                    'capture' => true,
                    'card' => [
                        'encrypted' => $encrypted,
                        'exp_month' => $expMonth,
                        'exp_year' => $expYear,
                        'security_code' => $securityCode,
                        'holder' => [
                            'name' => $holderName,
                            'tax_id' => $holderCpf,
                        ],
                        'store' => false,
                    ],
                ],
            ]],
            'notification_urls' => [URL_BASE . '/controller/router.php?pagina=api/pagbank-webhook'],
        ];

        $resposta = $pagbank->criarPedido($payload);

        if (!$resposta['sucesso']) {
            $mensagemErro = $resposta['dados']['error_messages'][0]['description']
                ?? 'Nao foi possivel processar o pagamento. Verifique os dados do cartao.';
            error_log('[PAGBANK ERROR] HTTP ' . $resposta['status'] . ' na reserva ' . $reserva['id']);
            $model->atualizarPagamento((int) $reserva['id'], 'recusado', null, null, 'ERRO_API', 'credito');
            http_response_code(402);
            echo json_encode(['sucesso' => false, 'mensagem' => $mensagemErro]);
            exit;
        }

        $orderId = $resposta['dados']['id'] ?? null;
        $charge = $resposta['dados']['charges'][0] ?? [];
        $chargeId = $charge['id'] ?? null;
        $chargeStatus = $charge['status'] ?? 'IN_ANALYSIS';
        $statusLocal = $this->mapearStatus($chargeStatus);

        $model->atualizarPagamento((int) $reserva['id'], $statusLocal, $orderId, $chargeId, $chargeStatus, 'credito');

        if ($statusLocal === 'pago') {
            $this->notificarAnfitriao((int) $reserva['id']);
        }

        $mensagens = [
            'pago' => 'Pagamento aprovado! Sua reserva esta confirmada.',
            'em_analise' => 'Pagamento em analise. Voce sera notificado assim que for confirmado.',
            'recusado' => 'Pagamento recusado. Verifique os dados do cartao ou tente outro cartao.',
        ];

        echo json_encode([
            'sucesso' => $statusLocal !== 'recusado',
            'status' => $statusLocal,
            'mensagem' => $mensagens[$statusLocal] ?? 'Pagamento em processamento.',
        ]);
    }

    // Endpoint AJAX: gera uma cobranca PIX (QR Code) para a reserva.
    public function gerarPix(): void {
        header('Content-Type: application/json');

        $reservaId = (int) ($_POST['reserva_id'] ?? 0);
        [$usuario, $model, $reserva] = $this->validarRequisicaoPagamento($reservaId);

        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? ($usuario['telefone'] ?? ''));
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? ($usuario['cpf'] ?? ''));

        if (strlen($cpf) !== 11) {
            http_response_code(422);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Informe um CPF valido para gerar o PIX.']);
            exit;
        }

        if (strlen($telefone) < 10) {
            http_response_code(422);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Informe um telefone valido para contato.']);
            exit;
        }

        $pagbank = new PagBankService();
        if (!$pagbank->configurado()) {
            error_log('[PAGBANK] Tentativa de PIX sem PAGBANK_TOKEN configurado.');
            http_response_code(503);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Pagamento indisponivel no momento. Tente novamente mais tarde.']);
            exit;
        }

        $referencia = 'reserva-' . $reserva['id'];
        $valorCentavos = (int) round(((float) $reserva['valor_total']) * 100);
        $expiracao = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->add(new DateInterval('PT30M'));

        $payload = [
            'reference_id' => $referencia,
            'customer' => [
                'name' => $usuario['nome'],
                'email' => $usuario['email'],
                'tax_id' => $cpf,
                'phones' => [[
                    'country' => '55',
                    'area' => substr($telefone, 0, 2),
                    'number' => substr($telefone, 2),
                    'type' => 'MOBILE',
                ]],
            ],
            'items' => [[
                'reference_id' => 'hostel-' . $reserva['hostel_id'],
                'name' => mb_substr($reserva['hostel_nome'], 0, 100),
                'quantity' => 1,
                'unit_amount' => $valorCentavos,
            ]],
            'charges' => [[
                'reference_id' => $referencia,
                'description' => mb_substr('Reserva - ' . $reserva['hostel_nome'], 0, 100),
                'amount' => [
                    'value' => $valorCentavos,
                    'currency' => 'BRL',
                ],
                'payment_method' => [
                    'type' => 'PIX',
                    'pix' => [
                        'expiration_date' => $expiracao->format('Y-m-d\TH:i:s\Z'),
                    ],
                ],
            ]],
            'notification_urls' => [URL_BASE . '/controller/router.php?pagina=api/pagbank-webhook'],
        ];

        $resposta = $pagbank->criarPedido($payload);

        if (!$resposta['sucesso']) {
            $mensagemErro = $resposta['dados']['error_messages'][0]['description']
                ?? 'Nao foi possivel gerar o PIX. Tente novamente.';
            error_log('[PAGBANK PIX ERROR] HTTP ' . $resposta['status'] . ' na reserva ' . $reserva['id']);
            http_response_code(402);
            echo json_encode(['sucesso' => false, 'mensagem' => $mensagemErro]);
            exit;
        }

        $orderId = $resposta['dados']['id'] ?? null;
        $charge = $resposta['dados']['charges'][0] ?? [];
        $chargeId = $charge['id'] ?? null;
        $chargeStatus = $charge['status'] ?? 'WAITING';
        $qrTexto = $charge['qr_code']['text'] ?? null;

        $model->atualizarPagamento((int) $reserva['id'], 'em_analise', $orderId, $chargeId, $chargeStatus, 'pix');

        if (!$qrTexto) {
            http_response_code(502);
            echo json_encode(['sucesso' => false, 'mensagem' => 'PIX gerado, mas o QR Code nao pode ser exibido. Atualize a pagina.']);
            exit;
        }

        echo json_encode([
            'sucesso' => true,
            'qr_texto' => $qrTexto,
            'qr_imagem_url' => routeUrl('api/pagbank-pix-imagem', ['id' => $reserva['id']]),
            'expira_em' => $expiracao->format(DateTimeInterface::ATOM),
        ]);
    }

    // Serve a imagem do QR Code do PIX (o link do PagBank exige o token, entao fazemos o proxy aqui).
    public function pixImagem(): void {
        $usuario = $this->exigirLogin();
        $id = (int) ($_GET['id'] ?? 0);

        $model = new reservaModel();
        $reserva = $model->getReservaCompleta($id, (int) $usuario['id']);

        if (!$reserva || empty($reserva['pagbank_order_id'])) {
            http_response_code(404);
            exit;
        }

        $pagbank = new PagBankService();
        $consulta = $pagbank->consultarPedido($reserva['pagbank_order_id']);

        $links = $consulta['dados']['charges'][0]['links'] ?? [];
        $hrefPng = null;
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'QRCODE.PNG') {
                $hrefPng = $link['href'] ?? null;
                break;
            }
        }

        if (!$hrefPng) {
            http_response_code(404);
            exit;
        }

        $imagem = $pagbank->buscarBinarioAutenticado($hrefPng);
        if (!$imagem['sucesso']) {
            http_response_code(502);
            exit;
        }

        header('Content-Type: ' . $imagem['contentType']);
        header('Cache-Control: no-store');
        echo $imagem['corpo'];
    }

    // Endpoint de polling: o front consulta o status atual da reserva enquanto aguarda a confirmacao do PIX.
    public function statusReserva(): void {
        header('Content-Type: application/json');
        $usuario = null;

        if (empty($_SESSION['usuario_email'])) {
            http_response_code(401);
            echo json_encode(['sucesso' => false]);
            exit;
        }

        $usuarioModel = new usuarioModel();
        $usuario = $usuarioModel->getUsuarioByEmail($_SESSION['usuario_email']);
        if (!$usuario) {
            http_response_code(401);
            echo json_encode(['sucesso' => false]);
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $model = new reservaModel();
        $reserva = $model->getReservaCompleta($id, (int) $usuario['id']);

        if (!$reserva) {
            http_response_code(404);
            echo json_encode(['sucesso' => false]);
            exit;
        }

        echo json_encode(['sucesso' => true, 'status' => $reserva['status']]);
    }

    // Recebe notificacoes assincronas do PagBank sobre mudanca de status do pedido (credito em analise, PIX pago etc.).
    public function webhook(): void {
        $rawBody = file_get_contents('php://input');
        $assinatura = $_SERVER['HTTP_X_AUTHENTICITY_TOKEN'] ?? '';
        $token = (string) getenv('PAGBANK_TOKEN');

        if ($token === '' || $assinatura === '' || $rawBody === '') {
            http_response_code(400);
            exit;
        }

        $esperado = hash('sha256', $token . '-' . $rawBody);
        if (!hash_equals($esperado, $assinatura)) {
            error_log('[PAGBANK WEBHOOK] Assinatura invalida recebida.');
            http_response_code(400);
            exit;
        }

        $dados = json_decode($rawBody, true);
        $orderId = $dados['id'] ?? null;
        $charge = $dados['charges'][0] ?? [];
        $chargeStatus = $charge['status'] ?? null;

        if (!$orderId || !$chargeStatus) {
            http_response_code(400);
            exit;
        }

        $model = new reservaModel();
        $reserva = $model->getReservaPorOrderId($orderId);
        if (!$reserva) {
            http_response_code(404);
            exit;
        }

        $statusLocal = $this->mapearStatus($chargeStatus);
        $model->atualizarPagamento((int) $reserva['id'], $statusLocal, $orderId, $charge['id'] ?? null, $chargeStatus);

        if ($statusLocal === 'pago') {
            $this->notificarAnfitriao((int) $reserva['id']);
        }

        http_response_code(200);
        echo 'OK';
    }

    private function mapearStatus(string $chargeStatus): string {
        return match ($chargeStatus) {
            'PAID' => 'pago',
            'DECLINED', 'CANCELED' => 'recusado',
            default => 'em_analise',
        };
    }

    // Avisa por e-mail o anfitriao do recinto que uma reserva foi paga, com os dados de contato do cliente.
    private function notificarAnfitriao(int $reservaId): void {
        $model = new reservaModel();

        if (!$model->marcarAnfitriaoNotificado($reservaId)) {
            return; // ja notificado ou reserva inexistente: evita e-mail duplicado.
        }

        $reserva = $model->getReservaPorId($reservaId);
        if (!$reserva || empty($reserva['anfitriao_email'])) {
            return;
        }

        $mailUsername = getenv('MAIL_USERNAME');
        $mailPassword = getenv('MAIL_PASSWORD');
        if (empty($mailUsername) || empty($mailPassword)) {
            error_log('[EMAIL ERROR] Credenciais de email nao configuradas para notificar anfitriao.');
            return;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE') ?: 'tls';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->CharSet = 'UTF-8';

            $fromEmail = getenv('MAIL_FROM') ?: $mailUsername;
            $mail->setFrom($fromEmail, getenv('MAIL_FROM_NAME') ?: 'Travel Hostel');
            $mail->addAddress($reserva['anfitriao_email'], $reserva['anfitriao_nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Nova reserva confirmada - ' . $reserva['hostel_nome'];

            $checkin = (new DateTimeImmutable($reserva['data_checkin']))->format('d/m/Y');
            $checkout = (new DateTimeImmutable($reserva['data_checkout']))->format('d/m/Y');

            $mail->Body = '<p>Ola ' . htmlspecialchars($reserva['anfitriao_nome']) . ',</p>'
                . '<p>Voce recebeu uma nova reserva paga para o recinto <strong>' . htmlspecialchars($reserva['hostel_nome']) . '</strong>.</p>'
                . '<ul>'
                . '<li>Check-in: ' . $checkin . '</li>'
                . '<li>Check-out: ' . $checkout . '</li>'
                . '<li>Hospedes: ' . (int) $reserva['hospedes'] . '</li>'
                . '<li>Valor: R$ ' . number_format((float) $reserva['valor_total'], 2, ',', '.') . '</li>'
                . '</ul>'
                . '<p><strong>Dados do cliente</strong><br>'
                . 'Nome: ' . htmlspecialchars($reserva['cliente_nome']) . '<br>'
                . 'E-mail: ' . htmlspecialchars($reserva['cliente_email']) . '<br>'
                . 'Telefone: ' . htmlspecialchars($reserva['cliente_telefone'] ?? 'nao informado') . '</p>';

            $mail->send();
        } catch (Exception $e) {
            error_log('[EMAIL ERROR] Falha ao notificar anfitriao da reserva ' . $reservaId . ': ' . $e->getMessage());
        }
    }

    // Avalia o hostel de uma reserva ja paga (uma avaliacao por reserva).
    public function avaliarHostel(): void {
        $usuario = $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido($_POST['csrf_token'] ?? null)) {
            $this->redirect('perfil');
        }

        $reservaId = (int) ($_POST['reserva_id'] ?? 0);
        $nota = (int) ($_POST['nota'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');

        $reservaModel = new reservaModel();
        $reserva = $reservaModel->getReservaCompleta($reservaId, (int) $usuario['id']);

        if ($reserva && $reserva['status'] === 'pago') {
            $avaliacaoModel = new avaliacaoModel();
            $resultado = $avaliacaoModel->avaliarHostel($reservaId, (int) $usuario['id'], (int) $reserva['hostel_id'], $nota, $comentario);
            $_SESSION['perfil_mensagem'] = $resultado['mensagem'];
            $_SESSION['perfil_tipo'] = $resultado['sucesso'] ? 'sucesso' : 'erro';
        } else {
            $_SESSION['perfil_mensagem'] = 'Reserva invalida para avaliacao.';
            $_SESSION['perfil_tipo'] = 'erro';
        }

        $this->redirect('perfil');
    }

    // Avalia o anfitriao de uma reserva ja paga (uma avaliacao por reserva).
    public function avaliarAnfitriao(): void {
        $usuario = $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValido($_POST['csrf_token'] ?? null)) {
            $this->redirect('perfil');
        }

        $reservaId = (int) ($_POST['reserva_id'] ?? 0);
        $nota = (int) ($_POST['nota'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');

        $reservaModel = new reservaModel();
        $reserva = $reservaModel->getReservaCompleta($reservaId, (int) $usuario['id']);

        $mensagem = 'Reserva invalida para avaliacao.';
        $sucesso = false;

        if ($reserva && $reserva['status'] === 'pago') {
            $reservaCompleta = $reservaModel->getReservaPorId($reservaId);
            if ($reservaCompleta && !empty($reservaCompleta['anfitriao_id'])) {
                $avaliacaoModel = new avaliacaoModel();
                $resultado = $avaliacaoModel->avaliarAnfitriao($reservaId, (int) $usuario['id'], (int) $reservaCompleta['anfitriao_id'], $nota, $comentario);
                $mensagem = $resultado['mensagem'];
                $sucesso = $resultado['sucesso'];
            } else {
                $mensagem = 'Este recinto nao possui anfitriao para avaliar.';
            }
        }

        $_SESSION['perfil_mensagem'] = $mensagem;
        $_SESSION['perfil_tipo'] = $sucesso ? 'sucesso' : 'erro';

        $this->redirect('perfil');
    }
}
