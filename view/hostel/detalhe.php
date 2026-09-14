<?php
$titulo        = htmlspecialchars($hostel['nome']) . ' — Travel Hostel';
$metaDescricao = htmlspecialchars(mb_substr($hostel['descricao'] ?? 'Conheça este hostel incrível no Travel Hostel.', 0, 160));
$palavrasChave = !empty($hostel['palavras_chave']) ? htmlspecialchars($hostel['palavras_chave']) : '';
$urlCanonica   = URL_BASE . '/hostel/' . htmlspecialchars($hostel['slug']);
$ogImagem      = $hostel['imagem_url'] ?? '';

$schemaOrg = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'LodgingBusiness',
    'name'            => $hostel['nome'],
    'description'     => $hostel['descricao'] ?? '',
    'url'             => $urlCanonica,
    'image'           => $hostel['imagem_url'] ?? '',
    'address'         => [
        '@type'           => 'PostalAddress',
        'addressLocality' => $hostel['cidade'] ?? '',
        'addressRegion'   => $hostel['estado'] ?? '',
        'addressCountry'  => $hostel['pais']   ?? 'BR',
    ],
    'aggregateRating' => [
        '@type'       => 'AggregateRating',
        'ratingValue' => $hostel['avaliacao']        ?? 0,
        'reviewCount' => $hostel['total_avaliacoes'] ?? 0,
    ],
    'priceRange' => 'R$ ' . number_format($hostel['preco_diaria'] ?? 0, 2, ',', '.') . '/noite',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include ROOT . '/view/layouts/header.php';

// Monta a lista de imagens da galeria: imagem principal + imagens adicionais cadastradas.
$imagensGaleria = [];
if (!empty($hostel['imagem_url'])) {
    $imagensGaleria[] = $hostel['imagem_url'];
}
if (!empty($hostel['imagens'])) {
    foreach ($hostel['imagens'] as $imagemExtra) {
        $urlImagem = (strpos($imagemExtra, 'http://') === 0 || strpos($imagemExtra, 'https://') === 0)
            ? $imagemExtra
            : URL_BASE . '/' . ltrim($imagemExtra, '/');
        if (!in_array($urlImagem, $imagensGaleria, true)) {
            $imagensGaleria[] = $urlImagem;
        }
    }
}
if (empty($imagensGaleria)) {
    $imagensGaleria[] = 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=900&q=80';
}
?>
<link rel="stylesheet" href="<?php echo assetUrl('css/hostel-details.css'); ?>"/>

<nav class="breadcrumb">
  <div class="container">
    <a href="<?php echo URL_BASE; ?>/">Início</a>
    <span>&rsaquo;</span>
    <a href="<?php echo URL_BASE; ?>/hostels">Hostels</a>
    <?php if (!empty($hostel['categoria_slug'])): ?>
      <span>&rsaquo;</span>
      <a href="<?php echo URL_BASE; ?>/hostels/categoria/<?php echo htmlspecialchars($hostel['categoria_slug']); ?>">
        <?php echo htmlspecialchars($hostel['categoria_nome']); ?>
      </a>
    <?php endif; ?>
    <span>&rsaquo;</span>
    <span><?php echo htmlspecialchars($hostel['nome']); ?></span>
  </div>
</nav>

<section class="hostel-details">
  <div class="container">

    <?php if (!empty($_SESSION['reserva_erro'])): ?>
      <div class="mensagem mensagem-erro mb-md"><?php echo htmlspecialchars($_SESSION['reserva_erro']); unset($_SESSION['reserva_erro']); ?></div>
    <?php endif; ?>

    <!-- Galeria (clique para ampliar em slide) -->
    <div class="details-gallery" id="galeriaHostel">
      <div class="gallery-main" data-index="0">
        <img src="<?php echo htmlspecialchars($imagensGaleria[0]); ?>"
             data-full="<?php echo htmlspecialchars($imagensGaleria[0]); ?>"
             alt="<?php echo htmlspecialchars($hostel['nome']); ?>"
             onerror="this.src='https://via.placeholder.com/920x420?text=Hostel'">
      </div>
      <?php foreach (array_slice($imagensGaleria, 1, 3) as $indice => $imagem): ?>
        <div class="gallery-thumb" data-index="<?php echo $indice + 1; ?>">
          <img src="<?php echo htmlspecialchars($imagem); ?>"
               data-full="<?php echo htmlspecialchars($imagem); ?>"
               alt="Imagem adicional de <?php echo htmlspecialchars($hostel['nome']); ?>"
               onerror="this.style.display='none'">
        </div>
      <?php endforeach; ?>
    </div>

    <div class="details-grid">
      <div class="details-main">

        <div class="details-header">
          <h1><?php echo htmlspecialchars($hostel['nome']); ?></h1>
          <div class="details-location">
            <i class="fa-solid fa-location-dot"></i>
            <span><?php echo htmlspecialchars($hostel['cidade'] . (!empty($hostel['estado']) ? ', ' . $hostel['estado'] : '')); ?></span>
            <?php if (!empty($hostel['categoria_nome'])): ?>
              &nbsp;·&nbsp;
              <i class="fa-solid <?php echo htmlspecialchars($hostel['categoria_icone'] ?? 'fa-bed'); ?>"></i>
              <span><?php echo htmlspecialchars($hostel['categoria_nome']); ?></span>
            <?php endif; ?>
          </div>
          <div class="details-rating">
            <div class="rating-stars">
              <span><i class="fa-solid fa-star"></i> <?php echo number_format($hostel['avaliacao'] ?? 0, 1); ?></span>
              <span class="rating-reviews">(<?php echo intval($hostel['total_avaliacoes'] ?? 0); ?> avaliações)</span>
            </div>
          </div>
        </div>

        <div class="details-description">
          <h3>Sobre o hostel</h3>
          <p><?php echo nl2br(htmlspecialchars($hostel['descricao'] ?? '')); ?></p>
        </div>

        <?php if (!empty($hostel['comodidades'])): ?>
        <div class="details-amenities">
          <h3>Comodidades</h3>
          <div class="amenities-grid">
            <?php foreach (explode(',', $hostel['comodidades']) as $item): $item = trim($item); if ($item): ?>
              <div class="amenity-item">
                <i class="fa-solid fa-circle-check amenity-icon" style="color:var(--color-primary-1);"></i>
                <span><?php echo htmlspecialchars($item); ?></span>
              </div>
            <?php endif; endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($hostel['anfitriao_nome'])): ?>
        <div class="details-amenities">
          <h3>Anfitrião</h3>
          <p>
            <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($hostel['anfitriao_nome']); ?>
            <?php if ($resumoAnfitriao && $resumoAnfitriao['total'] > 0): ?>
              &nbsp;·&nbsp;<i class="fa-solid fa-star" style="color:#ffc107;"></i>
              <?php echo number_format($resumoAnfitriao['media'], 1); ?>
              <span style="color:#888;font-size:.85rem;">(<?php echo $resumoAnfitriao['total']; ?> avaliações como anfitrião)</span>
            <?php else: ?>
              <span style="color:#888;font-size:.85rem;">&nbsp;·&nbsp;ainda sem avaliações como anfitrião</span>
            <?php endif; ?>
          </p>
        </div>
        <?php endif; ?>

      </div>

      <aside class="booking-sidebar">
        <div class="booking-card">
          <div class="booking-price">
            <span class="booking-price-value">R$ <?php echo number_format($hostel['preco_diaria'] ?? 0, 2, ',', '.'); ?></span>
            <span class="booking-price-unit">/noite</span>
          </div>

          <?php if (!empty($hostel['id'])): ?>
            <form class="booking-form" method="POST" action="<?php echo routeUrl('reservar'); ?>" id="formReserva"
                  data-preco="<?php echo (float) ($hostel['preco_diaria'] ?? 0); ?>">
              <input type="hidden" name="hostel_id" value="<?php echo (int) $hostel['id']; ?>">
              <input type="hidden" name="slug" value="<?php echo htmlspecialchars($hostel['slug']); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

              <div class="form-group">
                <label for="checkInDate">Check-in</label>
                <input type="date" name="checkin" id="checkInDate" required>
              </div>
              <div class="form-group">
                <label for="checkOutDate">Check-out</label>
                <input type="date" name="checkout" id="checkOutDate" required>
              </div>
              <div class="form-group">
                <label for="hospedesInput">Hóspedes</label>
                <input type="number" name="hospedes" id="hospedesInput" min="1" max="20" value="1" required>
              </div>

              <div class="booking-summary" id="resumoPreco" hidden>
                <p id="resumoNoites"></p>
                <p><strong id="resumoTotal"></strong></p>
              </div>

              <button type="submit" class="btn btn-primary booking-button">Reservar agora</button>
            </form>
            <p class="booking-info">Você não será cobrado agora. O pagamento é feito na próxima etapa.</p>
          <?php else: ?>
            <p class="booking-info">Reserva indisponível para este recinto no momento.</p>
          <?php endif; ?>
        </div>
      </aside>
    </div>

    <?php if (!empty($avaliacoesHostel)): ?>
    <div class="details-reviews">
      <h3>Avaliações de quem já se hospedou (<?php echo count($avaliacoesHostel); ?>)</h3>
      <?php foreach ($avaliacoesHostel as $avaliacao): ?>
        <div class="review-item">
          <div class="review-header">
            <div>
              <div class="review-author"><?php echo htmlspecialchars($avaliacao['usuario_nome']); ?></div>
              <div class="review-date"><?php echo (new DateTimeImmutable($avaliacao['criado_em']))->format('d/m/Y'); ?></div>
            </div>
            <div class="review-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-solid fa-star" style="<?php echo $i <= (int) $avaliacao['nota'] ? '' : 'color:#ddd;'; ?>"></i>
              <?php endfor; ?>
            </div>
          </div>
          <?php if (!empty($avaliacao['comentario'])): ?>
            <p class="review-text"><?php echo nl2br(htmlspecialchars($avaliacao['comentario'])); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- Lightbox da galeria -->
<div class="lightbox" id="lightbox" hidden>
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Fechar">&times;</button>
  <button type="button" class="lightbox-prev" id="lightboxPrev" aria-label="Imagem anterior">&lsaquo;</button>
  <img src="" alt="" id="lightboxImg">
  <button type="button" class="lightbox-next" id="lightboxNext" aria-label="Próxima imagem">&rsaquo;</button>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>

<script src="<?php echo assetUrl('js/hostel-gallery.js'); ?>"></script>

<?php include ROOT . '/view/layouts/footer.php'; ?>
