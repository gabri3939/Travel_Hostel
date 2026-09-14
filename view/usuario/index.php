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

      <section class="profile-section profile-section--card" style="margin-top:24px;">
        <div class="profile-section-header"><h2>Minhas reservas</h2></div>
        <div class="profile-section-body">
          <?php if (empty($minhasReservas)): ?>
            <p>Você ainda não fez nenhuma reserva. <a href="<?php echo routeUrl('hostels'); ?>">Explore os hostels</a>.</p>
          <?php else: ?>
            <?php
              $statusLabel = [
                  'pendente'   => ['texto' => 'Pagamento pendente', 'cor' => '#a16207', 'fundo' => '#fef9c3'],
                  'em_analise' => ['texto' => 'Em análise', 'cor' => '#a16207', 'fundo' => '#fef9c3'],
                  'pago'       => ['texto' => 'Pago', 'cor' => '#15803d', 'fundo' => '#dcfce7'],
                  'recusado'   => ['texto' => 'Pagamento recusado', 'cor' => '#b91c1c', 'fundo' => '#fee2e2'],
                  'cancelado'  => ['texto' => 'Cancelada', 'cor' => '#4b5563', 'fundo' => '#f3f4f6'],
              ];
            ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($minhasReservas as $reserva): $st = $statusLabel[$reserva['status']] ?? ['texto' => $reserva['status'], 'cor' => '#374151', 'fundo' => '#f3f4f6']; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;border:1px solid #eee;border-radius:10px;padding:12px 16px;">
                  <div>
                    <strong><?php echo htmlspecialchars($reserva['hostel_nome']); ?></strong>
                    <div style="font-size:.85rem;color:#666;">
                      <?php echo (new DateTimeImmutable($reserva['data_checkin']))->format('d/m/Y'); ?> a
                      <?php echo (new DateTimeImmutable($reserva['data_checkout']))->format('d/m/Y'); ?>
                      &middot; R$ <?php echo number_format((float) $reserva['valor_total'], 2, ',', '.'); ?>
                    </div>
                  </div>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <span style="background:<?php echo $st['fundo']; ?>;color:<?php echo $st['cor']; ?>;padding:4px 12px;border-radius:999px;font-size:.8rem;font-weight:600;">
                      <?php echo htmlspecialchars($st['texto']); ?>
                    </span>
                    <?php if ($reserva['status'] === 'pendente'): ?>
                      <a href="<?php echo routeUrl('checkout', ['id' => $reserva['id']]); ?>" class="btn btn-primary btn-sm">Pagar agora</a>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ($reserva['status'] === 'pago' && (!$reserva['ja_avaliou_hostel'] || !$reserva['ja_avaliou_anfitriao'])): ?>
                  <div style="border:1px dashed #ddd;border-radius:10px;padding:12px 16px;margin-top:-4px;display:flex;flex-wrap:wrap;gap:20px;">
                    <?php if (!$reserva['ja_avaliou_hostel']): ?>
                      <form method="POST" action="<?php echo routeUrl('avaliar-hostel'); ?>" style="flex:1;min-width:220px;">
                        <input type="hidden" name="reserva_id" value="<?php echo (int) $reserva['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px;">Avaliar o hostel</label>
                        <select name="nota" required style="margin-bottom:6px;">
                          <option value="">Nota</option>
                          <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?></option>
                          <?php endfor; ?>
                        </select>
                        <textarea name="comentario" rows="2" placeholder="Como foi sua estadia? (opcional)" style="width:100%;box-sizing:border-box;margin-bottom:6px;"></textarea>
                        <button type="submit" class="btn btn-outline btn-sm">Enviar avaliação do hostel</button>
                      </form>
                    <?php endif; ?>

                    <?php if (!$reserva['ja_avaliou_anfitriao']): ?>
                      <form method="POST" action="<?php echo routeUrl('avaliar-anfitriao'); ?>" style="flex:1;min-width:220px;">
                        <input type="hidden" name="reserva_id" value="<?php echo (int) $reserva['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px;">Avaliar o anfitrião</label>
                        <select name="nota" required style="margin-bottom:6px;">
                          <option value="">Nota</option>
                          <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?></option>
                          <?php endfor; ?>
                        </select>
                        <textarea name="comentario" rows="2" placeholder="Como foi o atendimento do anfitrião? (opcional)" style="width:100%;box-sizing:border-box;margin-bottom:6px;"></textarea>
                        <button type="submit" class="btn btn-outline btn-sm">Enviar avaliação do anfitrião</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <?php if (($usuario['nivel'] ?? '') === 'anfitriao'): ?>
        <section class="profile-section profile-section--card" style="margin-top:24px;">
          <div class="profile-section-header"><h2>Reservas recebidas</h2></div>
          <div class="profile-section-body">
            <?php if (empty($reservasRecebidas)): ?>
              <p>Nenhuma reserva paga recebida ainda para os seus recintos.</p>
            <?php else: ?>
              <div style="display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($reservasRecebidas as $recebida): ?>
                  <div style="border:1px solid #eee;border-radius:10px;padding:12px 16px;">
                    <strong><?php echo htmlspecialchars($recebida['hostel_nome']); ?></strong>
                    <div style="font-size:.85rem;color:#666;">
                      <?php echo (new DateTimeImmutable($recebida['data_checkin']))->format('d/m/Y'); ?> a
                      <?php echo (new DateTimeImmutable($recebida['data_checkout']))->format('d/m/Y'); ?>
                      &middot; Hóspede: <?php echo htmlspecialchars($recebida['cliente_nome']); ?>
                      (<?php echo htmlspecialchars($recebida['cliente_email']); ?>)
                    </div>
                    <div style="font-size:.85rem;margin-top:4px;">
                      Total da reserva: R$ <?php echo number_format((float) $recebida['valor_total'], 2, ',', '.'); ?>
                      &middot; Taxa da plataforma (<?php echo number_format((float) $recebida['taxa_plataforma'], 0); ?>%): -R$ <?php echo number_format((float) $recebida['valor_plataforma'], 2, ',', '.'); ?>
                      &middot; <strong style="color:#15803d;">Você recebe: R$ <?php echo number_format((float) $recebida['valor_anfitriao'], 2, ',', '.'); ?></strong>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

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
