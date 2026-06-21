<?php
// Pagina de login para usuarios ja cadastrados.
$titulo = 'Entrar - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card">

    <div class="auth-header">
      <h1>Entrar</h1>
      <p>Bem-vindo(a) de volta ao Travel Hostel</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <?php // Exibe mensagem de erro ou sucesso ao tentar logar. ?>
      <div class="mensagem mensagem-<?php echo $tipoMensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <?php // Formulario de login envia email e senha para o controlador. ?>
    <form method="POST"
          action="<?php echo routeUrl('login'); ?>"
          class="auth-form"
          onsubmit="return validarLogin()">

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required placeholder="seu@email.com"/>
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <div class="password-wrapper">
          <input type="password" name="password" id="password" required placeholder="Sua senha"/>
          <button type="button" class="password-toggle" aria-label="Mostrar senha">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Entrar</button>
    </form>

    <p class="auth-switch">
      Nao tem conta?
      <a href="<?php echo routeUrl('cadastro'); ?>">Cadastre-se</a>
    </p>
    <p class="auth-switch">
      Esqueceu a senha?
      <a href="<?php echo routeUrl('recuperar'); ?>">Recuper senha</a>
    </p>

  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
