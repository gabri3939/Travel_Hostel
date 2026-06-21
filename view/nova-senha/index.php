<?php
$titulo = 'Nova senha - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="recover-page">
  <div class="recover-card">

    <div class="recover-header">
      <h1>Nova senha</h1>
      <p>Escolha uma senha segura para sua conta.</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="mensagem mensagem-<?php echo $tipoMensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo routeUrl('nova-senha'); ?>" class="recover-form">

      <div class="form-group">
        <label for="password">Nova senha</label>
        <div class="password-wrapper">
          <input type="password" name="password" id="password" required
                 placeholder="Mínimo 8 caracteres" autofocus />
          <button type="button" class="password-toggle" onclick="toggleSenha('password', this)">
            <i class="fa fa-eye"></i>
          </button>
        </div>
        <span class="field-feedback"></span>
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirmar nova senha</label>
        <div class="password-wrapper">
          <input type="password" name="confirmPassword" id="confirmPassword" required
                 placeholder="Repita a senha" />
          <button type="button" class="password-toggle" onclick="toggleSenha('confirmPassword', this)">
            <i class="fa fa-eye"></i>
          </button>
        </div>
        <span class="field-feedback" id="confirm-feedback"></span>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Salvar nova senha</button>
    </form>

    <p class="auth-switch">
      Lembrou a senha?
      <a href="<?php echo routeUrl('login'); ?>">Voltar para o login</a>
    </p>

  </div>
</section>

<script>
  function toggleSenha(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }

  document.getElementById('confirmPassword').addEventListener('input', function () {
    const senha = document.getElementById('password').value;
    const feedback = document.getElementById('confirm-feedback');
    if (this.value && this.value !== senha) {
      feedback.textContent = 'As senhas não coincidem.';
    } else {
      feedback.textContent = '';
    }
  });
</script>

<?php include ROOT . '/view/layouts/footer.php'; ?>