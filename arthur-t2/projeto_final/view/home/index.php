<?php
$titulo        = 'Travel Hostel - Locação de Hostels';
$metaDescricao = 'Encontre e reserve os melhores hostels do Brasil. Filtre por categoria, cidade e preço.';
$palavrasChave = 'hostel brasil, reserva hostel, hospedagem mochileiro, hostel barato, hostel praia, hostel natureza';
include ROOT . '/view/layouts/header.php';
?>

<section class="hero">
  <div class="hero-content">
    <h1 class="hero-title">Encontre seu Hostel Perfeito</h1>
    <p class="hero-subtitle">Explore hostels incríveis ao redor do mundo e faça sua reserva com facilidade</p>
    <div class="search-container">
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Onde você quer ir?" class="search-input"/>
        <input type="date" id="checkInDate"  class="search-input"/>
        <input type="date" id="checkOutDate" class="search-input"/>
        <button class="btn btn-primary" onclick="irParaBusca()">Buscar</button>
      </div>
    </div>
  </div>
</section>

<?php // Navegação por categorias ?>
<section style="padding:2rem 1rem;background:#f8fafc;">
  <div class="container">
    <div class="section-header">
      <h2>Explore por Categoria</h2>
      <p>Escolha o estilo de viagem que combina com você</p>
    </div>
    <div id="categoriasGrid" style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin-top:1rem;"></div>
  </div>
</section>

<section class="featured-hostels">
  <div class="container">
    <div class="section-header">
      <h2>Hostels em Destaque</h2>
      <p>Descubra os hostels mais populares e bem avaliados</p>
    </div>
    <div class="grid grid-3" id="featuredHostelsGrid"></div>
    <div style="text-align:center;margin-top:2rem;">
      <a href="<?php echo URL_BASE; ?>/hostels" class="btn btn-primary btn-lg">Ver todos os hostels</a>
    </div>
  </div>
</section>

<section class="why-choose-us">
  <div class="container">
    <div class="section-header">
      <h2>Por que escolher Travel Hostel?</h2>
      <p>Somos a plataforma mais confiável para encontrar hostels</p>
    </div>
    <div class="grid grid-4">
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
        <h3>Busca Fácil</h3>
        <p>Encontre hostels por localização, preço e comodidades</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        <h3>Melhor Preço</h3>
        <p>Garantimos os melhores preços do mercado</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-star"></i></div>
        <h3>Avaliações Reais</h3>
        <p>Leia avaliações verificadas de hóspedes reais</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-lock"></i></div>
        <h3>Seguro e Confiável</h3>
        <p>Suas reservas estão protegidas conosco</p>
      </div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container text-center">
    <h2>Pronto para sua próxima aventura?</h2>
    <p>Comece a explorar hostels incríveis agora mesmo</p>
    <a href="<?php echo URL_BASE; ?>/hostels" class="btn btn-primary btn-lg">Explorar Hostels</a>
  </div>
</section>

<script>
function irParaBusca() {
  var q = document.getElementById('searchInput').value.trim();
  var url = URL_BASE + '/hostels';
  if (q) url += '?q=' + encodeURIComponent(q);
  window.location.href = url;
}
document.addEventListener('DOMContentLoaded', function () {
  carregarHostels();
  carregarCategorias();
  setMinDate();
  var si = document.getElementById('searchInput');
  if (si) si.addEventListener('keypress', function(e){ if(e.key==='Enter') irParaBusca(); });
});

function carregarCategorias() {
  var grid = document.getElementById('categoriasGrid');
  if (!grid) return;
  fetch(URL_BASE + '/config/categorias.php')
    .then(function(r){ return r.json(); })
    .then(function(cats) {
      if (!cats || cats.erro) return;
      grid.innerHTML = cats.map(function(c) {
        return '<a href="' + URL_BASE + '/hostels/categoria/' + c.slug + '" ' +
               'style="display:flex;flex-direction:column;align-items:center;gap:.4rem;padding:1rem 1.5rem;' +
               'background:#fff;border-radius:12px;text-decoration:none;color:#333;box-shadow:0 1px 4px rgba(0,0,0,.08);' +
               'min-width:100px;transition:box-shadow .2s;" ' +
               'onmouseover="this.style.boxShadow=\'0 4px 12px rgba(59,130,246,.2)\'" ' +
               'onmouseout="this.style.boxShadow=\'0 1px 4px rgba(0,0,0,.08)\'">' +
               '<i class="fa-solid ' + (c.icone||'fa-bed') + '" style="font-size:1.5rem;color:#3b82f6;"></i>' +
               '<span style="font-size:.85rem;font-weight:500;">' + c.nome + '</span>' +
               (c.total_hostels>0 ? '<small style="color:#aaa;font-size:.75rem;">' + c.total_hostels + ' hostel(s)</small>' : '') +
               '</a>';
      }).join('');
    })
    .catch(function(){});
}
</script>

<?php include ROOT . '/view/layouts/footer.php'; ?>
