<?php
$titulo = 'Verificacao de Email - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card">

    <div class="auth-header">
      <h1>Verificar Email</h1>
      <p>Digite o codigo enviado para seu email para concluir o cadastro.</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="mensagem mensagem-<?php echo $tipoMensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo routeUrl('verificar'); ?>" class="auth-form">
      <div class="form-group">
        <label for="verificationCode">Codigo de Verificacao</label>
        <input type="text" name="verificationCode" id="verificationCode" required maxlength="6"
               placeholder="000000" autofocus />
      </div>

      <button type="submit" class="btn btn-primary btn-full">Confirmar Codigo</button>
    </form>

    <p class="auth-switch">
      Ja possui um codigo?
      <a href="<?php echo routeUrl('verificar'); ?>">Inserir codigo</a>
    </p>

  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
