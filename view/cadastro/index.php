<?php
// Pagina de cadastro com formulatio de criacao de conta e validacoes simples.
$titulo = 'Cadastro - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>

<section class="auth-page">
  <div class="auth-card">

    <div class="auth-header">
      <h1>Criar Conta</h1>
      <p>Junte-se a comunidade Travel Hostel</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <?php // Exibe feedback de validacao ou erro de cadastro. ?>
      <div class="mensagem mensagem-<?php echo $tipoMensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <?php // Formulario de cadastro com campos obrigatorios e campos opcionais de endereco. ?>
    <form method="POST"
          action="<?php echo routeUrl('cadastro'); ?>"
          class="auth-form"
          onsubmit="return validarCadastroForm()">

      <div class="form-group">
        <label for="name">Nome Completo *</label>
        <input type="text" name="name" id="name" required maxlength="50"
               placeholder="Seu nome completo"/>
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" name="email" id="email" required maxlength="255"
               placeholder="seu@email.com"/>
      </div>

      <div class="form-group">
        <label for="cep">CEP</label>
        <input type="text" name="cep" id="cep" maxlength="9"
               placeholder="00000-000"
               autocomplete="postal-code"
               oninput="mascaraCEP(this)"
               onblur="buscarCEP()"/>
        <small id="cepFeedback" class="field-feedback"></small>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="street">Rua</label>
          <input type="text" name="street" id="street" maxlength="100"
                 placeholder="Rua"/>
        </div>
        <div class="form-group">
          <label for="city">Cidade</label>
          <input type="text" name="city" id="city" maxlength="100"
                 placeholder="Cidade"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="state">Estado</label>
          <input type="text" name="state" id="state" maxlength="2"
                 placeholder="UF"/>
        </div>
        <div class="form-group">
          <label for="cpf">CPF</label>
          <input type="text" name="cpf" id="cpf" maxlength="14"
                 placeholder="000.000.000-00"
                 oninput="mascaraCPF(this)"/>
        </div>
      </div>
      <div class="form-group">
        <label for="phone">Telefone</label>
        <input type="tel" name="phone" id="phone" maxlength="15"
               placeholder="(00) 00000-0000"
               oninput="mascaraTelefone(this)"/>
      </div>

      <div class="form-group">
        <label for="birthDate">Data de Nascimento</label>
        <input type="date" name="birthDate" id="birthDate"/>
      </div>

      <div class="form-group">
        <label for="password">Senha * <small>(mínimo 8 caracteres)</small></label>
        <div class="password-wrapper">
          <input type="password" name="password" id="password" required
                 minlength="8" maxlength="255"
                 placeholder="Sua senha"/>
          <button type="button" class="password-toggle" aria-label="Mostrar senha">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirmar Senha *</label>
        <div class="password-wrapper">
          <input type="password" name="confirmPassword" id="confirmPassword" required
                 minlength="8" maxlength="255"
                 placeholder="Repita sua senha"/>
          <button type="button" class="password-toggle" aria-label="Mostrar senha">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Cadastrar</button>
    </form>

    <p class="auth-switch">
      Ja tem conta?
      <a href="<?php echo routeUrl('login'); ?>">Entrar</a>
    </p>

  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
