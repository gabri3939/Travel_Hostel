<?php
include ROOT . '/view/layouts/header.php';

$noites = (int) $reserva['noites'];
$checkinFmt = (new DateTimeImmutable($reserva['data_checkin']))->format('d/m/Y');
$checkoutFmt = (new DateTimeImmutable($reserva['data_checkout']))->format('d/m/Y');
$jaFinalizada = $reserva['status'] !== 'pendente';
?>
<link rel="stylesheet" href="<?php echo assetUrl('css/checkout.css'); ?>"/>

<section class="checkout-section">
  <div class="container checkout-wrap">

    <div class="checkout-resumo">
      <h1>Finalizar reserva</h1>

      <div class="checkout-card-resumo">
        <img src="<?php echo htmlspecialchars($reserva['imagem_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($reserva['hostel_nome']); ?>"
             onerror="this.src='https://via.placeholder.com/300x200?text=Hostel'">
        <div>
          <h2><?php echo htmlspecialchars($reserva['hostel_nome']); ?></h2>
          <p class="checkout-local">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars($reserva['cidade'] . (!empty($reserva['estado']) ? ', ' . $reserva['estado'] : '')); ?>
          </p>
          <ul class="checkout-detalhes">
            <li><span>Check-in</span><strong><?php echo $checkinFmt; ?></strong></li>
            <li><span>Check-out</span><strong><?php echo $checkoutFmt; ?></strong></li>
            <li><span>Noites</span><strong><?php echo $noites; ?></strong></li>
            <li><span>Hospedes</span><strong><?php echo (int) $reserva['hospedes']; ?></strong></li>
          </ul>
        </div>
      </div>

      <div class="checkout-total">
        <span>Total a pagar</span>
        <strong>R$ <?php echo number_format((float) $reserva['valor_total'], 2, ',', '.'); ?></strong>
      </div>

      <?php if ($jaFinalizada): ?>
        <div class="checkout-status checkout-status--<?php echo htmlspecialchars($reserva['status']); ?>">
          <?php
            $statusTexto = [
                'pago' => 'Pagamento aprovado. Sua reserva esta confirmada!',
                'em_analise' => 'Pagamento em analise.',
                'recusado' => 'Pagamento recusado nesta reserva.',
                'cancelado' => 'Reserva cancelada.',
            ];
            echo htmlspecialchars($statusTexto[$reserva['status']] ?? 'Status: ' . $reserva['status']);
          ?>
        </div>
        <a href="<?php echo routeUrl('perfil'); ?>" class="btn btn-outline" style="width:100%;margin-top:12px;">Ver minhas reservas</a>
      <?php endif; ?>
    </div>

    <?php if (!$jaFinalizada): ?>
    <div class="checkout-pagamento">
      <h2>Forma de pagamento</h2>

      <div class="payment-tabs">
        <button type="button" class="payment-tab active" id="tabCredito" data-tab="credito">
          <i class="fa-solid fa-credit-card"></i> Cartão de crédito
        </button>
        <button type="button" class="payment-tab" id="tabPix" data-tab="pix">
          <i class="fa-brands fa-pix"></i> PIX
        </button>
      </div>

      <div id="checkoutErro" class="checkout-erro" hidden></div>
      <div id="checkoutSucesso" class="checkout-sucesso" hidden></div>

      <!-- Cartao de credito -->
      <div class="payment-panel" id="painelCredito">
        <p class="checkout-seguro"><i class="fa-solid fa-lock"></i> Seus dados de cartão são criptografados no navegador antes de sair do seu computador.</p>

        <form id="formPagamento" autocomplete="off">
          <div class="form-group">
            <label for="cardNumber">Número do cartão</label>
            <input type="text" id="cardNumber" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="cardExpMonth">Validade (MM/AAAA)</label>
              <div class="form-row" style="gap:8px;">
                <input type="text" id="cardExpMonth" inputmode="numeric" maxlength="2" placeholder="MM" required style="width:70px;">
                <input type="text" id="cardExpYear" inputmode="numeric" maxlength="4" placeholder="AAAA" required style="width:90px;">
              </div>
            </div>
            <div class="form-group">
              <label for="cardCvv">CVV</label>
              <input type="text" id="cardCvv" inputmode="numeric" maxlength="4" placeholder="123" required style="width:100px;">
            </div>
          </div>

          <div class="form-group">
            <label for="cardHolder">Nome impresso no cartão</label>
            <input type="text" id="cardHolder" placeholder="Como está no cartão" required>
          </div>

          <div class="form-group">
            <label for="holderCpf">CPF do titular do cartão</label>
            <input type="text" id="holderCpf" maxlength="14" placeholder="000.000.000-00" oninput="mascaraCPF(this)" required>
          </div>

          <div class="form-group">
            <label for="telefone">Telefone para contato</label>
            <input type="text" id="telefone" maxlength="15" placeholder="(00) 00000-0000"
                   value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" oninput="mascaraTelefone(this)" required>
          </div>

          <div class="form-group">
            <label for="installments">Parcelas</label>
            <select id="installments"></select>
            <small style="color:#888;">Parcelamento sujeito às condições do seu cartão.</small>
          </div>

          <button type="submit" class="btn btn-primary booking-button" id="btnPagar">
            Pagar R$ <?php echo number_format((float) $reserva['valor_total'], 2, ',', '.'); ?>
          </button>
        </form>
      </div>

      <!-- PIX -->
      <div class="payment-panel" id="painelPix" hidden>
        <p class="checkout-seguro"><i class="fa-solid fa-bolt"></i> Pagamento instantâneo. Assim que o PIX for confirmado, sua reserva é aprovada automaticamente.</p>

        <div id="pixFormulario">
          <div class="form-group">
            <label for="pixCpf">CPF</label>
            <input type="text" id="pixCpf" maxlength="14" placeholder="000.000.000-00" oninput="mascaraCPF(this)"
                   value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label for="pixTelefone">Telefone para contato</label>
            <input type="text" id="pixTelefone" maxlength="15" placeholder="(00) 00000-0000"
                   value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" oninput="mascaraTelefone(this)" required>
          </div>
          <button type="button" class="btn btn-primary booking-button" id="btnGerarPix">
            Gerar PIX de R$ <?php echo number_format((float) $reserva['valor_total'], 2, ',', '.'); ?>
          </button>
        </div>

        <div id="pixResultado" hidden>
          <div class="pix-qrcode">
            <img id="pixImagem" src="" alt="QR Code PIX">
          </div>
          <label style="font-size:.85rem;font-weight:600;">Ou copie o código PIX:</label>
          <div class="pix-copia">
            <input type="text" id="pixTexto" readonly>
            <button type="button" class="btn btn-outline btn-sm" id="btnCopiarPix">Copiar</button>
          </div>
          <p class="pix-aguardando"><i class="fa-solid fa-spinner fa-spin"></i> Aguardando confirmação do pagamento...</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
<script>
  var CHECKOUT_CONFIG = {
    reservaId: <?php echo (int) $reserva['id']; ?>,
    valorTotal: <?php echo (float) $reserva['valor_total']; ?>,
    publicKey: <?php echo json_encode($publicKey); ?>,
    csrfToken: <?php echo json_encode(csrfToken()); ?>,
    pagarUrl: <?php echo json_encode(routeUrl('api/pagbank-pagar')); ?>,
    pixUrl: <?php echo json_encode(routeUrl('api/pagbank-pix')); ?>,
    statusUrl: <?php echo json_encode(routeUrl('api/reserva-status')); ?>
  };
</script>
<script src="<?php echo assetUrl('js/checkout.js'); ?>"></script>

<?php include ROOT . '/view/layouts/footer.php'; ?>
