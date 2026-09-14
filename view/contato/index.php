<?php
$titulo = 'Contato - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card" style="max-width:820px;">
    <div class="auth-header">
      <h1>Contato</h1>
      <p>Dúvidas sobre sua conta, uma reserva ou sobre o projeto? Veja as informações abaixo.</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="mensagem mensagem-<?php echo htmlspecialchars($tipoMensagem); ?> mb-md"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <div class="policy-content">
      <style>
        .contact-form .form-row { display: flex; gap: 16px; }
        .contact-form .form-row .form-group { flex: 1; }
        @media (max-width: 600px) { .contact-form .form-row { flex-direction: column; gap: 0; } }
      </style>
      <h2>Envie uma mensagem</h2>
      <p>Preencha o formulário abaixo. Sua mensagem chega direto para a administração do Travel Hostel.</p>

      <form method="POST" action="<?php echo routeUrl('contato'); ?>" class="contact-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
        <div class="form-row">
          <div class="form-group">
            <label for="nome">Seu nome</label>
            <input type="text" id="nome" name="nome" required maxlength="100" value="<?php echo htmlspecialchars($nomePreenchido); ?>">
          </div>
          <div class="form-group">
            <label for="email">Seu e-mail</label>
            <input type="email" id="email" name="email" required maxlength="100" value="<?php echo htmlspecialchars($emailPreenchido); ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="assunto">Assunto</label>
          <input type="text" id="assunto" name="assunto" required maxlength="150" placeholder="Ex: Duvida sobre reserva, problema de seguranca...">
        </div>
        <div class="form-group">
          <label for="mensagem-campo">Mensagem</label>
          <textarea id="mensagem-campo" name="mensagem" rows="5" required maxlength="1000" placeholder="Escreva sua mensagem..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
      </form>
    </div>

    <?php if (!empty($minhasMensagens)): ?>
    <div class="policy-content">
      <h2>Minhas mensagens</h2>
      <?php
        $statusMsgLabel = [
            'nova' => ['texto' => 'Aguardando resposta', 'cor' => '#a16207', 'fundo' => '#fef9c3'],
            'lida' => ['texto' => 'Em analise', 'cor' => '#a16207', 'fundo' => '#fef9c3'],
            'respondida' => ['texto' => 'Respondida', 'cor' => '#15803d', 'fundo' => '#dcfce7'],
        ];
      ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($minhasMensagens as $minhaMsg): $stMsg = $statusMsgLabel[$minhaMsg['status']]; ?>
          <div style="border:1px solid #eee;border-radius:10px;padding:12px 16px;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <strong><?php echo htmlspecialchars($minhaMsg['assunto']); ?></strong>
              <span style="background:<?php echo $stMsg['fundo']; ?>;color:<?php echo $stMsg['cor']; ?>;padding:2px 10px;border-radius:999px;font-size:.8rem;font-weight:600;">
                <?php echo $stMsg['texto']; ?>
              </span>
            </div>
            <small style="color:#888;"><?php echo (new DateTimeImmutable($minhaMsg['criado_em']))->format('d/m/Y H:i'); ?></small>
            <p style="margin:6px 0 0;color:#444;"><?php echo nl2br(htmlspecialchars($minhaMsg['mensagem'])); ?></p>
            <?php if ($minhaMsg['status'] === 'respondida'): ?>
              <div style="background:#f0fdf4;border-left:3px solid #16a34a;padding:8px 12px;border-radius:6px;margin-top:8px;">
                <strong style="font-size:.85rem;color:#15803d;">Resposta da administração:</strong>
                <p style="margin:4px 0 0;color:#333;"><?php echo nl2br(htmlspecialchars($minhaMsg['resposta'])); ?></p>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="policy-content">
      <h2>Verificação de e-mail</h2>
      <p>Ao se cadastrar no Travel Hostel, enviamos um código de 6 dígitos para o e-mail informado, válido por 15 minutos. Esse código confirma que o e-mail é realmente seu antes da conta ser criada. Se você não recebeu o código, verifique a caixa de spam ou refaça o cadastro para receber um novo. O mesmo tipo de código é usado na recuperação de senha.</p>

      <h2>Fale conosco</h2>
      <p>Para dúvidas, sugestões ou para reportar um problema (incluindo questões de segurança), utilize os canais indicados dentro da plataforma após o login, ou entre em contato com a equipe responsável pelo projeto.</p>

      <h2>Sobre quem desenvolve o Travel Hostel</h2>
      <p>
        O Travel Hostel é desenvolvido por estudantes da <strong>ETEC Dr. Emílio Hernandez Aguilar</strong>,
        como projeto prático do curso técnico em Desenvolvimento de Sistemas. Mais do que um exercício acadêmico,
        o projeto foi construído com a mesma disciplina de uma aplicação real: autenticação segura de usuários,
        processamento de pagamentos via PagBank (cartão de crédito e PIX), avaliações verificadas de hóspedes e
        um painel completo para administradores e anfitriões.
      </p>
      <p>
        O objetivo é unir teoria e prática — aplicando conceitos de engenharia de software, segurança da informação
        e experiência do usuário em um produto funcional de ponta a ponta, do cadastro à confirmação da reserva.
      </p>
    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
