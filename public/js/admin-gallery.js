// admin-gallery.js: lightbox reutilizavel para as galerias de imagens no painel admin
// (varias solicitacoes de recinto na mesma pagina, cada uma com sua propria lista de fotos).
document.addEventListener('DOMContentLoaded', function () {
  var lightbox = document.getElementById('lightbox');
  if (!lightbox) return;

  var lightboxImg = document.getElementById('lightboxImg');
  var lightboxCounter = document.getElementById('lightboxCounter');
  var imagensAtuais = [];
  var indiceAtual = 0;

  function abrir(imagens, indice) {
    imagensAtuais = imagens;
    if (!imagensAtuais.length) return;
    indiceAtual = (indice + imagensAtuais.length) % imagensAtuais.length;
    lightboxImg.src = imagensAtuais[indiceAtual];
    lightboxCounter.textContent = (indiceAtual + 1) + ' / ' + imagensAtuais.length;
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function navegar(delta) {
    if (!imagensAtuais.length) return;
    indiceAtual = (indiceAtual + delta + imagensAtuais.length) % imagensAtuais.length;
    lightboxImg.src = imagensAtuais[indiceAtual];
    lightboxCounter.textContent = (indiceAtual + 1) + ' / ' + imagensAtuais.length;
  }

  function fechar() {
    lightbox.hidden = true;
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.admin-request-gallery[data-imagens]').forEach(function (galeria) {
    var imagens = [];
    try {
      imagens = JSON.parse(galeria.getAttribute('data-imagens') || '[]');
    } catch (e) {
      imagens = [];
    }

    galeria.querySelectorAll('.admin-gallery-thumb').forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        abrir(imagens, parseInt(thumb.getAttribute('data-index'), 10) || 0);
      });
    });
  });

  var btnClose = document.getElementById('lightboxClose');
  var btnPrev = document.getElementById('lightboxPrev');
  var btnNext = document.getElementById('lightboxNext');
  if (btnClose) btnClose.addEventListener('click', fechar);
  if (btnPrev) btnPrev.addEventListener('click', function () { navegar(-1); });
  if (btnNext) btnNext.addEventListener('click', function () { navegar(1); });

  lightbox.addEventListener('click', function (evento) {
    if (evento.target === lightbox) fechar();
  });

  document.addEventListener('keydown', function (evento) {
    if (lightbox.hidden) return;
    if (evento.key === 'Escape') fechar();
    if (evento.key === 'ArrowLeft') navegar(-1);
    if (evento.key === 'ArrowRight') navegar(1);
  });
});
