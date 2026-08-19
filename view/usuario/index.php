<?php
$titulo = 'Meu Perfil - Travel Hostel';
include ROOT . '/view/layouts/header.php';

$nomeUsuario = $usuario['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Usuario');
$avatarUrl = !empty($_SESSION['usuario_avatar'])
    ? $_SESSION['usuario_avatar']
    : 'https://ui-avatars.com/api/?name=' . urlencode($nomeUsuario) . '&background=0f766e&color=fff&size=256';
?>

<section class="section">
  <div class="container">
    <div class="card p-lg profile-page">

      <?php if (!empty($mensagem)): ?>
        <div class="mensagem mensagem-<?php echo $tipoMensagem; ?> mb-md">
          <?php echo $mensagem; ?>
        </div>
      <?php endif; ?>

      <div class="profile-top-card">
        <div class="profile-top-banner"></div>
        <div class="profile-top-panel">
          <div class="profile-top-left">
            <div class="profile-avatar profile-avatar--large">
              <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar de <?php echo htmlspecialchars($nomeUsuario); ?>">
            </div>
          </div>

          <div class="profile-top-right">
            <p class="profile-badge">Perfil Travel Hostel</p>
            <h1 class="profile-title"><?php echo htmlspecialchars($nomeUsuario); ?></h1>
            <p class="profile-headline">Aqui voce visualiza e gerencia seu perfil, mantem sua foto e acompanha sua avaliacao media.</p>

            <div class="profile-stat-grid">
              <div>
                <strong><?php echo number_format($usuario['avaliacao'] ?? 0, 1); ?></strong>
                <span>Nota media</span>
              </div>
              <div>
                <strong><?php echo (int) ($usuario['total_avaliacoes'] ?? 0); ?></strong>
                <span>Avaliacoes</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="profile-grid">
        <div class="profile-column profile-column--left">
          <div class="profile-section profile-section--card">
            <div class="profile-section-header">
              <h2>Foto de perfil</h2>
            </div>
            <div class="profile-section-body">
              <p class="profile-copy">Adicione ou troque sua foto. Aceita JPG, PNG, GIF ou WEBP com ate 2MB.</p>

              <form method="POST" action="<?php echo routeUrl('perfil'); ?>" enctype="multipart/form-data" class="profile-upload-form" id="avatarForm">
                <input type="file" name="avatar" id="avatar" accept=".jpg,.jpeg,.png,.gif,.webp" class="profile-file-input" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('avatar').click();">Escolher imagem</button>
              </form>
              <script>
                document.getElementById('avatar').addEventListener('change', function() {
                  if (this.files && this.files.length > 0) {
                    document.getElementById('avatarForm').submit();
                  }
                });
              </script>
            </div>
          </div>

          <div class="profile-section profile-section--card">
            <div class="profile-section-header">
              <h2>Sobre</h2>
            </div>
            <div class="profile-section-body">
              <p>Este e o espaco do seu perfil pessoal no Travel Hostel. Mantenha os dados atualizados para deixar sua conta organizada e confiavel.</p>
            </div>
          </div>

          <div class="profile-section profile-section--card">
            <div class="profile-section-header">
              <h2>Dados pessoais</h2>
            </div>
            <div class="profile-section-list">
              <div class="profile-detail">
                <span>Email</span>
                <strong><?php echo htmlspecialchars($usuario['email'] ?? ($_SESSION['usuario_email'] ?? '')); ?></strong>
              </div>
              <?php if (!empty($usuario['cpf'])): ?>
                <div class="profile-detail">
                  <span>CPF</span>
                  <strong><?php echo htmlspecialchars($usuario['cpf']); ?></strong>
                </div>
              <?php endif; ?>
              <?php if (!empty($usuario['telefone'])): ?>
                <div class="profile-detail">
                  <span>Telefone</span>
                  <strong><?php echo htmlspecialchars($usuario['telefone']); ?></strong>
                </div>
              <?php endif; ?>
              <?php if (!empty($usuario['data_nascimento'])): ?>
                <div class="profile-detail">
                  <span>Data de nascimento</span>
                  <strong><?php echo htmlspecialchars($usuario['data_nascimento']); ?></strong>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-column profile-column--right">
          <div class="profile-section profile-section--card profile-section--highlight">
            <div class="profile-section-header">
              <h2>Avaliacao do perfil</h2>
            </div>
            <div class="profile-section-body">
              <div class="profile-rating-summary">
                <div>
                  <span class="profile-rating-value"><?php echo number_format($usuario['avaliacao'] ?? 0, 1); ?></span>
                  <span class="profile-rating-subtitle">/ 5,0 media</span>
                </div>
                <p class="profile-rating-text"><?php echo (int) ($usuario['total_avaliacoes'] ?? 0); ?> avaliacoes registradas</p>
              </div>

              <form method="POST" action="<?php echo routeUrl('perfil'); ?>" class="profile-rating-form">
                <label for="rating"><strong>Avalie seu perfil</strong></label>
                <select name="rating" id="rating">
                  <option value="">Selecione uma nota</option>
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?></option>
                  <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary">Enviar avaliacao</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php if (($usuario['nivel'] ?? 'usuario') === 'usuario'): ?>
        <section class="profile-section profile-section--card" id="solicitar-anfitriao" style="margin-top:24px;">
          <div class="profile-section-header"><h2>Quer anunciar seu recinto?</h2></div>
          <div class="profile-section-body">
            <?php if (($usuario['solicitacao_anfitriao'] ?? 'nenhuma') === 'pendente'): ?>
              <p>Sua solicitacao para ser anfitriao esta em analise pelo administrador.</p>
            <?php elseif (($usuario['solicitacao_anfitriao'] ?? 'nenhuma') === 'rejeitada'): ?>
              <p>Sua solicitacao anterior foi rejeitada. Revise seus dados e envie uma nova solicitacao.</p>
              <form method="POST" action="<?php echo routeUrl('perfil'); ?>"><input type="hidden" name="acao" value="solicitar_anfitriao"><button type="submit" class="btn btn-primary">Solicitar novamente</button></form>
            <?php else: ?>
              <p>Solicite acesso para cadastrar seus recintos, enviar imagens e receber aprovacao para publica-los.</p>
              <form method="POST" action="<?php echo routeUrl('perfil'); ?>"><input type="hidden" name="acao" value="solicitar_anfitriao"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-house-chimney"></i> Solicitar acesso de anfitriao</button></form>
            <?php endif; ?>
          </div>
        </section>
      <?php elseif (($usuario['nivel'] ?? '') === 'anfitriao'): ?>
        <section class="profile-section profile-section--card" style="margin-top:24px;">
          <div class="profile-section-header"><h2>Area do anfitriao</h2></div>
          <div class="profile-section-body"><p>Voce ja pode cadastrar e acompanhar seus recintos.</p><a href="<?php echo routeUrl('anfitriao'); ?>" class="btn btn-primary">Cadastrar recinto</a></div>
        </section>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php include ROOT . '/view/layouts/footer.php'; ?>
