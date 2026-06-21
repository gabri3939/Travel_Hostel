<?php
$titulo = 'Recuperação de senha - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>
<style>
    .input-btn-row {
  display: flex;
  gap: var(--spacing-sm);
}

.input-btn-row input {
  flex: 1;
}
</style>
<section class="recover-page">
  <div class="recover-card">

    <div class="recover-header">
      <h1>Recuperar senha</h1>
      <p>Digite o código enviado para seu e-mail para redefinir sua senha.</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="mensagem mensagem-<?php echo $tipoMensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo routeUrl('recuperar'); ?>" class="recover-form">
      <div class="form-group">
        <label for="email">E-mail</label>
        <div class="input-btn-row">
          <input type="email" name="email" id="email" required
       placeholder="seu@email.com" autofocus
       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
          <button type="submit" name="acao" value="enviar" class="btn btn-primary">Enviar código</button>
        </div>
        <span class="field-feedback"></span>
      </div>

      <div class="form-group">
        <label for="verificationCode">Código de verificação</label>
        <input type="text" name="verificationCode" id="verificationCode" maxlength="6"
               placeholder="000000" inputmode="numeric" />
        <span class="field-feedback"></span>
      </div>

      <button type="submit" name="acao" value="verificar" class="btn btn-primary btn-full">Confirmar código</button>
    </form>

    <p class="auth-switch">
      Lembrou a senha?
      <a href="<?php echo routeUrl('login'); ?>">Voltar para o login</a>
    </p>

  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>