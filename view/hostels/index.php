<?php
$categoriaAtual = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$tituloPagina   = $categoriaAtual ? 'Hostels de ' . ucfirst($categoriaAtual) : 'Todos os Hostels';
$titulo         = $tituloPagina . ' — Travel Hostel';
$metaDescricao  = 'Encontre os melhores hostels do Brasil. Filtre por categoria, cidade, preço e avaliação.';
$palavrasChave  = 'hostels brasil, reserva hostel, hospedagem mochileiro, hostel barato';
$urlCanonica    = URL_BASE . '/hostels' . ($categoriaAtual ? '/categoria/' . htmlspecialchars($categoriaAtual) : '');
include ROOT . '/view/layouts/header.php';
?>

<link rel="stylesheet" href="<?php echo URL_PUBLIC; ?>/css/hostels-page.css"/>

<section style="background:var(--color-primary-1,#3b82f6);padding:2.5rem 1rem;text-align:center;color:#fff;">
  <h1 style="margin:0 0 .4rem;font-size:2rem;"><?php echo htmlspecialchars($tituloPagina); ?></h1>
  <p style="margin:0;opacity:.85;">Filtre por categoria, cidade, preço ou avaliação</p>
</section>

<section style="padding:2rem 1rem;">
  <div class="container" style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap;">

    <!-- Sidebar de filtros -->
    <aside style="min-width:210px;max-width:230px;flex-shrink:0;">
      <div style="background:#fff;border-radius:12px;padding:1.2rem;box-shadow:0 1px 6px rgba(0,0,0,.07);">

        <div style="margin-bottom:1.1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem;">Buscar</label>
          <input type="text" id="f-busca" placeholder="Ex: Rio de Janeiro..."
                 style="width:100%;padding:.5rem .7rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.9rem;box-sizing:border-box;"/>
        </div>

        <div style="margin-bottom:1.1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem;">Categoria</label>
          <div id="lista-categorias" style="display:flex;flex-direction:column;gap:.35rem;font-size:.87rem;">
            <label><input type="radio" name="categoria" value=""> Todas</label>
          </div>
        </div>

        <div style="margin-bottom:1.1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem;">Preço (R$/noite)</label>
          <div style="display:flex;gap:.4rem;">
            <input type="number" id="f-preco-min" placeholder="Mín" min="0"
                   style="width:50%;padding:.45rem .5rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;"/>
            <input type="number" id="f-preco-max" placeholder="Máx" min="0"
                   style="width:50%;padding:.45rem .5rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;"/>
          </div>
        </div>

        <div style="margin-bottom:1.1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem;">Avaliação mínima</label>
          <select id="f-avaliacao"
                  style="width:100%;padding:.45rem .6rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
            <option value="">Qualquer</option>
            <option value="3">3+ ⭐</option>
            <option value="4">4+ ⭐</option>
            <option value="4.5">4.5+ ⭐</option>
          </select>
        </div>

        <div style="margin-bottom:1.3rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem;">Ordenar por</label>
          <select id="f-ordenar"
                  style="width:100%;padding:.45rem .6rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
            <option value="avaliacao">Melhor avaliado</option>
            <option value="preco_asc">Menor preço</option>
            <option value="preco_desc">Maior preço</option>
            <option value="nome">Nome A-Z</option>
          </select>
        </div>

        <button onclick="aplicarFiltros()" class="btn btn-primary" style="width:100%;margin-bottom:.4rem;">
          <i class="fa-solid fa-filter"></i> Filtrar
        </button>
        <button onclick="limparFiltros()" class="btn btn-outline" style="width:100%;font-size:.85rem;">
          Limpar filtros
        </button>
      </div>
    </aside>

    <!-- Grid de resultados -->
    <main style="flex:1;min-width:0;">
      <div id="resultados-header" style="margin-bottom:.8rem;color:#666;font-size:.9rem;min-height:20px;"></div>
      <div class="grid grid-3" id="hostelsGrid"></div>
      <div id="paginacao" style="display:flex;gap:.4rem;justify-content:center;margin-top:2rem;flex-wrap:wrap;"></div>
    </main>

  </div>
</section>

<script>
var estadoFiltros = {
  q:'', categoria:'<?php echo htmlspecialchars($categoriaAtual); ?>',
  preco_min:'', preco_max:'', avaliacao_min:'', ordenar:'avaliacao', pagina:1, por_pagina:9
};

document.addEventListener('DOMContentLoaded', function () {
  carregarCategoriasSidebar();
  aplicarFiltros();
});

function carregarCategoriasSidebar() {
  fetch(URL_BASE + '/config/categorias.php')
    .then(function(r){ return r.json(); })
    .then(function(cats) {
      if (!cats || cats.erro) return;
      var cont = document.getElementById('lista-categorias');
      var html = '<label><input type="radio" name="categoria" value="' +
                 '" ' + (estadoFiltros.categoria==='' ? 'checked' : '') + '> Todas</label>';
      cats.forEach(function(c) {
        var checked = estadoFiltros.categoria === c.slug ? 'checked' : '';
        html += '<label style="display:flex;align-items:center;gap:.35rem;">' +
                '<input type="radio" name="categoria" value="' + c.slug + '" ' + checked + '> ' +
                '<i class="fa-solid ' + (c.icone||'fa-bed') + '" style="color:#3b82f6;width:14px;font-size:.8rem;"></i> ' +
                c.nome +
                (c.total_hostels>0 ? ' <small style="color:#bbb;margin-left:auto;">(' + c.total_hostels + ')</small>' : '') +
                '</label>';
      });
      cont.innerHTML = html;
    }).catch(function(){});
}

function aplicarFiltros(paginaNum) {
  estadoFiltros.q             = document.getElementById('f-busca').value.trim();
  estadoFiltros.preco_min     = document.getElementById('f-preco-min').value;
  estadoFiltros.preco_max     = document.getElementById('f-preco-max').value;
  estadoFiltros.avaliacao_min = document.getElementById('f-avaliacao').value;
  estadoFiltros.ordenar       = document.getElementById('f-ordenar').value;
  estadoFiltros.pagina        = paginaNum || 1;
  var radio = document.querySelector('input[name="categoria"]:checked');
  estadoFiltros.categoria = radio ? radio.value : '';
  buscarHostels();
}

function limparFiltros() {
  document.getElementById('f-busca').value    = '';
  document.getElementById('f-preco-min').value = '';
  document.getElementById('f-preco-max').value = '';
  document.getElementById('f-avaliacao').value = '';
  document.getElementById('f-ordenar').value   = 'avaliacao';
  var r = document.querySelector('input[name="categoria"][value=""]');
  if (r) r.checked = true;
  estadoFiltros.categoria = '';
  aplicarFiltros();
}

function buscarHostels() {
  var grid = document.getElementById('hostelsGrid');
  grid.innerHTML = gerarSkeletons(6);

  var p = new URLSearchParams();
  if (estadoFiltros.q)             p.set('q',            estadoFiltros.q);
  if (estadoFiltros.categoria)     p.set('categoria',    estadoFiltros.categoria);
  if (estadoFiltros.preco_min)     p.set('preco_min',    estadoFiltros.preco_min);
  if (estadoFiltros.preco_max)     p.set('preco_max',    estadoFiltros.preco_max);
  if (estadoFiltros.avaliacao_min) p.set('avaliacao_min',estadoFiltros.avaliacao_min);
  p.set('ordenar',   estadoFiltros.ordenar);
  p.set('pagina',    estadoFiltros.pagina);
  p.set('por_pagina',estadoFiltros.por_pagina);

  fetch(URL_BASE + '/config/buscar.php?' + p.toString())
    .then(function(r){ return r.json(); })
    .then(function(resp) {
      if (resp.erro) { grid.innerHTML = '<p style="color:#888;padding:2rem;">Erro ao buscar hostels.</p>'; return; }
      renderizarResultados(resp);
    })
    .catch(function() {
      var fb = typeof hostelsDataFallback !== 'undefined' ? hostelsDataFallback : [];
      renderizarResultados({hostels:fb, total:fb.length, paginas:1, pagina:1});
    });
}

function renderizarResultados(resp) {
  var grid   = document.getElementById('hostelsGrid');
  var header = document.getElementById('resultados-header');
  var pagDiv = document.getElementById('paginacao');
  header.textContent = resp.total + ' hostel(s) encontrado(s)';
  if (!resp.hostels || resp.hostels.length === 0) {
    grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:3rem;color:#888;">Nenhum hostel encontrado com esses filtros.</p>';
    pagDiv.innerHTML = '';
    return;
  }
  grid.innerHTML = resp.hostels.map(criarCard).join('');
  renderizarPaginacao(resp);
}

function criarCard(h) {
  var img   = h.imagem_url || 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=600&q=80';
  var aval  = parseFloat(h.avaliacao||0).toFixed(1);
  var preco = 'R$ ' + parseFloat(h.preco_diaria).toFixed(2).replace('.',',');
  var local = h.cidade + (h.estado ? ', '+h.estado : '');
  var url   = h.slug ? URL_BASE+'/hostel/'+h.slug : '#';
  var cat   = h.categoria_nome
    ? '<span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.7rem;padding:.2rem .5rem;border-radius:4px;">' +
      '<i class="fa-solid '+(h.categoria_icone||'fa-bed')+'"></i> '+h.categoria_nome+'</span>'
    : '';
  return '<div class="hostel-card">' +
    '<div class="hostel-card-image" style="position:relative;">' +
    '<img src="'+img+'" alt="'+h.nome+'" loading="lazy" onerror="this.src=\'https://via.placeholder.com/500x400?text=Hostel\'">' +
    '<span class="hostel-card-badge"><i class="fa-solid fa-star"></i> '+aval+'</span>' + cat +
    '</div>' +
    '<div class="hostel-card-body">' +
    '<h3 class="hostel-card-title">'+h.nome+'</h3>' +
    '<div class="hostel-card-location"><i class="fa-solid fa-location-dot"></i><span>'+local+'</span></div>' +
    '<p class="hostel-card-description">'+(h.descricao||'')+'</p>' +
    '</div>' +
    '<div class="hostel-card-footer">' +
    '<div class="hostel-card-price"><span class="hostel-card-price-label">A partir de</span>' +
    '<span class="hostel-card-price-value">'+preco+'</span></div>' +
    '<a href="'+url+'" class="btn btn-primary btn-sm">Ver Detalhes</a>' +
    '</div></div>';
}

function renderizarPaginacao(resp) {
  var div = document.getElementById('paginacao');
  if (resp.paginas <= 1) { div.innerHTML = ''; return; }
  var html = '';
  for (var i = 1; i <= resp.paginas; i++) {
    var s = i === resp.pagina ? 'background:#3b82f6;color:#fff;border-color:#3b82f6;' : '';
    html += '<button onclick="aplicarFiltros('+i+')" class="btn btn-outline btn-sm" style="'+s+'">'+i+'</button>';
  }
  div.innerHTML = html;
}

function gerarSkeletons(n) {
  var s = '<div class="hostel-card hostel-card--skeleton"><div class="hostel-card-image skeleton-img"></div>' +
          '<div class="hostel-card-body"><div class="skeleton-line skeleton-titulo"></div>' +
          '<div class="skeleton-line skeleton-texto"></div></div></div>';
  var o=''; for(var i=0;i<n;i++) o+=s; return o;
}
</script>

<?php include ROOT . '/view/layouts/footer.php'; ?>
