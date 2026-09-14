<?php
$titulo = 'Cadastrar recinto - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>
<link rel="stylesheet" href="<?php echo assetUrl('css/admin-dashboard.css'); ?>">

<main class="admin-dashboard host-request-page">
  <div class="container">
    <div class="admin-heading">
      <div>
        <p class="admin-eyebrow">Area do anfitriao</p>
        <h1>Cadastre seu recinto</h1>
        <p>Envie os dados e as imagens. Seu anuncio sera publicado depois da aprovacao.</p>
      </div>
      <span class="admin-mark"><i class="fa-solid fa-house-chimney"></i> Nova solicitacao</span>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="admin-alert admin-alert--<?php echo htmlspecialchars($tipoMensagem); ?>"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <?php $statusRecintoLabel = ['aprovado' => 'Aprovado', 'pendente' => 'Em analise', 'rejeitado' => 'Rejeitado']; ?>
    <section class="admin-panel">
      <div class="admin-panel-header">
        <div>
          <h2>Meus recintos</h2>
          <p>Edite os dados ou desative um recinto para que ele pare de aparecer para os viajantes.</p>
        </div>
        <span class="admin-count"><?php echo count($meusRecintos); ?> cadastrados</span>
      </div>

      <?php if (empty($meusRecintos)): ?>
        <p class="admin-empty">Voce ainda nao cadastrou nenhum recinto. Use o formulario abaixo.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Recinto</th>
                <th>Fotos</th>
                <th>Cidade / Preco</th>
                <th>Categoria</th>
                <th>Aprovacao</th>
                <th class="admin-actions-heading">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($meusRecintos as $recinto): $recintoAtivo = (int) $recinto['ativo'] === 1; ?>
                <tr class="<?php echo $recintoAtivo ? '' : 'is-inactive'; ?>">
                  <td>
                    <form method="POST" action="<?php echo routeUrl('anfitriao'); ?>" id="edit-hostel-<?php echo (int) $recinto['id']; ?>">
                      <input type="hidden" name="acao" value="editar_hostel">
                      <input type="hidden" name="id" value="<?php echo (int) $recinto['id']; ?>">
                    </form>
                    <label class="sr-only" for="nome-h-<?php echo (int) $recinto['id']; ?>">Nome</label>
                    <input form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="nome-h-<?php echo (int) $recinto['id']; ?>" name="nome" value="<?php echo htmlspecialchars($recinto['nome']); ?>" required>
                    <label class="sr-only" for="desc-h-<?php echo (int) $recinto['id']; ?>">Descricao</label>
                    <textarea form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="desc-h-<?php echo (int) $recinto['id']; ?>" name="descricao" rows="2" maxlength="500" style="width:100%;margin-top:6px;"><?php echo htmlspecialchars($recinto['descricao'] ?? ''); ?></textarea>
                  </td>
                  <td>
                    <?php
                      $urlsFotosRecinto = array_map(
                          static fn($img) => URL_BASE . '/' . ltrim($img, '/'),
                          $recinto['imagens'] ?? []
                      );
                    ?>
                    <?php if (empty($urlsFotosRecinto)): ?>
                      <span style="color:#b91c1c;font-size:.8rem;"><i class="fa-solid fa-triangle-exclamation"></i> Sem fotos</span>
                    <?php else: ?>
                      <div class="admin-request-gallery" data-imagens="<?php echo htmlspecialchars(json_encode($urlsFotosRecinto)); ?>" style="grid-template-columns:repeat(2,44px);grid-auto-rows:44px;">
                        <?php foreach (array_slice($urlsFotosRecinto, 0, 4) as $indiceFoto => $foto): ?>
                          <div class="admin-gallery-thumb" data-index="<?php echo $indiceFoto; ?>">
                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto <?php echo $indiceFoto + 1; ?> de <?php echo htmlspecialchars($recinto['nome']); ?>">
                            <?php if ($indiceFoto === 3 && count($urlsFotosRecinto) > 4): ?>
                              <span class="admin-gallery-mais">+<?php echo count($urlsFotosRecinto) - 4; ?></span>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <small style="color:#888;"><?php echo count($urlsFotosRecinto); ?> foto(s) salva(s)</small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <label class="sr-only" for="cidade-h-<?php echo (int) $recinto['id']; ?>">Cidade</label>
                    <input form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="cidade-h-<?php echo (int) $recinto['id']; ?>" name="cidade" value="<?php echo htmlspecialchars($recinto['cidade']); ?>" required>
                    <label class="sr-only" for="estado-h-<?php echo (int) $recinto['id']; ?>">Estado</label>
                    <input form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="estado-h-<?php echo (int) $recinto['id']; ?>" name="estado" value="<?php echo htmlspecialchars($recinto['estado'] ?? ''); ?>" placeholder="UF" style="width:60px;">
                    <label class="sr-only" for="preco-h-<?php echo (int) $recinto['id']; ?>">Preco</label>
                    <input form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="preco-h-<?php echo (int) $recinto['id']; ?>" name="preco_diaria" type="number" min="1" step="0.01" value="<?php echo (float) $recinto['preco_diaria']; ?>" required>
                  </td>
                  <td>
                    <label class="sr-only" for="cat-h-<?php echo (int) $recinto['id']; ?>">Categoria</label>
                    <select form="edit-hostel-<?php echo (int) $recinto['id']; ?>" id="cat-h-<?php echo (int) $recinto['id']; ?>" name="categoria_id">
                      <option value="">Sem categoria</option>
                      <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo (int) $categoria['id']; ?>" <?php echo (int) ($recinto['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nome']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <span class="admin-status admin-status--<?php echo $recinto['status_aprovacao'] === 'aprovado' ? 'active' : 'inactive'; ?>">
                      <?php echo $statusRecintoLabel[$recinto['status_aprovacao']] ?? htmlspecialchars($recinto['status_aprovacao']); ?>
                    </span>
                    <br>
                    <span class="admin-status admin-status--<?php echo $recintoAtivo ? 'active' : 'inactive'; ?>" style="margin-top:4px;">
                      <?php echo $recintoAtivo ? 'Visivel' : 'Desativado'; ?>
                    </span>
                  </td>
                  <td class="admin-actions">
                    <button form="edit-hostel-<?php echo (int) $recinto['id']; ?>" type="submit" class="admin-button admin-button--save"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                    <form method="POST" action="<?php echo routeUrl('anfitriao'); ?>">
                      <input type="hidden" name="acao" value="status_hostel">
                      <input type="hidden" name="id" value="<?php echo (int) $recinto['id']; ?>">
                      <input type="hidden" name="ativo" value="<?php echo $recintoAtivo ? '0' : '1'; ?>">
                      <button type="submit" class="admin-button admin-button--status">
                        <i class="fa-solid fa-<?php echo $recintoAtivo ? 'ban' : 'rotate-left'; ?>"></i> <?php echo $recintoAtivo ? 'Desativar' : 'Reativar'; ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- O anfitriao envia os dados em uma solicitacao que fica pendente para o admin. -->
    <form method="POST" action="<?php echo routeUrl('anfitriao'); ?>" enctype="multipart/form-data" class="host-request-form">
      <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Informacoes do recinto</h2><p>Preencha os dados que serao apresentados aos viajantes.</p></div></div>
        <div class="host-form-grid">
          <label>Nome do recinto<input name="nome" required maxlength="100" placeholder="Ex: Mar Azul Hostel"></label>
          <label>Categoria<select name="categoria_id"><option value="">Selecione</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int) $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></label>
          <label>Cidade<input name="cidade" required maxlength="100" placeholder="Cidade"></label>
          <label>Estado<input name="estado" maxlength="50" placeholder="UF"></label>
          <label>Pais<input name="pais" value="Brasil" maxlength="100"></label>
          <label>Preco por noite (R$)<input name="preco_diaria" required type="number" min="1" step="0.01" placeholder="0,00"></label>
          <label>Quantidade de camas<input name="camas" type="number" min="0" value="0"></label>
          <label>Tipo de acomodacao<select name="tipo"><option>Dormitorio</option><option>Quarto privativo</option><option>Casa inteira</option><option>Camping</option></select></label>
          <label class="host-form-wide">Comodidades<input name="comodidades" placeholder="WiFi, cozinha, lavanderia, piscina"></label>
          <label class="host-form-wide">Palavras-chave<input name="palavras_chave" placeholder="praia, centro, barato"></label>
          <label class="host-form-wide">Descricao<textarea name="descricao" required maxlength="500" rows="6" placeholder="Conte como e o local, sua estrutura e o que o hospede encontrara..."></textarea></label>
        </div>
      </section>

      <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Imagens do recinto</h2><p>Envie de 1 a 30 fotos reais do local. JPG, PNG ou WEBP, ate 5MB cada.</p></div></div>
        <div class="host-images-field"><label for="imagens"><i class="fa-solid fa-cloud-arrow-up"></i><strong>Escolher imagens</strong><span>A primeira foto sera usada como capa do anuncio.</span></label><input id="imagens" name="imagens[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required></div>
        <p id="imagensContador" style="font-size:.85rem;color:#666;margin:10px 0 0;"></p>
        <div id="imagensPreview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:10px;"></div>
        <div class="host-form-submit"><button class="admin-button admin-button--approve" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar para aprovacao</button></div>
      </section>
    </form>
  </div>
</main>

<!-- Lightbox compartilhado para ver as fotos salvas de cada recinto -->
<div class="lightbox" id="lightbox" hidden>
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Fechar">&times;</button>
  <button type="button" class="lightbox-prev" id="lightboxPrev" aria-label="Imagem anterior">&lsaquo;</button>
  <img src="" alt="" id="lightboxImg">
  <button type="button" class="lightbox-next" id="lightboxNext" aria-label="Proxima imagem">&rsaquo;</button>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>
<script src="<?php echo assetUrl('js/admin-gallery.js'); ?>"></script>
<script src="<?php echo assetUrl('js/anfitriao-upload.js'); ?>"></script>

<?php include ROOT . '/view/layouts/footer.php'; ?>