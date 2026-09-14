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
<link rel="stylesheet" href="<?php echo assetUrl('css/admin-dashboard.css'); ?>">

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

    <section class="admin-stats" aria-label="Resumo financeiro">
      <article class="admin-stat admin-stat--green">
        <span class="admin-stat-icon"><i class="fa-solid fa-sack-dollar"></i></span>
        <span><strong>R$ <?php echo number_format($resumoFinanceiro['total_plataforma'], 2, ',', '.'); ?></strong><small>Receita da plataforma (15%)</small></span>
      </article>
      <article class="admin-stat admin-stat--blue">
        <span class="admin-stat-icon"><i class="fa-solid fa-house-chimney"></i></span>
        <span><strong>R$ <?php echo number_format($resumoFinanceiro['total_anfitrioes'], 2, ',', '.'); ?></strong><small>A repassar aos anfitrioes</small></span>
      </article>
      <article class="admin-stat admin-stat--amber">
        <span class="admin-stat-icon"><i class="fa-solid fa-coins"></i></span>
        <span><strong>R$ <?php echo number_format($resumoFinanceiro['total_bruto'], 2, ',', '.'); ?></strong><small>Volume total pago</small></span>
      </article>
      <article class="admin-stat admin-stat--violet">
        <span class="admin-stat-icon"><i class="fa-solid fa-receipt"></i></span>
        <span><strong><?php echo $resumoFinanceiro['reservas_pagas']; ?></strong><small>Reservas pagas</small></span>
      </article>
    </section>
    <p style="margin:-14px 0 20px;font-size:.8rem;color:#888;">
      * O repasse aos anfitrioes ainda e feito manualmente (o pagamento cai integralmente na conta PagBank da plataforma).
    </p>

    <!-- Mensagens enviadas pela pagina de contato. -->
    <section class="admin-panel admin-approval-panel">
      <div class="admin-panel-header">
        <div><h2>Mensagens de contato</h2><p>Enviadas pelo formulario publico de contato.</p></div>
        <span class="admin-count"><?php echo count(array_filter($mensagensContato, static fn($m) => $m['status'] === 'nova')); ?> novas</span>
      </div>

      <?php if (empty($mensagensContato)): ?>
        <p class="admin-empty">Nenhuma mensagem recebida ainda.</p>
      <?php else: ?>
        <div class="admin-host-requests">
          <?php foreach ($mensagensContato as $msgContato): ?>
            <div class="admin-host-request" style="flex-direction:column;align-items:stretch;gap:10px;">
              <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <div>
                  <strong><?php echo htmlspecialchars($msgContato['assunto']); ?></strong>
                  <span> — <?php echo htmlspecialchars($msgContato['nome']); ?> (<?php echo htmlspecialchars($msgContato['email']); ?>)</span>
                  <small style="display:block;color:#888;"><?php echo (new DateTimeImmutable($msgContato['criado_em']))->format('d/m/Y H:i'); ?></small>
                </div>
                <span class="admin-status admin-status--<?php echo $msgContato['status'] === 'respondida' ? 'active' : 'inactive'; ?>">
                  <?php echo ['nova' => 'Nova', 'lida' => 'Lida', 'respondida' => 'Respondida'][$msgContato['status']]; ?>
                </span>
              </div>
              <p style="margin:0;color:#444;"><?php echo nl2br(htmlspecialchars($msgContato['mensagem'])); ?></p>

              <?php if ($msgContato['status'] === 'respondida'): ?>
                <div style="background:#f0fdf4;border-left:3px solid #16a34a;padding:8px 12px;border-radius:6px;">
                  <strong style="font-size:.85rem;color:#15803d;">Sua resposta:</strong>
                  <p style="margin:4px 0 0;color:#333;"><?php echo nl2br(htmlspecialchars($msgContato['resposta'])); ?></p>
                </div>
              <?php else: ?>
                <div class="admin-request-actions">
                  <?php if ($msgContato['status'] === 'nova'): ?>
                    <form method="POST" action="<?php echo routeUrl('admin'); ?>">
                      <input type="hidden" name="acao" value="marcar_lida">
                      <input type="hidden" name="id" value="<?php echo (int) $msgContato['id']; ?>">
                      <button type="submit" class="admin-button admin-button--status"><i class="fa-solid fa-eye"></i> Marcar como lida</button>
                    </form>
                  <?php endif; ?>
                  <form method="POST" action="<?php echo routeUrl('admin'); ?>" class="admin-reject-form" style="flex:1;">
                    <input type="hidden" name="acao" value="responder_mensagem">
                    <input type="hidden" name="id" value="<?php echo (int) $msgContato['id']; ?>">
                    <input name="resposta" placeholder="Escreva a resposta (enviada por e-mail)" style="flex:1;">
                    <button type="submit" class="admin-button admin-button--approve"><i class="fa-solid fa-reply"></i> Responder</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
              <?php
                $urlsImagensSolicitacao = array_map(
                    static fn($img) => URL_BASE . '/' . ltrim($img, '/'),
                    $solicitacao['imagens'] ?? []
                );
              ?>
              <div class="admin-request-gallery" data-imagens="<?php echo htmlspecialchars(json_encode($urlsImagensSolicitacao)); ?>">
                <?php foreach (array_slice($urlsImagensSolicitacao, 0, 4) as $indiceImg => $imagem): ?>
                  <div class="admin-gallery-thumb" data-index="<?php echo $indiceImg; ?>">
                    <img src="<?php echo htmlspecialchars($imagem); ?>" alt="Imagem de <?php echo htmlspecialchars($solicitacao['nome']); ?>">
                    <?php if ($indiceImg === 3 && count($urlsImagensSolicitacao) > 4): ?>
                      <span class="admin-gallery-mais">+<?php echo count($urlsImagensSolicitacao) - 4; ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                <?php if (empty($urlsImagensSolicitacao)): ?><span class="admin-no-image"><i class="fa-solid fa-image"></i></span><?php endif; ?>
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

    <!-- Acesso total do admin a todos os recintos, de qualquer anfitriao e status. -->
    <?php $statusHostelAdminLabel = ['aprovado' => 'Aprovado', 'pendente' => 'Em analise', 'rejeitado' => 'Rejeitado']; ?>
    <section class="admin-panel">
      <div class="admin-panel-header">
        <div>
          <h2>Todos os recintos</h2>
          <p>Visao completa da plataforma. Editar, desativar ou excluir qualquer recinto, de qualquer anfitriao.</p>
        </div>
        <span class="admin-count"><?php echo count($todosHostels); ?> recintos</span>
      </div>

      <?php if (empty($todosHostels)): ?>
        <p class="admin-empty">Nenhum recinto cadastrado.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Recinto</th>
                <th>Anfitriao</th>
                <th>Cidade / Preco</th>
                <th>Status</th>
                <th class="admin-actions-heading">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todosHostels as $h): $hAtivo = (int) $h['ativo'] === 1; ?>
                <tr class="<?php echo $hAtivo ? '' : 'is-inactive'; ?>">
                  <td>
                    <form method="POST" action="<?php echo routeUrl('admin'); ?>" id="edit-adm-hostel-<?php echo (int) $h['id']; ?>">
                      <input type="hidden" name="acao" value="editar_hostel_admin">
                      <input type="hidden" name="id" value="<?php echo (int) $h['id']; ?>">
                    </form>
                    <label class="sr-only" for="nome-adm-h-<?php echo (int) $h['id']; ?>">Nome</label>
                    <input form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" id="nome-adm-h-<?php echo (int) $h['id']; ?>" name="nome" value="<?php echo htmlspecialchars($h['nome']); ?>" required>
                    <label class="sr-only" for="cat-adm-h-<?php echo (int) $h['id']; ?>">Categoria</label>
                    <select form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" id="cat-adm-h-<?php echo (int) $h['id']; ?>" name="categoria_id" style="margin-top:6px;">
                      <option value="">Sem categoria</option>
                      <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo (int) $categoria['id']; ?>" <?php echo (int) ($h['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nome']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <?php if (!empty($h['anfitriao_nome'])): ?>
                      <?php echo htmlspecialchars($h['anfitriao_nome']); ?><br>
                      <small style="color:#888;"><?php echo htmlspecialchars($h['anfitriao_email']); ?></small>
                    <?php else: ?>
                      <small style="color:#888;">Sem anfitriao (recinto de exemplo)</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <label class="sr-only" for="cidade-adm-h-<?php echo (int) $h['id']; ?>">Cidade</label>
                    <input form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" id="cidade-adm-h-<?php echo (int) $h['id']; ?>" name="cidade" value="<?php echo htmlspecialchars($h['cidade']); ?>" required>
                    <label class="sr-only" for="estado-adm-h-<?php echo (int) $h['id']; ?>">Estado</label>
                    <input form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" id="estado-adm-h-<?php echo (int) $h['id']; ?>" name="estado" value="<?php echo htmlspecialchars($h['estado'] ?? ''); ?>" placeholder="UF" style="width:55px;">
                    <label class="sr-only" for="preco-adm-h-<?php echo (int) $h['id']; ?>">Preco</label>
                    <input form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" id="preco-adm-h-<?php echo (int) $h['id']; ?>" name="preco_diaria" type="number" min="1" step="0.01" value="<?php echo (float) $h['preco_diaria']; ?>" required>
                  </td>
                  <td>
                    <span class="admin-status admin-status--<?php echo $h['status_aprovacao'] === 'aprovado' ? 'active' : 'inactive'; ?>">
                      <?php echo $statusHostelAdminLabel[$h['status_aprovacao']] ?? htmlspecialchars($h['status_aprovacao']); ?>
                    </span><br>
                    <span class="admin-status admin-status--<?php echo $hAtivo ? 'active' : 'inactive'; ?>" style="margin-top:4px;">
                      <?php echo $hAtivo ? 'Visivel' : 'Desativado'; ?>
                    </span>
                  </td>
                  <td class="admin-actions">
                    <button form="edit-adm-hostel-<?php echo (int) $h['id']; ?>" type="submit" class="admin-button admin-button--save"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                    <form method="POST" action="<?php echo routeUrl('admin'); ?>">
                      <input type="hidden" name="acao" value="status_hostel_admin">
                      <input type="hidden" name="id" value="<?php echo (int) $h['id']; ?>">
                      <input type="hidden" name="ativo" value="<?php echo $hAtivo ? '0' : '1'; ?>">
                      <button type="submit" class="admin-button admin-button--status">
                        <i class="fa-solid fa-<?php echo $hAtivo ? 'ban' : 'rotate-left'; ?>"></i> <?php echo $hAtivo ? 'Desativar' : 'Reativar'; ?>
                      </button>
                    </form>
                    <form method="POST" action="<?php echo routeUrl('admin'); ?>" onsubmit="return confirm('Excluir este recinto permanentemente? So funciona se ele nao tiver reservas ou avaliacoes.');">
                      <input type="hidden" name="acao" value="excluir_hostel">
                      <input type="hidden" name="id" value="<?php echo (int) $h['id']; ?>">
                      <button type="submit" class="admin-button admin-button--reject"><i class="fa-solid fa-trash"></i> Excluir</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<!-- Lightbox compartilhado das galerias de fotos (solicitacoes pendentes) -->
<div class="lightbox" id="lightbox" hidden>
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Fechar">&times;</button>
  <button type="button" class="lightbox-prev" id="lightboxPrev" aria-label="Imagem anterior">&lsaquo;</button>
  <img src="" alt="" id="lightboxImg">
  <button type="button" class="lightbox-next" id="lightboxNext" aria-label="Proxima imagem">&rsaquo;</button>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>
<script src="<?php echo assetUrl('js/admin-gallery.js'); ?>"></script>

<?php include ROOT . '/view/layouts/footer.php'; ?>