<?php
$titulo = 'Cadastrar recinto - Travel Hostel';
include ROOT . '/view/layouts/header.php';
?>
<link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/admin-dashboard.css">

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
          <label class="host-form-wide">Descricao<textarea name="descricao" required rows="6" placeholder="Conte como e o local, sua estrutura e o que o hospede encontrara..."></textarea></label>
        </div>
      </section>

      <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Imagens do recinto</h2><p>Envie de 1 a 8 fotos reais do local. JPG, PNG ou WEBP, ate 5MB cada.</p></div></div>
        <div class="host-images-field"><label for="imagens"><i class="fa-solid fa-cloud-arrow-up"></i><strong>Escolher imagens</strong><span>A primeira foto sera usada como capa do anuncio.</span></label><input id="imagens" name="imagens[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required></div>
        <div class="host-form-submit"><button class="admin-button admin-button--approve" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar para aprovacao</button></div>
      </section>
    </form>
  </div>
</main>

<?php include ROOT . '/view/layouts/footer.php'; ?>