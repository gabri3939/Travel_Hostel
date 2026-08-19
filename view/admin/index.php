<?php
$titulo = 'Dashboard Admin - Travel Hostel';
include ROOT . '/view/layouts/header.php';

$totalUsuarios = count($usuarios);
$usuariosAtivos = count(array_filter($usuarios, static function (array $usuario): bool {
    return !array_key_exists('ativo', $usuario) || (int) $usuario['ativo'] === 1;
}));
$usuariosInativos = $totalUsuarios - $usuariosAtivos;
$totalAdmins = count(array_filter($usuarios, static function (array $usuario): bool {
    return ($usuario['nivel'] ?? '') === 'admin';
}));
$totalSolicitacoes = count($solicitacoesHostel ?? []);
$totalSolicitacoesAnfitriao = count($solicitacoesAnfitriao ?? []);
?>
<link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/admin-dashboard.css">

<main class="admin-dashboard">
  <div class="container">
    <div class="admin-heading">
      <div>
        <p class="admin-eyebrow">Painel de controle</p>
        <h1>Gestao de usuarios</h1>
        <p>Consulte, atualize e controle o acesso das contas cadastradas.</p>
      </div>
      <span class="admin-mark"><i class="fa-solid fa-shield-halved"></i> Admin</span>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="admin-alert admin-alert--<?php echo htmlspecialchars($tipoMensagem); ?>">
        <?php echo htmlspecialchars($mensagem); ?>
      </div>
    <?php endif; ?>

    <section class="admin-stats" aria-label="Resumo dos usuarios">
      <article class="admin-stat admin-stat--blue">
        <span class="admin-stat-icon"><i class="fa-solid fa-users"></i></span>
        <span><strong><?php echo $totalUsuarios; ?></strong><small>Total de usuarios</small></span>
      </article>
      <article class="admin-stat admin-stat--green">
        <span class="admin-stat-icon"><i class="fa-solid fa-user-check"></i></span>
        <span><strong><?php echo $usuariosAtivos; ?></strong><small>Contas ativas</small></span>
      </article>
      <article class="admin-stat admin-stat--amber">
        <span class="admin-stat-icon"><i class="fa-solid fa-user-slash"></i></span>
        <span><strong><?php echo $usuariosInativos; ?></strong><small>Contas inativas</small></span>
      </article>
      <article class="admin-stat admin-stat--violet">
        <span class="admin-stat-icon"><i class="fa-solid fa-user-shield"></i></span>
        <span><strong><?php echo $totalAdmins; ?></strong><small>Administradores</small></span>
      </article>
    </section>

    <!-- Primeira etapa: aprovar o usuario para liberar a area de anfitriao. -->
    <section class="admin-panel admin-approval-panel">
      <div class="admin-panel-header">
        <div><h2>Pedidos para ser anfitriao</h2><p>Aprove o acesso antes que o usuario possa cadastrar recintos.</p></div>
        <span class="admin-count"><?php echo $totalSolicitacoesAnfitriao; ?> pendentes</span>
      </div>
      <?php if ($totalSolicitacoesAnfitriao === 0): ?>
        <p class="admin-empty">Nenhum pedido de anfitriao pendente.</p>
      <?php else: ?>
        <div class="admin-host-requests">
          <?php foreach ($solicitacoesAnfitriao as $pedido): ?>
            <div class="admin-host-request">
              <div><strong><?php echo htmlspecialchars($pedido['nome']); ?></strong><span><?php echo htmlspecialchars($pedido['email']); ?></span><small>Cadastrado em <?php echo htmlspecialchars(date('d/m/Y', strtotime($pedido['data_cadastro']))); ?></small></div>
              <div class="admin-request-actions">
                <form method="POST" action="<?php echo routeUrl('admin'); ?>"><input type="hidden" name="acao" value="aprovar_anfitriao"><input type="hidden" name="id" value="<?php echo (int) $pedido['id']; ?>"><button class="admin-button admin-button--approve" type="submit"><i class="fa-solid fa-check"></i> Aprovar anfitriao</button></form>
                <form method="POST" action="<?php echo routeUrl('admin'); ?>" class="admin-reject-form"><input type="hidden" name="acao" value="rejeitar_anfitriao"><input type="hidden" name="id" value="<?php echo (int) $pedido['id']; ?>"><input name="motivo" placeholder="Motivo (opcional)"><button class="admin-button admin-button--reject" type="submit"><i class="fa-solid fa-xmark"></i> Rejeitar</button></form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Segunda etapa: aprovar o recinto e torna-lo publico no catalogo. -->
    <section class="admin-panel admin-approval-panel">
      <div class="admin-panel-header">
        <div>
          <h2>Aprovacao de recintos</h2>
          <p>Anfitrioes aguardam sua analise antes da publicacao no catalogo.</p>
        </div>
        <span class="admin-count"><?php echo $totalSolicitacoes; ?> pendentes</span>
      </div>

      <?php if ($totalSolicitacoes === 0): ?>
        <p class="admin-empty">Nenhum recinto aguardando aprovacao.</p>
      <?php else: ?>
        <div class="admin-requests">
          <?php foreach ($solicitacoesHostel as $solicitacao): ?>
            <article class="admin-request">
              <div class="admin-request-gallery">
                <?php foreach (array_slice($solicitacao['imagens'] ?? [], 0, 4) as $imagem): ?>
                  <img src="<?php echo URL_BASE . '/' . htmlspecialchars(ltrim($imagem, '/')); ?>" alt="Imagem de <?php echo htmlspecialchars($solicitacao['nome']); ?>">
                <?php endforeach; ?>
                <?php if (empty($solicitacao['imagens'])): ?><span class="admin-no-image"><i class="fa-solid fa-image"></i></span><?php endif; ?>
              </div>
              <div class="admin-request-content">
                <div class="admin-request-title">
                  <div>
                    <h3><?php echo htmlspecialchars($solicitacao['nome']); ?></h3>
                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($solicitacao['cidade'] . (!empty($solicitacao['estado']) ? ', ' . $solicitacao['estado'] : '')); ?> · <?php echo htmlspecialchars($solicitacao['categoria_nome'] ?? 'Sem categoria'); ?></p>
                  </div>
                  <strong>R$ <?php echo number_format((float) $solicitacao['preco_diaria'], 2, ',', '.'); ?><small>/noite</small></strong>
                </div>
                <p class="admin-request-description"><?php echo nl2br(htmlspecialchars($solicitacao['descricao'] ?? 'Sem descricao.')); ?></p>
                <div class="admin-request-meta">
                  <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($solicitacao['anfitriao_nome'] ?? 'Anfitriao'); ?></span>
                  <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($solicitacao['anfitriao_email'] ?? ''); ?></span>
                  <span><i class="fa-solid fa-bed"></i> <?php echo (int) $solicitacao['camas']; ?> camas</span>
                </div>
                <div class="admin-request-actions">
                  <form method="POST" action="<?php echo routeUrl('admin'); ?>">
                    <input type="hidden" name="acao" value="aprovar_hostel">
                    <input type="hidden" name="id" value="<?php echo (int) $solicitacao['id']; ?>">
                    <button class="admin-button admin-button--approve" type="submit"><i class="fa-solid fa-check"></i> Aprovar e publicar</button>
                  </form>
                  <form method="POST" action="<?php echo routeUrl('admin'); ?>" class="admin-reject-form">
                    <input type="hidden" name="acao" value="rejeitar_hostel">
                    <input type="hidden" name="id" value="<?php echo (int) $solicitacao['id']; ?>">
                    <input name="motivo" placeholder="Motivo da rejeicao (opcional)">
                    <button class="admin-button admin-button--reject" type="submit"><i class="fa-solid fa-xmark"></i> Rejeitar</button>
                  </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="admin-panel">
      <div class="admin-panel-header">
        <div>
          <h2>Usuarios cadastrados</h2>
          <p>Edite os dados diretamente na tabela. Inativar remove o acesso sem apagar o cadastro.</p>
        </div>
        <span class="admin-count"><?php echo $totalUsuarios; ?> registros</span>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Contato</th>
              <th>Nivel</th>
              <th>Status</th>
              <th class="admin-actions-heading">Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="5" class="admin-empty">Nenhum usuario encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($usuarios as $usuario): ?>
              <?php $ativo = !array_key_exists('ativo', $usuario) || (int) $usuario['ativo'] === 1; ?>
              <tr class="<?php echo $ativo ? '' : 'is-inactive'; ?>">
                <td>
                  <form method="POST" action="<?php echo routeUrl('admin'); ?>" id="edit-<?php echo (int) $usuario['id']; ?>">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                  </form>
                  <label class="sr-only" for="nome-<?php echo (int) $usuario['id']; ?>">Nome</label>
                  <input form="edit-<?php echo (int) $usuario['id']; ?>" id="nome-<?php echo (int) $usuario['id']; ?>" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                  <label class="sr-only" for="email-<?php echo (int) $usuario['id']; ?>">Email</label>
                  <input form="edit-<?php echo (int) $usuario['id']; ?>" id="email-<?php echo (int) $usuario['id']; ?>" type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </td>
                <td>
                    <label class="sr-only" for="cpf-<?php echo (int) $usuario['id']; ?>">CPF</label>
                    <input form="edit-<?php echo (int) $usuario['id']; ?>" id="cpf-<?php echo (int) $usuario['id']; ?>" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" placeholder="CPF">
                    <label class="sr-only" for="telefone-<?php echo (int) $usuario['id']; ?>">Telefone</label>
                    <input form="edit-<?php echo (int) $usuario['id']; ?>" id="telefone-<?php echo (int) $usuario['id']; ?>" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="Telefone">
                </td>
                <td>
                    <label class="sr-only" for="nivel-<?php echo (int) $usuario['id']; ?>">Nivel</label>
                    <select form="edit-<?php echo (int) $usuario['id']; ?>" id="nivel-<?php echo (int) $usuario['id']; ?>" name="nivel">
                      <?php foreach (['usuario' => 'Usuario', 'anfitriao' => 'Anfitriao', 'admin' => 'Admin'] as $valor => $label): ?>
                        <option value="<?php echo $valor; ?>" <?php echo ($usuario['nivel'] ?? 'usuario') === $valor ? 'selected' : ''; ?>><?php echo $label; ?></option>
                      <?php endforeach; ?>
                    </select>
                </td>
                <td><span class="admin-status admin-status--<?php echo $ativo ? 'active' : 'inactive'; ?>"><?php echo $ativo ? 'Ativo' : 'Inativo'; ?></span></td>
                <td class="admin-actions">
                    <button form="edit-<?php echo (int) $usuario['id']; ?>" type="submit" class="admin-button admin-button--save"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                  <form method="POST" action="<?php echo routeUrl('admin'); ?>">
                    <input type="hidden" name="acao" value="status">
                    <input type="hidden" name="id" value="<?php echo (int) $usuario['id']; ?>">
                    <input type="hidden" name="ativo" value="<?php echo $ativo ? '0' : '1'; ?>">
                    <button type="submit" class="admin-button admin-button--status" <?php echo (int) $usuario['id'] === (int) $admin['id'] ? 'disabled title="Sua conta nao pode ser inativada"' : ''; ?>>
                      <i class="fa-solid fa-<?php echo $ativo ? 'ban' : 'rotate-left'; ?>"></i> <?php echo $ativo ? 'Inativar' : 'Reativar'; ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>

<?php include ROOT . '/view/layouts/footer.php'; ?>