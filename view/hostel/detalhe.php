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
?>
<link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/hostel-details.css"/>

<article style="max-width:920px;margin:0 auto;padding:2rem 1rem;">

  <!-- Breadcrumb -->
  <nav style="margin-bottom:1rem;font-size:.83rem;color:#999;">
    <a href="<?php echo URL_BASE; ?>/" style="color:#3b82f6;">Início</a> &rsaquo;
    <a href="<?php echo URL_BASE; ?>/hostels" style="color:#3b82f6;">Hostels</a>
    <?php if (!empty($hostel['categoria_slug'])): ?>
    &rsaquo;
    <a href="<?php echo URL_BASE; ?>/hostels/categoria/<?php echo htmlspecialchars($hostel['categoria_slug']); ?>"
       style="color:#3b82f6;"><?php echo htmlspecialchars($hostel['categoria_nome']); ?></a>
    <?php endif; ?>
    &rsaquo; <span style="color:#444;"><?php echo htmlspecialchars($hostel['nome']); ?></span>
  </nav>

  <!-- Imagem -->
  <img src="<?php echo htmlspecialchars($hostel['imagem_url'] ?? ''); ?>"
       alt="<?php echo htmlspecialchars($hostel['nome']); ?>"
       style="width:100%;max-height:420px;object-fit:cover;border-radius:14px;margin-bottom:1.5rem;"
       onerror="this.src='https://via.placeholder.com/920x420?text=Hostel'">

  <!-- Cabeçalho -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.2rem;">
    <div>
      <h1 style="margin:0 0 .3rem;font-size:1.9rem;"><?php echo htmlspecialchars($hostel['nome']); ?></h1>
      <p style="margin:0;color:#666;font-size:.95rem;">
        <i class="fa-solid fa-location-dot" style="color:#3b82f6;"></i>
        <?php echo htmlspecialchars($hostel['cidade'] . (!empty($hostel['estado']) ? ', ' . $hostel['estado'] : '')); ?>
        <?php if (!empty($hostel['categoria_nome'])): ?>
          &nbsp;·&nbsp;
          <i class="fa-solid <?php echo htmlspecialchars($hostel['categoria_icone'] ?? 'fa-bed'); ?>" style="color:#3b82f6;"></i>
          <?php echo htmlspecialchars($hostel['categoria_nome']); ?>
        <?php endif; ?>
      </p>
    </div>
    <div style="text-align:right;">
      <div style="font-size:1.9rem;font-weight:700;color:#3b82f6;">
        R$ <?php echo number_format($hostel['preco_diaria'] ?? 0, 2, ',', '.'); ?>
        <span style="font-size:.85rem;font-weight:400;color:#888;">/noite</span>
      </div>
      <div style="color:#f59e0b;font-size:.95rem;">
        <i class="fa-solid fa-star"></i>
        <?php echo number_format($hostel['avaliacao'] ?? 0, 1); ?>
        <span style="color:#888;font-size:.82rem;">(<?php echo intval($hostel['total_avaliacoes']); ?> avaliações)</span>
      </div>
    </div>
  </div>

  <!-- Descrição -->
  <p style="font-size:1rem;line-height:1.75;color:#444;margin-bottom:1.5rem;">
    <?php echo nl2br(htmlspecialchars($hostel['descricao'] ?? '')); ?>
  </p>

  <!-- Comodidades -->
  <?php if (!empty($hostel['comodidades'])): ?>
  <div style="margin-bottom:1.5rem;">
    <h2 style="font-size:1.05rem;margin-bottom:.7rem;color:#222;">Comodidades</h2>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
      <?php foreach (explode(',', $hostel['comodidades']) as $item): $item = trim($item); if ($item): ?>
      <span style="background:#eff6ff;color:#3b82f6;padding:.3rem .85rem;border-radius:20px;font-size:.85rem;">
        <?php echo htmlspecialchars($item); ?>
      </span>
      <?php endif; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- CTA -->
  <a href="<?php echo URL_BASE; ?>/cadastro" class="btn btn-primary btn-lg" style="margin-top:.5rem;">
    Reservar agora
  </a>

  <p style="margin-top:2rem;">
    <a href="<?php echo URL_BASE; ?>/hostels" style="color:#3b82f6;font-size:.9rem;">
      &larr; Ver todos os hostels
    </a>
  </p>

</article>

<?php include ROOT . '/view/layouts/footer.php'; ?>
